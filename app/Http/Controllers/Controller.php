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

    public $token;
    public $userData;
    public $urlDept;
    public $urlAllDept;
    public $urlUser;
    public $urlAllUser;
    public $urlAllSuppliers;
    public $urlSupplier;
    // public $urlAllMIS;
    // public $urlMIS;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->token = $request->get('user_token');
            $this->userData = $request->get('decoded');
            $this->urlDept = env('BASE_URL_PORTAL')."department/get/";
            $this->urlAllDept = env('BASE_URL_PORTAL')."department";
            $this->urlUser = env('BASE_URL_PORTAL')."user/get/";
            $this->urlAllUser = env('BASE_URL_PORTAL')."user";
            $this->urlAllSuppliers = env('BASE_URL_ODOO')."supplier/index";
            $this->urlSupplier = env('BASE_URL_ODOO')."supplier/get/";
            // $this->urlAllMIS = env('BASE_URL_ODOO')."mis/index";
            // $this->urlMIS = env('BASE_URL_ODOO')."mis/get/";
            return $next($request);
        });
    }

    public function getAllSuppliers()
    {
        return Http::withHeaders([
            'Authorization' => $this->token,
        ])->get($this->urlAllSuppliers)->json()['data'] ?? [];
    }

    public function getSupplier($id)
    {
        return Http::withHeaders([
            'Authorization' => $this->token,
        ])->get($this->urlSupplier . $id)->json()['data'] ?? [];
    }

    public function calculateAssetAge($tglPerolehan, $tglNow)
    {
        $currentDate = Carbon::parse($tglNow);
        return $currentDate->diffInMonths($tglPerolehan);
    }

    public function getEndMasaManfaatDate($fixedAsset)
    {
        $tglPerolehan = Carbon::parse($fixedAsset->tgl_perolehan);
        $endMasaManfaat = $tglPerolehan->copy()->addMonths($fixedAsset->masa_manfaat)->startOfDay();
        return $endMasaManfaat->toDateString();
    }

    public function getMonthlyDepreciation($fixedAsset)
    {
        return $fixedAsset->nilai_perolehan / $fixedAsset->masa_manfaat;
    }

    public function calculateInitialBalance($tglPerolehan, $monthlyDepreciation, $initialDepreciationValue, $endMasaManfaatDate, $tglNow)
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
                $initialBalanceThisYear = ($year < 2023) ? 0 : ($currentYearTotal + $initialDepreciationValue);
                if ($year < 2023) {
                    if ($year == 2022) {
                        $totalThisYear = $initialDepreciationValue;
                        $totalAccumulationUntilThisYear = $initialDepreciationValue;
                    }else{
                        $totalThisYear = 0.0;
                        $totalAccumulationUntilThisYear = 0.0;
                    }
                }else{
                    $totalThisYear = 0.0;
                    $totalAccumulationUntilThisYear = 0.0;
                }
                $initialBalances[$year] = [
                    'initialBalanceThisYear' => $initialBalanceThisYear,
                    'totalThisYear' => $totalThisYear,
                    'totalAccumulationUntilThisYear' => $totalAccumulationUntilThisYear,
                    'monthlyDepreciation' => []
                ];
            }

            if ($year < 2023) {
                if ($year == 2022 && $month == 12) {
                    $initialBalances[$year]['monthlyDepreciation'][$month] = $initialDepreciationValue;
                }else{
                    $initialBalances[$year]['monthlyDepreciation'][$month] = 0.0;
                }
            }else{
                $initialBalances[$year]['monthlyDepreciation'][$month] = $monthlyDepreciation;
            }

            if ($year > 2023) {
                $currentYearTotal += $monthlyDepreciation;
                $initialBalanceThisYear =  $currentYearTotal + $initialDepreciationValue;
            }

            if ($year >= 2023) {

                $initialBalances[$year]['initialBalanceThisYear'] = floatval(sprintf("%.3f", $initialBalanceThisYear));
                $initialBalances[$year]['totalThisYear'] = floatval(sprintf("%.3f", $initialBalances[$year]['totalThisYear'] + $monthlyDepreciation));
                $initialBalances[$year]['totalAccumulationUntilThisYear'] = floatval(sprintf("%.3f", $initialBalances[$year]['totalThisYear'] + $initialDepreciationValue));
                if ($year > 2023) {
                    $previousYear = $year - 1;

                    // Check if the previous year's index exists
                    if (isset($initialBalances[$previousYear]['totalAccumulationUntilThisYear'])) {
                        $accumulation = $initialBalances[$previousYear]['totalAccumulationUntilThisYear'];
                    } else {
                        // Set a default value if the index doesn't exist
                        $accumulation = 0;
                    }

                    $initialBalances[$year]['totalAccumulationUntilThisYear'] = floatval(sprintf("%.3f", $accumulation + $initialBalances[$year]['totalThisYear']));
                }
            }

            $tglPerolehan->addMonth();
        }

        return $initialBalances;
    }

    public function getAccumulatedDepreciation($assetAge, $monthlyDepreciation, $tglPerolehan, $nilaiDepresiasiAwal, $masa_manfaat, $tglNow)
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

    public function getBookValue($nilaiPerolehan, $accumulatedDepreciation)
    {
        $bookValue = $nilaiPerolehan - $accumulatedDepreciation;
        return max($bookValue, 0);
    }

    public function getDepartmentData()
    {
        return Http::withHeaders([
            'Authorization' => $this->token,
        ])->get($this->urlAllDept)->json()['data'] ?? [];
    }

    public function getDepartmentById($id)
    {
        return Http::withHeaders([
            'Authorization' => $this->token,
        ])->get($this->urlDept. $id)->json()['data'] ?? [];
    }

    public function getUserData()
    {
        return Http::withHeaders([
            'Authorization' => $this->token,
        ])->get($this->urlAllUser)->json()['data'] ?? [];
    }

    public function getUserById($id)
    {
        return Http::withHeaders([
            'Authorization' => $this->token,
        ])->get($this->urlUser . $id)->json()['data'] ?? [];
    }

    public function formattingById($fixedAsset, $tglNow)
    {
        $departmentId = $fixedAsset->id_departemen;
        $misId = $fixedAsset->id_mis;
        $supplierId = $fixedAsset->id_supplier;
        $userId = $fixedAsset->id_pic;

        $deptData = $this->getDepartmentById($departmentId);
        if ($deptData == []) {
            return response()->json([
                'message' => 'Department not found',
                'success' => true,
                'code' => 401
            ], 401);
        }

        $userData = $this->getUserById($userId);

        if ($userData == []) {
            return response()->json([
                'message' => 'User not found',
                'success' => true,
                'code' => 401
            ], 401);
        }

        // $misData = $this->getMIS($misId);
        $supplierData = $this->getSupplier($supplierId);

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
        $fixedAsset->assetMIS = $misId;
        $fixedAsset->assetSupplier = $supplierData;

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
        $formattedLogs = $this->formatLogs($fixedAsset->logs);
        $fixedAsset->history = $formattedLogs;

        return $fixedAsset;
    }

    public function formatting($fixedAssets, $tglNow){
        $deptData = $this->getDepartmentData();
        $supplierData = $this->getAllSuppliers();
        // $misData = $this->getAllMIS();
        $userData = $this->getUserData();
        $departmentIdMapping = collect($deptData)->keyBy('id');
        // $misIdMapping = collect($misData)->keyBy('id');
        $supplierIdMapping = collect($supplierData)->keyBy('id');
        $userIdMapping = collect($userData)->keyBy('id');

        $romanNumeralMonths = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

        $fixedAssets->transform(function ($fixedAsset) use ($supplierIdMapping, $departmentIdMapping, $userIdMapping, $romanNumeralMonths, $tglNow) {
            $departmentId = $fixedAsset->id_departemen;
            $misId = $fixedAsset->id_mis;
            $supplierId = $fixedAsset->id_supplier;
            $userId = $fixedAsset->id_pic;

            $matchingDept = $departmentIdMapping->get($departmentId);
            // $matchingMis = $misIdMapping->get($misId);
            $matchingSupplier = $supplierIdMapping->get($supplierId);
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
                // $fixedAsset->assetMIS = $fixedAsset->assetMIS;
                $fixedAsset->assetSupplier = $matchingSupplier;

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

    public function formatLogs($logs)
    {
        return $logs->map(function ($log) {
            $user = $this->getUserById($log->user_id);
            $oldData = json_decode($log->old_data, true);
            $newData = json_decode($log->new_data, true);

            $changes = [];
            if ($log->action === 'update') {
                $changes = collect($newData)->map(function ($value, $key) use ($oldData) {
                    if ($oldData[$key] !== $value) {
                        return [
                            'old' => $oldData[$key],
                            'new' => $value,
                        ];
                    }
                })->filter();
            }

            return [
                'action' => $log->action,
                'user_name' => $user['name'],
                'changes' => $changes,
                'created_at' => $log->created_at,
            ];
        })->sortByDesc('created_at');
    }

    public function formatLogsForMultiple($logs)
    {
        $formattedLogs = $logs->map(function ($log) {
            $user = $this->getUserById($log->user_id);
            $oldData = json_decode($log->old_data, true);
            $newData = json_decode($log->new_data, true);

            return [
                'action' => $log->action,
                'user_name' => $user['name'],
                'old_data' => $oldData,
                'new_data' => $newData,
                'created_at' => $log->created_at,
            ];
        });

        $formattedLogs = $formattedLogs->sortByDesc('created_at');

        return $formattedLogs;
    }
}
