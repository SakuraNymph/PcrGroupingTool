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
                            $query->on('roles.role_id', '=', 'team_roles.role_id');
                        })->select(DB::raw('CASE WHEN roles.is_6 = 1 THEN roles.role_id_6 ELSE roles.role_id_3 END as image_id, roles.role_id, team_roles.team_id, team_roles.status'))->orderBy('roles.search_area_width', 'DESC');
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

    /**
     * [getTeamGroups 获取推荐分刀作业]
     * @param  [type]  $uid       [用户ID]
     * @param  array   $bossMap   [bossID数组]
     * @param  integer $type      [类型0手动分刀1自动分刀]
     * @param  integer $accountId [账号ID]
     * @return [type]             [结果数据]
     */
    public static function getTeamGroups($uid, $bossMap = [], $type = 0, $accountId = 0)
    {
        $cacheKey = ($type && $accountId) ? 'autoTeams' : '';

        $data = Team::with(['teamRoles' => function ($query) {
            $query->join('roles', function($join) {
                $join->on('roles.role_id', '=', 'team_roles.role_id');
            })->select(DB::raw('CASE WHEN roles.is_6 = 1 THEN roles.role_id_6 ELSE roles.role_id_3 END as image_id, roles.role_id, team_roles.team_id, team_roles.status'))->orderBy('roles.search_area_width', 'DESC');
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

            // 出战角色数量判断
            $is_ok = self::countRolesNum($roleStatus);
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
        return $res;
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
       foreach ($teams as $key => $teamInfo) {
            foreach ($teamInfo['team_roles'] as $k => $role) {
                if ($role['status'] == 0) {
                    $teams[$key]['borrow'] = $role['role_id'];
                    break;
                }
            }
        }
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

    /**
     * [makeTeams 每三个阵容编成一组]
     * @param  [type] $data [原数据]
     * @return [type]       [结果数据]
     */
    private static function makeTeams($data)
    {
        $n = count($data);
        $teamsRes = [];
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
                    $teamsRes[] = [
                        ['dataKey' => $i, 'boss' => $data[$i]['boss'], 'score' => $data[$i]['score']],
                        ['dataKey' => $j, 'boss' => $data[$j]['boss'], 'score' => $data[$j]['score']],
                        ['dataKey' => $k, 'boss' => $data[$k]['boss'], 'score' => $data[$k]['score']]
                    ];
                }
            }
        }
        return self::sortTeams($teamsRes);
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

    
}
