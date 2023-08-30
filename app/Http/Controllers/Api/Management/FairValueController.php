<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Models\FixedAssets;
use App\Models\FairValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\LoggerService;

class FairValueController extends Controller
{
    private $messageFail = 'Something went wrong';
    private $messageAll = 'Success to Fetch All Datas';
    private $messageCreate = 'Success to Create Data';

    public function index($asetId)
    {
        try {
            $fixedAsset = FixedAssets::findOrFail($asetId);
            $fairValues = FairValue::where('id_fixed_asset', $asetId)->get();

            $fairValuesWithLogs = $fairValues->map(function ($fairValue) {

                $fairValue->history = $this->formatLogs($fairValue->logs);
                unset($fairValue->logs);

                return $fairValue;
            });

            return response()->json([
                'data' => $fairValuesWithLogs,
                'message' => $this->messageAll,
                'code' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $this->messageFail,
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'code' => 500,
                'success' => false
            ], 500);
        }
    }

    public function store(Request $request, $asetId)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'nilai' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false,
                ], 400);
            }

            $data = FairValue::create([
                'id_fixed_asset' => $asetId,
                'nilai' => $request->nilai
            ]);

            LoggerService::logAction($this->userData, $data, 'create', null, $data->toArray());

            DB::commit();

            return response()->json([
                'data' => $data,
                'message' => $this->messageCreate,
                'code' => 200,
                'success' => true,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => $this->messageFail,
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'code' => 500,
                'success' => false,
            ], 500);
        }
    }

    public function update(Request $request, $asetId, $id)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'nilai' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false,
                ], 400);
            }

            // Find the FairValue record by id and id_fixed_asset
            $fairValue = FairValue::where('id_fixed_asset', $asetId)
                ->where('id', $id)
                ->first();

            if (!$fairValue) {
                return response()->json([
                    'message' => 'FairValue not found',
                    'code' => 404,
                    'success' => false,
                ], 404);
            }

            $oldData = $fairValue->toArray();

            // Update the fair value
            $fairValue->nilai = $request->nilai;
            $fairValue->save();

            LoggerService::logAction($this->userData, $fairValue, 'update', $oldData, $fairValue->toArray());

            DB::commit();

            return response()->json([
                'data' => $fairValue,
                'message' => 'FairValue updated successfully',
                'code' => 200,
                'success' => true,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Update failed',
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'code' => 500,
                'success' => false,
            ], 500);
        }
    }

    public function destroy($asetId, $id)
    {
        DB::beginTransaction();

        try {
            $fairValue = FairValue::where('id_fixed_asset', $asetId)
                ->where('id', $id)
                ->first();

            if (!$fairValue) {
                return response()->json([
                    'message' => 'FairValue not found',
                    'code' => 404,
                    'success' => false,
                ], 404);
            }

            $oldData = $fairValue->toArray();

            $otherFairValuesCount = FairValue::where('id_fixed_asset', $asetId)
                ->where('id', '<>', $id)
                ->count();

            if ($otherFairValuesCount === 0) {
                return response()->json([
                    'message' => 'Cannot delete the last FairValue belonging to this FixedAsset',
                    'code' => 400,
                    'success' => false,
                ], 400);
            }

            $fairValue->delete();

            LoggerService::logAction($this->userData, $fairValue, 'delete', $oldData, null);

            DB::commit();

            return response()->json([
                'message' => 'FairValue deleted successfully',
                'code' => 200,
                'success' => true,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Deletion failed',
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'code' => 500,
                'success' => false,
            ], 500);
        }
    }
}
