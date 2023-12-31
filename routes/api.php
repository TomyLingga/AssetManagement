<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => 'levelnine.checker'], function () {
    //adjustment
    Route::get('adjustment', [App\Http\Controllers\Api\Management\AdjustmentController::class, 'index']);
    Route::get('adjustment/get/{id}', [App\Http\Controllers\Api\Management\AdjustmentController::class, 'show']);
    Route::post('adjustment/add', [App\Http\Controllers\Api\Management\AdjustmentController::class, 'store']);
    Route::post('adjustment/update/{id}', [App\Http\Controllers\Api\Management\AdjustmentController::class, 'update']);

    //supplier
    Route::get('supplier', [App\Http\Controllers\Api\Management\SupplierController::class, 'index']);
    Route::get('supplier/get/{id}', [App\Http\Controllers\Api\Management\SupplierController::class, 'show']);
    Route::post('supplier/add', [App\Http\Controllers\Api\Management\SupplierController::class, 'store']);
    Route::post('supplier/update/{id}', [App\Http\Controllers\Api\Management\SupplierController::class, 'update']);

    //Fixed-Asset
    Route::post('fixed-asset', [App\Http\Controllers\Api\Aset\FixedAssetsController::class, 'index']);
    Route::post('fixed-asset/add', [App\Http\Controllers\Api\Aset\FixedAssetsController::class, 'store']);
    Route::post('fixed-asset/get/{id}', [App\Http\Controllers\Api\Aset\FixedAssetsController::class, 'show']);
    Route::post('fixed-asset/update/{id}', [App\Http\Controllers\Api\Aset\FixedAssetsController::class, 'update']);

    //Grup
    Route::post('group/add', [App\Http\Controllers\Api\Grup\GroupController::class, 'store']);

    //FairValue
    Route::get('fair-value/asset/{asetId}', [App\Http\Controllers\Api\Management\FairValueController::class, 'index']);
    Route::post('fair-value/add/{asetId}', [App\Http\Controllers\Api\Management\FairValueController::class, 'store']);
    Route::post('fair-value/update/{asetId}/{id}', [App\Http\Controllers\Api\Management\FairValueController::class, 'update']);
    Route::delete('fair-value/delete/{asetId}/{id}', [App\Http\Controllers\Api\Management\FairValueController::class, 'destroy']);

    //ValueInUse
    Route::get('value-in-use/asset/{asetId}', [App\Http\Controllers\Api\Management\ValueInUseController::class, 'index']);
    Route::post('value-in-use/add/{asetId}', [App\Http\Controllers\Api\Management\ValueInUseController::class, 'store']);
    Route::post('value-in-use/update/{asetId}/{id}', [App\Http\Controllers\Api\Management\ValueInUseController::class, 'update']);
    Route::delete('value-in-use/delete/{asetId}/{id}', [App\Http\Controllers\Api\Management\ValueInUseController::class, 'destroy']);

    //Export Fixed Asset
    Route::post('fixed-asset/export', [App\Http\Controllers\Api\Jurnal\FixedAssetExportController::class, 'index']);
    //Jurnal Pengakuan Aset
    Route::get('journal/asset-recognition', [App\Http\Controllers\Api\Jurnal\AssetRecognitionJournalController::class, 'index']);
    //Jurnal Depresiasi
    Route::get('journal/asset-depreciation', [App\Http\Controllers\Api\Jurnal\AssetDepreciationJournalController::class, 'index']);
    //Jurnal Koreksi Nilai Wajar
    Route::post('journal/asset-correction-fair-value', [App\Http\Controllers\Api\Jurnal\AssetCorrectionJournalController::class, 'nilaiWajar']);
    //Jurnal Koreksi Value in Use
    Route::post('journal/asset-correction-value-in-use', [App\Http\Controllers\Api\Jurnal\AssetCorrectionJournalController::class, 'valueInUse']);
});

Route::group(['middleware' => 'leveleight.checker'], function () {
    //area
    Route::get('area', [App\Http\Controllers\Api\Management\AreaController::class, 'index']);
    Route::get('area/get/{id}', [App\Http\Controllers\Api\Management\AreaController::class, 'show']);
    Route::post('area/add', [App\Http\Controllers\Api\Management\AreaController::class, 'store']);
    Route::post('area/update/{id}', [App\Http\Controllers\Api\Management\AreaController::class, 'update']);

    //lokasi
    Route::get('location', [App\Http\Controllers\Api\Management\LocationController::class, 'index']);
    Route::get('location/area/{id}', [App\Http\Controllers\Api\Management\LocationController::class, 'indexArea']);
    Route::get('location/get/{id}', [App\Http\Controllers\Api\Management\LocationController::class, 'show']);

    //Group
    Route::get('group', [App\Http\Controllers\Api\Grup\GroupController::class, 'index']);
    Route::get('group/get/{id}', [App\Http\Controllers\Api\Grup\GroupController::class, 'show']);
    Route::post('group/update/{id}', [App\Http\Controllers\Api\Grup\GroupController::class, 'update']);
    Route::post('group/check-kode', [App\Http\Controllers\Api\Grup\GroupController::class, 'kodeExist']);

    //sub-Group
    Route::get('sub-group', [App\Http\Controllers\Api\Grup\SubGroupController::class, 'index']);
    Route::get('sub-group/get/{id}', [App\Http\Controllers\Api\Grup\SubGroupController::class, 'show']);
    Route::get('sub-group/group/{id}', [App\Http\Controllers\Api\Grup\SubGroupController::class, 'indexGrup']);

    //fixedAset
    Route::get('dept-fixed-asset', [App\Http\Controllers\Api\Dept\FixedAssetsController::class, 'index']);
    Route::get('dept-fixed-asset/get/{id}', [App\Http\Controllers\Api\Dept\FixedAssetsController::class, 'show']);
    Route::post('dept-fixed-asset/update/{id}', [App\Http\Controllers\Api\Dept\FixedAssetsController::class, 'update']);
    Route::get('fixed-asset/active/{id}', [App\Http\Controllers\Api\Dept\FixedAssetsController::class, 'toggleActive']);

});

Route::group(['middleware' => 'levelone.checker'], function () {
    //bast
    Route::get('bast-fixed-asset/index-pic', [App\Http\Controllers\Api\Dept\BastController::class, 'indexPic']);
    Route::get('bast-fixed-asset/show-pic/{id}', [App\Http\Controllers\Api\Dept\BastController::class, 'showPic']);
    Route::get('bast-fixed-asset/accept/{id}', [App\Http\Controllers\Api\Dept\BastController::class, 'approvePic']);
    Route::get('bast-fixed-asset/check/{id}', [App\Http\Controllers\Api\Dept\BastController::class, 'approveChecker']);
    Route::get('bast-fixed-asset/reject/{id}', [App\Http\Controllers\Api\Dept\BastController::class, 'reject']);
    Route::get('bast-fixed-asset/print/{id}', [App\Http\Controllers\Api\Pdf\BastController::class, 'print']);
});

Route::fallback(function () {
    return response()->json(['code' => 404, 'message' => 'URL not Found'], 404);
});
