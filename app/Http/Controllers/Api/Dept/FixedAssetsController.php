<?php

namespace App\Http\Controllers\Api\Dept;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FixedAssets;
use App\Models\BastFixedAsset;
use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Services\LoggerService;
use Carbon\Carbon;

class FixedAssetsController extends Controller
{
    private function findOrFail($model, $conditions)
    {
        return $model::where($conditions)->firstOrFail();
    }

    public function index(Request $request)
    {
        $tglNow = $request->input('tanggal') ?? now()->toDateString();
        $fixedAssetsData = FixedAssets::where('id_departemen', $this->userData->departemen)
        ->with([
            'subGroup.group',
            'location.area',
        ])
        ->join('sub_groups', 'fixed_assets.id_sub_grup', '=', 'sub_groups.id')
        ->join('groups', 'sub_groups.id_grup', '=', 'groups.id')
        ->orderBy('groups.kode_aktiva_tetap', 'asc')
        ->orderBy('sub_groups.kode_aktiva_tetap', 'asc')
        ->orderBy('fixed_assets.kode_aktiva', 'asc')
        ->select('fixed_assets.*')
        ->get();

        if ($fixedAssetsData->isEmpty()) {
            return response()->json([
                'message' => "Fixed Assets Not Found",
                'success' => true,
                'code' => 401
            ], 401);
        }

        $deptData = $this->getDepartmentData();
        $userData = $this->getUserData();
        $departmentIdMapping = collect($deptData)->keyBy('id');
        $userIdMapping = collect($userData)->keyBy('id');

        $romanNumeralMonths = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

        $fixedAssetsData->transform(function ($fixedAsset) use ($departmentIdMapping, $userIdMapping, $romanNumeralMonths, $tglNow) {
            $departmentId = $fixedAsset->id_departemen;
            $userId = $fixedAsset->id_pic;

            $matchingDept = $departmentIdMapping->get($departmentId);
            $matchingUser = $userIdMapping->get($userId);

            if ($matchingDept) {
                $departmentCode = $matchingDept['kode'];
                $departmentName = $matchingDept['department'];
                $userName = $matchingUser['name'];
                $userJabatan = $matchingUser['jabatan'];

                $tglPerolehan = Carbon::parse($fixedAsset->tgl_perolehan);
                $tglPerolehanMonth = $tglPerolehan->format('m');
                $tglPerolehanYear = $tglPerolehan->format('Y');
                $groupFormat = $fixedAsset->subGroup->group->format;
                $fixedAsset->nilai_perolehan = floatval($fixedAsset->nilai_perolehan);

                $nomorAset = $fixedAsset->nomor;

                $monthInRoman = $romanNumeralMonths[(int) $tglPerolehanMonth - 1];

                $fixedAsset->nomor = "{$departmentCode}_INL/{$monthInRoman}/{$tglPerolehanYear}/{$groupFormat}{$nomorAset}";
                $fixedAsset->spesifikasi = json_decode($fixedAsset->spesifikasi);
                $fixedAsset->assetDepartment = $departmentName;
                $fixedAsset->assetUserName = $userName;
                $fixedAsset->assetUserPosition = $userJabatan;

                $assetAge = $this->calculateAssetAge($tglPerolehan, $tglNow);
                $fixedAsset->assetAge = $assetAge;
            }

            return $fixedAsset;
        });
        return response()->json([
            'data' => $fixedAssetsData,
            'message' => 'All Fixed Assets Retrieved Successfully',
            'code' => 200,
            'success' => true,
        ], 200);
    }

