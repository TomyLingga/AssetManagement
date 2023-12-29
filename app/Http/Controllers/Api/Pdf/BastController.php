<?php

namespace App\Http\Controllers\Api\Pdf;

use App\Http\Controllers\Controller;
use App\Models\FixedAssets;
use Illuminate\Http\Request;
use PDF;


class BastController extends Controller
{
    public function print(Request $request, $id) {

        $tglNow = $request->input('tanggal') ?? now()->toDateString();
        $fixedAssetData = FixedAssets::with([
            'subGroup.group',
            'fotoFixedAssets',
            'location.area',
            'adjustment',
            'fairValues',
            'valueInUses',
            'bastFixedAssets',
        ])->find($id);

        if (!$fixedAssetData ) {
            return response()->json([
                'message' => "Fixed Asset Not Found",
                'success' => true,
                'code' => 401
            ], 401);
        }

        $fixedAsset = $this->formattingById($fixedAssetData, $tglNow);
        $fixedAsset->fotoFixedAssets = $fixedAsset->fotoFixedAssets->map(function ($foto) {
            $foto->nama_file = env('BACKEND_FOTO_ASET') . $foto->nama_file;
            return $foto;
        });
        unset($fixedAsset->logs);

        $pdf = PDF::loadview('frPdf',['fixedAsset' => $fixedAsset])->setPaper('a4', 'landscape');

    	return $pdf->stream('BAST '.$fixedAsset->bastFixedAssets()->nomor_serah.'.pdf');
    }
}
