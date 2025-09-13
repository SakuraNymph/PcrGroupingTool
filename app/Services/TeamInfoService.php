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
    const MULTIPLIER_RATES = [0, 4.5, 4.5, 4.7, 4.8, 5];

    /**
     * [getTeams 获取公共/个人作业]
     * @param  [type]  $boss [bossID]
     * @param  integer $type [类型1公共2个人]
     * * @param  [type]  $uid  [用户ID]
     * @return [type]        [description]
     */
    public static function getTeams($boss, $type = 1, $uid = 0)
    {
        if ($type) {
            if ($type == 1) {
                // 公共作业
                $where = [
                    ['boss', '=', $boss],
                    ['stage', '=', 5],
                ];
            }
            if ($type == 2) {
               
            }
            if ($type == 3) {
                // D面作业
                $where = [
                    ['boss', '=', $boss],
                    ['stage', '=', 5],
                ];
            }
            if ($type == 4) {
                // B面作业
                $where = [
                    ['boss', '=', $boss],
                    ['stage', '=', 2],
                ];
            }
        } else {
             // 个人作业
            $where = [
                ['boss', '=', $boss],
                ['uid', '=', $uid],
            ];
        }

        $data = self::getPublicTeams($where, $type);

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

    private static function getPublicTeams($where, $modelType = 1)
    {
        $modelMap = [
            0 => \App\Models\UserTeam::class, // 个人作业
            1 => \App\Models\Team::class, // 公共自动作业
            2 => \App\Models\Team::class, // 公共手动作业
            3 => \App\Models\Team::class, // D面作业
            4 => \App\Models\Team::class, // B面作业
        ];

        $data = buildWhere($modelMap[$modelType], $where)->with(['teamRoles' => function ($query) {
            $query->join('roles', function($join) {
                $join->on('roles.role_id', '=', 'team_roles.role_id');
            })->select(DB::raw('CASE WHEN roles.is_6 = 1 THEN roles.role_id_6 ELSE roles.role_id_3 END as image_id, roles.role_id, team_roles.team_id, team_roles.status'))->orderBy('roles.search_area_width', 'DESC');
        }])
        ->where('status', 1)
        ->whereYear('created_at', Carbon::now()->year)
        ->whereMonth('created_at', Carbon::now()->month)
        ->orderBy('id')
        ->get()
        ->toArray();

        return $data;
    }



    private static function filterTeamCompositions($groups, &$bossMap = [], $accountId = 0, $atkType = 0, $lockedIds = [], $hiddenIds = [])
    {
        if ($accountId) {
            $userBox = Account::where('id', $accountId)->value('roles');
            if ($userBox) {
                $userBox = explode(',', $userBox);
            }
        } else {
            $userBox = [];
        }

        $successNum = 10;
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

        foreach ($groups as $key => &$teams) {
            $roleStatus     = [];
            $teamBossMap    = [];
            $continueSwitch = false;
            $teamIds = array_column($teams, 'id');

            if ($lockedIds) { // 锁定作业ID
                if (count(array_intersect($teamIds, $lockedIds)) < count($lockedIds)) {
                    unset($groups[$key]);
                    $failNum++;
                    continue;
                }
            }

            if ($hiddenIds) { // 隐藏作业ID
                if (array_intersect($teamIds, $hiddenIds)) {
                    unset($groups[$key]);
                    $failNum++;
                    continue;
                }
            }

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
                unset($groups[$key]);
                $failNum++;
                continue;
            }


            foreach ($teams as $k => &$teamInfo) {
                $teamInfo['borrow'] = 0; // 0:默认不借人
                if ($accountId) { // 筛选角色 每队最多只能缺一个角色
                    if (count(array_diff(array_keys($teamInfo['roles']), $userBox)) > 1) { // 每队最多只能缺一个角色
                        $continueSwitch = true;
                        break;
                    } else {
                        $teamInfo['roles'] = self::presentRoles($teamInfo['roles'], $userBox); 
                    }
                }
            }
            unset($teamInfo);

            if ($continueSwitch) {
                unset($groups[$key]);
                $failNum++;
                continue;
            }

            // 借人数量判断
            $is_ok = self::checkStatusOneUnique($teams);
            if (!$is_ok) {
                unset($groups[$key]);
                $failNum++;
                continue;
            }

            if (($key - $failNum) >= $successNum) { // 实际比successNum多一个
                break;
            }
        }
        unset($teams);

        if (count($groups) > $successNum) {
            $groups = array_slice($groups, 0, $successNum);
        }

        return $groups;
    }

        /**
     * 转换成前端展示的结构
     * [ ['role_id'=>123, 'status'=>1], ... ]
     */
    private static function presentRoles(array $rolesMap, array $userBox): array
    {
        foreach ($rolesMap as $role => &$status) {
            $status = in_array($role, $userBox) ? 1 : 0;
        }
        return $rolesMap;
    }

    /**
     * [firstDayHomework 首日B+D分刀套餐]
     * @param  [type]  $uid       [用户ID]
     * @param  array   $bossMap   [bossID数组]
     * @param  integer $type      [类型0手动分刀1自动分刀]
     * @param  integer $accountId [账号ID]
     * @param  integer $atkType   [作业类型0不限制1物理三刀2物理两刀3魔法两刀4魔法三刀]
     * @param  array   $lockedIds [锁定作业ID]
     * @param  array   $hiddenIds [隐藏作业ID]
     * @return [type]             [description]
     */
    public static function firstDayHomework($uid, $bossMapB = [], $bossMapD = [], $type = 0, $accountId = 0, $atkType = 0, $lockedIdsB = [], $lockedIdsD = [], $hiddenIds = [])
    {
        if ($type == 1) {
            $cacheKeyD = 'autoTeams';
        }
        if ($type == 2) {
            $cacheKeyD = 'handTeams';
        }
        $cacheKeyB = 'bTeams';

        $whereB = [
            ['auto', '=', 1],
            ['stage', 'in', [2, 3]],
        ];

        $whereD = [
            ['auto', '=', $type],
            ['stage', '=', 5],
        ];

        $dataB = self::getPublicTeams($whereB, 1);
        $dataD = self::getPublicTeams($whereD, 1);

        $makeArr = false;
        // $makeArr = true;
        if ($cacheKeyD) {
            $oldDataJsonB = Cache::get($cacheKeyB);
            $oldDataJsonD = Cache::get($cacheKeyD);
            if (json_encode($dataB) != $oldDataJsonB || json_encode($dataD) != $oldDataJsonD) {
                $makeArr = true;
                Cache::put($cacheKeyB, json_encode($dataB));
                Cache::put($cacheKeyD, json_encode($dataD));
            }
        }

        if ($cacheKeyD) {
            if ($makeArr) {
                $teamsResB = self::makeTeams($dataB);
                Cache::put($cacheKeyB . 'makeTeams', $teamsResB);
                $teamsResD = self::makeTeams($dataD);
                Cache::put($cacheKeyD . 'makeTeams', $teamsResD);
            } else {
                $teamsResB = Cache::get($cacheKeyB . 'makeTeams');
                $teamsResD = Cache::get($cacheKeyD . 'makeTeams');
            }
        } else {
            // $data_huawu = Cache::get('data_huawu');
            // $data_huawu = 0;
            // if (empty($data_huawu)) {
            //     $data_huawu = team::where(['uid' => 0, 'status' => 1, 'stage' => 5])
            //                     ->whereYear('created_at', Carbon::now()->year)
            //                     ->whereMonth('created_at', Carbon::now()->month)
            //                     ->orderBy('id')
            //                     ->get();
            //     // Cache::put('data_huawu', $data_huawu, 600);
            // }

            // $data_huawu_new = [];
            // foreach ($data_huawu as $key => $value) {
            //     $data_huawu_new[$value->id] = $value->link ?? '';
            // }

            // // 替换link数据
            // foreach ($data as $key => $value) {
            //     if ($value['otid'] && $data_huawu_new[$value['otid']]) {
            //         $data[$key]['link'] = $data_huawu_new[$value['otid']];
            //     }
            // }
            // $teamsRes = self::makeTeams($data);
        }

        $teamsResB = self::filterTeamCompositions($teamsResB, $bossMapB, $accountId,        0, $lockedIdsB, $hiddenIds);
        $teamsResD = self::filterTeamCompositions($teamsResD, $bossMapD, $accountId, $atkType, $lockedIdsD, $hiddenIds);

        $res       = self::matchArraysAllStructuredFull($teamsResB, $teamsResD);
        $data      = self::getPublicTeams([]);

        $result    = [];
        foreach ($data as $item) {
            if (isset($item['id'])) {
                $result[$item['id']] = $item;
            }
        }

        $res = array_slice($res, 0, 10);

        foreach ($res as &$groups) {
            foreach ($groups as &$group) {
                $group = self::resolveBorrows($group);
                if (is_array($group)) {
                    foreach ($group as &$teams) {
                        foreach ($teams as &$team) {
                            $team['roles']  = self::addDisplayId($team['roles'], $result[$team['id']]['team_roles']);
                            $team['remark'] = $result[$team['id']]['remark'];
                            $team['stage']  = $result[$team['id']]['stage'];
                            $team['link']   = json_decode($result[$team['id']]['link'], 1);
                            if ($team['link']) {
                                foreach ($team['link'] as $k => $link) {
                                    $team['link'][$k]['image'] = json_encode($link['image']);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $res;
    }


    /**
     * Resolves borrow assignments for teams to minimize conflicts in role_id across comparable teams.
     * @param array $pairs Array of 3 pairs, each containing 2 teams with roles, and borrow fields.
     * @return array|bool Updated pairs with borrow assignments, or false if impossible.
     */
    public static function resolveBorrows(array $pairs) {
        if (count($pairs) !== 3 || !self::isValidPairs($pairs)) {
            return false;
        }

        $teams = [];
        foreach ($pairs as $pair) {
            foreach ($pair as $team) {
                $teams[] = $team;
            }
        }
        $n = count($teams);

        // Define comparison map (exclude same-pair conflicts: 0↔1, 2↔3, 4↔5)
        $compareMap = [
            0 => [2,3,4,5], // B0 compares with B1,D1,B2,D2
            1 => [2,3,4,5], // D0 compares with B1,D1,B2,D2
            2 => [0,1,4,5], // B1 compares with B0,D0,B2,D2
            3 => [0,1,4,5], // D1 compares with B0,D0,B2,D2
            4 => [0,1,2,3], // B2 compares with B0,D0,B1,D1
            5 => [0,1,2,3], // D2 compares with B0,D0,B1,D1
        ];

        $locked = array_fill(0, $n, false);
        for ($i = 0; $i < $n; $i++) {
            if (!isset($teams[$i]['borrow'])) {
                $teams[$i]['borrow'] = 0;
            }
            if (!empty($teams[$i]['roles']) && is_array($teams[$i]['roles'])) {
                // foreach ($teams[$i]['roles'] as $r) {
                //     if (isset($r['status']) && intval($r['status']) === 0) {
                //         $teams[$i]['borrow'] = intval($r['role_id']);
                //         $locked[$i] = true;
                //         break;
                //     }
                // }
                foreach ($teams[$i]['roles'] as $rid => $status) {
                    if (intval($status) === 0) {
                        $teams[$i]['borrow'] = intval($rid);
                        $locked[$i] = true;
                        break;
                    }
                }
            }
            if ($teams[$i]['borrow'] != 0) {
                $locked[$i] = true;
            }
        }

        $roleMap = [];
        for ($i = 0; $i < $n; $i++) {
            if (empty($teams[$i]['roles']) || !is_array($teams[$i]['roles'])) {
                continue;
            }
            // foreach ($teams[$i]['roles'] as $r) {
            //     if (!isset($r['role_id']) || !isset($r['status'])) {
            //         continue;
            //     }
            //     $rid = intval($r['role_id']);
            //     $status = intval($r['status']);
            //     if ($status !== 1) {
            //         continue;
            //     }
            //     if (!isset($roleMap[$rid])) {
            //         $roleMap[$rid] = [];
            //     }
            //     if (!in_array($i, $roleMap[$rid], true)) {
            //         $roleMap[$rid][] = $i;
            //     }
            // }
            foreach ($teams[$i]['roles'] as $rid => $status) {
                $rid = intval($rid);
                $status = intval($status);
                if ($status !== 1) {
                    continue;
                }
                if (!isset($roleMap[$rid])) {
                    $roleMap[$rid] = [];
                }
                if (!in_array($i, $roleMap[$rid], true)) {
                    $roleMap[$rid][] = $i;
                }
            }
        }

        // Filter roleMap: for each role_id, keep only one team per pair (min index)
        foreach ($roleMap as $rid => &$indices) {
            $groups = [];
            foreach ($indices as $i) {
                $pair_id = (int)($i / 2); // Pair 0: 0-1, Pair 1: 2-3, Pair 2: 4-5
                if (!isset($groups[$pair_id])) {
                    $groups[$pair_id] = [];
                }
                $groups[$pair_id][] = $i;
            }
            $new_indices = [];
            foreach ($groups as $group) {
                sort($group); // Keep smallest index in pair
                $new_indices[] = $group[0];
            }
            $indices = array_values(array_unique($new_indices));
        }

        $components = [];
        foreach ($roleMap as $rid => $indices) {
            if (count($indices) <= 1) {
                continue;
            }
            $indices = array_values(array_unique($indices));
            $adj = array_fill_keys($indices, []);
            foreach ($indices as $i) {
                foreach ($indices as $j) {
                    if ($i === $j) {
                        continue;
                    }
                    if (in_array($j, $compareMap[$i], true)) {
                        $adj[$i][] = $j;
                    }
                }
            }
            $visited = [];
            foreach ($indices as $start) {
                if (isset($visited[$start])) {
                    continue;
                }
                $comp = [];
                $stack = [$start];
                $visited[$start] = true;
                while (!empty($stack)) {
                    $u = array_pop($stack);
                    $comp[] = $u;
                    foreach ($adj[$u] as $v) {
                        if (!isset($visited[$v])) {
                            $visited[$v] = true;
                            $stack[] = $v;
                        }
                    }
                }
                if (count($comp) > 1) {
                    sort($comp);
                    $components[] = ['rid' => $rid, 'indices' => $comp];
                }
            }
        }

        if (empty($components)) {
            return self::rebuildPairs($pairs, $teams);
        }

        $compInfos = [];
        foreach ($components as $comp) {
            $rid = $comp['rid'];
            $indices = $comp['indices'];
            $size = count($indices);
            $preAssigned = 0;
            foreach ($indices as $idx) {
                if ($teams[$idx]['borrow'] == $rid) {
                    $preAssigned++;
                }
            }
            $need = max(0, $size - 1 - $preAssigned);
            if ($need === 0) {
                continue;
            }
            $candidates = [];
            foreach ($indices as $idx) {
                if ($teams[$i]['borrow'] == 0 && !$locked[$idx]) {
                    $candidates[] = $idx;
                }
            }
            if (count($candidates) < $need) {
                return false;
            }
            $compInfos[] = [
                'rid' => $rid,
                'indices' => $indices,
                'need' => $need,
                'candidates' => $candidates,
            ];
        }

        if (empty($compInfos)) {
            return self::rebuildPairs($pairs, $teams);
        }

        usort($compInfos, function($a, $b) {
            return $b['need'] <=> $a['need'];
        });

        $combCache = [];
        $combinations = function(array $arr, int $k) use (&$combCache) {
            $key = md5(json_encode($arr) . '|' . $k);
            if (isset($combCache[$key])) {
                return $combCache[$key];
            }
            $res = [];
            $n = count($arr);
            if ($k === 0) {
                return [[]];
            }
            if ($k > $n) {
                return [];
            }
            $generate = function($start, $k, $path) use (&$generate, $arr, $n, &$res) {
                if ($k === 0) {
                    $res[] = array_map(function($p) use ($arr) { return $arr[$p]; }, $path);
                    return;
                }
                for ($i = $start; $i <= $n - $k; $i++) {
                    $generate($i + 1, $k - 1, array_merge($path, [$i]));
                }
            };
            $generate(0, $k, []);
            $combCache[$key] = $res;
            return $res;
        };

        $found = false;
        $assign = [];
        $usedIdx = [];
        $dfs = function($pos) use (&$dfs, &$found, &$assign, &$usedIdx, $compInfos, $teams, $locked, $combinations) {
            if ($found) return;
            if ($pos >= count($compInfos)) {
                $valid = true;
                foreach ($compInfos as $comp) {
                    $rid = $comp['rid'];
                    $indices = $comp['indices'];
                    $need = $comp['need'];
                    $assigned = 0;
                    foreach ($indices as $idx) {
                        if (isset($assign[$idx]) && $assign[$idx] == $rid) {
                            $assigned++;
                        }
                    }
                    if ($assigned < $need) {
                        $valid = false;
                        break;
                    }
                }
                if ($valid) {
                    $found = true;
                }
                return;
            }
            $comp = $compInfos[$pos];
            $rid = $comp['rid'];
            $need = $comp['need'];
            if ($need === 0) {
                $dfs($pos + 1);
                return;
            }
            $candidates = [];
            foreach ($comp['candidates'] as $idx) {
                if (!isset($usedIdx[$idx])) {
                    $candidates[] = $idx;
                }
            }
            if (count($candidates) < $need) {
                return;
            }
            $combos = $combinations($candidates, $need);
            foreach ($combos as $combo) {
                foreach ($combo as $idx) {
                    $assign[$idx] = $rid;
                    $usedIdx[$idx] = true;
                }
                $dfs($pos + 1);
                if ($found) return;
                foreach ($combo as $idx) {
                    unset($assign[$idx]);
                    unset($usedIdx[$idx]);
                }
            }
        };

        $dfs(0);

        if (!$found) {
            return false;
        }

        foreach ($assign as $idx => $rid) {
            if ($teams[$idx]['borrow'] == 0 && !$locked[$idx]) {
                $teams[$idx]['borrow'] = $rid;
            }
        }

        return self::rebuildPairs($pairs, $teams);
    }

    private static function isValidPairs(array $pairs): bool {
        if (count($pairs) !== 3) {
            return false;
        }
        foreach ($pairs as $pair) {
            if (count($pair) !== 2) {
                return false;
            }
            foreach ($pair as $team) {
                if (!is_array($team) || !isset($team['roles']) || !is_array($team['roles'])) {
                    return false;
                }
            }
        }
        return true;
    }

    private static function rebuildPairs(array $originalPairs, array $teams): array {
        $out = [];
        $cursor = 0;
        foreach ($originalPairs as $pair) {
            $row = [];
            foreach ($pair as $team) {
                $updated = $team;
                $updated['borrow'] = $teams[$cursor]['borrow'];
                $row[] = $updated;
                $cursor++;
            }
            $out[] = $row;
        }
        return $out;
    }

    // 主匹配方法
    public static function matchArraysAllStructuredFull($B, $D)
    {
        $result = [];

        foreach ($B as $bItem) {
            foreach ($D as $dItem) {

                // 1️⃣ 计算匹配分数矩阵
                $scores = [];
                foreach ($bItem as $bi => $bChild) {
                    $bIds = array_column($bChild['roles'], 'role_id'); // 取 role_id 列表
                    foreach ($dItem as $di => $dChild) {
                        $dIds = array_column($dChild['roles'], 'role_id');
                        $scores[$bi][$di] = count(array_intersect($bIds, $dIds));
                    }
                }

                // 2️⃣ 生成 D 子元素的全排列
                $dIndexes = range(0, count($dItem) - 1);
                $permutations = self::permute($dIndexes);

                // 3️⃣ 找出总分数最高的所有匹配
                $maxScore = -1;
                $bestMatches = [];
                foreach ($permutations as $perm) {
                    $score = 0;
                    foreach ($perm as $bi => $di) {
                        $score += $scores[$bi][$di];
                    }

                    if ($score > $maxScore) {
                        $maxScore = $score;
                        $bestMatches = [$perm];
                    } elseif ($score === $maxScore) {
                        $bestMatches[] = $perm;
                    }
                }

                // 4️⃣ 按最高匹配组合生成输出，保留所有原字段
                $pairResults = [];
                foreach ($bestMatches as $bestMatch) {
                    $pairResult = [];
                    foreach ($bestMatch as $bi => $di) {
                        // 深拷贝元素，保留所有字段
                        $bCopy = $bItem[$bi];
                        $dCopy = $dItem[$di];

                        $pairResult[] = [
                            $bCopy,
                            $dCopy,
                        ];
                    }
                    $pairResults[] = $pairResult;
                }

                $result[] = $pairResults;
            }
        }

        return $result;
    }


    // 生成全排列
    public static function permute($items, $perms = [])
    {
        if (empty($items)) {
            return [$perms];
        }

        $result = [];
        foreach ($items as $i => $item) {
            $newItems = $items;
            unset($newItems[$i]);
            $result = array_merge($result, self::permute($newItems, array_merge($perms, [$item])));
        }
        return $result;
    }





    /**
     * [getTeamGroups 获取推荐分刀作业]
     * @param  [type]  $uid       [用户ID]
     * @param  array   $bossMap   [bossID数组]
     * @param  integer $type      [分刀类型0自定义刀1自动刀2手动刀]
     * @param  integer $accountId [账号ID]
     * @param  integer $atkType   [攻击类型]
     * @return [type]             [结果数据]
     */
    public static function getTeamGroups($uid, $bossMap = [], $type = 0, $accountId = 0, $atkType = 0, $lockedIds = [], $hiddenIds = [])
    {
        if ($type) {
            if ($type == 1) {
                $cacheKey = 'autoTeams';
                $where = [
                    ['auto', '=', $type],
                    ['stage', '=', 5],
                ];
            }
            if ($type == 2) {
                $cacheKey = 'handTeams';
                $where = [
                    ['auto', '=', $type],
                    ['stage', '=', 5],
                ];
            }
        } else {
            $cacheKey = '';
            $where = ['uid' => $uid];
        }

        $data = self::getPublicTeams($where, $type);

        $makeArr = false;
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
            $data_huawu = 0;
            if (empty($data_huawu)) {
                $data_huawu = team::where(['uid' => 0, 'status' => 1, 'stage' => 5])
                                ->whereYear('created_at', Carbon::now()->year)
                                ->whereMonth('created_at', Carbon::now()->month)
                                ->orderBy('id')
                                ->get();
                // Cache::put('data_huawu', $data_huawu, 600);
            }

            $data_huawu_new = [];
            foreach ($data_huawu as $key => $value) {
                $data_huawu_new[$value->id] = $value->link ?? '';
            }

            // 替换link数据
            foreach ($data as $key => $value) {
                if ($value['otid'] && $data_huawu_new[$value['otid']]) {
                    $data[$key]['link'] = $data_huawu_new[$value['otid']];
                }
            }
            $teamsRes = self::makeTeams($data);
        }

        $teamsRes = self::filterTeamCompositions($teamsRes, $bossMap, $accountId, $atkType, $lockedIds, $hiddenIds);

        $result = [];
        foreach ($data as $item) {
            if (isset($item['id'])) {
                $result[$item['id']] = $item;
            }
        }

        foreach ($teamsRes as &$teams) {
            $teams = self::assignBorrow($teams);
            foreach ($teams as &$teamInfo) {
                $teamInfo['roles']  = self::addDisplayId($teamInfo['roles'], $result[$teamInfo['id']]['team_roles']);
                $teamInfo['remark'] = $result[$teamInfo['id']]['remark'];
                $teamInfo['stage']  = $result[$teamInfo['id']]['stage'];
                $teamInfo['link']   = json_decode($result[$teamInfo['id']]['link'], 1);
                if ($teamInfo['link']) {
                    foreach ($teamInfo['link'] as $k => $link) {
                        $teamInfo['link'][$k]['image'] = json_encode($link['image']);
                    }
                }
            }
            unset($teamInfo);
        }
        unset($teams);

        // 用户分刀数据记录
        self::dataFightLog($uid, $type, $bossMap);
        return $teamsRes;
    }

    private static function addDisplayId($resData, $rawData)
    {
        foreach ($rawData as &$roleInfo) {
            $roleInfo['status'] = $resData[$roleInfo['role_id']];
            unset($roleInfo['team_id']);
        }
        unset($roleInfo);
        return $rawData;
    }

    public static function matchArraysAllStructured($B, $D)
    {
        $result = [];

        foreach ($B as $bItem) {
            foreach ($D as $dItem) {

                // 1️⃣ 计算匹配分数矩阵
                $scores = [];
                foreach ($bItem as $bi => $bChild) {
                    $bIds = array_column($bChild['roles'], 'role_id');
                    foreach ($dItem as $di => $dChild) {
                        $dIds = array_column($dChild['roles'], 'role_id');
                        $scores[$bi][$di] = count(array_intersect($bIds, $dIds));
                    }
                }

                // 2️⃣ 生成 D 子元素的全排列
                $dIndexes = [0, 1, 2];
                $permutations = self::permute($dIndexes);

                // 3️⃣ 找出总分数最高的所有匹配
                $maxScore = -1;
                $bestMatches = [];
                foreach ($permutations as $perm) {
                    $score = 0;
                    foreach ($perm as $bi => $di) {
                        $score += $scores[$bi][$di];
                    }

                    if ($score > $maxScore) {
                        $maxScore = $score;
                        $bestMatches = [$perm];
                    } elseif ($score === $maxScore) {
                        $bestMatches[] = $perm;
                    }
                }

                // 4️⃣ 按所有最高匹配组合生成输出，包一层数组
                $pairResults = [];
                foreach ($bestMatches as $bestMatch) {
                    $pairResult = [];
                    foreach ($bestMatch as $bi => $di) {
                        $pairResult[] = [
                            [
                                'roles' => $bItem[$bi]['roles'],
                                'borrow' => $bItem[$bi]['borrow'],
                            ],
                            [
                                'roles' => $dItem[$di]['roles'],
                                'borrow' => $dItem[$di]['borrow'],
                            ],
                        ];
                    }
                    $pairResults[] = $pairResult; // 每个最高相似度排列
                }

                // 将同一个 B&D 元素组合的所有子组合包一层
                $result[] = $pairResults;
            }
        }

        return $result;
    }

    private static function checkStatusOneUnique(array $teams): bool
    {
        // 校验输入
        if (count($teams) !== 3) {
            return false;
        }

        // 预处理每组数据，提取 status=1 的角色 和 status=0 的数量
        $teamData = array_map(function ($team) {
            $activeRoles = [];
            $inactiveCount = 0;
            // foreach ($team['roles'] as $role) {
            //     if ($role['status']) {
            //         $activeRoles[] = $role['role_id'];
            //     } else {
            //         $inactiveCount++;
            //     }
            // }
            foreach ($team['roles'] as $role => $status) {
                if ($status) {
                    $activeRoles[] = $role;
                } else {
                    $inactiveCount++;
                }
            }
            return ['active' => $activeRoles, 'inactive' => $inactiveCount];
        }, $teams);

        // 两两比较
        for ($i = 0; $i < 2; $i++) {
            for ($j = $i + 1; $j < 3; $j++) {
                $sameNum = count(array_intersect($teamData[$i]['active'], $teamData[$j]['active']));
                $totalInactive = $teamData[$i]['inactive'] + $teamData[$j]['inactive'];

                if ($totalInactive + $sameNum > 2) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * [assignBorrow 确定推荐借用角色]
     * @param  array  $groups [分组后数据]
     * @return [type]          [补全借用角色的数据]
     */
    private static function assignBorrow(array $groups)
    {
        // 验证输入
        if (count($groups) !== 3) {
            return $groups;
        }

        foreach ($groups as &$group) {
            $group['borrow'] = 0;
        }
        unset($group);

        // 先找出有 status=0 的组并直接设置 borrow
        $statusZeroGroups = [];
        foreach ($groups as $i => &$group) {
            foreach ($group['roles'] as $role => $status) {
                if ($status == 0) {
                    $group['borrow'] = $role;
                    $statusZeroGroups[$i] = true;
                    break;
                }
            }
        }
        unset($group);

        // 构建每两组之间的交集
        $intersections = [];
        for ($i = 0; $i < 3; $i++) {
            for ($j = $i + 1; $j < 3; $j++) {
                $idsI = array_keys($groups[$i]['roles']);
                $idsJ = array_keys($groups[$j]['roles']);
                $intersections[$i][$j] = $intersections[$j][$i] = array_intersect($idsI, $idsJ);
            }
        }

        // 封装一个分配函数，减少重复逻辑
        $assignPair = function ($a, $b) use (&$groups, &$intersections) {
            if (!empty($groups[$a]['borrow']) && !empty($groups[$b]['borrow'])) return;

            $sameRoles = $intersections[$a][$b];
            if (empty($sameRoles)) return;

            if (!empty($groups[$a]['borrow']) && empty($groups[$b]['borrow'])) {
                if (($key = array_search($groups[$a]['borrow'], $sameRoles)) !== false) {
                    unset($sameRoles[$key]);
                }
                if (!empty($sameRoles)) {
                    $groups[$b]['borrow'] = reset($sameRoles);
                }
            } elseif (empty($groups[$a]['borrow']) && !empty($groups[$b]['borrow'])) {
                if (($key = array_search($groups[$b]['borrow'], $sameRoles)) !== false) {
                    unset($sameRoles[$key]);
                }
                if (!empty($sameRoles)) {
                    $groups[$a]['borrow'] = reset($sameRoles);
                }
            } elseif (empty($groups[$a]['borrow']) && empty($groups[$b]['borrow'])) {
                $groups[$a]['borrow'] = reset($sameRoles);
                $groups[$b]['borrow'] = next($sameRoles);
            }
        };

        // 判断是否有共享两个角色的组合
        $hasTwoShared = false;
        $pair = [];
        for ($i = 0; $i < 2; $i++) {
            for ($j = $i + 1; $j < 3; $j++) {
                if (count($intersections[$i][$j]) == 2) {
                    $hasTwoShared = true;
                    $pair = [$i, $j];
                    break 2;
                }
            }
        }

        if ($hasTwoShared) {
            // 找到第三个组
            $k = 3 - $pair[0] - $pair[1];
            $assignPair($pair[0], $pair[1]);
            $assignPair($pair[0], $k);
            $assignPair($pair[1], $k);
        } elseif (!empty($statusZeroGroups)) {
            // 有 status=0 的组
            $key = array_keys($statusZeroGroups)[0];
            $others = array_diff([0, 1, 2], [$key]);
            $others = array_values($others);
            $assignPair($key, $others[0]);
            $assignPair($key, $others[1]);
            $assignPair($others[0], $others[1]);
        } else {
            // 普通情况
            $assignPair(0, 1);
            $assignPair(0, 2);
            $assignPair(1, 2);
        }

        return $groups;
    }


    /**
     * [borrowRoles 确定推荐借用角色](废弃)
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
        $roleMapB = [];
        foreach ($teamA['team_roles'] as $key => $role) {
            if ($role['status'] == 0) {
                $limitNum--;
            } else {
                $roleMapA[] = $role['role_id'];
            }
        }
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
        $n            = count($data);
        $teamsRes     = [];
        $invalidPairs = [];

        for ($a = 0; $a < $n - 2; $a++) { 
            for ($b = $a + 1; $b < $n - 1; $b++) { 
                if (self::hasInvalidPair([$a], $b, $invalidPairs, $data)) continue;
                for ($c = $b + 1; $c < $n; $c++) { 
                    if (self::hasInvalidPair([$a, $b], $c, $invalidPairs, $data)) continue;

                    // 先用紧凑的 roles 映射来判断
                    $rolesA = self::makeRolesMap(array_column($data[$a]['team_roles'], 'role_id'));
                    $rolesB = self::makeRolesMap(array_column($data[$b]['team_roles'], 'role_id'));
                    $rolesC = self::makeRolesMap(array_column($data[$c]['team_roles'], 'role_id'));

                    $bool = self::checkRoleOverlapLimit([$rolesA, $rolesB, $rolesC]);
                    if ($bool) {
                        $teamsRes[] = [
                            ['id' => $data[$a]['id'], 'boss' => $data[$a]['boss'], 'score' => $data[$a]['score'], 'atk_value' => $data[$a]['atk_value'], 'roles' => $rolesA],
                            ['id' => $data[$b]['id'], 'boss' => $data[$b]['boss'], 'score' => $data[$b]['score'], 'atk_value' => $data[$b]['atk_value'], 'roles' => $rolesB],
                            ['id' => $data[$c]['id'], 'boss' => $data[$c]['boss'], 'score' => $data[$c]['score'], 'atk_value' => $data[$c]['atk_value'], 'roles' => $rolesC]
                        ];
                    }
                }
            }
        }
        return self::sortTeams($teamsRes);
    }

    /**
     * 内部存储的紧凑映射
     * [ role_id => 1 ]
     */
    private static function makeRolesMap(array $roles = []): array
    {
        $res = [];
        foreach ($roles as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $res[$id] = 1;
            }
        }
        return $res;
    }

    // 检查任意两两组合是否非法
    private static function hasInvalidPair($group, $last, &$invalidPairs, $data) {
        $count = count($group);
        for ($i = 0; $i < $count; $i++) {
            $a = $data[$group[$i]]['id'];
            $b = $data[$last]['id'];

            // 已经标记过非法
            if (isset($invalidPairs["$a,$b"]) || isset($invalidPairs["$b,$a"])) {
                return true;
            }

            // 业务规则判断
            if (self::checkSameRoleNum($data[$group[$i]], $data[$last])) {
                $invalidPairs["$a,$b"] = true; // 直接记录
                return true;
            }
        }
        return false;
    }

    /**
     * [checkRoleOverlapLimit 借人数量判断 最多可以借3个角色 如果有三个相同角色 那么需要借两个角色 如果有两个相同角色 那么需要借一个角色]
     * @param  array  $group [description]
     * @return [bool]        [借人大于3个返回false(不符合)借人小于3个返回true(符合)]
     */
    private static function checkRoleOverlapLimit($group = [])
    {
        $roles = [];
        foreach ($group as $team) {
            $roles = array_merge($roles, $team);
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
        $multiplierRates = self::MULTIPLIER_RATES;

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
