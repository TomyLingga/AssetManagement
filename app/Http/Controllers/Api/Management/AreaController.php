<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AreaController extends Controller
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
            $areas = Area::with('locations')->orderBy('nama', 'asc')->get();

            if ($areas->isEmpty()) {

                return response()->json([
                    'message' => $this->messageMissing,
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            return response()->json([
                'data' => $areas,
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
            $area = Area::with(['locations' => function ($query) {
                $query->orderBy('nama', 'asc');
            }])->find($id);

            if (!$area) {

                return response()->json([
                    'message' => $this->messageMissing,
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            return response()->json([
                'data' => $area,
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

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'nama_area' => 'required',
                'locations.*.nama' => 'required',
                'locations.*.keterangan' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false
                ], 400);
            }

            $data = Area::create([
                'nama' => $request->nama_area
            ]);

            $locations = collect($request->locations)->map(function ($location) use ($data) {
                return [
                    'id_area' => $data->id,
                    'nama' => $location['nama'],
                    'keterangan' => $location['keterangan'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->all();

            Location::insert($locations);

            $data->load('locations');

            DB::commit();

            return response()->json([
                'data' => $data,
                'message' => $this->messageCreate,
                'code' => 200,
                'success' => true,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            DB::rollback();
            return response()->json([
                'message' => 'Category not found.',
                'err' => $ex->getTrace()[0],
                'errMsg' => $ex->getMessage(),
                'code' => 401,
                'success' => false,
            ], 401);
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
            $area = Area::with(['locations' => function ($query) {
                $query->orderBy('nama', 'asc');
            }])->find($id);

            if (!$area) {

                return response()->json([
                    'message' => $this->messageMissing,
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'nama_area' => 'required',
                'locations.*.id' => 'nullable', // Optional ID
                'locations.*.nama' => 'required',
                'locations.*.keterangan' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false
                ], 400);
            }

            $area->update([
                'nama' => $request->nama_area
            ]);

            $updatedLocationIds = [];

            foreach ($request->locations as $locationData) {
                if (isset($locationData['id'])) {
                    $location = Location::where('id_area', $area->id)->find($locationData['id']);

                    if ($location) {
                        $location->update([
                            'nama' => $locationData['nama'],
                            'keterangan' => $locationData['keterangan'],
                        ]);

                        $updatedLocationIds[] = $location->id;
                    }
                } else {
                    $location = Location::create([
                        'id_area' => $area->id,
                        'nama' => $locationData['nama'],
                        'keterangan' => $locationData['keterangan'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $updatedLocationIds[] = $location->id;
                }
            }

            $deletedLocationIds = $area->locations()->whereNotIn('id', $updatedLocationIds)->pluck('id');
            Location::whereIn('id', $deletedLocationIds)->delete();

            $area->load('locations');

            DB::commit();

            return response()->json([
                'data' => $area,
                'message' => $this->messageUpdate,
                'code' => 200,
                'success' => true,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            DB::rollback();
            return response()->json([
                'message' => 'Category not found.',
                'err' => $ex->getTrace()[0],
                'errMsg' => $ex->getMessage(),
                'code' => 401,
                'success' => false,
            ], 401);
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

    // public function destroy(Area $area)
    // {
    //     //
    // }
}
