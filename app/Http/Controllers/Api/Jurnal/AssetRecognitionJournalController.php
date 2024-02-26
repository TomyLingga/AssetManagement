<?php

namespace App\Http\Controllers\Api\Jurnal;

use App\Http\Controllers\Controller;
use App\Models\FixedAssets;

class AssetRecognitionJournalController extends Controller
{
    public function index()
    {
        $fixedAssetsData = FixedAssets::with([
            'subGroup.group',
            'supplier',
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

        // $getSupplierName = function ($supplierId) {
        //     $supplierData = $this->getSupplier($supplierId); // Use appropriate method here
        //     return $supplierData['name'] ?? '';
        // };

        $fixedAssetsData->transform(function ($fixedAsset){
            return [
                'debet' => $fixedAsset->nama,
                'kredit' => $fixedAsset->supplier->nama,
                'grup' => $fixedAsset->subGroup->group->nama,
                'sub_grup' => $fixedAsset->subGroup->nama,
                'tgl_perolehan' => $fixedAsset->tgl_perolehan,
                'nilai_perolehan' => $fixedAsset->nilai_perolehan,
                'masa_manfaat' => $fixedAsset->masa_manfaat,
                'formated_kode_aktiva' => $fixedAsset->formated_kode_aktiva,
                'formated_kode_penyusutan' => $fixedAsset->formated_kode_penyusutan,
            ];
        });

        return response()->json([
            'data' => $fixedAssetsData,
            'message' => 'Asset Recognition Journal Retrieved Successfully',
            'code' => 200,
            'success' => true,
        ], 200);
    }
}
