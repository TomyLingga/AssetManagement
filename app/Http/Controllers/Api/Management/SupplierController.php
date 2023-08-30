<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use App\Services\LoggerService;

class SupplierController extends Controller
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
            $suppliers = Supplier::orderBy('nama', 'asc')->get();

            if ($suppliers->isEmpty()) {
                return response()->json([
                    'message' => $this->messageMissing,
                    'code' => 401
                ], 401);
            }

            return response()->json([
                'data' => $suppliers,
                'message' => $this->messageAll,
                'code' => 200
            ], 200);
        } catch (QueryException $ex) {
            return response()->json([
                'message' => $this->messageFail,
                'errMsg' => $ex->getMessage(),
                'code' => 500
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);

            $supplier->history = $this->formatLogs($supplier->logs);
            unset($supplier->logs);

            return response()->json([
                'data' => $supplier,
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
                'nama' => 'required',
                'kode' => 'required|unique:suppliers',
                'keterangan' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false,
                ], 400);
            }

            $data = Supplier::create($request->all());

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
            $supplier = Supplier::find($id);

            if (!$supplier) {

                return response()->json([
                    'message' => $this->messageMissing,
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'kode' => [
                    Rule::unique('suppliers')->ignore($supplier->id)
                ]
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false
                ], 400);
            }

            $dataToUpdate = [
                'nama' => $request->filled('nama') ? $request->nama : $supplier->nama,
                'kode' => $request->filled('kode') ? $request->kode : $supplier->kode,
                'keterangan' => $request->filled('keterangan') ? $request->keterangan : $supplier->keterangan
            ];
            $oldData = $supplier->toArray();

            $supplier->update($dataToUpdate);

            LoggerService::logAction($this->userData, $supplier, 'update', $oldData, $supplier->toArray());

            DB::commit();

            return response()->json([
                'data' => $supplier,
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

    public function destroy(Supplier $supplier)
    {
        //
    }
}
