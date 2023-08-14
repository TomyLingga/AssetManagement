<?php

namespace App\Http\Controllers\Api\Grup;

use App\Http\Controllers\Controller;
use App\Models\SubGroup;

class SubGroupController extends Controller
{
    private $messageAll = 'Success to Fetch All Datas';
    private $messageSuccess = 'Success to Fetch Data';
    private $messageFail = 'Something went wrong';
    private $messageMissing = 'Data not found in record';

    public function index()
    {
        try {
            $subGroups = SubGroup::with('group')
                            ->orderBy('id_grup', 'asc')
                            ->orderBy('kode_aktiva_tetap', 'asc')
                            ->get();

            if ($subGroups->isEmpty()) {

                return response()->json([
                    'message' => $this->messageMissing,
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            return response()->json([
                'data' => $subGroups,
                'message' => $this->messageAll,
                'success' => true,
                'code' => 200
            ], 200);
        } catch (\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'message' => $this->messageFail,
                'err' => $ex->getTrace()[0],
                'errMsg' => $ex->getMessage(),
                'success' => false,
                'code' => 500
            ], 500);
        }
    }

    public function indexGrup($id)
    {
        try {
            $subGroups = SubGroup::where('id_grup', $id)->with('group')->orderBy('nama', 'asc')->get();

            if ($subGroups->isEmpty()) {

                return response()->json([
                    'message' => $this->messageMissing,
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            return response()->json([
                'data' => $subGroups,
                'message' => $this->messageAll,
                'success' => true,
                'code' => 200
            ], 200);
        } catch (\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'message' => $this->messageFail,
                'err' => $ex->getTrace()[0],
                'errMsg' => $ex->getMessage(),
                'success' => false,
                'code' => 500
            ], 500);
        }
    }


    public function show($id)
    {
        try {
            $subGroup = SubGroup::with('group')->find($id);

            if (!$subGroup) {

                return response()->json([
                    'message' => $this->messageMissing,
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            return response()->json([
                'data' => $subGroup,
                'message' => $this->messageSuccess,
                'success' => true,
                'code' => 200,
            ], 200);
        } catch (\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'message' => $this->messageFail,
                'err' => $ex->getTrace()[0],
                'errMsg' => $ex->getMessage(),
                'success' => false,
                'code' => 500,
            ], 500);
        }
    }
}
