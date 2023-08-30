<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Models\Adjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use App\Services\LoggerService;

class AdjustmentController extends Controller
{
    private $messageFail = 'Something went wrong';
    private $messageMissing = 'Data not found in record';
    private $messageAll = 'Success to Fetch All Datas';
    private $messageSuccess = 'Success to Fetch Data';
    private $messageCreate = 'Success to Create Data';
    private $messageUpdate = 'Success to Update Data';

    public function index()
    {
        try {
            $adjustments = Adjustment::all();

            if ($adjustments->isEmpty()) {
                return response()->json([
                    'message' => $this->messageMissing,
                    'code' => 401
                ], 401);
            }

            // $adjustmentsWithLogs = $adjustments->map(function ($adjustment) {
            //     return [
            //         'id' => $adjustment->id,
            //         'nama_margin' => $adjustment->nama_margin,
            //         'kode_margin' => $adjustment->kode_margin,
            //         'nama_loss' => $adjustment->nama_loss,
            //         'kode_loss' => $adjustment->kode_loss,
            //         'logs' => $this->formatLogs($adjustment->logs),
            //     ];
            // });

            return response()->json([
                'data' => $adjustments,
                'message' => $this->messageAll,
                'code' => 200
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'message' => $this->messageFail,
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'code' => 500,
                'success' => false,
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $adjustment = Adjustment::findOrFail($id);

            $adjustment->history = $this->formatLogs($adjustment->logs);
            unset($adjustment->logs);

            return response()->json([
                'data' => $adjustment,
                'message' => $this->messageSuccess,
                'code' => 200
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'message' => $this->messageFail,
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'code' => 500,
                'success' => false,
            ], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'nama_margin' => 'required',
                'kode_margin' => 'required|unique:adjustments',
                'nama_loss' => 'required',
                'kode_loss' => 'required|unique:adjustments',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false,
                ], 400);
            }

            $data = Adjustment::create($request->all());

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

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $adjustment = Adjustment::find($id);

            if (!$adjustment) {

                return response()->json([
                    'message' => $this->messageMissing,
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'kode_margin' => [
                    Rule::unique('adjustments')->ignore($adjustment->id)
                ],
                'kode_loss' => [
                    Rule::unique('adjustments')->ignore($adjustment->id)
                ],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false
                ], 400);
            }

            $dataToUpdate = [
                'nama_margin' => $request->filled('nama_margin') ? $request->nama_margin : $adjustment->nama_margin,
                'kode_margin' => $request->filled('kode_margin') ? $request->kode_margin : $adjustment->kode_margin,
                'nama_loss' => $request->filled('nama_loss') ? $request->nama_loss : $adjustment->nama_loss,
                'kode_loss' => $request->filled('kode_loss') ? $request->kode_loss : $adjustment->kode_loss,
            ];

            $oldData = $adjustment->toArray();
            $adjustment->update($dataToUpdate);

            LoggerService::logAction($this->userData, $adjustment, 'update', $oldData, $adjustment->toArray());

            DB::commit();

            return response()->json([
                'data' => $adjustment,
                'message' => $this->messageUpdate,
                'code' => 200,
                'success' => true
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => $this->messageFail,
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'code' => 500,
                'success' => false
            ], 500);
        }
    }
}
