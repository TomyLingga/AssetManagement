<?php

namespace App\Http\Controllers\Api\Grup;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\SubGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use App\Rules\UniqueValues;
use Illuminate\Validation\Rule;
use App\Services\LoggerService;

class GroupController extends Controller
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
            $groups = Group::with('subGroups')->orderBy('kode_aktiva_tetap', 'asc')->get();

            if ($groups->isEmpty()) {
                return response()->json([
                    'message' => $this->messageMissing,
                    'code' => 401
                ], 401);
            }

            return response()->json([
                'data' => $groups,
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

            $group = Group::with(['subGroups' => function ($query) {
                $query->orderBy('kode_aktiva_tetap', 'asc');
            }])->find($id);

            if (!$group) {

                return response()->json([
                    'message' => $this->messageMissing,
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            $group->history = $this->formatLogsForMultiple($group->logs);
            unset($group->logs);

            return response()->json([
                'data' => $group,
                'message' => $this->messageSuccess,
                'code' => 200
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
                'nama_grup' => 'required',
                'kode_aktiva_tetap' => 'required|unique:groups,kode_aktiva_tetap',
                'kode_akm_penyusutan' => 'required|unique:groups,kode_akm_penyusutan',
                'format' => 'required|unique:groups',
                'subGroups.*.nama' => 'required',
                'subGroups.*.kode_aktiva_tetap' => [
                    'required',
                    new UniqueValues($request->input('subGroups.*.kode_aktiva_tetap')),
                ],
                'subGroups.*.kode_akm_penyusutan' => [
                    'required',
                    new UniqueValues($request->input('subGroups.*.kode_akm_penyusutan')),
                ],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false
                ], 400);
            }

            $data = Group::create([
                'nama' => $request->nama_grup,
                'kode_aktiva_tetap' => $request->kode_aktiva_tetap,
                'kode_akm_penyusutan' => $request->kode_akm_penyusutan,
                'format' => $request->format
            ]);

            $subGroups = $request->input('subGroups', []);

            $subGroupData = collect($subGroups)->map(function ($subGroup) use ($data) {
                return [
                    'id_grup' => $data->id,
                    'nama' => $subGroup['nama'],
                    'kode_aktiva_tetap' => $subGroup['kode_aktiva_tetap'],
                    'kode_akm_penyusutan' => $subGroup['kode_akm_penyusutan'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->all();

            SubGroup::insert($subGroupData);

            $data->load('subGroups');

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
            $group = Group::with(['subGroups' => function ($query) {
                $query->orderBy('kode_aktiva_tetap', 'asc');
            }])->findOrFail($id);

            if (!$group) {

                return response()->json([
                    'message' => $this->messageMissing,
                    'success' => true,
                    'code' => 401
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'nama_grup' => 'required',
                'kode_aktiva_tetap' => [
                    Rule::unique('groups')->ignore($group->id)->where(function ($query) use ($request) {
                        return $query->where('kode_aktiva_tetap', $request->kode_aktiva_tetap);
                    })
                ],
                'kode_akm_penyusutan' => [
                    Rule::unique('groups')->ignore($group->id)->where(function ($query) use ($request) {
                        return $query->where('kode_akm_penyusutan', $request->kode_akm_penyusutan);
                    })
                ],
                'format' => [
                    Rule::unique('groups')->ignore($group->id)->where(function ($query) use ($request) {
                        return $query->where('format', $request->format);
                    })
                ],
                'subGroups.*.id' => 'nullable',
                'subGroups.*.nama' => 'required',
                'subGroups.*.kode_aktiva_tetap' => [
                    'required',
                    new UniqueValues($request->input('subGroups.*.kode_aktiva_tetap')),
                ],
                'subGroups.*.kode_akm_penyusutan' => [
                    'required',
                    new UniqueValues($request->input('subGroups.*.kode_akm_penyusutan')),
                ],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'code' => 400,
                    'success' => false
                ], 400);
            }
            $oldData = $group->toArray();

            $group->update([
                'nama' => $request->nama_grup,
                'kode_aktiva_tetap' => $request->kode_aktiva_tetap,
                'kode_akm_penyusutan' => $request->kode_akm_penyusutan,
                'format' => $request->format
            ]);

            $updatedSubGroupIds = [];

            foreach ($request->subGroups as $subGroupData) {
                if (isset($subGroupData['id'])) {
                    $subGroup = SubGroup::where('id_grup', $group->id)->find($subGroupData['id']);

                    if ($subGroup) {
                        $subGroup->update([
                            'nama' => $subGroupData['nama'],
                            'kode_aktiva_tetap' => $subGroupData['kode_aktiva_tetap'],
                            'kode_akm_penyusutan' => $subGroupData['kode_akm_penyusutan'],
                        ]);

                        $updatedSubGroupIds[] = $subGroup->id;
                    }
                } else {
                    $subGroup = SubGroup::create([
                        'id_grup' => $group->id,
                        'nama' => $subGroupData['nama'],
                        'kode_aktiva_tetap' => $subGroupData['kode_aktiva_tetap'],
                        'kode_akm_penyusutan' => $subGroupData['kode_akm_penyusutan'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $updatedSubGroupIds[] = $subGroup->id;
                }
            }

            $deletedSubGroupIds = $group->subGroups()->whereNotIn('id', $updatedSubGroupIds)->pluck('id');
            SubGroup::whereIn('id', $deletedSubGroupIds)->delete();

            $group->load('subGroups');

            LoggerService::logAction($this->userData, $group, 'update', $oldData, $group->toArray());

            DB::commit();

            return response()->json([
                'data' => $group,
                'message' => $this->messageUpdate,
                'code' => 200,
                'success' => true,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            DB::rollback();
            return response()->json([
                'message' => 'Group not found.',
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

    public function kodeExist(Request $request)
    {
        $field = $request->has('kode_aktiva_tetap') ? 'kode_aktiva_tetap' : 'kode_akm_penyusutan';
        $group = Group::where($field, $request->input($field))->first();

        if (!$group) {
            return response()->json([
                'message' => 'Code can be used',
                'success' => true,
                'code' => 200
            ], 200);
        } else {
            return response()->json([
                'message' => 'Code is already taken',
                'success' => true,
                'code' => 500
            ], 500);
        }
    }
}
