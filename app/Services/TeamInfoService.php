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
    public static function getTeams($boss = 1, $type = 1, $uid = 0, $status = 1, $stage = 5, $atk = 0, $auto = 0)
    {
        $where = [];
        if ($boss) {
            $where[] = ['boss', '=', $boss];
        }
        if (!is_null($uid)) {
            if ($type == 0) {
                $where[] = ['uid', '=', $uid];
            }
            if ($type == 1) {
                $where[] = ['uid', '=', 0];
            }
        }
        if ($status === 0 || $status === 1) {
            $where[] = ['status', '=', $status];
        }
        if ($stage) {
            $where[] = ['stage', '=', $stage];
        }
        if ($atk) {
            if ($atk > 0) {
                $where[] = ['atk_value', '>', 0];
            } else {
                $where[] = ['atk_value', '<', 0];
            }
        }
        if ($auto) {
            $where[] = ['auto', '=', $auto];
        }

        $data = self::getPublicTeams($where, $type);

        $rolesMap = DB::table('roles')->select(DB::raw('CASE WHEN `is_6` = 1 THEN `role_id_6` ELSE `role_id_3` END as `image_id`, `id`, `atk_type`, `search_area_width`'))->get()->keyBy('id')->toArray();

        $data = self::mergeTeamRolesByRef($data, $rolesMap);

        if ($data) {
            usort($data, function($a, $b) { // 对作业进行排序 尽量保证相似阵容的作业在一起
                // 逐个比较 role_id
                for ($i = 4; $i >= 0; $i--) {
                    if (isset($a['team'][$i]['role_id']) && isset($b['team'][$i]['role_id'])) {
                        $comparison = $a['team'][$i]['role_id'] <=> $b['team'][$i]['role_id'];
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
            foreach ($teamInfo['team'] as $k => $roleInfo) {
                if ($type == 1 || $type == 3) { // 公共作业全展示
                    $data[$key]['team'][$k]['status'] = 1;
                }
            }
        }
        if ($type == 1) {
            $otherTeamInfo = Team::where(['uid' => $uid, 'status' => 1])
                    ->whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->orderBy('id')
                    ->get();
            $otherTeamInfo = $otherTeamInfo ? $otherTeamInfo->toArray() : [];
            $userTeamInfo = [];
            foreach ($otherTeamInfo as $key => $teamInfo) {
                $statusRoleId = 0;
                foreach ($teamInfo['team'] as $k => $roles) {
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
                        foreach ($teamInfo['team'] as $k => $roleInfo) {
                            if ($roleInfo['role_id'] == $userTeamInfo[$teamInfo['id']]) {
                                $data[$key]['team'][$k]['status'] = 0;
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
     * 为每条 team 数据的角色添加 image_id，并按 search_area_width 降序排序
     *
     * @param array $teams 原始团队数据（每条记录包含 team 数组）
     * @param array $refData 参照数据（以 role_id 为键，包含 image_id 和 search_area_width）
     * @return array 处理后的团队数据
     */
    private static function mergeTeamRolesByRef(array $teams, array $refData): array
    {
        foreach ($teams as &$teamInfo) {
            if (!isset($teamInfo['team']) || !is_array($teamInfo['team'])) {
                continue;
            }

            // 遍历每个 team 的角色
            foreach ($teamInfo['team'] as &$member) {
                $roleId = $member['role_id'] ?? null;

                if ($roleId && isset($refData[$roleId])) {
                    $ref = $refData[$roleId];
                    $member['image_id'] = $ref->image_id ?? null;
                    $member['search_area_width'] = $ref->search_area_width ?? 0;
                } else {
                    $member['image_id'] = null;
                    $member['search_area_width'] = 0;
                }
            }
            unset($member);

            // 按 search_area_width 降序排序
            usort($teamInfo['team'], function ($a, $b) {
                return ($b['search_area_width'] ?? 0) <=> ($a['search_area_width'] ?? 0);
            });
        }
        unset($teamInfo);

        return $teams;
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

        $teamInfo = Team::where(['uid' => $uid, 'otid' => $teamId])->first();

        $team = $otherTeamInfo['team']; // 作业中角色阵容

        if ($roleId) { // 缺少某个角色
            foreach ($team as &$roleInfo) {
                if ($roleInfo['role_id'] == $roleId) {
                    $roleInfo['status'] = 0;
                }
            }
            unset($roleInfo);
        }
        
        if ($teamInfo) { // 修改
            Team::where(['uid' => $uid, 'otid' => $teamId])->update(['status' => 1, 'team' => json_encode($team), 'updated_at' => Carbon::now()]);
            return true;
        }

        // 添加
        $insert = ['uid' => $uid, 'otid' => $teamId, 'created_at' => Carbon::now()];
        foreach ($otherTeamInfo as $key => $value) {
            if (!in_array($key, ['id', 'otid', 'created_at', 'updated_at', 'open', 'uid'])) {
                $insert[$key] = $value;
            }
            if (in_array($key, ['link'])) {
                $insert[$key] = json_encode($value);
            }
        }
        $insert['team'] = json_encode($team);
        $utid = Team::insertGetId($insert);
        if (!$utid) {
            return false;
        }

        return true;
    }

    private static function getPublicTeams($where, $modelType = 1, $lastMonth = 0)
    {
        $modelMap = [
            0 => \App\Models\Team::class, // 个人作业
            1 => \App\Models\Team::class, // 公共自动作业
            2 => \App\Models\Team::class, // 公共手动作业
            3 => \App\Models\Team::class, // D面作业
            4 => \App\Models\Team::class, // B面作业
        ];

        // $data = buildWhere($modelMap[$modelType], $where)
        // ->whereYear('created_at', Carbon::now()->year)
        // ->whereMonth('created_at', Carbon::now()->month)
        // ->orderBy('id')
        // ->get()
        // ->toArray();

        $applyMonthFilter = function ($query, $lastMonth = 'current') {
            if ($lastMonth === 0) {
                $query->whereMonth('created_at', Carbon::now()->month)
                      ->whereYear('created_at', Carbon::now()->year);
            } elseif ($lastMonth === 1) {
                $lastMonth = Carbon::now()->subMonth();
                $query->whereMonth('created_at', $lastMonth->month)
                      ->whereYear('created_at', $lastMonth->year);
            }
            return $query;
        };

        $data = $applyMonthFilter(
                    buildWhere($modelMap[$modelType], $where)->orderBy('id'),
                    $lastMonth
                )
                ->get()
                ->toArray();

        return $data;
    }



    private static function filterTeamCompositions($groups, &$bossMap = [], $accountId = 0, $atkType = 0, $lockedIds = [], $hiddenIds = [], $successNum = 10)
    {
        if ($accountId) {
            $userBox = Account::where('id', $accountId)->value('roles');
            if ($userBox) {
                $userBox = explode(',', $userBox);
            }
        } else {
            $userBox = [];
        }

        // $successNum = 10;
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

            $groupRoles = [];
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
                $groupRoles[] = $teamInfo['roles'];
            }
            unset($teamInfo);

            // 借人数量判断
            $is_ok = self::checkRoleOverlapLimit($groupRoles);
            if (!$is_ok) {
                unset($groups[$key]);
                $failNum++;
                continue;
            }

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
     * @param  integer $accountId [账号ID]
     * @param  integer $type      [类型0手动分刀1自动分刀]
     * @param  integer $stage     [低阶段1不限2仅限B阶段3仅限C阶段]
     * @param  integer $atkType   [作业类型0不限制1物理三刀2物理两刀3魔法两刀4魔法三刀]
     * @param  array   $lockedIds [锁定作业ID]
     * @param  array   $hiddenIds [隐藏作业ID]
     * @return [type]             [description]
     */
    public static function firstDayHomework($uid, $bossMapB = [], $bossMapD = [], $accountId = 0, $type = 0, $stage = 0, $atkType = 0, $lockedIdsB = [], $lockedIdsD = [], $hiddenIds = [])
    {
        $experience = $uid == 0 ? 1 : 0;
        if ($type == 1) {
            $cacheKeyD =  $experience ? 'experienceAutoTeams' : 'autoTeams';
        }
        if ($type == 2) {
            $cacheKeyD =  $experience ? 'experienceHandTeams' : 'handTeams';
        }
        $cacheKeyB = $experience ? 'experienceBTeams' : 'bTeams';

        $whereB = [
            ['uid', '=', 0],
            ['status', '=', 1],
            ['auto', '=', 1],
        ];

        if ($stage == 1) {
            $whereB[] = ['stage', 'in', [2, 3]];
        } else {
            $whereB[] = ['stage', '=', $stage];
            $cacheKeyB .= $stage;
        }

        $whereD = [
            ['uid', '=', 0],
            ['status', '=', 1],
            ['auto', '=', $type],
            ['stage', '=', 5],
        ];

        $dataB = self::getPublicTeams($whereB, 1, $experience);
        $dataD = self::getPublicTeams($whereD, 1, $experience);

        $makeArr = false; // 正式
        // $makeArr = true; // 测试
        $oldDataJsonB = Cache::get($cacheKeyB);
        $oldDataJsonD = Cache::get($cacheKeyD);
        if (json_encode($dataB) != $oldDataJsonB || json_encode($dataD) != $oldDataJsonD) {
            $makeArr = true;
            Cache::put($cacheKeyB, json_encode($dataB));
            Cache::put($cacheKeyD, json_encode($dataD));
        }

        if ($makeArr) {
            $teamsResB = self::makeTeams($dataB);
            Cache::put($cacheKeyB . 'makeTeams', $teamsResB);
            $teamsResD = self::makeTeams($dataD);
            Cache::put($cacheKeyD . 'makeTeams', $teamsResD);
        } else {
            $teamsResB = Cache::get($cacheKeyB . 'makeTeams');
            $teamsResD = Cache::get($cacheKeyD . 'makeTeams');
        }

        $teamsResB = self::filterTeamCompositions($teamsResB, $bossMapB, $accountId,        0, $lockedIdsB, $hiddenIds, 5);
        $teamsResD = self::filterTeamCompositions($teamsResD, $bossMapD, $accountId, $atkType, $lockedIdsD, $hiddenIds, 5);

        $combinedTeams    = self::combineBD($teamsResB, $teamsResD);
        $conflicts        = self::calculateConflicts($combinedTeams);
        $minConflictTeams = self::filterMinConflict($combinedTeams, $conflicts);

        $groups = [];
        $error  = [];

        foreach ($minConflictTeams as $aItem) {
            foreach ($aItem as $Bitem) {
                $res = self::assignBorrowForB($Bitem);
                if ($res === false) {
                    $error[] = $Bitem;
                    // echo "该B数组无法分配borrow\n";
                } else {
                    $groups[] = $res;
                }
            }
        }

        $data      = self::getPublicTeams(['uid', '=', 0], 1, $experience);

        $result    = [];
        foreach ($data as $item) {
            if (isset($item['id'])) {
                $result[$item['id']] = $item;
            }
        }

        $groups = array_slice($groups, 0, 10);

        foreach ($groups as &$group) {
            if (is_array($group)) {
                foreach ($group as &$teams) {
                    foreach ($teams as &$team) {
                        $team['roles']  = self::addDisplayId($team['roles']);
                        $team['remark'] = $result[$team['id']]['remark'];
                        $team['stage']  = $result[$team['id']]['stage'];
                        $team['link']   = $result[$team['id']]['link'];
                        if ($team['link']) {
                            foreach ($team['link'] as $k => $link) {
                                $team['link'][$k]['image'] = json_encode($link['image']);
                            }
                        }
                    }
                }
            }
        }
        // 用户分刀数据记录
        self::dataFightLog($uid, $type, array_merge($bossMapB, $bossMapD));
        return $groups;
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

// 工具函数：生成数组索引的全排列
public static function permute2($items, $perms = [])
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

// 主函数：生成 B 和 D 的排列组合
public static function combineBD(array $B, array $D)
{
    $result = [];

    foreach ($B as $bItem) {
        foreach ($D as $dItem) {
            // 当前 (B[i], D[j]) 组合的结果数组
            $groupResults = [];

            $len = min(count($bItem), count($dItem));
            $indexes = range(0, $len - 1);
            $permutations = self::permute($indexes);

            // 为每种排列生成一个子元素
            foreach ($permutations as $perm) {
                $pairResults = [];
                for ($k = 0; $k < $len; $k++) {
                    $pairResults[] = [
                        $bItem[$k],
                        $dItem[$perm[$k]],
                    ];
                }
                $groupResults[] = $pairResults;
            }

            // 每个 groupResults 包含 6 个子元素
            $result[] = $groupResults;
        }
    }

    return $result;
}

public static function calculateConflicts(array $A)
{
    $conflictResults = []; // [A索引 => [B索引 => 冲突值]]

    foreach ($A as $aIndex => $BArray) {
        $conflictResults[$aIndex] = [];

        foreach ($BArray as $bIndex => $CArrayList) {

            $roleToCs = [];   // 记录每个角色在哪些C数组出现
            $conflict = 0;    // 最终冲突值
            $hasZeroStatus = 0; // D数组中status=0的计数器

            foreach ($CArrayList as $cIndex => $DArray) {

                $rolesInThisC = [];

                foreach ($DArray as $dItem) {
                    if (!isset($dItem['roles'])) continue;

                    foreach ($dItem['roles'] as $roleId => $status) {
                        if ($status == 0) {
                            // 有一个status=0的角色 -> 整个B数组冲突+1
                            $hasZeroStatus++;
                            continue; // 这个角色不参与冲突计算
                        }

                        // status==1 的角色计入 map
                        $rolesInThisC[$roleId] = true;
                    }
                }

                // 将此C中所有角色添加到 roleToCs 映射中
                foreach (array_keys($rolesInThisC) as $rid) {
                    if (!isset($roleToCs[$rid])) {
                        $roleToCs[$rid] = [];
                    }
                    $roleToCs[$rid][$cIndex] = true;
                }
            }

            // 计算角色跨C的冲突值
            foreach ($roleToCs as $rid => $cSet) {
                $count = count($cSet);
                if ($count > 1) {
                    $conflict += $count - 1; // 出现在2个C -> +1, 3个C -> +2
                }
            }

            // 加上D数组中存在status=0的情况（每个D算1个）
            $conflict += $hasZeroStatus;

            $conflictResults[$aIndex][$bIndex] = $conflict;
        }
    }

    return $conflictResults;
}

public static function filterMinConflict(array $A, array $conflicts)
{
    $filtered = [];

    foreach ($A as $aIndex => $BArray) {
        if (!isset($conflicts[$aIndex])) {
            // 若冲突值数组缺失，跳过
            continue;
        }

        $minIndex = null;
        $minValue = PHP_INT_MAX;

        // 找出最小冲突值的B数组索引
        foreach ($conflicts[$aIndex] as $bIndex => $value) {
            if ($value < $minValue) {
                $minValue = $value;
                $minIndex = $bIndex;
            }
        }

        // 保留该B数组
        if ($minIndex !== null && isset($BArray[$minIndex])) {
            $filtered[] = [$BArray[$minIndex]]; // 保留为数组结构（方便后续扩展）
        }
    }

    return $filtered;
}

/**
 * 输入：$B（一个 B 数组，包含 6 个 C，每个 C 是长度为2的数组，两个元素即为 E 数组）
 * 输出：修改后的 $B（每个 E 的 borrow 被设置为角色 id 或 0），或 false 表示无法分配
 *
 * 约定：每个 E 为一个数组，包含 'roles' => [ role_id => status, ... ] 和 'borrow' 字段（会被写入）
 */
public static function assignBorrowForB(array $B)
{
    // 1) 展平 E 列表并记录映射： eIndex => [cIndex, idxWithinC]
    $Elist = []; // 每项是引用索引信息 ['c'=>int,'idx'=>0|1]
    $cCount = count($B); // 应为 6
    for ($ci = 0; $ci < $cCount; $ci++) {
        $C = $B[$ci];
        // 假设每个 C 有 2 个 E（没有的填充处理）
        for ($ei = 0; $ei < 2; $ei++) {
            if (!isset($C[$ei])) {
                // 若结构不完备，创建一个空占位
                $B[$ci][$ei] = ['roles'=>[], 'borrow'=>0];
            }
            $Elist[] = ['c' => $ci, 'idx' => $ei];
        }
    }
    $Ecount = count($Elist); // 应等于 cCount * 2

    // 2) 预处理：固定 borrow（status==0）
    $borrowAssigned = array_fill(0, $Ecount, null); // null 表示未分配，0 表示无借用，>0 表示借某角色
    $forced = array_fill(0, $Ecount, null); // 若某 E 包含 status==0 的角色，则 forced[eIdx] = roleId

    for ($eIdx = 0; $eIdx < $Ecount; $eIdx++) {
        $ci = $Elist[$eIdx]['c'];
        $idx = $Elist[$eIdx]['idx'];
        $E = $B[$ci][$idx];
        if (!isset($E['roles']) || !is_array($E['roles'])) continue;

        foreach ($E['roles'] as $rid => $status) {
            if ($status === 0) {
                $forced[$eIdx] = (int)$rid;
                $borrowAssigned[$eIdx] = (int)$rid; // 预设固定
                break; // 假设每个 E 最多一个 status==0 或只取第一个
            }
        }
    }

    // 3) 构建角色到 E索引 的映射，但只统计 status==1 的出现（status==0 已被视为不存在）
    //    同时记录角色出现在哪些 C（C 级别）
    $roleToEIndices = []; // roleId => [ eIdx1, eIdx2, ... ]
    $roleToCs = [];       // roleId => set of cIndex where it appears (status==1)
    for ($eIdx = 0; $eIdx < $Ecount; $eIdx++) {
        $ci = $Elist[$eIdx]['c'];
        $idx = $Elist[$eIdx]['idx'];
        $E = $B[$ci][$idx];
        if (!isset($E['roles']) || !is_array($E['roles'])) continue;

        foreach ($E['roles'] as $rid => $status) {
            if ($status === 1) {
                $rid = (int)$rid;
                $roleToEIndices[$rid][] = $eIdx;
                if (!isset($roleToCs[$rid])) $roleToCs[$rid] = [];
                $roleToCs[$rid][$ci] = true;
            }
        }
    }

    // 4) 我们要通过选择若干 E 去 borrow（包括已 forced 的）并为其中每个选定的 E 指定要 borrow 的角色，
    //    使得对每个角色 r：在去掉被 borrow 的 E 出现后，r 所在的 C 的数量 <= 1。
    //
    //    算法：枚举被借用的 E 的集合 S（按大小从小到大），S 必须包含所有 forced 的 eIdx。
    //    对于每个 S，回溯为 S 中的每个 eIdx 指定一个 role（该 role 必须出现在该 E 的 roles 中，或等于 forced）。
    //    指定完成后，模拟删除（即若 E 的 borrow == r，则该 E 不再包含 r），然后检查每个角色在多少个 C 中仍然存在，
    //    若所有角色的 C-count <=1，则返回成功。
    //
    //    优化：
    //    - S 的总数上限为 2^(Ecount)，但 Ecount 一般为 12（6*2），2^12=4096，可接受；
    //    - 但为每个 S 再穷举为每个 E 指定角色时有分支，我们对每个 E 只尝试其 roles 列表上的角色；
    //    - 按 |S| 从小到大遍历，找到第一个可行解即返回（最省借用数量）。
    //

    // 工具：计算 mask 的 popcount
    $popcount = function(int $x) {
        return substr_count(decbin($x), '1');
    };

    $limit = 1 << $Ecount;

    // 先构建每个 E 的可选角色列表（status==1 的 role 列表；若 forced 则只有 forced）
    $Eoptions = [];
    for ($eIdx = 0; $eIdx < $Ecount; $eIdx++) {
        $ci = $Elist[$eIdx]['c'];
        $idx = $Elist[$eIdx]['idx'];
        $E = $B[$ci][$idx];

        $roles = [];
        if (isset($E['roles']) && is_array($E['roles'])) {
            foreach ($E['roles'] as $rid => $status) {
                if ($status === 1) $roles[] = (int)$rid;
            }
        }

        if ($forced[$eIdx] !== null) {
            // 若 forced，选项仅为强制 role（无须重复尝试）
            $Eoptions[$eIdx] = [$forced[$eIdx]];
        } else {
            // 若该 E 没有任何 status==1 的角色，那么它的 borrow 没有意义（只能为 0）
            if (empty($roles)) {
                $Eoptions[$eIdx] = []; // 表示不可能为该 E 分配 borrow（除非 forced）
            } else {
                $Eoptions[$eIdx] = $roles;
            }
        }
    }

    // 强制 E 索引集合位掩码
    $forcedMask = 0;
    for ($eIdx = 0; $eIdx < $Ecount; $eIdx++) {
        if ($forced[$eIdx] !== null) $forcedMask |= (1 << $eIdx);
    }

    // 枚举 S（被借用 E 的集合），按大小从小到大
    for ($k = 0; $k <= $Ecount; $k++) {
        // k 必须 >= count(forced)
        if ($k < $popcount($forcedMask)) continue;

        // 遍历所有 mask
        for ($mask = 0; $mask < $limit; $mask++) {
            if ($popcount($mask) !== $k) continue;

            // 强制集合必须被包含
            if ((($mask & $forcedMask) ^ $forcedMask) !== 0) continue;

            // 构造 S 列表（eIndex 列表）
            $S = [];
            for ($eIdx = 0; $eIdx < $Ecount; $eIdx++) {
                if ((($mask >> $eIdx) & 1) === 1) $S[] = $eIdx;
            }

            // 对 S 中的每个 eIdx 指定 borrow role 的回溯
            $assignment = array_fill(0, $Ecount, null); // 指定角色或 null
            // 先写入强制 borrow
            for ($eIdx = 0; $eIdx < $Ecount; $eIdx++) {
                if ((($mask >> $eIdx) & 1) === 0) continue;
                if ($forced[$eIdx] !== null) $assignment[$eIdx] = $forced[$eIdx];
            }

            // 待选择的 eIdx 列表（在 S 中但未 forced）
            $toChoose = [];
            foreach ($S as $eIdx) {
                if ($forced[$eIdx] === null) $toChoose[] = $eIdx;
            }

            $foundSolutionForMask = false;

            // 回溯函数为每个待选 E 选择一个 role
            $dfsAssign = function($pos) use (&$dfsAssign, &$toChoose, &$assignment, &$Eoptions, &$Elist, $B, $roleToCs, &$foundSolutionForMask) {
                if ($foundSolutionForMask) return;

                if ($pos === count($toChoose)) {
                    // 所有 S 中的 E 都被指定了 borrow（assignment），现在检验全局冲突条件：
                    // 对每个角色 r，计算在每个 C 中是否仍然存在（即某个 E 在该 C 中未被 assignment 指定为 r 且该 E 包含 r）
                    // 若某角色在多个不同 C 中仍然存在 -> 失败
                    // 构建 role -> set(cIndex)
                    $rolePresenceCs = [];

                    // 遍历每个 E
                    $EcountLocal = count($assignment);
                    for ($ei = 0; $ei < $EcountLocal; $ei++) {
                        $ci = $Elist[$ei]['c'];
                        $idx = $Elist[$ei]['idx'];
                        $E = $B[$ci][$idx];

                        if (!isset($E['roles']) || !is_array($E['roles'])) continue;
                        foreach ($E['roles'] as $rid => $status) {
                            $rid = (int)$rid;
                            if ($status === 0) continue; // status==0 不参与（已被 forced）
                            // 如果该 E 被分配为 borrow 且 assignment[ei] == rid，则该 rid 在此 E 被“移除”
                            if ($assignment[$ei] !== null && $assignment[$ei] === $rid) {
                                continue;
                            }
                            // 否则，rid 在此 E 存在 -> 计入该 C
                            if (!isset($rolePresenceCs[$rid])) $rolePresenceCs[$rid] = [];
                            $rolePresenceCs[$rid][$ci] = true;
                        }
                    }

                    // 检查每个角色在多少个不同 C 中仍然存在
                    foreach ($rolePresenceCs as $rid => $cset) {
                        if (count($cset) > 1) {
                            // 在多个 C 中仍然存在 -> 无效分配
                            return;
                        }
                    }

                    // 通过验证
                    $foundSolutionForMask = true;
                    return;
                }

                $ei = $toChoose[$pos];
                // 若 Eoptions 为空，无法为该 E 指定 borrow（但 E 属于 S），跳过（回溯失败）
                if (empty($Eoptions[$ei])) return;

                foreach ($Eoptions[$ei] as $candidateRole) {
                    $assignment[$ei] = $candidateRole;

                    // 快速剪枝（可选）：检查部分 assignment 是否已必然导致某角色在 >=2 个 C 中保留
                    // 这一检查较复杂；为保持代码简洁，这里不做额外部分剪枝（可在需要时加入）
                    $dfsAssign($pos + 1);
                    if ($foundSolutionForMask) return;
                    $assignment[$ei] = null;
                }
            };

            // 如果没有待选（S 仅包含 forced），直接检验
            if (empty($toChoose)) {
                // 验证 assignment（只有 forced）
                $rolePresenceCs = [];
                for ($ei = 0; $ei < $Ecount; $ei++) {
                    $ci = $Elist[$ei]['c'];
                    $idx = $Elist[$ei]['idx'];
                    $E = $B[$ci][$idx];
                    if (!isset($E['roles']) || !is_array($E['roles'])) continue;
                    foreach ($E['roles'] as $rid => $status) {
                        $rid = (int)$rid;
                        if ($status === 0) continue;
                        if ($assignment[$ei] !== null && $assignment[$ei] === $rid) continue;
                        if (!isset($rolePresenceCs[$rid])) $rolePresenceCs[$rid] = [];
                        $rolePresenceCs[$rid][$ci] = true;
                    }
                }
                $ok = true;
                foreach ($rolePresenceCs as $rid => $cset) {
                    if (count($cset) > 1) { $ok = false; break; }
                }
                if ($ok) {
                    $foundSolutionForMask = true;
                }
            } else {
                $dfsAssign(0);
            }

            if ($foundSolutionForMask) {
                // 写回 borrow 到 B 的对应 E 中（未分配的 E 写 0）
                for ($ei = 0; $ei < $Ecount; $ei++) {
                    $ci = $Elist[$ei]['c'];
                    $idx = $Elist[$ei]['idx'];
                    $B[$ci][$idx]['borrow'] = $assignment[$ei] ?? 0;
                }
                return $B;
            }
            // 否则继续下一个 mask
        }
    }

    // 枚举完所有 S 仍无解，返回 false
    return false;
}










    /**
     * [getTeamGroups 获取推荐分刀作业]
     * @param  [type]  $uid       [用户ID]
     * @param  array   $bossMap   [bossID数组]
     * @param  integer $accountId [账号ID]
     * @param  integer $type      [分刀类型0自定义刀1自动刀2手动刀]
     * @param  integer $atkType   [攻击类型]
     * @return [type]             [结果数据]
     */
    public static function getTeamGroups($uid, $bossMap = [], $accountId = 0, $type = 0, $atkType = 0, $lockedIds = [], $hiddenIds = [])
    {
        $experience = $uid == 0 ? 1 : 0; // 体验账号
        if ($type) {
            if ($type == 1) {
                $cacheKey = $experience ? 'experienceAutoTeams' : 'autoTeams';
                $where = [
                    ['status', '=', 1],
                    ['auto', '=', $type],
                    ['stage', '=', 5],
                    ['uid', '=', 0]
                ];
            }
            if ($type == 2) {
                $cacheKey = $experience ? 'experienceHandTeams' : 'handTeams';
                $where = [
                    ['status', '=', 1],
                    ['auto', '=', $type],
                    ['stage', '=', 5],
                    ['uid', '=', 0]
                ];
            }
        } else {
            $cacheKey = '';
            $where = [
                ['status', '=', 1],
                ['uid', '=', $uid]
            ];
        }

        $data = self::getPublicTeams($where, $type, $experience);
// return $data;
        $makeArr = false;
        $makeArr = true;
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
                                ->get()
                                ->keyBy('id');
                // Cache::put('data_huawu', $data_huawu, 600);
            }
// return $data;
            // 替换link数据
            foreach ($data as $key => $value) {
                if ($value['otid'] && $data_huawu[$value['otid']]) {
                    $data[$key]['link'] = $data_huawu[$value['otid']]->link;
                }
            }
        // return $data;
            $teamsRes = self::makeTeams($data);
        }
// return $teamsRes;
        $teamsRes = self::filterTeamCompositions($teamsRes, $bossMap, $accountId, $atkType, $lockedIds, $hiddenIds);
// return $teamsRes;
        $result = [];
        foreach ($data as $item) {
            if (isset($item['id'])) {
                $result[$item['id']] = $item;
            }
        }

        foreach ($teamsRes as &$teams) {
            $teams = self::assignBorrow($teams);
            foreach ($teams as &$teamInfo) {
                $teamInfo['roles']  = self::addDisplayId($teamInfo['roles']);
                $teamInfo['remark'] = $result[$teamInfo['id']]['remark'];
                $teamInfo['stage']  = $result[$teamInfo['id']]['stage'];
                $teamInfo['link']   = $result[$teamInfo['id']]['link'];
            }
            unset($teamInfo);
        }
        unset($teams);

        // 用户分刀数据记录
        self::dataFightLog($uid, $type, $bossMap);
        return $teamsRes;
    }

    private static function addDisplayId($resData)
    {
        $rolesMap = DB::table('roles')->select(DB::raw('CASE WHEN `is_6` = 1 THEN `role_id_6` ELSE `role_id_3` END as `image_id`, `id`'))->get()->keyBy('id')->toArray();

        $res = [];
        foreach ($resData as $roleId => $status) {
            $res[] = ['image_id' => $rolesMap[$roleId]->image_id, 'role_id' => $roleId, 'status' => $status];
        }
        return $res;
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
        foreach ($teamA['team'] as $key => $role) {
            if ($role['status'] == 0) {
                $limitNum--;
            } else {
                $roleMapA[] = $role['role_id'];
            }
        }
        foreach ($teamB['team'] as $key => $role) {
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
                    $rolesA = self::makeRolesMap($data[$a]['team']);
                    $rolesB = self::makeRolesMap($data[$b]['team']);
                    $rolesC = self::makeRolesMap($data[$c]['team']);
                    
                    $bool   = self::checkRoleOverlapLimit([$rolesA, $rolesB, $rolesC]);
                    if ($bool) {
                        $teamsRes[] = [
                            ['id' => $data[$a]['id'], 'boss' => $data[$a]['boss'], 'damage' => $data[$a]['damage'], 'atk_value' => $data[$a]['atk_value'], 'roles' => $rolesA],
                            ['id' => $data[$b]['id'], 'boss' => $data[$b]['boss'], 'damage' => $data[$b]['damage'], 'atk_value' => $data[$b]['atk_value'], 'roles' => $rolesB],
                            ['id' => $data[$c]['id'], 'boss' => $data[$c]['boss'], 'damage' => $data[$c]['damage'], 'atk_value' => $data[$c]['atk_value'], 'roles' => $rolesC]
                        ];
                    }
                }
            }
        }
        return self::sortTeams($teamsRes);
    }

    private static function makeTeams2($data = [])
    {
        $n            = count($data);
        $teamsRes     = [];
        $invalidPairs = [];

        for ($a = 0; $a < $n - 1; $a++) { 
            for ($b = $a + 1; $b < $n; $b++) { 
                if (self::hasInvalidPair([$a], $b, $invalidPairs, $data)) continue;
                // 先用紧凑的 roles 映射来判断
                $rolesA = self::makeRolesMap(array_column($data[$a]['team_roles'], 'role_id'));
                $rolesB = self::makeRolesMap(array_column($data[$b]['team_roles'], 'role_id'));

                $bool   = self::checkRoleOverlapLimit([$rolesA, $rolesB]);
                if ($bool) {
                    $teamsRes[] = [
                        ['id' => $data[$a]['id'], 'boss' => $data[$a]['boss'], 'score' => $data[$a]['score'], 'atk_value' => $data[$a]['atk_value'], 'roles' => $rolesA],
                        ['id' => $data[$b]['id'], 'boss' => $data[$b]['boss'], 'score' => $data[$b]['score'], 'atk_value' => $data[$b]['atk_value'], 'roles' => $rolesB],
                    ];
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
        foreach ($roles as $role) {
            if (isset($role['role_id']) && $role['role_id']) {
                $res[$role['role_id']] = $role['status'];
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
    private static function checkRoleOverlapLimit($group = []): bool
    {
        $limitNum = count($group);
        $roles = [];
        $statusNum = 0;
        foreach ($group as $team) {
            foreach ($team as $status) {
                if ($status == 0) {
                    $statusNum++;
                }
            }
            $roles = array_merge($roles, array_keys($team));
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
        $targetNum += $statusNum;

        return $targetNum > $limitNum ? false : true;
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
        unset($teams);

        $sort = 3;
        // 综合排序 已弃用
        if ($sort == 1) {
            usort($data, function($a, $b) use ($multiplierRates) {
                $teamAugScoreA = 0;
                $teamAugScoreB = 0;
                for ($i=0; $i < 3; $i++) { 
                    $teamAugScoreA += $a[$i]['damage'] * $multiplierRates[$a[$i]['boss']] / $a[$i]['difficulty'];
                    $teamAugScoreB += $b[$i]['damage'] * $multiplierRates[$b[$i]['boss']] / $b[$i]['difficulty'];
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
                foreach ($a as $item) {
                    $teamScoreA += $item['damage'] * $multiplierRates[$item['boss']];
                }
                foreach ($b as $item) {
                    $teamScoreB += $item['damage'] * $multiplierRates[$item['boss']];
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
