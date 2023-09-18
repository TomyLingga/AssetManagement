<?php

namespace App\Http\Controllers\Api\Jurnal;

use App\Http\Controllers\Controller;
use App\Models\FixedAssets;
use Illuminate\Http\Request;

class FixedAssetExportController extends Controller
{
    public function index(Request $request)
    {
        $tglNow = $request->input('tanggal') ?? now()->toDateString();
        $fixedAssetsData = FixedAssets::with([
            'subGroup.group',
            'location.area',
            'fairValues',
            'valueInUses',
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

        $fixedAssets = $this->formatting($fixedAssetsData, $tglNow);

        return response()->json([
            'data' => $fixedAssets,
            'message' => 'All Fixed Assets Retrieved Successfully',
            'code' => 200,
            'success' => true,
        ], 200);
    }
}
