<?php

namespace App\Http\Controllers\Api\Jurnal;

use App\Http\Controllers\Controller;
use App\Models\FixedAssets;
use Illuminate\Http\Request;

class AssetDepreciationJournalController extends Controller
{
    public function index()
    {
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

        if (!$fixedAssetsData->isEmpty()) {
            $fixedAssetsData->transform(function ($fixedAsset) {
                $fixedAsset->monthlyDepreciation = $this->getMonthlyDepreciation($fixedAsset);
                return [
                    'nama_aset' => $fixedAsset->nama,
                    'grup' => $fixedAsset->subGroup->group->nama,
                    'subgrup' => $fixedAsset->subGroup->nama,
                    'tgl_perolehan' => $fixedAsset->tgl_perolehan,
                    'nilai_perolehan' => $fixedAsset->nilai_perolehan,
                    'masa_manfaat' => $fixedAsset->masa_manfaat,
                    'formated_kode_aktiva' => $fixedAsset->formated_kode_aktiva,
                    'formated_kode_penyusutan' => $fixedAsset->formated_kode_penyusutan,
                    'monthlyDepreciation' => $fixedAsset->monthlyDepreciation,
                ];
            });

            return response()->json([
                'data' => $fixedAssetsData,
                'message' => 'Asset Depreciation Journal Retrieved Successfully',
                'code' => 200,
                'success' => true,
            ], 200);
        } else {
            return response()->json([
                'message' => "Fixed Assets Not Found",
                'success' => true,
                'code' => 401
            ], 401);
        }
    }
}
