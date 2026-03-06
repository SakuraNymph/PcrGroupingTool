<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamRole;
use App\Models\UserTeam;
use App\Services\TeamInfoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class TeamInfoController extends Controller
{
    public function team()
    {
        return view('team.index');
    }

    public function teamList()
    {
        return view('team.list');
    }

    public function getUserTeams(Request $request)
    {
        $uid = Auth::guard('user')->id();
        if (!$uid) {
            $uid = session('id');
        }
        $boss = (int)$request->input('boss');
        if (!in_array($boss, [1,2,3,4,5])) {
            $boss = 1;
        }
        $data = TeamInfoService::getTeams($boss, 0, $uid);
        return json_encode(['status' => 1, 'result' => $data]);
    }

    public function getPublicTeams(Request $request)
    {
        $uid = Auth::guard('user')->id();
        if (!$uid) {
            $uid = session('id');
        }
        $boss = (int)$request->input('boss');
        if (!in_array($boss, [1,2,3,4,5])) {
            $boss = 1;
        }
        $data = TeamInfoService::getTeams($boss, 1, $uid);
        return json_encode(['status' => 1, 'result' => $data]);
    }

    public function addOtherTeam(Request $request)
    {
        $uid = Auth::guard('user')->id();
        if (!$uid) {
            $uid = session('id');
        }
        $teamId = (int)$request->input('id');
        $roleId = (int)$request->input('role_id');
        $ok     = TeamInfoService::addOtherTeam($uid, $teamId, $roleId);
        if ($ok) {
            $cacheKey = 'group' . $uid;
            Cache::put($cacheKey, []);
        }
        show_json($ok);
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
        if ($params['method'] == 'POST') {
            // 验证请求参数
            $rules = [
                'boss'       => 'required|integer|min:1|max:5',
                'score'      => 'required|integer|min:1|max:99999',
                // 'difficulty' => 'integer|min:1|max:99',
                'auto'       => 'required|integer|min:0|max:1',
                'remark'     => 'max:255',
                'teams'      => 'required|array|size:5',
            ];

            $messages = [
                'boss.required'      => '缺少:attribute信息',
                'boss.integer'       => ':attribute参数错误',
                'score.required'     => '缺少:attribute信息',
                'score.integer'      => ':attribute参数错误',
                'score.min'          => '最低:attribute为1',
                // 'difficulty.integer' => ':attribute参数错误',
                // 'difficulty.min'     => '最低:attribute为1',
                // 'difficulty.max'     => '最大:attribute为99',
                'auto.required'      => '参所错误',
                'auto.min'           => '参所错误',
                'auto.max'           => '参所错误',
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
            $validator = Validator::make($params, $rules, $messages, $paramsName);

            if ($validator->fails()) {
                $errorMessages = '';
                $errorArr      = $validator->errors()->toArray();
                foreach ($errorArr as $key => $value) {
                    $errorMessages = $value[0];
                }
                show_json(0, $errorMessages);
            }


            // boss
            $boss   = (int)$params['boss'];
            // 备注
            $remark = htmlspecialchars($params['remark']) ?? '';
            // 伤害
            $damage  = (int)$params['score'];
            // 是否自动
            $auto   = (int)$params['auto'];
            // 是否公开
            $open   = 0;
            // 阵容
            $teams  = $params['teams'];

            $rolesMap = DB::table('roles')->select(DB::raw('CASE WHEN is_6 = 1 THEN role_id_6 ELSE role_id_3 END as image, atk_type, id, search_area_width, nickname'))->get()->keyBy('id');

            // 按 search_area_width 降序排序 A
            usort($teams, function($itemX, $itemY) use ($rolesMap) {
                $roleIdX = $itemX['role_id'];
                $roleIdY = $itemY['role_id'];
                $widthX = $rolesMap[$roleIdX]->search_area_width ?? 0;  // ID 不存在用 0
                $widthY = $rolesMap[$roleIdY]->search_area_width ?? 0;
                return $widthY <=> $widthX;  // 降序：y 的宽度 > x 的宽度，返回负数（y 先）
                // 或者用: return $widthX - $widthY;  // 简单减法，也实现降序（但数字大时注意溢出）
            });

            $uid = Auth::guard('user')->id();
            if (!$uid) {
                $uid = session('id');
            }
            if (empty($uid)) {
                show_json(0, '系统错误请刷新');  
            }

            $role_ids = array_column($teams, 'role_id');
            // 验证角色参数
            $count = Role::whereIn('id', $role_ids)->count();
            if ($count != 5) {
                show_json(0, '角色参数错误');
            }

            $this->addUseTimes($role_ids);

            $atk_value = 0;
            foreach ($teams as $key => $value) {
                $atk_type = (int)DB::table('roles')->where('id', $value['role_id'])->value('atk_type');
                $atk_value += $atk_type;
            }

            $insert_teams = [
                'stage'      => 5,
                'uid'        => $uid,
                'boss'       => $boss,
                'damage'     => $damage,
                'team'       => json_encode($teams),
                'open'       => $open,
                'status'     => 1,
                'auto'       => $auto,
                'atk_value'  => $atk_value,
                'remark'     => $remark,
                'created_at' => timeToStr()
            ];
            $team_id = DB::table('teams')->insertGetId($insert_teams);
            if (!$team_id) {
                show_json(0, 'Error');
            }

            $cacheKey = 'group' . $uid;
            Cache::put($cacheKey, []);
            show_json(1);
        }

        $id           = 0;
        $bossId       = (int)$params['boss'] ?? 1;
        $data         = [];
        $role_ids_use = [];
        $ids_not_in   = [];
        $ids_json     = '';

        if (!in_array($bossId, [1,2,3,4,5])) {
            $bossId = 1;
        }


        if (!empty($params['id'])) {
            $id             = (int)$params['id'];
            $data           = DB::table('rank_infos')->where('id', $id)->first();
            $data->role_ids = explode(',', $data->role_ids);
            $role_ids_use   = $data->role_ids;
            $ids_json = json_encode($role_ids_use);
            $author_id     = (int)$data->author_id;
        }

        if (!empty($params['id'])) {
            $ids_not_in = array_diff($ids_not_in, $role_ids_use);
        }

        $ids_not_in = Role::where('status', 1)->where(function ($query) use ($ids_not_in) {
            $query->whereIn('role_id_3', $ids_not_in)->orWhereIn('role_id_6', $ids_not_in);
        })->get('role_id')->toArray();
        $ids_not_in = array_column($ids_not_in, 'role_id');

        // 角色
        $roles = DB::table('roles')->select(DB::raw(' CASE WHEN `is_6` = 1 THEN `role_id_6` ELSE `role_id_3` END as `role_id`, `position` '))->where('status', 1)->where(function ($query) use ($ids_not_in) {
            $query->whereNotIn('role_id', $ids_not_in);
        })->orderBy('use_times', 'DESC')->orderBy('role_id')->get();
        $rolesMap = [];
        foreach ($roles as $key => $value) {
            $rolesMap[$value->position][] = $value;
        }

        return view('team.post', ['data' => $data, 'id' => $id, 'bossId' => $bossId, 'rolesMap' => $rolesMap, 'ids_not_in' => $ids_not_in, 'ids_json' => $ids_json]);
    }

    public function getTeamGroups(Request $request)
    {
        $uid = Auth::guard('user')->id();
        if (!$uid) {
            $uid = session('id');
        }
        $row1      = in_array((int)$request->input('row1'), [1,2,3,4,5]) ? (int)$request->input('row1') : 0;
        $row2      = in_array((int)$request->input('row2'), [1,2,3,4,5]) ? (int)$request->input('row2') : 0;
        $row3      = in_array((int)$request->input('row3'), [1,2,3,4,5]) ? (int)$request->input('row3') : 0;
        $atkType   = (int)$request->input('atk') ?? 0;
        $lockedIds = is_array($request->input('lockedIds')) ? $request->input('lockedIds') : [];
        $hiddenIds = is_array($request->input('hiddenIds')) ? $request->input('hiddenIds') : [];

        $teamsRes  = TeamInfoService::getTeamGroups($uid, [$row1, $row2, $row3], 0, 0, $atkType, $lockedIds, $hiddenIds);
        return json_encode(['status' => 1, 'result' => $teamsRes]);
    }

    public function getAllRoles(Request $request)
    {
        $teamId = (int)$request->input('id');
        $roles = Cache::get('roles');
        if (empty($roles)) {
            // 角色
            $roles = DB::table('roles')->select(DB::raw(' CASE WHEN `is_6` = 1 THEN `role_id_6` ELSE `role_id_3` END as `image_id`, `id`, `position`, `name` '))->where('status', 1)->orderBy('use_times', 'DESC')->orderBy('id')->get();
            $roles = $roles ? $roles->toArray() : [];
            Cache::put('roles', $roles, 7200);
        }

        $teamRoles = [];
        if ($teamId) {
            $teamRoles = TeamRole::where('team_id', $teamId)->pluck('role_id');
            $teamRoles = $teamRoles ? $teamRoles->toArray() : [];
        }

        $rolesMap = [];
        foreach ($roles as $key => $value) {
            $switch = 0;
            if (in_array($value->id, $teamRoles)) {
                $switch = 1;
            }
            $rolesMap[$value->position][] = ['role_id' => $value->id, 'image_id' => $value->image_id, 'switch' => $switch, 'name' => $value->name];
        }
        return json_encode(['status' => 1, 'result' => $rolesMap]);
    }

    public function resTeam()
    {
        return view('user.account.team', ['id' => 0, 'select_is_show' => false]);
    }

    public function deleteAll()
    {
        $uid = Auth::guard('user')->id();
        if (!$uid) {
            $uid = session('id');
        }
        // DB::beginTransaction();
        $ok = DB::table('teams')
                ->where('uid', $uid)
                ->whereYear('created_at', Carbon::now()->year)
                ->whereMonth('created_at', Carbon::now()->month)
                ->update(['status' => 0]);
        if ($ok) {
            // DB::commit();
            show_json(1);
        }
        // DB::rollBack();
        show_json(0);
    }

    public function delete(Request $request)
    {
        $id    = (int)$request->input('id');
        $type  = (int)$request->input('type');
        $uid = Auth::guard('user')->id();
        if (!$uid) {
            $uid = session('id');
        }
        $where = $type ? ['otid' => $id] : ['id' => $id];
        $where['uid'] = $uid;

        $teamInfo = Team::where($where)->first();
        if (!$teamInfo) {
            show_json(1);
        }
        $teamInfo->status = 0;
        $teamInfo->save();
        show_json(1);
    }

    public function bStageTable()
    {
        return view('b_stage_table');
    }

    public function bStageTableData(Request $request)
    {
        $uid = Auth::guard('user')->id();
        if (!$uid) {
            $uid = session('id');
        }

        $method = $request->method();
        if ($method == 'POST') {
            $validated = $request->validate([
                'content' => 'required|array',
                'content.*' => 'array',
                'content.*.*' => 'nullable|string|max:32',
            ]);

            $content = $validated['content'];
            $content = $content ? $content : array_fill(0, 6, array_fill(0, 5, null));

            $ok = DB::table('b_stage_table')->where('uid', $uid)->value('content');
            if ($ok) {
                DB::table('b_stage_table')->where('uid', $uid)->update(['content' => $content]);
            } else {
                DB::table('b_stage_table')->insert(['uid' => $uid, 'content' => json_encode($content)]);
            }
            show_json(1);
        }
        $content = DB::table('b_stage_table')->where('uid', $uid)->value('content');
        return ['content' => json_decode($content)];
    }

    /**
     * [getTeamNum 获取当月作业数量]
     * @param  Request $request [description]
     * @return [type]           [1用户添加2作业网数据]
     */
    public function getTeamNum(Request $request)
    {
        $type = (int)$request->input('type');
        if (!in_array($type, [1,2,3])) {
            $type = 1;
        }

        $uid = Auth::guard('user')->id();
        if (!$uid) {
            $uid = session('id');
            if ($type == 3) {
                return 1;
            }
        }

        if ($type == 1) { // 用户添加
            $where = ['uid' => $uid, 'status' => 1];
            $model = \App\Models\Team::class;
        }

        if ($type == 2) { // 作业网数据 D面
            $where = ['uid' => 0, 'status' => 1, 'stage' => 5];
            $model = \App\Models\Team::class;
        }

        if ($type == 3) { // 作业网数据 B面
            $where = ['uid' => 0, 'status' => 1, 'stage' => 2];
            $model = \App\Models\Team::class;
        }

        $num = $model::where($where)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', Carbon::now()->month)->count();
        return $num;
    }

    private function sumStatusNum($teams = [])
    {
        $sum = 0;
        foreach ($teams as $key => $teamInfo) {
            if (in_array(0, $teamInfo['role_status'])) {
                $sum++;
            }
        }
        return $sum;
    }

    /**
     * [addUseTimes 提高角色优先级（排名）]
     * @param array $role_ids [description]
     */
    private function addUseTimes($role_ids = [])
    {
        if (is_array($role_ids)) {
            foreach ($role_ids as $key => $value) {
                DB::table('roles')->where('id', (int)$value)->increment('use_times');
            }
        }
    }

}
