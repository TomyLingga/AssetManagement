<?php

namespace App\Http\Controllers\Api\Aset;

use App\Http\Controllers\Controller;
use App\Models\FixedAssets;
use App\Models\SubGroup;
use App\Models\Group;
use App\Models\Location;
use App\Models\Supplier;
use App\Models\Adjustment;
use App\Models\FairValue;
use App\Models\ValueInUse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Carbon\Carbon;

class FixedAssetsController extends Controller
{
    private $token;
    private $urlDept = "http://36.92.181.10:4763/api/department/get/";
    private $urlAllDept = "http://36.92.181.10:4763/api/department";
    private $urlUser = "http://36.92.181.10:4763/api/user/get/";

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->token = $request->get('user_token');
            return $next($request);
        });
    }

    private function calculateAssetAge($tglPerolehan)
    {
        $currentDate = now();
        return $currentDate->diffInMonths($tglPerolehan);
    }

    private function getMonthlyDepreciation($fixedAsset)
    {
        return $fixedAsset->nilai_perolehan / $fixedAsset->masa_manfaat;
    }

    private function getInitialBalance($tglPerolehan, $monthlyDepreciation, $nilaiDepresiasiAwal)
    {
        $tglPerolehanYear = Carbon::parse($tglPerolehan)->year;
        // $currentYear = 2025;
        $currentYear = now()->year;
        $previousYear = $currentYear - 1;

        $startOf2023 = Carbon::createFromDate(2022, 12, 31)->endOfDay();
        // $startOf2023 = Carbon::createFromDate(2023, 1, 1)->startOfDay();
        $lastYearEndDate = Carbon::create($previousYear, 12, 31)->endOfDay();
        $lastYearStartDate = Carbon::create($previousYear, 1, 1)->startOfDay();

        if ($tglPerolehanYear < 2023) {
            if ($currentYear <= 2023) {
                $initialBalance = $nilaiDepresiasiAwal;
            } else {
                $monthsInLastYear = $startOf2023->diffInMonths($lastYearEndDate);
                $initialBalance = $nilaiDepresiasiAwal + ($monthlyDepreciation * $monthsInLastYear);
            }
        } else {
            if ($lastYearStartDate->year < $tglPerolehanYear) {
                $initialBalance = 0;
            } else {
                $tglPerolehan = Carbon::parse($tglPerolehan);
                $monthFromTglPerolehan = max($tglPerolehan->diffInMonths($lastYearEndDate), 1);
                $initialBalance = $monthlyDepreciation * $monthFromTglPerolehan;
            }
        }

        return floatval($initialBalance);
    }

    private function getAccumulatedDepreciation($assetAge, $monthlyDepreciation, $tglPerolehan, $nilai_depresiasi_awal)
    {
        $tglPerolehanYear = Carbon::parse($tglPerolehan)->year;

        if ($tglPerolehanYear < 2023) {
            $monthsSince2023 = Carbon::createFromDate(2022, 12, 31)->endOfDay()->diffInMonths(now());

            return ($monthlyDepreciation * $monthsSince2023) + $nilai_depresiasi_awal;
        }
        return $monthlyDepreciation * $assetAge;
    }

    private function getBookValue($nilaiPerolehan, $accumulatedDepreciation)
    {
        return $nilaiPerolehan - $accumulatedDepreciation;
    }

    private function getDepartmentData()
    {
        return Http::withHeaders([
            'Authorization' => $this->token,
        ])->get($this->urlAllDept)->json()['data'] ?? [];
    }

    private function findOrFail($model, $conditions)
    {
        return $model::where($conditions)->firstOrFail();
    }

    public function index()
    {
        $fixedAssets = FixedAssets::with([
            'subGroup.group',
            'location.area',
            'supplier',
            'adjustment',
            'fairValues',
            'valueInUses',
        ])
        ->join('sub_groups', 'fixed_assets.id_sub_grup', '=', 'sub_groups.id')
        ->join('groups', 'sub_groups.id_grup', '=', 'groups.id')
        ->orderBy('groups.kode_aktiva_tetap', 'asc')
        ->orderBy('sub_groups.kode_aktiva_tetap', 'asc')
        ->orderBy('fixed_assets.kode_aktiva', 'asc')
        ->select('fixed_assets.*')
        ->get();

        if (!$fixedAssets ) {
            return response()->json([
                'message' => "Fixed Assets Not Found",
                'success' => true,
                'code' => 401
            ], 401);
        }

        $deptData = $this->getDepartmentData();
        $departmentIdMapping = collect($deptData)->keyBy('id');

        $romanNumeralMonths = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

        $fixedAssets->transform(function ($fixedAsset) use ($departmentIdMapping, $romanNumeralMonths) {
            $departmentId = $fixedAsset->id_departemen;

            $matchingDept = $departmentIdMapping->get($departmentId);

            if ($matchingDept) {
                $departmentCode = $matchingDept['kode'];

                $tglPerolehan = Carbon::parse($fixedAsset->tgl_perolehan);
                $tglPerolehanMonth = $tglPerolehan->format('m');
                $tglPerolehanYear = $tglPerolehan->format('Y');
                $groupFormat = $fixedAsset->subGroup->group->format;
                $nomorAset = $fixedAsset->nomor;

                $monthInRoman = $romanNumeralMonths[(int) $tglPerolehanMonth - 1];

                $fixedAsset->nomor = "{$departmentCode}_INL/{$monthInRoman}/{$tglPerolehanYear}/{$groupFormat}{$nomorAset}";
                $fixedAsset->spesifikasi = json_decode($fixedAsset->spesifikasi);

                $assetAge = $this->calculateAssetAge($tglPerolehan);
                $fixedAsset->assetAge = $assetAge;

                $monthlyDepreciation = $this->getMonthlyDepreciation($fixedAsset);
                $fixedAsset->monthlyDepreciation = $monthlyDepreciation;
                $fixedAsset->annualDepreciation = $monthlyDepreciation * 12;

                $initialBalance = $this->getInitialBalance($fixedAsset->tgl_perolehan, $monthlyDepreciation, $fixedAsset->nilai_depresiasi_awal);
                $fixedAsset->initialBalanceThisYear = $initialBalance;

                $accumulatedDepreciation = $this->getAccumulatedDepreciation($assetAge, $monthlyDepreciation, $tglPerolehan, $fixedAsset->nilai_depresiasi_awal);
                $fixedAsset->accumulatedDepreciation = $accumulatedDepreciation;

                $bookValue = $this->getBookValue($fixedAsset->nilai_perolehan, $fixedAsset->accumulatedDepreciation);
                $fixedAsset->bookValue = $bookValue;
            }

            $fixedAsset->formated_kode_aktiva = $fixedAsset->formated_kode_aktiva;
            $fixedAsset->formated_kode_penyusutan = $fixedAsset->formated_kode_penyusutan;

            return $fixedAsset;
        });

        return response()->json([
            'data' => $fixedAssets,
            'message' => 'All Fixed Assets Retrieved Successfully',
            'code' => 200,
            'success' => true,
        ], 200);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'id_grup' => 'required',
                'id_sub_grup' => 'required',
                'nama' => 'required',
                'brand' => 'required',
                'masa_manfaat' => 'required',
                'tgl_perolehan' => 'required',
                'nilai_perolehan' => 'required|numeric',
                'nilai_depresiasi_awal' => 'required|numeric',
                'id_lokasi' => 'required',
                'id_departemen' => 'required',
                'id_pic' => 'required',
                'cost_centre' => 'required',
                'kondisi' => 'required',
                'id_supplier' => 'required',
                'id_kode_adjustment' => 'required',
                'spesifikasi' => 'required|array',
                'keterangan' => 'required',
                'fairValue' => 'required|numeric',
                'valueInUse' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false
                ], 400);
            }

            $groupId = $request->input('id_grup');
            $subGroupId = $request->input('id_sub_grup');
            $tglPerolehan = $request->input('tgl_perolehan');
            $spesifikasi = json_encode($request->spesifikasi);

            $group = $this->findOrFail(Group::class, ['id' => $groupId]);
            $subGroup = $this->findOrFail(SubGroup::class, ['id' => $subGroupId, 'id_grup' => $groupId]);
            $lokasi = $this->findOrFail(Location::class, ['id' => $request->input('id_lokasi')]);
            $supplier = $this->findOrFail(Supplier::class, ['id' => $request->input('id_supplier')]);
            $adjustment = $this->findOrFail(Adjustment::class, ['id' => $request->input('id_kode_adjustment')]);

            $departmentId = $request->input('id_departemen');

            $deptResponse = Http::withHeaders([
                'Authorization' => $this->token,
            ])->get($this->urlDept . $departmentId);

            $departmentData = $deptResponse->json()['data'] ?? [];

            if (empty($departmentData)) {
                return response()->json([
                    'message' => 'Department not found',
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            $getPic = Http::withHeaders([
                'Authorization' => $this->token,
            ])->get($this->urlUser . $request->input('id_pic'));

            $pic = $getPic->json()['data'] ?? [];

            if (empty($pic)) {
                return response()->json([
                    'message' => 'User/PIC not found',
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            $kodeAktiva = str_pad(FixedAssets::where('id_sub_grup', $subGroupId)->count(), 2, '0', STR_PAD_LEFT);

            $month = date('m', strtotime($tglPerolehan));
            $year = date('Y', strtotime($tglPerolehan));

            $nomorAset = FixedAssets::whereHas('subGroup', function ($query) use ($groupId) {
                $query->where('id_grup', $groupId);
            })
                ->where('id_departemen', $departmentData['id'])
                ->whereMonth('tgl_perolehan', $month)
                ->whereYear('tgl_perolehan', $year)
                ->count() + 1;

            $numberOfLeadingZeros = max(0, 5 - strlen((string) $nomorAset));
            $formattedNomorAset = $numberOfLeadingZeros > 0 ? str_repeat('0', $numberOfLeadingZeros) . $nomorAset : $nomorAset;

            $data = FixedAssets::create([
                'id_sub_grup' => $subGroupId,
                'nama' => $request->nama,
                'brand' => $request->brand,
                'kode_aktiva' => $kodeAktiva,
                'kode_penyusutan' => $kodeAktiva,
                'nomor' => $formattedNomorAset,
                'masa_manfaat' => $request->masa_manfaat,
                'tgl_perolehan' => $tglPerolehan,
                'nilai_perolehan' => $request->nilai_perolehan,
                'nilai_depresiasi_awal' => $request->nilai_depresiasi_awal,
                'id_lokasi' => $lokasi->id,
                'id_departemen' => $departmentData['id'],
                'id_pic' => $pic['id'],
                'cost_centre' => $request->cost_centre,
                'kondisi' => $request->kondisi,
                'id_supplier' => $supplier->id,
                'id_kode_adjustment' => $adjustment->id,
                'spesifikasi' => $spesifikasi,
                'keterangan' => $request->keterangan,
                'status' => 1,
            ]);

            $fairValue = FairValue::create([
                'id_fixed_asset' => $data->id,
                'nilai' => $request->fairValue,
            ]);

            $valueInUse = ValueInUse::create([
                'id_fixed_asset' => $data->id,
                'nilai' => $request->valueInUse,
            ]);

            $data->load('subGroup', 'location', 'supplier', 'adjustment', 'fairValues', 'valueInUses');

            DB::commit();

            return response()->json([
                'data' => $data,
                'message' => 'Asset Created Successfully',
                'code' => 200,
                'success' => true,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Something went wrong',
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'code' => 500,
                'success' => false,
            ], 500);
        }
    }

    public function show($id)
    {
        $fixedAsset = FixedAssets::with([
            'subGroup.group',
            'location.area',
            'supplier',
            'adjustment',
            'fairValues',
            'valueInUses',
        ])->find($id);

        if (!$fixedAsset ) {
            return response()->json([
                'message' => "Fixed Asset Not Found",
                'success' => true,
                'code' => 401
            ], 401);
        }

        $departmentId = $fixedAsset->id_departemen;
        $a = $fixedAsset->spesifikasi;

        $deptResponse = Http::withHeaders([
            'Authorization' => $this->token,
        ])->get($this->urlDept . $departmentId);

        $deptData = $deptResponse->json();

        if (!isset($deptData['data'])) {
            return response()->json([
                'message' => 'Department not found',
                'success' => true,
                'code' => 401
            ], 401);
        }

        $departmentData = $deptData['data'];

        $departmentCode = $departmentData['kode'];
        $tglPerolehanMonth = Carbon::parse($fixedAsset->tgl_perolehan)->format('m');
        $tglPerolehanYear = Carbon::parse($fixedAsset->tgl_perolehan)->format('Y');
        $groupFormat = $fixedAsset->subGroup->group->format;
        $nomorAset = $fixedAsset->nomor;
        $romanNumeralMonths = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $monthInRoman = $romanNumeralMonths[(int) $tglPerolehanMonth - 1];
        $fixedAsset->nomor = "{$departmentCode}_INL/{$monthInRoman}/{$tglPerolehanYear}/{$groupFormat}{$nomorAset}";
        $fixedAsset->spesifikasi = json_decode($fixedAsset->spesifikasi);

        $fixedAsset->formated_kode_aktiva = $fixedAsset->formated_kode_aktiva;
        $fixedAsset->formated_kode_penyusutan = $fixedAsset->formated_kode_penyusutan;

        return response()->json([
            'data' => $fixedAsset,
            'message' => 'Fixed Asset Retrieved Successfully',
            'code' => 200,
            'success' => true,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'id_grup' => 'required',
                'id_sub_grup' => 'required',
                'nama' => 'required',
                'brand' => 'required',
                'masa_manfaat' => 'required',
                'tgl_perolehan' => 'required',
                'nilai_perolehan' => 'required|numeric',
                'nilai_depresiasi_awal' => 'required|numeric',
                'id_lokasi' => 'required',
                'id_departemen' => 'required',
                'id_pic' => 'required',
                'cost_centre' => 'required',
                'kondisi' => 'required',
                'id_supplier' => 'required',
                'id_kode_adjustment' => 'required',
                'spesifikasi' => 'required|array',
                'keterangan' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false
                ], 400);
            }

            $fixedAsset = FixedAssets::with([
                'subGroup.group',
                'location.area',
                'supplier',
                'adjustment',
                'fairValues',
                'valueInUses',
            ])->find($id);

            if (!$fixedAsset ) {
                return response()->json([
                    'message' => "Fixed Asset Not Found",
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            $groupId = $request->input('id_grup');
            $subGroupId = $request->input('id_sub_grup');
            $tglPerolehan = $request->input('tgl_perolehan');
            $spesifikasi = json_encode($request->spesifikasi);

            $group = $this->findOrFail(Group::class, ['id' => $groupId]);
            $subGroup = $this->findOrFail(SubGroup::class, ['id' => $subGroupId, 'id_grup' => $groupId]);
            $lokasi = $this->findOrFail(Location::class, ['id' => $request->input('id_lokasi')]);
            $supplier = $this->findOrFail(Supplier::class, ['id' => $request->input('id_supplier')]);
            $adjustment = $this->findOrFail(Adjustment::class, ['id' => $request->input('id_kode_adjustment')]);

            $departmentId = $request->input('id_departemen');
            $deptResponse = Http::withHeaders(['Authorization' => $this->token])->get($this->urlDept . $departmentId);
            $departmentData = $deptResponse->json()['data'] ?? [];

            if (empty($departmentData)) {
                return response()->json([
                    'message' => 'Department not found',
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            $getPic = Http::withHeaders(['Authorization' => $this->token])->get($this->urlUser . $request->input('id_pic'));
            $pic = $getPic->json()['data'] ?? [];

            if (empty($pic)) {
                return response()->json([
                    'message' => 'User/PIC not found',
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            $month = date('m', strtotime($tglPerolehan));
            $year = date('Y', strtotime($tglPerolehan));

            $nomorAset = FixedAssets::whereHas('subGroup', function ($query) use ($groupId) {
                $query->where('id_grup', $groupId);
            })
                ->where('id_departemen', $departmentData['id'])
                ->whereMonth('tgl_perolehan', $month)
                ->whereYear('tgl_perolehan', $year)
                ->count() + 1;

            $numberOfLeadingZeros = max(0, 5 - strlen((string) $nomorAset));
            $formattedNomorAset = $numberOfLeadingZeros > 0 ? str_repeat('0', $numberOfLeadingZeros) . $nomorAset : $nomorAset;

            $fixedAsset->update([
                'id_sub_grup' => $subGroupId,
                'nama' => $request->nama,
                'brand' => $request->brand,
                'nomor' => $formattedNomorAset,
                'masa_manfaat' => $request->masa_manfaat,
                'tgl_perolehan' => $tglPerolehan,
                'nilai_perolehan' => $request->nilai_perolehan,
                'nilai_depresiasi_awal' => $request->nilai_depresiasi_awal,
                'id_lokasi' => $lokasi->id,
                'id_departemen' => $departmentData['id'],
                'id_pic' => $pic['id'],
                'cost_centre' => $request->cost_centre,
                'kondisi' => $request->kondisi,
                'id_supplier' => $supplier->id,
                'id_kode_adjustment' => $adjustment->id,
                'spesifikasi' => $spesifikasi,
                'keterangan' => $request->keterangan,
                'status' => 1,
            ]);

            DB::commit();

            return response()->json([
                'data' => $fixedAsset,
                'message' => 'Asset Updated Successfully',
                'code' => 200,
                'success' => true,
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Something went wrong',
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'code' => 500,
                'success' => false,
            ], 500);
        }
    }

    public function toggleActive($id)
    {
        try {

            $data = FixedAssets::findOrFail($id);

            $newStatusValue = ($data->status == 0) ? 1 : 0;

            $data->update([
                'status' => $newStatusValue,
            ]);

            $message = ($newStatusValue == 0) ? 'Status updated to Non-Active.' : 'Status updated to Active.';

            return response()->json([
                'data' => $data,
                'message' => $message,
                'code' => 200,
                'success' => true
            ], 200);
        } catch (ModelNotFoundException $e) {

            return response()->json([
                'message' => "Something went wrong",
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'code' => 500,
                'success' => false
            ], 500);
        }
    }
}
