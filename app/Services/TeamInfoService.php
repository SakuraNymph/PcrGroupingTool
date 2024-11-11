<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Role;
use App\Models\User;
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
            $where = ['boss' => $boss, 'open' => 2, 'status' => 1];
        }
        if ($type == 2) {
            // 个人作业
            $where = ['uid' => $uid, 'boss' => $boss, 'status' => 1];
        }
        if ($type == 3) {
            // 公共作业
            $where = ['boss' => $boss, 'status' => 1];
        }
        $data = Team::with(['teamRoles' => function ($query) {
                        $query->join('roles', function ($query) {
                            $query->on('roles.role_id_3', '=', 'team_roles.role_id')
                                    ->orOn('roles.role_id_6', '=', 'team_roles.role_id');
                        })->select('team_roles.team_id', 'team_roles.role_id', 'team_roles.status')->orderBy('roles.search_area_width', 'DESC');
                    }])
                    ->where($where)
                    ->where(function ($query) use ($type) {
                        if ($type == 3) { // 公共作业 open = 1 或者 2
                            $query->whereIn('open', [1, 2]);
                        }
                    })
                    ->whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->orderBy('score', 'DESC')
                    ->get();
        $data = $data ? $data->toArray() : [];
// return $data;
        if ($data) {
            usort($data, function($a, $b) {
                // 逐个比较 role_id
                for ($i = 4; $i >= 0; $i--) {
                    $comparison = $a['team_roles'][$i]['role_id'] <=> $b['team_roles'][$i]['role_id'];
                    if ($comparison !== 0) {
                        return $comparison; // 如果不相等，返回比较结果
                    }
                }

                return 0; // 如果所有 role_id 相同，返回 0
            });
        }
