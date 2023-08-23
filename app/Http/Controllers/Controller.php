<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    private $token;
    private $urlDept = "http://36.92.181.10:4763/api/department/get/";
    private $urlAllDept = "http://36.92.181.10:4763/api/department";
    private $urlUser = "http://36.92.181.10:4763/api/user/get/";
    private $urlAllUser = "http://36.92.181.10:4763/api/user";

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->token = $request->get('user_token');
            return $next($request);
        });
    }

    private function calculateAssetAge($tglPerolehan, $tglNow)
    {
        $currentDate = Carbon::parse($tglNow);
        return $currentDate->diffInMonths($tglPerolehan);
    }

    private function getEndMasaManfaatDate($fixedAsset)
    {
        $tglPerolehan = Carbon::parse($fixedAsset->tgl_perolehan);
        $endMasaManfaat = $tglPerolehan->copy()->addMonths($fixedAsset->masa_manfaat)->startOfDay();
        return $endMasaManfaat->toDateString();
    }

    private function getMonthlyDepreciation($fixedAsset)
    {
        return $fixedAsset->nilai_perolehan / $fixedAsset->masa_manfaat;
    }

    private function calculateInitialBalance($tglPerolehan, $monthlyDepreciation, $initialDepreciationValue, $endMasaManfaatDate, $tglNow)
    {
        $tglPerolehan = Carbon::parse($tglPerolehan)->addMonth();
        $endMasaManfaatDate = Carbon::parse($endMasaManfaatDate);
        $tglNow = Carbon::parse($tglNow);

        if ($tglNow >= $endMasaManfaatDate) {
            $tglNow = $endMasaManfaatDate;
        }

        $monthlyDepreciation = floatval($monthlyDepreciation);
        $initialBalances = [];
        $currentYearTotal = 0.0;

        while ($tglPerolehan <= $tglNow) {
            $year = $tglPerolehan->year;
            $month = $tglPerolehan->month;

            if (!isset($initialBalances[$year])) {
                $initialBalanceThisYear = ($year < 2023) ? $initialDepreciationValue : ($currentYearTotal + $initialDepreciationValue);
                $initialBalances[$year] = [
                    'initialBalanceThisYear' => $initialBalanceThisYear,
                    'totalThisYear' => ($year < 2023) ? $initialDepreciationValue : 0.0,
                    'totalAccumulationUntilThisYear' => ($year < 2023) ? $initialDepreciationValue : 0.0,
                    'monthlyDepreciation' => []
                ];
            }

            $initialBalances[$year]['monthlyDepreciation'][$month] = $monthlyDepreciation;

            if ($year > 2023) {
                $currentYearTotal += $monthlyDepreciation;
                $initialBalanceThisYear =  $currentYearTotal + $initialDepreciationValue;
            }

            if ($year >= 2023) {

                $initialBalances[$year]['initialBalanceThisYear'] = floatval(sprintf("%.3f", $initialBalanceThisYear));
                $initialBalances[$year]['totalThisYear'] = floatval(sprintf("%.3f", $initialBalances[$year]['totalThisYear'] + $monthlyDepreciation));
                $initialBalances[$year]['totalAccumulationUntilThisYear'] = floatval(sprintf("%.3f", $initialBalances[$year]['totalThisYear'] + $initialDepreciationValue));
                if ($year > 2023) {
                    $initialBalances[$year]['totalAccumulationUntilThisYear'] = floatval(sprintf("%.3f", $initialBalances[$year-1]['totalAccumulationUntilThisYear'] + $initialBalances[$year]['totalThisYear']));
                }
            }

            $tglPerolehan->addMonth();
        }

        return $initialBalances;
    }

    private function getAccumulatedDepreciation($assetAge, $monthlyDepreciation, $tglPerolehan, $nilaiDepresiasiAwal, $masa_manfaat, $tglNow)
    {
        $tglNow = Carbon::parse($tglNow);
        $tglPerolehanYear = Carbon::parse($tglPerolehan)->year;
        $tglPerolehanDay = Carbon::parse($tglPerolehan)->day;
        $monthBefore2023 = Carbon::createFromDate(2022, 12, $tglPerolehanDay)->startOfDay()->diffInMonths($tglPerolehan);

        if ($tglPerolehanYear < 2023) {
            $monthsSince2023 = Carbon::createFromDate(2022, 12, $tglPerolehanDay)->endOfDay()->diffInMonths($tglNow);
            $totalMonth = $monthBefore2023 + $monthsSince2023;
            $monthResult = ($totalMonth >= $masa_manfaat) ? $monthsSince2023 - ($totalMonth - $masa_manfaat) : $monthsSince2023;

            return ($monthlyDepreciation * $monthResult) + $nilaiDepresiasiAwal;
        }

        return min($assetAge, $masa_manfaat) * $monthlyDepreciation;
    }

    private function getBookValue($nilaiPerolehan, $accumulatedDepreciation)
    {
        $bookValue = $nilaiPerolehan - $accumulatedDepreciation;
        return max($bookValue, 0);
    }

    private function getDepartmentData()
    {
        return Http::withHeaders([
            'Authorization' => $this->token,
        ])->get($this->urlAllDept)->json()['data'] ?? [];
    }

    private function getDepartmentById($id)
    {
        return Http::withHeaders([
            'Authorization' => $this->token,
        ])->get($this->urlDept. $id)->json()['data'] ?? [];
    }

    private function getUserData()
    {
        return Http::withHeaders([
            'Authorization' => $this->token,
        ])->get($this->urlAllUser)->json()['data'] ?? [];
    }

    private function getUserById($id)
    {
        return Http::withHeaders([
            'Authorization' => $this->token,
        ])->get($this->urlUser . $id)->json()['data'] ?? [];
    }

    public function formattingById($fixedAsset, $tglNow)
    {
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

        $endMasaManfaatDate = $this->getEndMasaManfaatDate($fixedAsset);
        $fixedAsset->endMasaManfaatDate = $endMasaManfaatDate;

        $monthlyDepreciation = $this->getMonthlyDepreciation($fixedAsset);
        $fixedAsset->monthlyDepreciation = $monthlyDepreciation;
        $fixedAsset->annualDepreciation = $monthlyDepreciation * 12;

        $initialBalance = $this->calculateInitialBalance($fixedAsset->tgl_perolehan, $monthlyDepreciation, $fixedAsset->nilai_depresiasi_awal, $endMasaManfaatDate, $tglNow);
        $fixedAsset->calculateInitialBalance = $initialBalance;

        $accumulatedDepreciation = $this->getAccumulatedDepreciation($assetAge, $monthlyDepreciation, $tglPerolehan, $fixedAsset->nilai_depresiasi_awal, $fixedAsset->masa_manfaat, $tglNow);
        $fixedAsset->accumulatedDepreciation = $accumulatedDepreciation;

        $bookValue = $this->getBookValue($fixedAsset->nilai_perolehan, $fixedAsset->accumulatedDepreciation);
        $fixedAsset->bookValue = $bookValue;
        $fixedAsset->formated_kode_aktiva = $fixedAsset->formated_kode_aktiva;
        $fixedAsset->formated_kode_penyusutan = $fixedAsset->formated_kode_penyusutan;

        return $fixedAsset;
    }

    public function formatting($fixedAssets, $tglNow){
        $deptData = $this->getDepartmentData();
        $userData = $this->getUserData();
        $departmentIdMapping = collect($deptData)->keyBy('id');
        $userIdMapping = collect($userData)->keyBy('id');

        $romanNumeralMonths = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

        $fixedAssets->transform(function ($fixedAsset) use ($departmentIdMapping, $userIdMapping, $romanNumeralMonths, $tglNow) {
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
                $fixedAsset->nilai_depresiasi_awal = floatval($fixedAsset->nilai_depresiasi_awal);

                $nomorAset = $fixedAsset->nomor;

                $monthInRoman = $romanNumeralMonths[(int) $tglPerolehanMonth - 1];

                $fixedAsset->nomor = "{$departmentCode}_INL/{$monthInRoman}/{$tglPerolehanYear}/{$groupFormat}{$nomorAset}";
                $fixedAsset->spesifikasi = json_decode($fixedAsset->spesifikasi);
                $fixedAsset->assetDepartment = $departmentName;
                $fixedAsset->assetUserName = $userName;
                $fixedAsset->assetUserPosition = $userJabatan;

                $assetAge = $this->calculateAssetAge($tglPerolehan, $tglNow);
                $fixedAsset->assetAge = $assetAge;

                $endMasaManfaatDate = $this->getEndMasaManfaatDate($fixedAsset);
                $fixedAsset->endMasaManfaatDate = $endMasaManfaatDate;

                $monthlyDepreciation = $this->getMonthlyDepreciation($fixedAsset);
                $fixedAsset->monthlyDepreciation = $monthlyDepreciation;
                $fixedAsset->annualDepreciation = $monthlyDepreciation * 12;

                $initialBalance = $this->calculateInitialBalance($fixedAsset->tgl_perolehan, $monthlyDepreciation, $fixedAsset->nilai_depresiasi_awal, $endMasaManfaatDate, $tglNow);
                $fixedAsset->calculateInitialBalance = $initialBalance;

                $accumulatedDepreciation = $this->getAccumulatedDepreciation($assetAge, $monthlyDepreciation, $tglPerolehan, $fixedAsset->nilai_depresiasi_awal, $fixedAsset->masa_manfaat, $tglNow);
                $fixedAsset->accumulatedDepreciation = $accumulatedDepreciation;

                $bookValue = $this->getBookValue($fixedAsset->nilai_perolehan, $fixedAsset->accumulatedDepreciation);
                $fixedAsset->bookValue = $bookValue;
            }

            $fixedAsset->formated_kode_aktiva = $fixedAsset->formated_kode_aktiva;
            $fixedAsset->formated_kode_penyusutan = $fixedAsset->formated_kode_penyusutan;

            return $fixedAsset;
        });
        return $fixedAssets;
    }
}
