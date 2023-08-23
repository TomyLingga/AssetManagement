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

class FixedAssetsController extends Controller
{
    private $token;
    private $urlDept = "http://36.92.181.10:4763/api/department/get/";
    private $urlUser = "http://36.92.181.10:4763/api/user/get/";

    private function findOrFail($model, $conditions)
    {
        return $model::where($conditions)->firstOrFail();
    }

    public function index(Request $request)
    {
        $tglNow = $request->input('tanggal') ?? now()->toDateString();
        $fixedAssetsData = FixedAssets::with([
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

        if (!$fixedAssetsData ) {
            return response()->json([
                'message' => "Fixed Assets Not Found",
                'success' => true,
                'code' => 401
            ], 401);
        }

        $fixedAssets = $this->formatting($fixedAssetsData, $tglNow);

        return response()->json([
            'data' => $fixedAssets,
            'message' => 'All Fixed Assets Retrieved Successfully',
            'code' => 200,
            'success' => true,
        ], 200);
    }

    public function show(Request $request, $id)
    {
        $tglNow = $request->input('tanggal') ?? now()->toDateString();
        $fixedAssetData = FixedAssets::with([
            'subGroup.group',
            'location.area',
            'supplier',
            'adjustment',
            'fairValues',
            'valueInUses',
        ])->find($id);

        if (!$fixedAssetData ) {
            return response()->json([
                'message' => "Fixed Asset Not Found",
                'success' => true,
                'code' => 401
            ], 401);
        }

        $fixedAsset = $this->formattingById($fixedAssetData, $tglNow);

        return response()->json([
            'data' => $fixedAsset,
            'message' => 'Fixed Asset Retrieved Successfully',
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
