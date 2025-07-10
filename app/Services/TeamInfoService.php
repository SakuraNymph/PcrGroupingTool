<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Role;
use App\Models\User;
use App\Models\UserTeam;
use App\Models\Team;
use App\Models\TeamRole;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TeamInfoService
{
    public static $multiplierRates = [0, 3.5, 3.5, 3.7, 3.8, 4];

    /**
     * [getTeams 获取公共/个人作业]
     * @param  [type]  $boss [bossID]
     * @param  integer $type [类型1公共2个人]
     * * @param  [type]  $uid  [用户ID]
     * @return [type]        [description]
     */
    public static function getTeams($boss, $type, $uid = 0)
    {
        if ($type == 1) {
            // 公共作业
            $where = ['boss' => $boss, 'stage' => 5];
        }
        if ($type == 2) {
            // 个人作业
            $where = ['boss' => $boss, 'uid' => $uid];
        }
        if ($type == 3) {
            // D面公共作业
            $where = ['boss' => $boss, 'stage' => 5];
        }
        if ($type == 4) {
            // B面公共作业
            $where = ['boss' => $boss, 'stage' => 2];
        }
        $where['status'] = 1;

        $modelMap = [
            1 => \App\Models\Team::class,
            2 => \App\Models\UserTeam::class,
        ];

        $data = $modelMap[$type]::with(['teamRoles' => function ($query) {
                        $query->join('roles', function ($query) {
                            $query->on('roles.role_id', '=', 'team_roles.role_id');
                        })->select(DB::raw('CASE WHEN roles.is_6 = 1 THEN roles.role_id_6 ELSE roles.role_id_3 END as image_id, roles.role_id, team_roles.team_id, team_roles.status'))->orderBy('roles.search_area_width', 'DESC');
                    }])
                    ->where($where)
                    ->where(function ($query) use ($type) {
                        if ($type == 3) { // 公共作业 open = 1 或者 2
                            $query->whereIn('open', [0, 1, 2]);
                        }
                    })
                    ->whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->orderBy('score', 'DESC')
                    ->get();

        $data = $data ? $data->toArray() : [];
        if ($data) {
            usort($data, function($a, $b) {
                // 逐个比较 role_id
                for ($i = 4; $i >= 0; $i--) {
                    if (isset($a['team_roles'][$i]['role_id']) && isset($b['team_roles'][$i]['role_id'])) {
                        $comparison = $a['team_roles'][$i]['role_id'] <=> $b['team_roles'][$i]['role_id'];
                        if ($comparison !== 0) {
                            return $comparison; // 如果不相等，返回比较结果
                        }
                    }
                }

                return 0; // 如果所有 role_id 相同，返回 0
            });
        }
        foreach ($data as $key => $teamInfo) {
            $data[$key]['has_add'] = 0; // 是否已经添加到我的作业中  0未添加 1已添加
            $data[$key]['link'] = json_decode(($teamInfo['link']), 1);
            if ($data[$key]['link']) {
                foreach ($data[$key]['link'] as $k => $link) {
                    $data[$key]['link'][$k]['image'] = json_encode($link['image']);
                }
            }
            foreach ($teamInfo['team_roles'] as $k => $roleInfo) {
                if ($type == 1 || $type == 3) { // 公共作业全展示
                    $data[$key]['team_roles'][$k]['status'] = 1;
                }
            }
        }
        if ($type == 1) {
            $otherTeamInfo = UserTeam::with(['teamRoles' => function ($query) {
                        $query->select('role_id', 'team_id', 'status'); // 指定需要的字段
                    }])
                    ->where(['uid' => $uid, 'status' => 1])
                    ->whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->orderBy('id')->get();
            $otherTeamInfo = $otherTeamInfo ? $otherTeamInfo->toArray() : [];
            $userTeamInfo = [];
            foreach ($otherTeamInfo as $key => $teamInfo) {
                $statusRoleId = 0;
                foreach ($teamInfo['team_roles'] as $k => $roles) {
                    if ($roles['status'] == 0) {
                        $statusRoleId = $roles['role_id'];
                        break;
                    }
                }
                $userTeamInfo[$teamInfo['otid']] = $statusRoleId;
            }
            foreach ($data as $key => $teamInfo) {
                if (array_key_exists($teamInfo['id'], $userTeamInfo)) {
                    $data[$key]['has_add'] = 1;
                    if ($userTeamInfo[$teamInfo['id']]) {
                        foreach ($teamInfo['team_roles'] as $k => $roleInfo) {
                            if ($roleInfo['role_id'] == $userTeamInfo[$teamInfo['id']]) {
                                $data[$key]['team_roles'][$k]['status'] = 0;
                            }
                        }
                    }
                } else {
                    $data[$key]['has_add'] = 0;
                }
            }
        }
        return $data;
    }

    /**
     * [addOtherTeam 将公共作业添加到我的作业]
     * @param [type] $uid    [用户ID]
     * @param [type] $teamId [公共作业ID]
     * @param [type] $roleId [空缺角色ID]
     */
    public static function addOtherTeam($uid, $teamId, $roleId)
    {
        $otherTeamInfo = Team::where(['id' => $teamId, 'status' => 1])
                ->whereYear('created_at', Carbon::now()->year)
                ->whereMonth('created_at', Carbon::now()->month)->first();
        $otherTeamInfo = $otherTeamInfo ? $otherTeamInfo->toArray() : [];
        if (!$otherTeamInfo) {
            return false;
        }

        $teamInfo = UserTeam::where(['uid' => $uid, 'otid' => $teamId])->first();
        $teamRoles = TeamRole::where('team_id', $teamId)->pluck('role_id');
        $teamRolesInsert = [];
        
        if ($teamInfo) {
            UserTeam::where(['uid' => $uid, 'otid' => $teamId])->update(['status' => 1, 'updated_at' => Carbon::now()]);
            foreach ($teamRoles as $key => $role_id) {
                if ($roleId && $roleId == $role_id) {
                    $teamRolesInsert[] = ['team_id' => $teamInfo->id, 'role_id' => $role_id, 'status' => 0];
                } else {
                    $teamRolesInsert[] = ['team_id' => $teamInfo->id, 'role_id' => $role_id, 'status' => 1];
                }
            }
            TeamRole::insert($teamRolesInsert);
            return true;
        }

        $insert = ['uid' => $uid, 'otid' => $teamId, 'created_at' => Carbon::now()];
        foreach ($otherTeamInfo as $key => $value) {
            if (!in_array($key, ['id', 'otid', 'created_at', 'updated_at', 'open', 'uid'])) {
                $insert[$key] = $value;
            }
        }
        $utid = UserTeam::insertGetId($insert);
        if (!$utid) {
            return false;
        }

        foreach ($teamRoles as $key => $role_id) {
            if ($roleId && $roleId == $role_id) {
                $teamRolesInsert[] = ['team_id' => $utid, 'role_id' => $role_id, 'status' => 0];
            } else {
                $teamRolesInsert[] = ['team_id' => $utid, 'role_id' => $role_id, 'status' => 1];
            }
        }

        $ok = TeamRole::insert($teamRolesInsert);
        return true;
    }

    /**
     * [getTeamGroups 获取推荐分刀作业]
     * @param  [type]  $uid       [用户ID]
     * @param  array   $bossMap   [bossID数组]
     * @param  integer $type      [类型0手动分刀1自动分刀]
     * @param  integer $accountId [账号ID]
     * @param  integer $atkType   [攻击类型]
     * @return [type]             [结果数据]
     */
    public static function getTeamGroups($uid, $bossMap = [], $type = 0, $accountId = 0, $atkType = 0)
    {
        if ($type) {
            if ($accountId) {
                if ($type == 1) {
                    $cacheKey = 'autoTeams';
                }
                if ($type == 2) {
                    $cacheKey = 'handTeams';
                }
                $where = ['open' => 2, 'auto' => $type];
            } else {
                if ($type == 3) {
                    $cacheKey = 'bStageTeams';
                    $where = ['stage' => 2, 'open' => 0];
                }
            }
            $model = \App\Models\Team::class;
        } else {
            $cacheKey = '';
            $where = ['uid' => $uid];
            $model = \App\Models\UserTeam::class;
        }

        $data = $model::with(['teamRoles' => function ($query) {
            $query->join('roles', function($join) {
                $join->on('roles.role_id', '=', 'team_roles.role_id');
            })->select(DB::raw('CASE WHEN roles.is_6 = 1 THEN roles.role_id_6 ELSE roles.role_id_3 END as image_id, roles.role_id, team_roles.team_id, team_roles.status'))->orderBy('roles.search_area_width', 'DESC');
        }])
        ->where(function ($query) use ($uid, $type, $accountId, $where) {
            if ($type && $accountId) {
                $query->where($where);
            } else {
                $query->where($where);
            }
        })
        ->where('status', 1)
        ->whereYear('created_at', Carbon::now()->year)
        ->whereMonth('created_at', Carbon::now()->month)
        ->orderBy('id')
        ->get();

        $data = $data ? $data->toArray() : [];
        $makeArr = false;
        // $makeArr = true;
        if ($cacheKey) {
            $oldDataJson = Cache::get($cacheKey);
            if (json_encode($data) != $oldDataJson) {
                $makeArr = true;
                Cache::put($cacheKey, json_encode($data));
            }
        }

        if ($cacheKey) {
            if ($makeArr) {
                $teamsRes = self::makeTeams($data);
                Cache::put($cacheKey . 'makeTeams', $teamsRes);
            } else {
                $teamsRes = Cache::get($cacheKey . 'makeTeams');
            }
        } else {
            $data_huawu = Cache::get('data_huawu');
            if (empty($data_huawu)) {
                $data_huawu = team::where(['uid' => 0, 'status' => 1])
                                ->whereYear('created_at', Carbon::now()->year)
                                ->whereMonth('created_at', Carbon::now()->month)
                                ->orderBy('id')
                                ->get();
                Cache::put('data_huawu', $data_huawu, 1800);
            }

            $data_huawu_new = [];
            foreach ($data_huawu as $key => $value) {
                $data_huawu_new[$value->id] = $value->link;
            }
            
            // 替换link数据
            foreach ($data as $key => $value) {
                if ($value['otid']) {
                    $data[$key]['link'] = $data_huawu_new[$value['otid']];
                }
            }
            $teamsRes = self::makeTeams($data);
        }

        $userBox = [];
        if ($type && $accountId) {
            $userBox = Account::where('id', $accountId)->value('roles');
            if ($userBox) {
                $userBox = explode(',', $userBox);
            }
        }

        $successNum = 50;
        $failNum    = 0;
        $bossMapNum = 0;
        foreach ($bossMap as $key => $value) {
            if (!$value) {
                unset($bossMap[$key]);
            }
        }
        $bossMapNum = count($bossMap);
        $bossMapCountValues = count(array_count_values($bossMap));

        sort($bossMap);

        foreach ($teamsRes as $key => $teams) {
            $roleStatus     = [];
            $teamBossMap    = [];
            $continueSwitch = false;
            

            $gt = 0; // 大于0的数量
            $lt = 0; // 小于0的数量
            foreach ($teams as $k => $teamInfo) {
                $teamBossMap[] = $teamInfo['boss'];

                if ($teamInfo['atk_value'] >= 0) {
                    $gt++;
                }
                if ($teamInfo['atk_value'] <= 0) {
                    $lt++;
                }
            }

            if ($atkType) {
                $continueSwitch = true;
                switch ($atkType) {
                    case 1:
                        if ($gt == 3) { // 3物理
                            $continueSwitch = false;
                        }
                        break;
                    case 2:
                        if ($gt == 2 && $lt == 1) { // 2物1法 0的情况没考虑
                            $continueSwitch = false;
                        }
                        break;
                    case 3:
                        if ($gt == 1 && $lt == 2) { // 2法1物 0的情况没考虑
                            $continueSwitch = false;
                        }
                        break;
                    case 4:
                        if ($lt == 3) { // 3魔法
                            $continueSwitch = false;
                        }
                        break;
                    
                    default:
                        $continueSwitch = true;
                        break;
                }
            }

            if (!$continueSwitch) {
                if ($bossMap) {
                    if ($bossMapNum == 1) { // 只选择一个boss
                        if (!array_intersect($bossMap, $teamBossMap)) {
                            $continueSwitch = true;
                        }
                    }
                    if ($bossMapNum == 2) { // 选择两个boss
                        if ($bossMapCountValues == 1) { // 两个boss相同
                            if (count(array_intersect($teamBossMap, $bossMap)) < 2) {
                                $continueSwitch = true;
                            }
                        }
                        if ($bossMapCountValues == 2) { // 两个boss不同
                            if (count(array_intersect($bossMap, $teamBossMap)) != 2) {
                                $continueSwitch = true;
                            }
                        }
                    }
                    if ($bossMapNum == 3) { // 选择三个boss
                        if ($teamBossMap != $bossMap) {
                            $continueSwitch = true;
                        }
                    }
                }
            }

            if ($continueSwitch) {
                unset($teamsRes[$key]);
                $failNum++;
                continue;
            }

            foreach ($teams as $k => $teamInfo) {
                if ($type && $accountId) { // 筛选角色 每队至少要拥有4名角色
                    if (count(array_intersect($userBox, array_column($data[$teamInfo['dataKey']]['team_roles'], 'role_id'))) < 4) { // 每队至少拥有4名角色
                        $continueSwitch = true;
                        break;
                    } else {
                        foreach ($data[$teamInfo['dataKey']]['team_roles'] as $kk => $role) {
                            if (!in_array($role['role_id'], $userBox)) {
                                $data[$teamInfo['dataKey']]['team_roles'][$kk]['status'] = 0;
                            }
                        }
                    }
                }
                $data[$teamInfo['dataKey']]['borrow'] = 0;
                $roleStatus[] = $data[$teamInfo['dataKey']];
            }

            if ($continueSwitch) {
                unset($teamsRes[$key]);
                $failNum++;
                continue;
            }

            // 借人数量判断
            $is_ok = self::checkRoleSumNum($roleStatus);
            if (!$is_ok) {
                unset($teamsRes[$key]);
                $failNum++;
                continue;
            }

            // 出战角色数量判断
            $is_ok = self::countRolesNum($roleStatus);
            if (!$is_ok) {
                unset($teamsRes[$key]);
                $failNum++;
                continue;
            }

            if (($key - $failNum) >= $successNum) { // 实际比successNum多一个
                break;
            }
        }

        if (count($teamsRes) > $successNum) {
            $teamsRes = array_slice($teamsRes, 0, $successNum);
        }

        $res = [];

        foreach ($teamsRes as $key => $teams) {
            $temp = [];
            foreach ($teams as $k => $teamInfo) {
                $temp[] = $data[$teamInfo['dataKey']];
            }
            $res[] = $temp;
        }
        foreach ($res as $key => &$teams) {
            foreach ($teams as $k => $team) {
                $teams[$k]['link'] = json_decode($team['link'], 1);
                if ($teams[$k]['link']) {
                    foreach ($teams[$k]['link'] as $kk => $link) {
                        $teams[$k]['link'][$kk]['image'] = json_encode($link['image']);
                    }
                }
            }
            self::borrowRoles($teams);
        }
        // 用户分刀数据记录
        self::dataFightLog($uid, $type, $bossMap);
        return $res;
    }

    public static function getAdminTeamGroups()
    {
        set_time_limit(300); // 设置最大执行时间为60秒

        $where = ['open' => 0];
        $data = Team::with(['teamRoles' => function ($query) {
            $query->join('roles', function($join) {
                $join->on('roles.role_id', '=', 'team_roles.role_id');
            })->select(DB::raw('CASE WHEN roles.is_6 = 1 THEN roles.role_id_6 ELSE roles.role_id_3 END as image_id, roles.role_id, team_roles.team_id, team_roles.status'))->orderBy('roles.search_area_width', 'DESC');
        }])
        ->where(function ($query) use ($where) {
            $query->where($where);
        })
        ->where('status', 1)
        ->whereYear('created_at', Carbon::now()->year)
        ->whereMonth('created_at', Carbon::now()->month)
        ->orderBy('stage')
        ->get();

        $data = $data ? $data->toArray() : [];



// return $data;


        $aaa = self::makeTeams2($data);
        return $aaa;
    }

    /**
     * [checkRoleSumNum 任意两队之间借用角色和共用角色之和不能超过2]
     * @param  array  $teams [原数据]
     * @return [type]        [不超过2返回true 超过2返回false]
     */
    private static function checkRoleSumNum($teams = [])
    {
        for ($i=0; $i < 2; $i++) { 
            $statusNum = 0;
            $aRoles = [];
            foreach ($teams[$i]['team_roles'] as $akey => $aRoleInfo) {
                if ($aRoleInfo['status']) {
                    $aRoles[] = $aRoleInfo['role_id'];
                } else {
                    $statusNum++;
                }
            }
            for ($j=$i+1; $j < 3; $j++) { 
                $bRoles = [];
                foreach ($teams[$j]['team_roles'] as $bkey => $bRoleInfo) {
                    if ($bRoleInfo['status']) {
                        $bRoles[] = $bRoleInfo['role_id'];
                    } else {
                        $statusNum++;
                    }
                }
                $sameNum = count(array_intersect($aRoles, $bRoles));
                if ($statusNum + $sameNum > 2) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * [countRolesNum 三队作业至少要保证12个自己的角色]
     * @param  array  $teams [原数据]
     * @return [bool]        [大于等于12个角色返回true 小于12个角色返回false]
     */
    private static function countRolesNum($teams = [])
    {
        $map = [];
        foreach ($teams as $key => $teamInfo) {
            foreach ($teamInfo['team_roles'] as $k => $role) {
                if ($role['status'] && !in_array($role['role_id'], $map)) {
                    $map[] = $role['role_id'];
                }
            }
        }
        return count($map) >= 12 ? true : false;
    }

    /**
     * [borrowRoles 确定推荐借用角色]
     * @param  array  &$teams [分组后数据]
     * @return [type]         [补全借用角色的数据]
     */
    private static function borrowRoles(&$teams = [])
    {
        $borrow_switch = false;
        $borrow_key    = 0;
        foreach ($teams as $key => $teamInfo) {
            foreach ($teamInfo['team_roles'] as $k => $role) {
                if ($role['status'] == 0) {
                    $teams[$key]['borrow'] = $role['role_id'];
                    $borrow_switch = true;
                    $borrow_key    = $key;
                    break;
                }
            }
        }

        // 先找出共用两个角色的两个队伍
        $two_same_roles_switch = false;
        for ($i=0; $i < 2; $i++) { 
            for ($j=$i+1; $j < 3; $j++) { 
                $sameRoles = array_intersect(array_column($teams[$i]['team_roles'], 'role_id'), array_column($teams[$j]['team_roles'], 'role_id'));
                if (count($sameRoles) == 2) {
                    $two_same_roles_switch = true;
                    break 2;
                }
            }
        }

        if ($two_same_roles_switch || $borrow_switch) {
            if ($two_same_roles_switch) {
                $k = 3 - $i - $j;
            } else {
                $teamKeys = [0,1,2];
                $i = $borrow_key;
                unset($teamKeys[array_search($i, $teamKeys)]);
                $j = current($teamKeys);
                $k = next($teamKeys);
            }

            // i&j
            if (empty($teams[$i]['borrow']) || empty($teams[$j]['borrow'])) {
                $sameRoles = array_intersect(array_column($teams[$i]['team_roles'], 'role_id'), array_column($teams[$j]['team_roles'], 'role_id'));
                if ($sameRoles) {
                    if ($teams[$i]['borrow'] && empty($teams[$j]['borrow'])) {
                        if (in_array($teams[$i]['borrow'], $sameRoles)) {
                            unset($sameRoles[array_search($teams[$i]['borrow'], $sameRoles)]);
                            if (empty($sameRoles)) {
                            } else {
                                $teams[$j]['borrow'] = current($sameRoles);
                            }
                        } else {
                            $teams[$j]['borrow'] = current($sameRoles);
                        }
                    } elseif ($teams[$j]['borrow'] && empty($teams[$i]['borrow'])) {
                        if (in_array($teams[$j]['borrow'], $sameRoles)) {
                            unset($sameRoles[array_search($teams[$j]['borrow'], $sameRoles)]);
                            if (empty($sameRoles)) {
                            } else {
                                $teams[$i]['borrow'] = current($sameRoles);
                            }
                        } else {
                            $teams[$i]['borrow'] = current($sameRoles);
                        }
                    } elseif (empty($teams[$i]['borrow']) && empty($teams[$j]['borrow'])) {
                        $teams[$i]['borrow'] = current($sameRoles);
                        $teams[$j]['borrow'] = next($sameRoles);
                    }
                }
            }

            // i&k
            if (empty($teams[$i]['borrow']) || empty($teams[$k]['borrow'])) {
                $sameRoles = array_intersect(array_column($teams[$i]['team_roles'], 'role_id'), array_column($teams[$k]['team_roles'], 'role_id'));
                if ($sameRoles) {
                    if ($teams[$i]['borrow'] && empty($teams[$k]['borrow'])) {
                        if (in_array($teams[$i]['borrow'], $sameRoles)) {
                            unset($sameRoles[array_search($teams[$i]['borrow'], $sameRoles)]);
                            if (empty($sameRoles)) {
                            } else {
                                $teams[$k]['borrow'] = current($sameRoles);
                            }
                        } else {
                            $teams[$k]['borrow'] = current($sameRoles);
                        }
                    } elseif ($teams[$k]['borrow'] && empty($teams[$i]['borrow'])) {
                        if (in_array($teams[$k]['borrow'], $sameRoles)) {
                            unset($sameRoles[array_search($teams[$k]['borrow'], $sameRoles)]);
                            if (empty($sameRoles)) {
                            } else {
                                $teams[$i]['borrow'] = current($sameRoles);
                            }
                        } else {
                            $teams[$i]['borrow'] = current($sameRoles);
                        }
                    } elseif (empty($teams[$i]['borrow']) && empty($teams[$k]['borrow'])) {
                        $teams[$i]['borrow'] = current($sameRoles);
                        $teams[$k]['borrow'] = next($sameRoles);
                    }
                }
            }

            // $j&k
            if (empty($teams[$j]['borrow']) || empty($teams[$k]['borrow'])) {
                $sameRoles = array_intersect(array_column($teams[$j]['team_roles'], 'role_id'), array_column($teams[$k]['team_roles'], 'role_id'));
                if ($sameRoles) {
                    if ($teams[$j]['borrow'] && empty($teams[$k]['borrow'])) {
                        if (in_array($teams[$j]['borrow'], $sameRoles)) {
                            unset($sameRoles[array_search($teams[$j]['borrow'], $sameRoles)]);
                            if (empty($sameRoles)) {
                            } else {
                                $teams[$k]['borrow'] = current($sameRoles);
                            }
                        } else {
                            $teams[$k]['borrow'] = current($sameRoles);
                        }
                    } elseif ($teams[$k]['borrow'] && empty($teams[$j]['borrow'])) {
                        if (in_array($teams[$k]['borrow'], $sameRoles)) {
                            unset($sameRoles[array_search($teams[$k]['borrow'], $sameRoles)]);
                            if (empty($sameRoles)) {
                            } else {
                                $teams[$j]['borrow'] = current($sameRoles);
                            }
                        } else {
                            $teams[$j]['borrow'] = current($sameRoles);
                        }
                    } elseif (empty($teams[$j]['borrow']) && empty($teams[$k]['borrow'])) {
                        $teams[$j]['borrow'] = current($sameRoles);
                        $teams[$k]['borrow'] = next($sameRoles);
                    }
                }
            }
        } else {
            for ($i=0; $i < 2; $i++) { 
                for ($j=$i+1; $j < 3; $j++) { 
                    if ($teams[$i]['borrow'] && $teams[$j]['borrow']) {
                        continue;
                    }
                    $sameRoles = array_intersect(array_column($teams[$i]['team_roles'], 'role_id'), array_column($teams[$j]['team_roles'], 'role_id'));
                    if (empty($sameRoles)) {
                        continue;
                    }
                    if ($teams[$i]['borrow']) {
                        if (in_array($teams[$i]['borrow'], $sameRoles)) {
                            unset($sameRoles[array_search($teams[$i]['borrow'], $sameRoles)]);
                            if (empty($sameRoles)) {
                                continue;
                            } else {
                                $teams[$j]['borrow'] = current($sameRoles);
                                continue;
                            }
                        } else {
                            $teams[$j]['borrow'] = current($sameRoles);
                            continue;
                        }
                    }
                    if ($teams[$j]['borrow']) {
                        if (in_array($teams[$j]['borrow'], $sameRoles)) {
                            unset($sameRoles[array_search($teams[$j]['borrow'], $sameRoles)]);
                            if (empty($sameRoles)) {
                                continue;
                            } else {
                                $teams[$i]['borrow'] = current($sameRoles);
                                continue;
                            }
                        } else {
                            $teams[$i]['borrow'] = current($sameRoles);
                            continue;
                        }
                    }
                    $teams[$i]['borrow'] = current($sameRoles);
                    $teams[$j]['borrow'] = next($sameRoles);
                }
            }
        }
        return $teams; 
    }

    /**
     * [checkSameRoleNum 任意两组阵容不能有三个相同角色]
     * @param  [type] $teamA [作业A阵容]
     * @param  [type] $teamB [作业B阵容]
     * @return [bool]        [不超过三个角色返回true 超过三个角色返回false]
     */
    private static function checkSameRoleNum($teamA, $teamB)
    {
        if ($teamA['stage'] != $teamB['stage']) {
            return false;
        }
        $limitNum = 3;
        $roleMapA = [];
        foreach ($teamA['team_roles'] as $key => $role) {
            if ($role['status'] == 0) {
                $limitNum--;
            } else {
                $roleMapA[] = $role['role_id'];
            }
        }
        $roleMapB = [];
        foreach ($teamB['team_roles'] as $key => $role) {
            if ($role['status'] == 0) {
                $limitNum--;
            } else {
                $roleMapB[] = $role['role_id'];
            }
        }
        $sameRoleNum = count(array_intersect($roleMapA, $roleMapB));
        return $sameRoleNum >= $limitNum ? true : false;
    }

    /**
     * [makeTeams 每三个阵容编成一组]
     * @param  [type] $data [原数据]
     * @return [type]       [结果数据]
     */
    private static function makeTeams($data = [])
    {
        $n = count($data);
        $teamsRes = [];
        for ($i = 0; $i < $n - 2; $i++) {
            for ($j = $i + 1; $j < $n - 1; $j++) {
                if (self::checkSameRoleNum($data[$i], $data[$j])) {
                    continue;
                }
                for ($k = $j + 1; $k < $n; $k++) {
                    if (self::checkSameRoleNum($data[$i], $data[$k])) {
                        continue;
                    }
                    if (self::checkSameRoleNum($data[$j], $data[$k])) {
                        continue;
                    }
                    $teamsRes[] = [
                        ['dataKey' => $i, 'boss' => $data[$i]['boss'], 'score' => $data[$i]['score'], 'atk_value' => $data[$i]['atk_value']],
                        ['dataKey' => $j, 'boss' => $data[$j]['boss'], 'score' => $data[$j]['score'], 'atk_value' => $data[$j]['atk_value']],
                        ['dataKey' => $k, 'boss' => $data[$k]['boss'], 'score' => $data[$k]['score'], 'atk_value' => $data[$k]['atk_value']]
                    ];
                }
            }
        }
        return self::sortTeams($teamsRes);
    }

    private static function makeTeams2($data = [])
    {
        $n = count($data);
        $teamsRes = [];

        $map = ['i', 'j', 'k', 'l', 'm', 'p'];


        $aaa = 0;

        for ($i=0; $i < $n - 5; $i++) { 
            for ($j=$i + 1; $j < $n - 4; $j++) { 
                if (self::checkSameRoleNum($data[$i], $data[$j])) {
                    continue;
                }
                for ($k=$j + 1; $k < $n - 3; $k++) { 
                    if (self::checkSameRoleNum($data[$i], $data[$k])) {
                        continue;
                    }
                    if (self::checkSameRoleNum($data[$j], $data[$k])) {
                        continue;
                    }
                    for ($l=$k + 1; $l < $n - 2; $l++) { 
                        $sum = $data[$i]['stage'] + $data[$j]['stage'] + $data[$k]['stage'] + $data[$l]['stage'];
                        if ($sum == 8 || $sum == 20) {
                            continue;
                        }
                        if (self::checkSameRoleNum($data[$i], $data[$l])) {
                            continue;
                        }
                        if (self::checkSameRoleNum($data[$j], $data[$l])) {
                            continue;
                        }
                        if (self::checkSameRoleNum($data[$k], $data[$l])) {
                            continue;
                        }
                        for ($m=$l + 1; $m < $n - 1; $m++) { 
                            $sum += $data[$m]['stage'];
                            if ($sum == 13 || $sum == 22) {
                                continue;
                            }
                            if (self::checkSameRoleNum($data[$i], $data[$m])) {
                                continue;
                            }
                            if (self::checkSameRoleNum($data[$j], $data[$m])) {
                                continue;
                            }
                            if (self::checkSameRoleNum($data[$k], $data[$m])) {
                                continue;
                            }
                            if (self::checkSameRoleNum($data[$l], $data[$m])) {
                                continue;
                            }
                            for ($p=$m + 1; $p < $n; $p++) { 
                                $sum += $data[$p]['stage'];
                                if ($sum == 18 || $sum == 24) {
                                    continue;
                                }
                                if (self::checkSameRoleNum($data[$i], $data[$p])) {
                                    continue;
                                }
                                if (self::checkSameRoleNum($data[$j], $data[$p])) {
                                    continue;
                                }
                                if (self::checkSameRoleNum($data[$k], $data[$p])) {
                                    continue;
                                }
                                if (self::checkSameRoleNum($data[$l], $data[$p])) {
                                    continue;
                                }
                                if (self::checkSameRoleNum($data[$m], $data[$p])) {
                                    continue;
                                }
                                
                                $tempB = [];
                                $tempD = [];
                                foreach ($map as $key => $value) {
                                    $arr = ['dataKey' => $$value, 'boss' => $data[$$value]['boss'], 'score' => $data[$$value]['score'], 'team_roles' => $data[$$value]['team_roles']];
                                    if ($data[$$value]['stage'] == 2) {
                                        $tempB[] = $arr;
                                    }
                                    if ($data[$$value]['stage'] == 5) {
                                        $tempD[] = $arr;
                                    }
                                }

                                if (self::checkRoleSumNum2($tempB) && self::checkRoleSumNum2($tempD)) {


                                    $tempTeam = ['B' => $tempB, 'D' => $tempD];

                                    // $aaa = self::borrowRoles2($tempTeam);



                                    // return $aaa;


                                    $aaa++;

                                    if ($aaa == 4) {
                                        // $teamsRes[] = self::borrowRoles2($tempTeam);
                                    }

                                    $teamsRes[] = self::borrowRoles2($tempTeam);

                                    if ($aaa > 20) {
                                        break 6;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $teamsRes;

        return $aaa;
    }

    private static function borrowRoles2($team = [])
    {
        $bTeam = $team['B'];
        $dTeam = $team['D'];

        $bTeamRoles = [];
        $dTeamRoles = [];

        foreach ($bTeam as $key => $teamInfo) {
            $bTeamRoles[] = array_column($teamInfo['team_roles'], 'image_id');
        }

        foreach ($dTeam as $key => $teamInfo) {
            $dTeamRoles[] = array_column($teamInfo['team_roles'], 'image_id');
        }

        $bestScore = -1;
        $bestMatch = [];

        $permutations = self::permute([0,1,2]);

        foreach ($permutations as $perm) {
            $totalScore = 0;
            $match = [];
            foreach ($perm as $i => $dIndex) {
                $bIndex = $i;
                $score = count(array_intersect($bTeamRoles[$bIndex], $dTeamRoles[$dIndex]));
                $totalScore += $score;
                $match[] = [
                    'b_index' => $bIndex,
                    'd_index' => $dIndex,
                    'score'   => $score,
                ];
            }

            if ($totalScore > $bestScore) {
                $bestScore = $totalScore;
                $bestMatch = $match;
            }
        }


        $bTeamRoles = self::findSameRolesFromTwoTeams($bTeamRoles);
        $dTeamRoles = self::findSameRolesFromTwoTeams($dTeamRoles);

        $teamsRes = [];

        $myRoles = [];


        foreach ($bestMatch as $key => $x_index) {
            if ($x_index['score']) {
                $bdSameRoles = array_intersect($bTeamRoles[$x_index['b_index']]['team'], $dTeamRoles[$x_index['d_index']]['team']);

                foreach ($bTeamRoles[$x_index['b_index']]['sameRoles'] as $k => $role_id) {
                    if (in_array($role_id, $bdSameRoles)) {
                        unset($bTeamRoles[$x_index['b_index']]['sameRoles'][$k]);
                    }
                }
                $b_borrow = current($bTeamRoles[$x_index['b_index']]['sameRoles']);
                $bTeamRoles[$x_index['b_index']]['borrow'] = $b_borrow;
                if ($b_borrow) {
                    foreach ($bTeamRoles[$x_index['b_index']]['team'] as $k => $role_id) {
                        if ($role_id != $b_borrow) { // 必须使用自己的角色
                            $myRoles[] = $role_id;
                        }
                    }
                }

                foreach ($dTeamRoles[$x_index['d_index']]['sameRoles'] as $k => $role_id) {
                    if (in_array($role_id, $bdSameRoles)) {
                        unset($dTeamRoles[$x_index['d_index']]['sameRoles'][$k]);
                    }
                }
                $d_borrow = current($dTeamRoles[$x_index['d_index']]['sameRoles']);
                $dTeamRoles[$x_index['d_index']]['borrow'] = $d_borrow;
                if ($d_borrow) {
                    foreach ($dTeamRoles[$x_index['d_index']]['team'] as $k => $role_id) {
                        if ($role_id != $d_borrow) { // 必须使用自己的角色
                            $myRoles[] = $role_id;
                        }
                    }
                }
            } else {
                $bTeamRoles[$x_index['b_index']]['borrow'] = 0;
                $dTeamRoles[$x_index['d_index']]['borrow'] = 0;
            }
        }

        foreach ($bestMatch as $key => $x_index) {
            if (!$bTeamRoles[$x_index['b_index']]['borrow']) {
                foreach ($bTeamRoles[$x_index['b_index']]['team'] as $k => $role_id) {
                    if (in_array($role_id, $myRoles)) {
                        $bTeamRoles[$x_index['b_index']]['borrow'] = $role_id;
                        break;
                    }
                }
            }

            if (!$dTeamRoles[$x_index['d_index']]['borrow']) {
                foreach ($dTeamRoles[$x_index['d_index']]['team'] as $k => $role_id) {
                    if (in_array($role_id, $myRoles)) {
                        $dTeamRoles[$x_index['d_index']]['borrow'] = $role_id;
                        break;
                    }
                }
            }

            $teamsRes[] = [
                'b' => ['team' => $bTeamRoles[$x_index['b_index']]['team'], 'borrow' => $bTeamRoles[$x_index['b_index']]['borrow'], 'boss' => $bTeam[$x_index['b_index']]['boss'], 'score' => $bTeam[$x_index['b_index']]['score']],
                'd' => ['team' => $dTeamRoles[$x_index['d_index']]['team'], 'borrow' => $dTeamRoles[$x_index['d_index']]['borrow'], 'boss' => $dTeam[$x_index['d_index']]['boss'], 'score' => $dTeam[$x_index['d_index']]['score']],
            ];
        }

        return $teamsRes;

        return $bestMatch;

        return [ 'b' => $bTeamRoles, 'd' => $dTeamRoles];
        
    }

    private static function findSameRolesFromTwoTeams($teams = [])
    {
        $res = [];
        foreach ($teams as $key => $value) {
            $sameRoles = [];
            foreach ($teams as $k => $v) {
                if ($k != $key) {
                    $sameRoles = array_merge($sameRoles, array_intersect($value, $v));
                }
            }
            $res[$key]['team'] = $value;
            $res[$key]['sameRoles'] = $sameRoles;
        }
        return $res;
    }

    // 生成所有的排列
    private static function permute($items = []) {
        if (count($items) <= 1) return [$items];

        $result = [];
        foreach ($items as $key => $item) {
            $rest = $items;
            unset($rest[$key]);
            foreach (self::permute(array_values($rest)) as $perm) {
                array_unshift($perm, $item);
                $result[] = $perm;
            }
        }
        return $result;
    }

    private static function checkRoleSumNum2($team = [])
    {
        $roles = [];
        foreach ($team as $key => $value) {
            $roles = array_merge($roles, array_column($value['team_roles'], 'role_id'));
        }

        $targetNum = 0;
        $roleSameNumArr = array_count_values(array_count_values($roles));
        if (isset($roleSameNumArr[3])) {
            $targetNum = 2;
            if ($roleSameNumArr[3] >= 2) { // 三队共用相同角色的数量不能超过2
                return false;
            }
        }

        if (isset($roleSameNumArr[2])) {
            $targetNum += $roleSameNumArr[2];
        }

        return $targetNum > 3 ? false : true;
    }

    /**
     * [sortTeams 把每组数据按boss从小到大排序 把数据按分数由大到小排序]
     * @param  [type] $data [原数据]
     * @return [type]       [结果数据]
     */
    private static function sortTeams($data)
    {
        $multiplierRates = self::$multiplierRates;

        foreach ($data as &$teams) {
            usort($teams, function($a, $b) {
                return $a['boss'] <=> $b['boss'];
            });
        }

        $sort = 3;
        // 综合排序 已弃用
        if ($sort == 1) {
            usort($data, function($a, $b) use ($multiplierRates) {
                $teamAugScoreA = 0;
                $teamAugScoreB = 0;
                for ($i=0; $i < 3; $i++) { 
                    $teamAugScoreA += $a[$i]['score'] * $multiplierRates[$a[$i]['boss']] / $a[$i]['difficulty'];
                    $teamAugScoreB += $b[$i]['score'] * $multiplierRates[$b[$i]['boss']] / $b[$i]['difficulty'];
                }
                return $teamAugScoreB <=> $teamAugScoreA;
            });
        }
        // 难度排序 已弃用
        if ($sort == 2) {
            usort($data, function($a, $b) {
                $teamDifficultyA = 0;
                $teamDifficultyB = 0;
                for ($i=0; $i < 3; $i++) { 
                    $teamDifficultyA += $a[$i]['difficulty'];
                    $teamDifficultyB += $b[$i]['difficulty'];
                }
                return $teamDifficultyA <=> $teamDifficultyB;
            });
        }
        // 分数排序
        if ($sort == 3) {
            usort($data, function($a, $b) use ($multiplierRates) {
                $teamScoreA = 0;
                $teamScoreB = 0;
                for ($i=0; $i < 3; $i++) { 
                    $teamScoreA += $a[$i]['score'] * $multiplierRates[$a[$i]['boss']];
                    $teamScoreB += $b[$i]['score'] * $multiplierRates[$b[$i]['boss']];
                }
                return $teamScoreB <=> $teamScoreA;
            });
        }
        return $data;
    }

    private static function dataFightLog($uid, $type = 0, $boss = [])
    {
        if ($boss && is_array($boss)) {
            $boss = implode(',', $boss);
        } else {
            $boss = '';
        }
        $insert = ['uid' => $uid, 'type' => $type, 'boss' => $boss, 'created_at' => Carbon::now()];
        DB::table('data_fight')->insert($insert);
    }

    
}