// return $data;
        foreach ($data as $key => $teamInfo) {
            $data[$key]['has_add'] = 0;
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
            $otherTeamInfo = Team::with(['teamRoles' => function ($query) {
                        $query->select('role_id', 'team_id', 'status'); // 指定需要的字段
                    }])
                    ->where(['uid' => $uid, 'status' => 1])
                    ->where('otid', '!=', '')
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
        $otherTeamInfo = Team::where(['id' => $teamId, 'open' => 2, 'status' => 1])
                ->whereYear('created_at', Carbon::now()->year)
                ->whereMonth('created_at', Carbon::now()->month)->first();
        $otherTeamInfo = $otherTeamInfo ? $otherTeamInfo->toArray() : [];
        if (!$otherTeamInfo) {
            return false;
        }
        $teamInfo = Team::where(['uid' => $uid, 'otid' => $teamId])->first();
        if ($teamInfo) {
            Team::where(['uid' => $uid, 'otid' => $teamId])->update(['status' => 1]);
            TeamRole::where('team_id', $teamInfo['id'])->update(['status' => 1]);
            if ($roleId) {
                TeamRole::where('team_id', $teamInfo['id'])->where('role_id', $roleId)->update(['status' => 0]);
            }
            return true;
        }
        $insert = ['uid' => $uid, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(), 'otid' => $teamId, 'open' => 0];
        foreach ($otherTeamInfo as $key => $value) {
            if (!in_array($key, ['id', 'created_at', 'updated_at', 'open', 'uid', 'otid'])) {
                $insert[$key] = $value;
            }
        }
        $id = Team::insertGetId($insert);

        $teamRoles = TeamRole::where('team_id', $teamId)->pluck('role_id');
        $teamRolesInsert = [];
        foreach ($teamRoles as $key => $role_id) {
            if ($roleId && $roleId == $role_id) {
                $teamRolesInsert[] = ['team_id' => $id, 'role_id' => $role_id, 'status' => 0];
            } else {
                $teamRolesInsert[] = ['team_id' => $id, 'role_id' => $role_id, 'status' => 1];
            }
        }

        $ok = TeamRole::insert($teamRolesInsert);
        return true;
    }

    public static function getTeamGroups($uid, $bossMap = [], $type = 0, $accountId = 0)
    {
        if ($type && $accountId) {
            $cacheKey = 'autoTeams';
        } else {
            $cacheKey = '';
        }

        $data = Team::with(['teamRoles' => function ($query) {
            $query->join('roles', function($join) {
                $join->on('roles.role_id_3', '=', 'team_roles.role_id')
                     ->orOn('roles.role_id_6', '=', 'team_roles.role_id');
            })->orderBy('roles.search_area_width', 'DESC')->select('team_roles.team_id', 'team_roles.role_id', 'team_roles.status');
        }])
        ->where(function ($query) use ($uid, $type, $accountId) {
            if ($type && $accountId) {
                $query->where(['open' => 2, 'auto' => 1]);
            } else {
                $query->where(['uid' => $uid]);
            }
        })
        ->where('status', 1)
        ->whereYear('created_at', Carbon::now()->year)
        ->whereMonth('created_at', Carbon::now()->month)
        ->orderBy('id')
        ->get();

        $data = $data ? $data->toArray() : [];

        $makeArr = false;
// $makeArr = 1;
        if ($cacheKey) {
            $oldDataJson = Cache::get($cacheKey);
            if (json_encode($data) != $oldDataJson) {
                $makeArr = true;
                Cache::put($cacheKey, json_encode($data));
            }
        }

        if ($type && $accountId) {
            if ($makeArr) {
                $teamsRes = self::makeTeams($data);
                Cache::put('makeTeams', $teamsRes);
            } else {
                $teamsRes = Cache::get('makeTeams');
            }
        } else {
            $teamsRes = self::makeTeams($data);
        }

        $userBox = [];
        if ($type && $accountId) {
            $userBox = Account::where('id', $accountId)->value('roles');
            if ($userBox) {
                $userBox = explode(',', $userBox);
            }
        }

        $successNum = 20;
        $failNum = 0;

        $bossMapCount = $bossMap ? count($bossMap) : 0;

        foreach ($teamsRes as $key => $teams) {
            $teamsBoss = [];
            $roleStatus = [];
            $continueSwitch = false;
            foreach ($teams as $k => $teamInfo) {
                if ($bossMap) { // 筛选特定boss作业
                    $teamsBoss[] = $teamInfo['boss'];
                }
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

            if ($bossMap) {
                if (count(array_intersect($bossMap, $teamsBoss)) < $bossMapCount) {
                    unset($teamsRes[$key]);
                    $failNum++;
                    continue;
                }
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

// return $res;
        foreach ($res as $key => &$teams) {
            foreach ($teams as $k => $team) {
                $teams[$k]['link'] = json_decode($team['link'], 1);
                if ($teams[$k]['link']) {
                    foreach ($teams[$k]['link'] as $kk => $link) {
                        $teams[$k]['link'][$kk]['image'] = json_encode($link['image']);
                    }
                }
            }
            $status_times = self::sumStatusNum($teams);
            if ($status_times == 3) {
                foreach ($teams as $k => $teamInfo) {
                    foreach ($teamInfo['team_roles'] as $kk => $role) {
                        if ($role['status'] == 0) {
                            $teams[$k]['borrow'] = $role['role_id'];
                            break;
                        }
                    }
                }
            }
            if ($status_times == 2) {
                $f_key = 0;
                foreach ($teams as $k => $teamInfo) {
                    foreach ($teamInfo['team_roles'] as $kk => $role) {
                        if ($role['status'] == 0) {
                            $teams[$k]['borrow'] = $role['role_id'];
                            break;
                        }
                    }
                }
                $tmp = [];
                foreach ($teams as $k => $teamInfo) {
                    if ($teamInfo['borrow']) {
                        $tmp[] = $k;
                    } else {
                        $f_key = $k;
                    }
                }
                $k_role = array_merge(array_column($teams[$tmp[0]]['team_roles'], 'role_id'), array_column($teams[$tmp[1]]['team_roles'], 'role_id'));
                if (in_array($teams[$tmp[0]]['borrow'], $k_role)) {
                    unset($k_role[array_search($teams[$tmp[0]]['borrow'], $k_role)]);
                }
                if (in_array($teams[$tmp[1]]['borrow'], $k_role)) {
                    unset($k_role[array_search($teams[$tmp[1]]['borrow'], $k_role)]);
                }
                if (array_intersect(array_column($teams[$f_key]['team_roles'], 'role_id'), $k_role)) {
                    $teams[$f_key]['borrow'] = current(array_intersect(array_column($teams[$f_key]['team_roles'], 'role_id'), $k_role));
                } else {
                    $teams[$f_key]['borrow'] = 0;
                }
            }
            if ($status_times == 1) {
                $b_key = 0;
                // 先找到缺角色的那个阵容并标记出来
                foreach ($teams as $k => $teamInfo) {
                    foreach ($teamInfo['team_roles'] as $kk => $role) {
                        if ($role['status'] == 0) {
                            $teams[$k]['borrow'] = $role['role_id'];
                            $b_key = $k;
                            break;
                        }
                    }
                }
                // 再统计剩下两个队伍
                $ts = 0;
                foreach ($teams as $k => $teamInfo) {
                    if ($k != $b_key) {
                        $k_role = array_values(array_intersect(array_column($teams[$b_key]['team_roles'], 'role_id'), array_column($teams[$k]['team_roles'], 'role_id')));
                        if (array_search($teams[$b_key]['borrow'], $k_role)) {
                            unset($k_role[array_search($teams[$b_key]['borrow'], $k_role)]);
                        }
                        if ($k_role) {  // 该阵容和缺角色的阵容有共用角色
                            $teams[$k]['borrow'] = current($k_role);
                            $ts++;
                        }
                    }
                }
                // 另外两个不缺角色的阵容都不和缺角色的阵容有公共角色
                if ($ts == 0) {
                    $key_map = [];
                    for ($i=0; $i < 3; $i++) { 
                        if ($i != $b_key) {
                            $key_map[] = $i;
                        }
                    }
                    $k_role = array_intersect(array_column($teams[$key_map[0]]['team_roles'], 'role_id'), array_column($teams[$key_map[1]]['team_roles'], 'role_id'));
                    $teams[$key_map[0]]['borrow'] = current($k_role);
                    $teams[$key_map[1]]['borrow'] = next($k_role);
                }
                // 其中一个阵容与缺角色的阵容拥有公共角色
                if ($ts == 1) {
                    foreach ($teams as $k => $teamInfo) {
                        if ($teamInfo['borrow'] == 0) {
                            $a_key = 3 - $k - $b_key;
                            $k_role = array_values(array_intersect(array_column($teams[$a_key]['team_roles'], 'role_id'), array_column($teams[$k]['team_roles'], 'role_id')));
                            if (array_search($teams[$a_key]['borrow'], $k_role)) {
                                unset($k_role[array_search($teams[$a_key]['borrow'], $k_role)]);
                            }
                            $teams[$k]['borrow'] = current($k_role) ?? 0;
                        }
                    }
                }
            }
            if ($status_times == 0) {
                $sum_arr = self::sumRoleNum($teams, 2);
                // 三组都没有共用角色的情况
                if ($sum_arr['sum'] == 0) {
                    $teams[0]['borrow'] = $teams[1]['borrow'] = $teams[2]['borrow'] = 0;
                }
                // 其中两组共用一个角色
                if ($sum_arr['sum'] == 1) {
                    for ($i=0; $i < 2; $i++) { 
                        for ($j=$i+1; $j < 3; $j++) { 
                            $k_role = current(array_intersect(array_column($teams[$i]['team_roles'], 'role_id'), array_column($teams[$j]['team_roles'], 'role_id')));
                            if ($k_role) {
                                if ($teams[$i]['borrow'] == 0 && $teams[$j]['borrow'] == 0) {
                                    $teams[$i]['borrow'] = $k_role;
                                    continue;
                                }
                                if ($teams[$i]['borrow'] && $teams[$i]['borrow'] != $k_role) {
                                    $teams[$j]['borrow'] = $k_role;
                                    continue;
                                }
                                if ($teams[$j]['borrow'] && $teams[$j]['borrow'] != $k_role) {
                                    $teams[$i]['borrow'] = $k_role;
                                    continue;
                                }
                            }
                        }
                    }
                }
                // 其中两组共用两个角色
                if ($sum_arr['sum'] == 2) {
                    $same_role_arr = array_intersect(array_column($teams[$sum_arr['i']]['team_roles'], 'role_id'), array_column($teams[$sum_arr['j']]['team_roles'], 'role_id'));
                    $teams[$sum_arr['i']]['borrow'] = current($same_role_arr);
                    $teams[$sum_arr['j']]['borrow'] = next($same_role_arr);
                    $k = 3 - $sum_arr['i'] - $sum_arr['j'];
                    $k_i = count(array_intersect(array_column($teams[$k]['team_roles'], 'role_id'), array_column($teams[$sum_arr['i']]['team_roles'], 'role_id')));
                    $k_j = count(array_intersect(array_column($teams[$k]['team_roles'], 'role_id'), array_column($teams[$sum_arr['j']]['team_roles'], 'role_id')));
                    // 和另外两队共用0个角色
                    if ($k_i == 0 && $k_j == 0) {
                        $teams[$k]['borrow'] = 0;
                    }
                    if ($k_i == 1) {
                        $teams[$k]['borrow'] = current(array_intersect(array_column($teams[$k]['team_roles'], 'role_id'), array_column($teams[$sum_arr['i']]['team_roles'], 'role_id')));
                    }
                    if ($k_j == 1) {
                        $teams[$k]['borrow'] = current(array_intersect(array_column($teams[$k]['team_roles'], 'role_id'), array_column($teams[$sum_arr['j']]['team_roles'], 'role_id')));
                    }
                }
            }
        }
        return $res;
    }

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

    private static function sumStatusNum($teams = [])
    {
        $sum = 0;
        foreach ($teams as $key => $teamInfo) {
            foreach ($teamInfo['team_roles'] as $k => $role) {
                if ($role['status'] == 0) {
                    $sum++;
                }
            }
        }
        return $sum;
    }

    private static function sumRoleNum($teams = [], $num = 2)
    {
        $sum = 0;
        for ($i=0; $i < 2; $i++) { 
            for ($j=$i+1; $j < 3; $j++) { 
                $same_num = count(array_intersect(array_column($teams[$i]['team_roles'], 'role_id'), array_column($teams[$j]['team_roles'], 'role_id')));
                if ($same_num > $sum) {
                    $sum = $same_num;
                }
                if ($same_num == $num) {
                    break 2;
                }
            }
        }
        return ['sum' => $sum, 'i' => $i, 'j' => $j];
    }

    private static function checkSameRoleNum($teamA, $teamB)
    {
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
        return $sameRoleNum >= $limitNum ? false : true;
    }

    private static function makeTeams($data)
    {
        $n = count($data);
        for ($i = 0; $i < $n - 2; $i++) {
            for ($j = $i + 1; $j < $n - 1; $j++) {
                $is_ok = self::checkSameRoleNum($data[$i], $data[$j]);
                if (!$is_ok) {
                    continue;
                }
                for ($k = $j + 1; $k < $n; $k++) {
                    $is_ok = self::checkSameRoleNum($data[$i], $data[$k]);
                        if (!$is_ok) {
                        continue;
                    }
                    $is_ok = self::checkSameRoleNum($data[$j], $data[$k]);
                        if (!$is_ok) {
                        continue;
                    }
                    if (count(array_count_values(array_merge(array_column($data[$i]['team_roles'], 'role_id'), array_column($data[$j]['team_roles'], 'role_id'), array_column($data[$k]['team_roles'], 'role_id')))) >= 12) {
                        // $teamsRes[] = [$data[$i], $data[$j], $data[$k]];
                        $teamsRes[] = [
                            ['dataKey' => $i, 'boss' => $data[$i]['boss'], 'score' => $data[$i]['score']],
                            ['dataKey' => $j, 'boss' => $data[$j]['boss'], 'score' => $data[$j]['score']],
                            ['dataKey' => $k, 'boss' => $data[$k]['boss'], 'score' => $data[$k]['score']]
                        ];
                    }
                }
            }
        }
        return self::sortTeams($teamsRes);
    }

    private static function sortTeams($data)
    {
        $multiplierRates = self::$multiplierRates;

        foreach ($data as &$teams) {
            usort($teams, function($a, $b) {
                return $a['boss'] <=> $b['boss'];
            });
        }

        $sort = 3;
        // 综合排序
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
        // 难度排序
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
}
