<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Models\Location;

class LocationController extends Controller
{
    private $messageAll = 'Success to Fetch All Datas';
    private $messageSuccess = 'Success to Fetch Data';
    private $messageFail = 'Something went wrong';
    private $messageMissing = 'Data not found in record';

    public function index()
    {
        try {
            $locations = Location::with('area')->orderBy('nama', 'asc')->get();

            if ($locations->isEmpty()) {

                return response()->json([
                    'message' => $this->messageMissing,
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            return response()->json([
                'data' => $locations,
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

    public function indexArea($id)
    {
        try {
            $locations = Location::where('id_area', $id)->with('area')->orderBy('nama', 'asc')->get();

            if ($locations->isEmpty()) {

                return response()->json([
                    'message' => $this->messageMissing,
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            return response()->json([
                'data' => $locations,
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
            $location = Location::with('area')->find($id);

            if (!$location) {

                return response()->json([
                    'message' => $this->messageMissing,
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            return response()->json([
                'data' => $location,
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
