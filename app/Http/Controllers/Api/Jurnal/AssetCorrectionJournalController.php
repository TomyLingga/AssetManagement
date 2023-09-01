<?php

namespace App\Http\Controllers\Api\Jurnal;

use App\Http\Controllers\Controller;
use App\Models\FixedAssets;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AssetCorrectionJournalController extends Controller
{
    public function nilaiWajar(Request $request)
    {
        $tglNow = $request->input('tanggal') ?? now()->toDateString();
        $fixedAssetsData = FixedAssets::with([
            'subGroup.group',
        ])
        ->join('sub_groups', 'fixed_assets.id_sub_grup', '=', 'sub_groups.id')
        ->join('groups', 'sub_groups.id_grup', '=', 'groups.id')
        ->where('fixed_assets.status', 1)
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

        $fixedAssetsData->transform(function ($fixedAsset) use ($tglNow) {
            $fixedAsset->tgl_perolehan = Carbon::parse($fixedAsset->tgl_perolehan);
            $fixedAsset->nilai_perolehan = floatval($fixedAsset->nilai_perolehan);
            $fixedAsset->nilai_depresiasi_awal = floatval($fixedAsset->nilai_depresiasi_awal);

            $assetAge = $this->calculateAssetAge($fixedAsset->tgl_perolehan, $tglNow);
            $monthlyDepreciation = $this->getMonthlyDepreciation($fixedAsset);

            $accumulatedDepreciation = $this->getAccumulatedDepreciation($assetAge, $monthlyDepreciation, $fixedAsset->tgl_perolehan, $fixedAsset->nilai_depresiasi_awal, $fixedAsset->masa_manfaat, $tglNow);

            $bookValue = $this->getBookValue($fixedAsset->nilai_perolehan, $accumulatedDepreciation);
            $fixedAsset->bookValue = floatval(sprintf("%.3f",$bookValue));
            $fairValue = $fixedAsset->fairValues->last();
            $latestFairValue = floatval(sprintf("%.3f",$fairValue ? $fairValue->nilai : 0));
            $difference = floatval(sprintf("%.3f",$fixedAsset->bookValue - $latestFairValue));
            $isGreater = $difference > 0 ? 'Book Value is greater' : ($difference < 0 ? 'Fair Value is greater' : 'Book Value and Fair Value are equal');

            return [
                'id' => $fixedAsset->id,
                'formated_kode_aktiva' => $fixedAsset->formated_kode_aktiva,
                'formated_kode_penyusutan' => $fixedAsset->formated_kode_penyusutan,
                'grup' => $fixedAsset->subGroup->group->nama,
                'subgrup' => $fixedAsset->subGroup->nama,
                'nama_aset' => $fixedAsset->nama,
                'bookValue' => $fixedAsset->bookValue,
                'latest_fair_values_nilai' => $latestFairValue,
                'difference' => $difference,
                'comparison_result' => $isGreater,
            ];
        });

        return response()->json([
            'data' => $fixedAssetsData,
            'message' => 'All Fixed Assets Retrieved Successfully',
            'code' => 200,
            'success' => true,
        ], 200);
    }

    public function valueInUse(Request $request)
    {
        $tglNow = $request->input('tanggal') ?? now()->toDateString();
        $fixedAssetsData = FixedAssets::with([
            'subGroup.group',
            'adjustment'
        ])
        ->join('sub_groups', 'fixed_assets.id_sub_grup', '=', 'sub_groups.id')
        ->join('groups', 'sub_groups.id_grup', '=', 'groups.id')
        ->where('fixed_assets.status', 1)
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

        $fixedAssetsData->transform(function ($fixedAsset) use ($tglNow) {
            $fixedAsset->tgl_perolehan = Carbon::parse($fixedAsset->tgl_perolehan);
            $fixedAsset->nilai_perolehan = floatval($fixedAsset->nilai_perolehan);
            $fixedAsset->nilai_depresiasi_awal = floatval($fixedAsset->nilai_depresiasi_awal);

            $assetAge = $this->calculateAssetAge($fixedAsset->tgl_perolehan, $tglNow);
            $monthlyDepreciation = $this->getMonthlyDepreciation($fixedAsset);

            $accumulatedDepreciation = $this->getAccumulatedDepreciation($assetAge, $monthlyDepreciation, $fixedAsset->tgl_perolehan, $fixedAsset->nilai_depresiasi_awal, $fixedAsset->masa_manfaat, $tglNow);

            $bookValue = $this->getBookValue($fixedAsset->nilai_perolehan, $accumulatedDepreciation);
            $fixedAsset->bookValue = $bookValue;

            $latestValueInUse = $fixedAsset->valueInUses->last();
            $latestValueInUseNilai = $latestValueInUse ? $latestValueInUse->nilai : 0;

            $difference = $bookValue - $latestValueInUseNilai;
            $isGreater = $difference > 0 ? 'Book Value is greater' : ($difference < 0 ? 'Value in Use is greater' : 'Book Value and Value in Use are equal');

            $debet = '';
            $kredit = '';

            if ($bookValue > $latestValueInUseNilai) {
                $debet = $fixedAsset->adjustment->kode_loss;
                $kredit = $fixedAsset->formated_kode_aktiva;
                $info = 'Loss Impairment';
            } elseif ($bookValue < $latestValueInUseNilai) {
                $debet = $fixedAsset->formated_kode_aktiva;
                $kredit = $fixedAsset->adjustment->kode_margin;
                $info = 'Margin';
            } else {
                $debet = $kredit = $info = 'Equal';
            }

            return [
                'id' => $fixedAsset->id,
                'formated_kode_aktiva' => $fixedAsset->formated_kode_aktiva,
                'formated_kode_penyusutan' => $fixedAsset->formated_kode_penyusutan,
                'grup' => $fixedAsset->subGroup->group->nama,
                'subgrup' => $fixedAsset->subGroup->nama,
                'nama_aset' => $fixedAsset->nama,
                'bookValue' => floatval(sprintf("%.3f",$bookValue)),
                'latest_value_in_use' => floatval(sprintf("%.3f",$latestValueInUseNilai)),
                'difference' => floatval(sprintf("%.3f",$difference)),
                'comparison_result' => $isGreater,
                'debet' => $debet,
                'kredit' => $kredit,
                'info' => $info
            ];
        });

        return response()->json([
            'data' => $fixedAssetsData,
            'message' => 'All Fixed Assets Retrieved Successfully',
            'code' => 200,
            'success' => true,
        ], 200);
    }
}
