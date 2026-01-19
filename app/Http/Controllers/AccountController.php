<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamRole;
use App\Services\TeamInfoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    public $ds_coin = [
        118631,
        129731,
        129631,
        129031,
        181131,
        126531,
        126431,
        118231,
        181031,
        106931,
        123031,
        180931,
        118131,
        180831,
        106731,
        118531,
        180731,
        180331,
        180631,
        180131,
        106831,
        180231,
        180531,
        180431,
        107031,
        106131,
        107131, // 克总
        127131,
        126931,
        126631,
        126231,
        125631,
        125531,
        125331,
        120031,
        125031,
        124831,
        124531,
        124631,
        124031,
        123831,
        123631,
        123331,
        122931,
        122531,
        121531,
        121031,
        121131,
        120731,
        120931,
        119931,
        117731,
        118031,
        117231,
        117031,
        115531,
        111831,
        115031,
        114431,
        113931,
        113431,
        113131,
        112531,
        112431,
        112031,
        111931,
        111531,
        109931,
        109731,
        111131,
        110431,
        110631,
        110331,
        110031,
        109131,
        108831,
        108731,
        108431,
        108631,
        108131,
        108331,
        107931,
        107731,
        107831,
        107531
    ];
    
    public function list(Request $request)
    {
        if ($request->method() == 'POST') {
            $uid = Auth::guard('user')->id();
            $data = Account::where('uid', $uid)->where('status', 1)->get();
            $data = $data ? $data->toArray() : [];
            return json_encode(['code' => 0, 'data' => $data]);
        }
        return view('user.account.list');
    }

    public function add(Request $request)
    {
        $data = $request->all();
        $data['method'] = $request->method();
        return $this->post($data);
    }

    public function edit(Request $request)
    {
        $data = $request->all();
        $data['method'] = $request->method();
        return $this->post($data);
    }

    private function post($params = [])
    {
        $uid = Auth::guard('user')->id();
        $id = isset($params['id']) ? (int)$params['id'] : 0;
        if ($params['method'] == 'POST') {
            // 验证请求参数
            $rules = [
                'boss'       => 'required|integer|min:1|max:5',
                'score'      => 'required|integer|min:1|max:99999',
                'difficulty' => 'integer|min:1|max:99',
                'open'       => 'required|integer|min:0|max:1',
                'remark'     => 'max:255',
                'teams'      => 'required|array|size:5',
            ];

            $messages = [
                'boss.required'      => '缺少:attribute信息',
                'boss.integer'       => ':attribute参数错误',
                'score.required'     => '缺少:attribute信息',
                'score.integer'      => ':attribute参数错误',
                'score.min'          => '最低:attribute为1',
                'difficulty.integer' => ':attribute参数错误',
                'difficulty.min'     => '最低:attribute为1',
                'difficulty.max'     => '最大:attribute为99',
                'open.required'      => '参所错误',
                'open.min'           => '参所错误',
                'open.max'           => '参所错误',
                'remark.max:255'     => '备注信息长度最大为255',
                'teams.required'     => '缺少阵容信息',
                'teams.array'        => '阵容参数错误',
                'teams.size'         => '阵容参数错误',
            ];

            $paramsName = [
                'boss'       => 'Boss',
                'score'      => '伤害',
                'difficulty' => '难度',
                'open'       => '是否公开',
                'remark'     => '备注',
                'teams'      => '角色',
            ];

            // 创建验证器
            // $validator = Validator::make($params, $rules, $messages, $paramsName);

            // if ($validator->fails()) {
            //     $errorMessages = '';
            //     $errorArr      = $validator->errors()->toArray();
            //     foreach ($errorArr as $key => $value) {
            //         $errorMessages = $value[0];
            //     }
            //     show_json(0, $errorMessages);
            // }


            // 昵称
            $nickname = htmlspecialchars($params['nickname']) ?? '';
            // 角色
            $role_ids = $params['role_ids'];
            // 狐狸
            $fox = 0;


            // 验证角色参数
            // $count = Role::where(function ($query) use ($role_ids) {
            //     $query->whereIn('role_id_3', $role_ids)
            //         ->orWhereIn('role_id_6', $role_ids);
            // })->count();
            // if ($count != 5) {
            //     show_json(0, '角色参数错误');
            // }

            // 判断狐狸 狐狸ID 101001
            if (in_array(101001, $role_ids)) {
                $fox = 1;
            }

            // $this->addUseTimes($role_ids);

            if ($id) {
                $updateAccount = [
                    'nickname'   => $nickname,
                    'roles'      => implode(',', $role_ids),
                    'fox_switch' => $fox,
                    'fox_level'  => 0,
                    'updated_at' => Carbon::now(),
                ];
                Account::where('id', $id)->update($updateAccount);
                $cacheKey = 'group' . $uid . 'accountId' . $id;
                Cache::put($cacheKey, []); // 清空box缓存
            } else {
               $insertAccount = [
                    'uid'        => $uid,
                    'nickname'   => $nickname,
                    'roles'      => implode(',', $role_ids),
                    'fox_switch' => $fox,
                    'fox_level'  => 0,
                    'status'     => 1,
                    'created_at' => Carbon::now()
                ];
                $ok = DB::table('accounts')->insertGetId($insertAccount);
                if (!$ok) {
                    show_json(0, 'Error');
                }
            }
            show_json(1); 
        }

        $data = Account::find($id);
        $data = $data ? $data->toArray() : [];
        // dd($data);
        return view('user.account.post', ['id' => $id, 'data' => $data]);
    }

    public function delete(Request $request)
    {
        $id = (int)$request->input('id');
        $uid = Auth::guard('user')->id();
        DB::table('accounts')->where(['uid' => $uid, 'id' => $id])->update(['status' => 0]);
        show_json(1);
    }

    public function fox(Request $request)
    {
        $id = (int)$request->input('id');
        $uid = Auth::guard('user')->id();
        $account = Account::where(['id' => $id, 'uid' => $uid])->first();
        $roles = explode(',', $account->roles);
        if (in_array(101001, $roles)) {
            unset($roles[array_search(101001, $roles)]);
        }
        if ($account->fox_switch) {
            $account->fox_switch = 0;
        } else {
            $account->fox_switch = 1;
            array_push($roles, 101001);
        }
        $account->roles = implode(',', $roles);
        $account->save();
        show_json(1);
    }

    public function getCanUseRoles(Request $request)
    {
        $id        = (int)$request->input('id');
        $uid       = Auth::guard('user')->id();
        $usedRoles = [];
        $roles     = [];

        // 角色
        $allRoles = DB::table('roles')
                        ->select(DB::raw(' CASE WHEN `is_6` = 1 THEN `role_id_6` ELSE `role_id_3` END as `image_id`, `id`, `position`, `nickname`, `name` '))
                        ->where('status', 1)
                        ->orderBy('use_times', 'DESC')
                        ->orderBy('id')
                        ->get();
        $allRoles = $allRoles ? $allRoles->toArray() : [];

        if ($id) {
            $usedRoles = Account::where('id', $id)->value('roles');
            if ($usedRoles) {
                $usedRoles = explode(',', $usedRoles);
            }
        }

        foreach ($allRoles as $key => $value) {
            $switch = in_array($value->id, $usedRoles) ? 1 : 0;
            $roles[$value->position][] = ['role_id' => $value->id, 'image_id' => $value->image_id, 'switch' => $switch, 'name' => $value->name];
        }
        show_json(1, $roles);
    }

    public function team(Request $request)
    {
        $id = (int)$request->input('id');
        return view('user.account.team', ['id' => $id, 'select_is_show' => true]);
    }

    public function group(Request $request)
    {
        $id = (int)$request->input('id');
        return view('user.account.group', ['id' => $id, 'select_is_show' => true]);
    }

    public function coin(Request $request)
    {
        $id = (int)$request->input('id');
        $uid = Auth::guard('user')->id();
        $coinRoles = $this->ds_coin;
        if ($request->method() == 'POST') {
            $roles = $request->input('roles');
            $account = Account::where(['id' => $id, 'uid' => $uid])->first();
            if ($account) {
                $account->coin = $roles ? implode(',', $roles) : '';
                $account->save();
                show_json(1);
            }
            show_json(0, 'Error');
        }
        $selectRoles = Account::where(['id' => $id, 'uid' => $uid])->value('coin');
        $selectRoles = $selectRoles ? explode(',', $selectRoles) : [];
        $rolesMap = [];
        foreach ($coinRoles as $key => $roleId) {
            $select = in_array($roleId, $selectRoles) ? 1 : 0;
            $rolesMap[] = ['roleId' => $roleId, 'select' => $select];
        }
        return view('user.account.coin', ['roles' => $rolesMap, 'id' => $id, 'ids' => json_encode($selectRoles)]);
    }

    public function statistics(Request $request)
    {
        $uid       = Auth::guard('user')->id();
        $accountId = (int)$request->input('id');
        $year      = (int)$request->input('year');

        $where = ['uid' => $uid, 'account_id' => $accountId, 'year' => $year];

        if ($request->method() == 'POST') {

            $validator = Validator::make($request->all(), [
                'data'               => ['required', 'array', 'min:1'],
                'data.*.id'          => ['required', 'integer', 'digits:4'],
                'data.*.count'       => ['required', 'integer', 'min:0', 'max:200'],
            ], [
                // 中文错误消息（可选）
                'data.*.id.digits'   => 'id 必须是4位数字',
                'data.*.count.max'   => 'count 不能超过200',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => '验证失败',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $data  = $validator->validated()['data'];
            
            // 查询数据是否存在 存在就修改  不存在就写入
            $ok = DB::table('card_statistics')->where($where)->first();

            if ($ok) { // 修改
                DB::table('card_statistics')->where($where)->update(['data' => json_encode($data), 'updated_at' => Carbon::now()]);
            } else { // 写入
                $insert               = $where;
                $insert['data']       = json_encode($data);
                $insert['created_at'] = Carbon::now();
                DB::table('card_statistics')->insert($insert);
            }

            return response()->json([
                'code'    => 200,
                'message' => '操作成功',
                'data'    => $data,
            ]);
        }


        $imageUrl = asset('images');

        $roles_2025 = Cache::get('roles_2025');
        // $roles_2025 = 0;
        if (empty($roles_2025)) {
            $roles_2025 = Role::select(['id', 'role_id_3', 'probability', 'name', DB::raw('null as count')])->whereYear('up_time', 2025)->where('obtain', 1)->orderBy('up_time')->get()
            ->map(function ($item) {
                $item->img = asset('images') . "/{$item->role_id_3}.webp";
                return $item;
            })->toArray();
            Cache::put('roles_2025', $roles_2025, 7200);
        }

        $roles_2026 = Cache::get('roles_2026');
        // $roles_2026 = 0;
        if (empty($roles_2026)) {
            $roles_2026 = Role::select(['id', 'role_id_3', 'probability', 'name', DB::raw('null as count')])->whereYear('up_time', 2026)->where('obtain', 1)->orderBy('up_time')->get()
            ->map(function ($item) {
                $item->img = asset('images') . "/{$item->role_id_3}.webp";
                return $item;
            })->toArray();
            Cache::put('roles_2026', $roles_2026, 7200);
        }

        $where['year'] = 2025;
        $user_roles_2025 = DB::table('card_statistics')->where($where)->value('data');
        $user_roles_2025 = $user_roles_2025 ? array_column(json_decode($user_roles_2025), null, 'id') : [];

        foreach ($roles_2025 as &$roleInfo) {
            if (isset($user_roles_2025[$roleInfo['id']])) {
                $roleInfo['count'] = $user_roles_2025[$roleInfo['id']]->count;
            }
        }
        unset($roleInfo);

        $where['year'] = 2026;
        $user_roles_2026 = DB::table('card_statistics')->where($where)->value('data');
        $user_roles_2026 = $user_roles_2026 ? array_column(json_decode($user_roles_2026), null, 'id') : [];

        foreach ($roles_2026 as &$roleInfo) {
            if (isset($user_roles_2026[$roleInfo['id']])) {
                $roleInfo['count'] = $user_roles_2026[$roleInfo['id']]->count;
            }
        }
        unset($roleInfo);

        $cardData = [
            2025 => $roles_2025,
            2026 => $roles_2026,
        ];

        return view('user.account.statistics', ['accountId' => $accountId, 'cardData' => $cardData]);
    }

    public function getTeamGroups(Request $request)
    {
        $uid       = Auth::guard('user')->id();
        $id        = (int)$request->input('id');
        $type      = (int)$request->input('type') ?? 1;
        $atkType   = (int)$request->input('atk') ?? 0;

        $row1      = in_array((int)$request->input('row1'), [1,2,3,4,5]) ? (int)$request->input('row1') : 0;
        $row2      = in_array((int)$request->input('row2'), [1,2,3,4,5]) ? (int)$request->input('row2') : 0;
        $row3      = in_array((int)$request->input('row3'), [1,2,3,4,5]) ? (int)$request->input('row3') : 0;
        $lockedIds = is_array($request->input('lockedIds')) ? $request->input('lockedIds') : [];
        $hiddenIds = is_array($request->input('hiddenIds')) ? $request->input('hiddenIds') : [];

        $teamsRes  = TeamInfoService::getTeamGroups($uid, [$row1, $row2, $row3], $id, $type, $atkType, $lockedIds, $hiddenIds);
        return json_encode(['status' => 1, 'result' => $teamsRes]);
    }

    public function firstDay(Request $request)
    {
        $uid     = Auth::guard('user')->id();
        $id      = (int)$request->input('id');
        $type    = (int)$request->input('type') ?? 1;
        $stage   = (int)$request->input('stage') ?? 1;
        $atkType = (int)$request->input('atk') ?? 0;

        $row1       = in_array((int)$request->input('row1'), [1,2,3,4,5]) ? (int)$request->input('row1') : 0;
        $row2       = in_array((int)$request->input('row2'), [1,2,3,4,5]) ? (int)$request->input('row2') : 0;
        $row3       = in_array((int)$request->input('row3'), [1,2,3,4,5]) ? (int)$request->input('row3') : 0;
        $row4       = in_array((int)$request->input('row4'), [1,2,3,4,5]) ? (int)$request->input('row4') : 0;
        $row5       = in_array((int)$request->input('row5'), [1,2,3,4,5]) ? (int)$request->input('row5') : 0;
        $row6       = in_array((int)$request->input('row6'), [1,2,3,4,5]) ? (int)$request->input('row6') : 0;
        $lockedIdsB = is_array($request->input('lockedIdsB')) ? $request->input('lockedIdsB') : [];
        $lockedIdsD = is_array($request->input('lockedIdsD')) ? $request->input('lockedIdsD') : [];
        $hiddenIds  = is_array($request->input('hiddenIds')) ? $request->input('hiddenIds') : [];

        $teamsRes   = TeamInfoService::firstDayHomework($uid, [$row1, $row2, $row3], [$row4, $row5, $row6], $id, $type, $stage, $atkType, $lockedIdsB, $lockedIdsD, $hiddenIds);
        return json_encode(['status' => 1, 'result' => $teamsRes]);
    }
}
