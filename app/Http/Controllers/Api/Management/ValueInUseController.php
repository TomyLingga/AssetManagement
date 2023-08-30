<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Models\FixedAssets;
use App\Models\ValueInUse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\LoggerService;

class ValueInUseController extends Controller
{
    private $messageFail = 'Something went wrong';
    private $messageAll = 'Success to Fetch All Datas';
    private $messageCreate = 'Success to Create Data';

    public function index($asetId)
    {
        try {
            $fixedAsset = FixedAssets::findOrFail($asetId);
            $valueInUses = ValueInUse::where('id_fixed_asset', $asetId)->get();

            $valueInUsesWithLogs = $valueInUses->map(function ($valueInUse) {
                $valueInUse->history = $this->formatLogs($valueInUse->logs);
                unset($valueInUse->logs);

                return $valueInUse;
            });

            return response()->json([
                'data' => $valueInUsesWithLogs,
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

            $data = ValueInUse::create([
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

            // Find the ValueInUse record by id and id_fixed_asset
            $valueInUse = ValueInUse::where('id_fixed_asset', $asetId)
                ->where('id', $id)
                ->first();

            if (!$valueInUse) {
                return response()->json([
                    'message' => 'ValueInUse not found',
                    'code' => 404,
                    'success' => false,
                ], 404);
            }

            $oldData = $valueInUse->toArray();

            // Update the fair value
            $valueInUse->nilai = $request->nilai;
            $valueInUse->save();

            LoggerService::logAction($this->userData, $valueInUse, 'update', $oldData, $valueInUse->toArray());

            DB::commit();

            return response()->json([
                'data' => $valueInUse,
                'message' => 'ValueInUse updated successfully',
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
            $valueInUse = ValueInUse::where('id_fixed_asset', $asetId)
                ->where('id', $id)
                ->first();

            if (!$valueInUse) {
                return response()->json([
                    'message' => 'ValueInUse not found',
                    'code' => 404,
                    'success' => false,
                ], 404);
            }

            $oldData = $valueInUse;

            $otherValueInUsesCount = ValueInUse::where('id_fixed_asset', $asetId)
                ->where('id', '<>', $id)
                ->count();

            if ($otherValueInUsesCount === 0) {
                return response()->json([
                    'message' => 'Cannot delete the last ValueInUse belonging to this FixedAsset',
                    'code' => 400,
                    'success' => false,
                ], 400);
            }

            $valueInUse->delete();

            LoggerService::logAction($this->userData, $valueInUse, 'delete', $oldData, null);

            DB::commit();

            return response()->json([
                'message' => 'ValueInUse deleted successfully',
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