    public function show(Request $request, $id)
    {
        $tglNow = $request->input('tanggal') ?? now()->toDateString();
        $fixedAsset = FixedAssets::where('id_departemen', $this->userData->departemen)
        ->with([
            'subGroup.group',
            'location.area'
        ])->find($id);

        if (!$fixedAsset ) {
            return response()->json([
                'message' => "Fixed Asset not found or not belongs to user's department",
                'success' => true,
                'code' => 401
            ], 401);
        }

        $departmentId = $fixedAsset->id_departemen;
        $userId = $fixedAsset->id_pic;

        $deptData = $this->getDepartmentById($departmentId);

        if (!isset($deptData)) {
            return response()->json([
                'message' => 'Department not found',
                'success' => true,
                'code' => 401
            ], 401);
        }

        $userData = $this->getUserById($userId);

        if (!isset($userData)) {
            return response()->json([
                'message' => 'User not found',
                'success' => true,
                'code' => 401
            ], 401);
        }

        $departmentData = $deptData;
        $userData = $userData;

        $departmentCode = $departmentData['kode'];
        $departmentName = $departmentData['department'];
        $userName = $userData['name'];
        $userJabatan = $userData['jabatan'];
        $tglPerolehan = Carbon::parse($fixedAsset->tgl_perolehan);
        $tglPerolehanMonth = Carbon::parse($fixedAsset->tgl_perolehan)->format('m');
        $tglPerolehanYear = Carbon::parse($fixedAsset->tgl_perolehan)->format('Y');
        $groupFormat = $fixedAsset->subGroup->group->format;
        $nomorAset = $fixedAsset->nomor;
        $romanNumeralMonths = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $monthInRoman = $romanNumeralMonths[(int) $tglPerolehanMonth - 1];
        $fixedAsset->nomor = "{$departmentCode}_INL/{$monthInRoman}/{$tglPerolehanYear}/{$groupFormat}{$nomorAset}";
        $fixedAsset->spesifikasi = json_decode($fixedAsset->spesifikasi);
        $fixedAsset->assetDepartment = $departmentName;
        $fixedAsset->assetUserName = $userName;
        $fixedAsset->assetUserPosition = $userJabatan;

        $assetAge = $this->calculateAssetAge($tglPerolehan, $tglNow);
        $fixedAsset->assetAge = $assetAge;

        $formattedLogs = $this->formatLogs($fixedAsset->logs);
        $fixedAsset->history = $formattedLogs;
        unset($fixedAsset->logs);

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
                'nama' => 'required',
                'brand' => 'required',
                'id_lokasi' => 'required',
                'id_pic' => 'required',
                'kondisi' => 'required',
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
            ])->find($id);

            if (!$fixedAsset ) {
                return response()->json([
                    'message' => "Fixed Asset Not Found",
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            $oldPicId = $fixedAsset->id_pic;
            $oldData = $fixedAsset->toArray();

            $spesifikasi = json_encode($request->spesifikasi);

            $lokasi = $this->findOrFail(Location::class, ['id' => $request->input('id_lokasi')]);

            $picId = $request->input('id_pic');
            $deptId = $fixedAsset->id_departemen;
            $picResponse = Http::withHeaders(['Authorization' => $this->token])->get($this->urlUser . $picId);
            $deptResponse = Http::withHeaders(['Authorization' => $this->token])->get($this->urlDept . $deptId);
            $picData = $picResponse->json()['data'] ?? [];
            $deptData = $deptResponse->json()['data'] ?? [];
            $deptCode = $deptData['kode'] ?? 'UNKNOWN';

            $fixedAsset->update([
                'nama' => $request->input('nama'),
                'brand' => $request->input('brand'),
                'id_lokasi' => $lokasi->id,
                'id_pic' => $picData['id'],
                'kondisi' => $request->input('kondisi'),
                'spesifikasi' => $spesifikasi,
                'keterangan' => $request->input('keterangan'),
            ]);

            LoggerService::logAction($this->userData, $fixedAsset, 'update', $oldData, $fixedAsset->toArray());

            if ($fixedAsset->id_pic != $oldPicId) {
                $validator = Validator::make($request->all(), [
                    'tgl_serah' => 'required'
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'message' => $validator->errors(),
                        'code' => 400,
                        'success' => false
                    ], 400);
                }

                $tglSerah = $request->input('tgl_serah'); // Assuming it's in 'Y-m-d' format
                $month = date('m', strtotime($tglSerah));
                $year = date('Y', strtotime($tglSerah));

                $count = BastFixedAsset::whereMonth('tgl_serah', $month)
                                        ->whereYear('tgl_serah', $year)
                                        ->count();

                $formattedCount = str_pad($count + 1, 2, '0', STR_PAD_LEFT);
                $nomorSerah = "INL/HO/VII-$formattedCount/$month/$year/$deptCode";

                BastFixedAsset::create([
                    'id_fixed_asset' => $fixedAsset->id,
                    'tgl_serah' => $request->input('tgl_serah'),
                    'nomor_serah' => $nomorSerah,
                    'id_user' => $this->userData->sub,
                    'id_pic' => $fixedAsset->id_pic,
                    'ttd_terima' => null,
                    'ttd_checker' => null,
                ]);
            }

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
}
