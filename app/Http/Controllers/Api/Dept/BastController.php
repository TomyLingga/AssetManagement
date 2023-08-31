<?php

namespace App\Http\Controllers\Api\Dept;

use App\Http\Controllers\Controller;
use App\Models\BastFixedAsset;
use App\Models\FixedAssets;
use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Services\LoggerService;
use Carbon\Carbon;

class BastController extends Controller
{
    public function indexPic()
    {
        $tglNow = now()->toDateString();
        $fixedAssetsData = FixedAssets::whereHas('bastFixedAssets', function ($query) {
            $query->where('id_pic', $this->userData->sub);
        })
        ->with([
            'subGroup.group',
            'location.area',
            'bastFixedAssets',
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

            $matchingDept = $departmentIdMapping->get($departmentId);

            if ($matchingDept) {
                $departmentCode = $matchingDept['kode'];
                $departmentName = $matchingDept['department'];

                $tglPerolehan = Carbon::parse($fixedAsset->tgl_perolehan);
                $tglPerolehanMonth = $tglPerolehan->format('m');
                $tglPerolehanYear = $tglPerolehan->format('Y');
                $groupFormat = $fixedAsset->subGroup->group->format;

                $nomorAset = $fixedAsset->nomor;

                $monthInRoman = $romanNumeralMonths[(int) $tglPerolehanMonth - 1];

                $fixedAsset->nomor = "{$departmentCode}_INL/{$monthInRoman}/{$tglPerolehanYear}/{$groupFormat}{$nomorAset}";
                $fixedAsset->spesifikasi = json_decode($fixedAsset->spesifikasi);
                $fixedAsset->assetDepartment = $departmentName;
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

    public function showPic($id)
    {
        $tglNow = now()->toDateString();
        $fixedAsset = FixedAssets::where('id_departemen', $this->userData->departemen)
        ->with([
            'subGroup.group',
            'location.area',
            'bastFixedAssets'
        ])->find($id);

        if (!$fixedAsset ) {
            return response()->json([
                'message' => "Fixed Asset not found or not belongs to user's department",
                'success' => true,
                'code' => 401
            ], 401);
        }

        $departmentId = $fixedAsset->id_departemen;
        $picId = $fixedAsset->id_pic;

        $deptData = $this->getDepartmentById($departmentId);

        if (!isset($deptData)) {
            return response()->json([
                'message' => 'Department not found',
                'success' => true,
                'code' => 401
            ], 401);
        }

        $picData = $this->getUserById($picId);

        foreach ($fixedAsset->bastFixedAssets as $bast) {
            $bastUserData = $this->getUserById($bast->id_user);
            $bastPicData = $this->getUserById($bast->id_pic);
            $bastCheckerData = $this->getUserById($bast->id_checker);

            $bast->id_user_name = $bastUserData['name'] ?? null;
            $bast->id_user_jabatan = $bastUserData['jabatan'] ?? null;
            $bast->id_user_signature = $bastUserData['signature'] ?? null;
            $bast->id_pic_name = $bastPicData['name'] ?? null;
            $bast->id_pic_jabatan = $bastPicData['jabatan'] ?? null;
            $bast->id_pic_signature = $bastPicData['signature'] ?? null;
            $bast->id_checker_name = $bastCheckerData['name'] ?? null;
            $bast->id_checker_jabatan = $bastCheckerData['jabatan'] ?? null;
            $bast->id_checker_signature = $bastCheckerData['signature'] ?? null;
        }

        if (!isset($picData)) {
            return response()->json([
                'message' => 'User not found',
                'success' => true,
                'code' => 401
            ], 401);
        }

        $departmentData = $deptData;

        $departmentCode = $departmentData['kode'];
        $departmentName = $departmentData['department'];
        $userName = $picData['name'];
        $userJabatan = $picData['jabatan'];
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

    public function approvePic($id)
    {
        try {

            $data = BastFixedAsset::findOrFail($id);

            if($this->userData->sub != $data->id_pic)
            {
                return response()->json([
                    'message' => 'User is not the PIC for this BAST',
                    'success' => false,
                    'code' => 500
                ], 500);
            }

            $oldData = $data->toArray();

            $data->update([
                'ttd_terima' => now(),
            ]);

            LoggerService::logAction($this->userData, $data, "Accept", $oldData, $data->toArray(), $data);

            return response()->json([
                'data' => $data,
                'message' => "Approved",
                'code' => 200,
                'success' => true
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => "Something went wrong",
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'code' => 500,
                'success' => false
            ], 500);
        }
    }

    public function approveChecker($id)
    {
        try {

            $data = BastFixedAsset::findOrFail($id);

            if($data->ttd_terima == null)
            {
                return response()->json([
                    'message' => 'PIC not yet accepted/approved this BAST',
                    'success' => false,
                    'code' => 500
                ], 500);
            }

            $oldData = $data->toArray();

            $data->update([
                'ttd_checker' => now(),
                'id_checker' => $this->userData->sub,
            ]);

            LoggerService::logAction($this->userData, $data, "Check", $oldData, $data->toArray(), $data);

            return response()->json([
                'data' => $data,
                'message' => "Checked",
                'code' => 200,
                'success' => true
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => "Something went wrong",
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'code' => 500,
                'success' => false
            ], 500);
        }
    }

    public function reject($id)
    {
        try {

            $data = BastFixedAsset::findOrFail($id);

            $oldData = $data->toArray();

            $data->update([
                'status' => '0',
            ]);

            LoggerService::logAction($this->userData, $data, "Reject", $oldData, $data->toArray(), $data);

            return response()->json([
                'data' => $data,
                'message' => "Rejected",
                'code' => 200,
                'success' => true
            ], 200);
        } catch (\Exception $e) {

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
