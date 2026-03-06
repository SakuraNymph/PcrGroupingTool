<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamRole;
use App\Services\TeamInfoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminTeamController extends Controller
{
    const AUTOMAP = ['', '纯AUTO', '简单SET', '开关SET', '简单目押', '目押'];
    const BOSSHEALTH = [
        [],
        [],
        [0, 1200, 1500, 2000, 2300, 3000], // B阶段
        [0, 3500, 4000, 4500, 5000, 5800], // C阶段
        [],
    ];

    public function list()
    {
        return view('admin.team.list');
    }

    public function export()
    {
        $data = Team::where(['status' => 1])
                    ->whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->get()
                    ->toArray();

        
        $stageMap = ['', '', 'B', 'C', '', 'E'];
        $autoMap  = ['纯AUTO' => 1, '简单SET' => 2, '开关SET' => 3, '简单目押' => 4, '目押' => 5];

        // 1) 读取角色表并检测重复 nickname
        $roles = DB::table('roles')
                ->select(DB::raw('CASE WHEN is_6 = 1 THEN role_id_6 ELSE role_id_3 END as image, atk_type, id, search_area_width, nickname'))
                ->get()
                ->map(function ($item) {
                    $item->icon = asset('images') . '/' . $item->image . '.webp';
                    return $item;
                });
        $nicknameCounts = $roles->pluck('nickname')->countBy()->filter(function($c){ return $c > 1; });
        if ($nicknameCounts->isNotEmpty()) {
            dd('data_roles 表中存在重复 nickname，请先修正');
        }
        $rolesMap = $roles->keyBy('id');
        $handler  = fopen(date('Y-m-d') . '会战作业' . '.txt', 'w');
        foreach ($data as $homeworkInfo) {
            $roles = '';
            foreach ($homeworkInfo['team'] as $role) {
                $roles .= $rolesMap[$role['role_id']]->nickname . ' ';
            }
            foreach ($homeworkInfo['link'] as $authorInfo) {
                $title   = $authorInfo['text'];
                $pattern = '/^(\S+)\s+(\S+)\s+(\d+)[a-zA-Z]*$/u';
                if (preg_match($pattern, $title, $matches)) {
                    $author     = $matches[1]; // 第一组：一只浅梦丶
                    $difficulty = $matches[2]; // 第二组：纯AUTO
                    $damage     = $matches[3]; // 第三组：2300 (已自动过滤 w)
                } else {
                    fwrite($handler, 'ID:' . $data['id'] . '解析错误' . PHP_EOL);
                    continue;
                }
                fwrite($handler, $stageMap[$homeworkInfo['stage']] . $homeworkInfo['boss'] . ' ' . $author . ' ' . '(' . $autoMap[$difficulty] . ')' . ' ' . $authorInfo['url'] . ' ' . $damage . ' / ' . $roles . PHP_EOL);
            }
        }
        fclose($handler);
        dd('Success');
    }

    public function getPublicTeams(Request $request)
    {
        $stage  = (int)$request->input('stage');
        $boss   = (int)$request->input('boss');
        $auto   = (int)$request->input('auto');
        $atk    = (int)$request->input('atk');
        $status = $request->input('status');

        $data = TeamInfoService::getTeams($boss, 1, 0, $status, $stage, $atk, $auto);
        return json_encode(['status' => 1, 'result' => $data]);
    }

    public function add(Request $request)
    {
        $data = $request->all();
        $data['method'] = $request->method();

        // 1) 读取角色表并检测重复 nickname
        $roles = DB::table('roles')
                ->select(DB::raw('CASE WHEN is_6 = 1 THEN role_id_6 ELSE role_id_3 END as image, atk_type, id, search_area_width, nickname'))
                ->get()
                ->map(function ($item) {
                    $item->icon = asset('images') . '/' . $item->image . '.webp';
                    return $item;
                });
        $nicknameCounts = $roles->pluck('nickname')->countBy()->filter(function($c){ return $c > 1; });
        if ($nicknameCounts->isNotEmpty()) {
            return response()->json([
                'code'       => 0,
                'msg'        => 'data_roles 表中存在重复 nickname，请先修正',
                'duplicates' => $nicknameCounts->keys()->values()->all()
            ]);
        }
        $rolesMap  = $roles->keyBy('nickname');
        $rolesMap2 = $roles->keyBy('id');

        if ($data['method'] == 'POST') {

            $rules = [
                // 顶部全局信息
                'author' => 'required|string|max:16',
                'link'   => 'required|url',
                
                // 卡片数组校验
                'records'               => 'required|array|min:1',
                'records.*.boss'        => 'required|array|min:1',
                'records.*.boss.*'      => 'integer|in:1,2,3,4,5',
                'records.*.difficulty'  => 'required|integer|in:1,2,3,4,5',
                'records.*.damage'      => 'required|integer|between:1,99999',
                'records.*.stage'       => 'required|integer|in:2,3,5',
                
                // 角色校验：必填、数组、1-5个、元素不能重复
                // 关键修改点：对 roles 数组本身进行校验
                'records.*.roles' => [
                    'required', 
                    'array', 
                    'min:1', 
                    'max:5',
                    // 自定义闭包：检查当前这组 ID 是否有重复
                    function ($attribute, $value, $fail) {
                        // $value 是当前这条 record 里的 roles 数组
                        if (count($value) !== count(array_unique($value))) {
                            $fail('同一组队伍中不能出现重复的角色');
                        }
                    },
                ],
                'records.*.roles.*' => 'integer', 
            ];

            // 自定义错误提示消息（可选）
            $messages = [
                'author.required'          => '别忘了写作者名字',
                'link.url'                 => '链接地址格式不对哦',
                'records.*.damage.between' => '伤害数值超出范围了',
                'records.*.roles.distinct' => '同一个队伍里不能有两个相同的角色',
                'records.*.roles.required' => '请至少输入一个角色',
            ];

            $validatedData = $request->validate($rules, $messages);

            // 如果校验通过，执行后续入库逻辑...
            // return response()->json(['message' => '数据校验成功！']);

            $author  = $validatedData['author'];
            $url     = $validatedData['link'];
            $autoMap = self::AUTOMAP;
            $parsed  = [];

            foreach ($validatedData['records'] as $homeworkInfo) {

                $teamRoleIds = $homeworkInfo['roles'];
                $atkValue   = 0;
                foreach ($teamRoleIds as $roleId) {
                    $atkValue += (int)$rolesMap2[$roleId]->atk_type;
                }

                

                // 按 search_area_width 降序排序 A
                usort($teamRoleIds, function($x, $y) use ($rolesMap2) {
                    $widthX = $rolesMap2[$x]->search_area_width ?? 0;  // ID 不存在用 0
                    $widthY = $rolesMap2[$y]->search_area_width ?? 0;
                    return $widthY <=> $widthX;  // 降序：y 的宽度 > x 的宽度，返回负数（y 先）
                    // 或者用: return $widthX - $widthY;  // 简单减法，也实现降序（但数字大时注意溢出）
                });

                $teamKey = implode(',', $teamRoleIds);

                $bossHealth = self::BOSSHEALTH;

                foreach ($homeworkInfo['boss'] as $boss) {
                    $damage = $homeworkInfo['damage'];
                    if ($homeworkInfo['stage'] != 5) {
                        $damage = $bossHealth[$homeworkInfo['stage']][$boss];
                    }
                    $parsed[] = [
                        'stage'         => $homeworkInfo['stage'],
                        'boss'          => $boss,
                        'title'         => $author . ' ' . $autoMap[$homeworkInfo['difficulty']] . ' ' . $damage . 'w',
                        'auto'          => $homeworkInfo['difficulty'],
                        'url'           => $url,
                        'damage'        => $damage,
                        'team_role_ids' => $teamRoleIds,
                        'team_key'      => $teamKey,
                        'atk_value'     => $atkValue,
                    ];
                }
            }

            
            return $this->insertTeamInfo($parsed);


            return response()->json([
                'code'       => 0,
                'msg'        => 'data_roles 表中存在重复 nickname，请先修正',
                'duplicates' => $validatedData
            ]);
        }
        return view('admin.team.add', ['roles' => $rolesMap]);
    }

    public function edit(Request $request)
    {
        $data = $request->all();
        $data['method'] = $request->method();
        return $this->post($data);
    }

    private function post($params = [])
    {
        $id = isset($params['id']) ? (int)$params['id'] : 0;
        if ($params['method'] == 'POST') {
            // 验证请求参数
            $rules = [
                'boss'       => 'required|integer|min:1|max:5',
                'score'      => 'required|integer|min:1|max:99999',
                // 'difficulty' => 'integer|min:1|max:99',
                'auto'       => 'required|integer|min:0|max:1',
                'open'       => 'required|integer|min:0|max:2',
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
            $boss_num   = (int)$params['boss'];
            // 备注
            $remark     = htmlspecialchars($params['remark']) ?? '';
            // 伤害
            $score      = (int)$params['score'];
            // 难度
            // $difficulty = (int)$params['difficulty'];
            // 是否公开
            $auto       = (int)$params['auto'];
            $open       = 2;
            // 是否通用
            // $ordinary   = (int)$params['ordinary'] ?? 0;
            // 阵容
            $teams      = $params['teams'];

            $uid = 0;

            $role_ids = array_column($teams, 'role_id');
            // 验证角色参数
            $count = Role::whereIn('role_id', $role_ids)->count();
            if ($count != 5) {
                show_json(0, '角色参数错误');
            }

            $this->addUseTimes($role_ids);

            if ($id) {
                $team_id = $id;
                $update_teams = [
                    'uid'        => 0,
                    'boss'       => $boss_num,
                    'score'      => $score,
                    'open'       => $open,
                    'auto'       => $auto,
                    'status'     => 1,
                    'remark'     => $remark,
                    'updated_at' => timeToStr(),
                ];
                Team::where('id', $id)->update($update_teams);
                TeamRole::where('team_id', $id)->delete();
            } else {
               $insert_teams = [
                    'uid'        => $uid,
                    'boss'       => $boss_num,
                    'score'      => $score,
                    'open'       => $open,
                    'auto'       => $auto,
                    'status'     => 1,
                    'remark'     => $remark,
                    'created_at' => timeToStr(),
                    'updated_at' => timeToStr(),
                ];
                $team_id = DB::table('teams')->insertGetId($insert_teams);
                if (!$team_id) {
                    show_json(0, 'Error');
                }
            }
            $insert_team_roles = [];

            foreach ($teams as $key => $value) {
                $insert_team_roles[] = ['team_id' => $team_id, 'role_id' => $value['role_id'], 'status' => $value['status']];
            }

            $res = DB::table('team_roles')->insert($insert_team_roles);

            if (!$res) {
                DB::table('teams')->delete($team_id);
                show_json(0, 'Error');
            }
            $cacheKey = 'group' . $uid;
            Cache::put($cacheKey, []);
            show_json(1); 
        }

        $bossId       = isset($params['boss']) ? (int)$params['boss'] : 0;
        $data         = [];

        if (!in_array($bossId, [1,2,3,4,5])) {
            $bossId = 1;
        }

        if ($id) {
            $data = Team::with(['teamRoles' => function ($query) {

            }])->find($id);
            $data = $data ? $data->toArray() : [];
            $bossId = $data['boss'];
        }

        return view('admin.team.post', ['data' => $data, 'id' => $id, 'bossId' => $bossId]);
    }

    public function open(Request $request)
    {
        $id = (int)$request->input('id');
        $team = Team::find($id);
        if (!$team) {
            show_json(0);
        }
        $team->open = 2 - $team->open;
        $team->save();
        show_json(1);
    }

    public function status(Request $request)
    {
        $id = (int)$request->input('id');
        $team = Team::find($id);
        if (!$team) {
            show_json(0);
        }
        $team->status = 1 - $team->status;
        $team->save();
        show_json(1);
    }

    public function upload(Request $request)
    {
        $file = $request->file('file');

        if (!$file || !$file->isValid()) {
            return response()->json(['code' => 0, 'msg' => '文件上传失败']);
        }

        $content = file_get_contents($file->getRealPath());
        $lines = preg_split('/\r\n|\r|\n/', trim($content));

        // 1) 读取角色表并检测重复 nickname
        $roles = DB::table('roles')->select(DB::raw('CASE WHEN is_6 = 1 THEN role_id_6 ELSE role_id_3 END as image, atk_type, id, search_area_width, nickname'))->get();
        $nicknameCounts = $roles->pluck('nickname')->countBy()->filter(function($c){ return $c > 1; });

        if ($nicknameCounts->isNotEmpty()) {
            return response()->json([
                'code'       => 0,
                'msg'        => 'data_roles 表中存在重复 nickname，请先修正',
                'duplicates' => $nicknameCounts->keys()->values()->all()
            ]);
        }

        $rolesMap  = $roles->keyBy('nickname');
        $rolesMap2 = $roles->keyBy('id');
        $stageMap  = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5];
        $autoMap   = ['', '纯AUTO', '简单SET', '开关SET', '简单目押', '目押'];
        $autoMap = self::AUTOMAP;

        $parsed = [];
        $errors = [];

        foreach ($lines as $index => $lineRaw) {
            $lineNumber = $index + 1;
            $line = trim($lineRaw);
            if ($line === '') continue;

            // 新格式匹配： stage boss author (1|2) url damage / team-names
            if (!preg_match('/^([A-Ea-e])([1-5])\s+(.+?)\s*\(\s*([12345])\s*\)\s+(https?:\/\/\S+)\s+(\d{1,5})\s*\/\s*(.+)$/u', $line, $m)) {
                $errors[] = "第 {$lineNumber} 行格式不正确";
                continue;
            }

            [, $stageRaw, $bossRaw, $author, $auto, $url, $damageRaw, $teamStr] = $m;
            $stage = strtoupper($stageRaw);
            $boss = (int)$bossRaw;
            $damage = (int)$damageRaw;

            if ($author === '') { $errors[] = "第 {$lineNumber} 行 author 为空"; continue; }
            if (!filter_var($url, FILTER_VALIDATE_URL)) { $errors[] = "第 {$lineNumber} 行 URL 无效"; continue; }
            if ($damage < 1 || $damage > 99999) { $errors[] = "第 {$lineNumber} 行 damage 超出 1~99999"; continue; }

            $teamNames = array_filter(preg_split('/\s+/', trim($teamStr)), fn($t) => trim($t) !== '');
            if (empty($teamNames)) { $errors[] = "第 {$lineNumber} 行 team 为空"; continue; }

            $teamImages  = [];
            $teamRoleIds = [];
            $atk_value   = 0;

            foreach ($teamNames as $nick) {
                if (!isset($rolesMap[$nick])) {
                    $errors[] = "第 {$lineNumber} 行角色【{$nick}】在 data_roles 中未找到";
                    continue;
                }
                $role          = $rolesMap[$nick];
                $teamImages[]  = $role->image;
                $teamRoleIds[] = $role->id;
                $atk_value     += (int)$rolesMap2[$role->id]->atk_type;
            }

            // 按 search_area_width 降序排序 A
            usort($teamRoleIds, function($x, $y) use ($rolesMap2) {
                $widthX = $rolesMap2[$x]->search_area_width ?? 0;  // ID 不存在用 0
                $widthY = $rolesMap2[$y]->search_area_width ?? 0;
                return $widthY <=> $widthX;  // 降序：y 的宽度 > x 的宽度，返回负数（y 先）
                // 或者用: return $widthX - $widthY;  // 简单减法，也实现降序（但数字大时注意溢出）
            });

            if (count($teamImages) !== count($teamNames)) continue;

            // rsort($teamIds, SORT_NUMERIC);
            $teamKey = implode(',', $teamRoleIds);

            $parsed[] = [
                'stage'         => $stageMap[$stage],
                'boss'          => $boss,
                'title'         => $author . ' ' . $autoMap[$auto] . ' ' . $damage . 'w',
                'auto'          => $auto,
                'url'           => $url,
                'damage'        => $damage,
                'team_images'   => $teamImages,
                'team_role_ids' => $teamRoleIds,
                'team_key'      => $teamKey,
                'line'          => $lineNumber,
                'atk_value'     => $atk_value,
            ];
        }

        if (!empty($errors)) {
            return response()->json([
                'code'   => 0,
                'msg'    => '文件解析失败，存在格式或映射错误',
                'errors' => $errors
            ]);
        }

        // 生成 stage_boss_auto_team_key，用于去重判断
        foreach ($parsed as &$p) {
            $p['stage_boss_auto_team_key'] = $p['stage'].'_'.$p['boss'].'_'.$p['auto'].'_'.$p['team_key'];
        }
        unset($p);

        // 批量查询已有记录 stage + boss + team_key
        $keys = array_column($parsed, 'stage_boss_auto_team_key');
        $existing = DB::table('teams')
            ->where(function($query) use ($keys) {
                foreach ($keys as $key) {
                    [$stage, $boss, $auto, $teamKey] = explode('_', $key, 4);
                    $query->orWhere(function($q) use ($stage, $boss, $auto, $teamKey){
                        $q->where('stage', $stage)
                          ->where('boss', $boss)
                          ->where('auto', $auto)
                          ->where('team_key', $teamKey);
                    });
                }
            })
            ->whereYear('created_at', Carbon::now()->year)
            ->whereMonth('created_at', Carbon::now()->month)
            ->select(DB::raw("CONCAT(stage,'_',boss,'_',auto,'_',team_key) as stage_boss_auto_team_key, id, damage, link"))
            ->get()
            ->keyBy('stage_boss_auto_team_key')
            ->toArray();

        return $this->insertTeamInfo($parsed);
    }

    private function insertTeamInfo($parsed)
    {
        $toInsert      = [];
        $returnedData  = [];
        $insertedCount = 0;

        // DB::beginTransaction();
        try {
            if ($parsed) {
                foreach ($parsed as $p) {
                    $auto = $p['auto'] == 5 ? 2 : 1; // 1 纯AUTO 2 简单SET 3 开关SET 4 简单目押 5 目押
                    $updateData = Team::where(['status' => 1, 'stage' => $p['stage'], 'boss' => $p['boss'], 'auto' => $auto, 'team_key' => $p['team_key'], 'uid' => 0])
                                ->whereYear('created_at', Carbon::now()->year)
                                ->whereMonth('created_at', Carbon::now()->month)
                                ->first();

                    if ($updateData) {
                        // 修改
                        $toUpdate = [];
                        if ($p['damage'] > $updateData->damage) { // 伤害量
                            $toUpdate['damage'] = $p['damage'];
                        }
                        if (!in_array($p['title'], array_column($updateData->link, 'text'))) {
                            $toUpdate['link'] = array_merge($updateData->link, [['text' => $p['title'], 'url' => $p['url'], 'image' => [], 'note' => '']]);
                        }
                        if ($toUpdate) {
                            DB::table('teams')->where('id', $updateData->id)->update($toUpdate);
                        }
                        $returnedData[] = array_merge($p, ['update' => true]);
                        continue;
                    }

                    
                    $team = [];
                    foreach ($p['team_role_ids'] as $roleId) {
                        $team[] = ['role_id' => (int)$roleId, 'status' => 1];
                    }

                    $toInsert = [
                        'stage'      => $p['stage'],
                        'uid'        => 0,
                        'boss'       => $p['boss'],
                        'damage'     => $p['damage'],
                        'team_key'   => $p['team_key'],
                        'team'       => json_encode($team, JSON_UNESCAPED_UNICODE),
                        'status'     => 1,
                        'auto'       => $auto,
                        'atk_value'  => $p['atk_value'],
                        'remark'     => '',
                        'link'       => json_encode([['text' => $p['title'], 'url' => $p['url'], 'image' => [], 'note' => '']], JSON_UNESCAPED_UNICODE),
                        'timestamp'  => time(),
                        'created_at' => Carbon::now(),
                    ];

                    DB::table('teams')->insert($toInsert);

                    $this->addUseTimes($p['team_role_ids']);

                    $returnedData[] = array_merge($p, ['inserted' => true]);
                }
            }
            // DB::commit();
        } catch (Exception $e) {
            // DB::rollBack();
            return response()->json([
                'code' => 0,
                'msg' => '数据库写入失败: ' . $e->getMessage()
            ]);
        }

        return response()->json([
            'code'           => 1,
            'msg'            => '解析并写入完成',
            'timestamp'      => time(),
            'inserted_count' => $insertedCount,
            'skipped_count'  => count($parsed) - $insertedCount,
            'data'           => $returnedData,
        ]);
    }





    /**
     * [addUseTimes 提高角色优先级（排名）]
     * @param array $role_ids [description]
     */
    private function addUseTimes($role_ids = [])
    {
        if (is_array($role_ids)) {
            foreach ($role_ids as $role_id) {
                Role::where('id', (int)$role_id)->increment('use_times');
            }
        }
    }

    public function getBossImages()
    {
        $url = 'https://www.caimogu.cc/gzlj/data/icon?date=&lang=zh-cn';
        $headerArray = [
            'accept:*/*',
            'referer:https://www.caimogu.cc/gzlj.html?',
            'sec-ch-ua:"Chromium";v="130", "Google Chrome";v="130", "Not?A_Brand";v="99"',
            'sec-ch-ua-mobile:?0',
            'sec-ch-ua-platform:"Windows"',
            'user-agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
            'x-requested-with:XMLHttpRequest',
        ];

        $res = $this->getApiUrl($url, $headerArray);

        $data = $res['status'] ? $res['data'][3] : [];


        if ($data) {
            foreach ($data as $key => $boss) {
                $info = ['id' => $boss['id'], 'name' => $boss['iconValue'], 'status' => 0];
                $res = DB::table('boss')->where($info)->first();
                if (is_null($res)) {
                    $ex = $this->getFileExtension($boss['iconFilePath']);
                    $fileName = md5(rand(100000, 999999)) . '.' . $ex;
                    $ok = $this->downloadImage($boss['iconFilePath'], $fileName, 'boss');
                    if ($ok) {
                        $info['file_path'] = $fileName;
                        DB::table('boss')->insert($info);
                    }
                }
            }
        }

        dd('Success');

        // dd($data);


    }

    public function getData()
    {

        // $apiUrl = 'https://www.caimogu.cc/gzlj/data/icon?date=&lang=zh-cn';

        $apiUrl = 'https://www.caimogu.cc/gzlj/data?date=&lang=zh-cn';


        $headerArray = [
            'accept:*/*',
            'referer:https://www.caimogu.cc/gzlj.html?',
            'sec-ch-ua:"Chromium";v="130", "Google Chrome";v="130", "Not?A_Brand";v="99"',
            'sec-ch-ua-mobile:?0',
            'sec-ch-ua-platform:"Windows"',
            'user-agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
            'x-requested-with:XMLHttpRequest',
        ];




        $res = $this->getApiUrl($apiUrl, $headerArray);

        $data = $res['status'] ? $res['data'] : [];

        $map = [];

        foreach ($data as $key => $value) {
            if ($value['stage'] == 5) {
                $map[] = $value['homework'];
            }
        }

        $data = [];

        foreach ($map as $key => $bossTeams) {
            foreach ($bossTeams as $k => $team) {
                if ($team['remain'] == 0) {
                    $data[] = $team;
                }
            }
        }


        $roles = DB::table('data_roles')->join('roles', 'data_roles.role_id', '=', 'roles.id')->select(DB::raw('data_roles.id as `hid`, CASE WHEN `roles`.`is_6` = 1 THEN `roles`.`role_id_6` ELSE `roles`.`role_id_3` END as `role_id`'))->get();
        $roles = $roles ? $roles->toArray() : [];

        $mapRoles = [];
        foreach ($roles as $key => $value) {
            $mapRoles[$value->hid] = $value->role_id;
        }


        
        foreach ($data as $key => $homework) {
            $sn         = $homework['sn'];
            $oldJsonStr = Cache::get($sn);
            $jsonStr    = json_encode($homework);
            if (empty($oldJsonStr)) {
                $type = 1; // 写入
            } else {
                if ($oldJsonStr != $jsonStr) {
                    $type = 2; // 修改
                } else {
                    $type = 3; // 不管
                }
            }

            if ($type == 1 || $type == 2) { // 写入或者修改
                Cache::put($sn, $jsonStr, 7200);
                $oldVideoJsonStr = $oldJsonStr ? json_encode(json_decode($oldJsonStr, 1)['video']) : '';
                $videoJsonStr = json_encode($homework['video']);
                $homeworkInfo = [
                    'id'     => $homework['id'],
                    'sn'     => $sn,
                    'uid'    => 0,
                    'boss'   => $this->getFirstDigit($sn),
                    'score'  => (int)$homework['damage'],
                    'open'   => 2,
                    'status' => 1,
                    'auto'   => $homework['auto'],
                    'remark' => $homework['info']
                ];

                if ($homework['video'] && $oldVideoJsonStr != $videoJsonStr) {
                    foreach ($homework['video'] as $k => $video) {
                        if ($video['image'] && is_array($video['image'])) {
                            foreach ($video['image'] as $kk => $url) {
                                if (isset($url['url']) && $url['url']) {
                                    $ex = $this->getFileExtension($url['url']);
                                    if ($ex) {
                                        $fileName = md5(rand(100000,999999)) . '.' . $ex;
                                        $is_ok = $this->downloadImage($url['url'], $fileName, 'homework');
                                        if ($is_ok) {
                                            $data[$key]['video'][$k]['image'][$kk]['url'] = 'homework/' . $fileName;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $homeworkInfo['video'] = json_encode($data[$key]['video']);
                }
                if ($type == 1) { // 写入
                    $homeworkInfo['created_at'] = Carbon::now();
                    DB::table('teams')->insert($homeworkInfo);
                }
                if ($type == 2) { // 修改
                    $homeworkInfo['updated_at'] = Carbon::now();
                    DB::table('teams')->where('id', $homework['id'])->update($homeworkInfo);

                    // 判断是否需要修改角色
                    $roles = $homework['unit'];
                    $oldRoles = json_decode($oldJsonStr, 1)['unit'];
                    if ($roles != $oldRoles) {
                        DB::table('team_roles')->where('team_id', $homework['id'])->delete();
                        $type = 1;
                    }
                }
                if ($type == 1) {
                    $insertTeamRoles = [];
                    foreach ($homework['unit'] as $kkk => $val) {
                        $insertTeamRoles[] = [
                            'team_id' => $homework['id'],
                            'role_id' => $mapRoles[$val],
                            'status'  => 1,
                        ];
                    }
                    DB::table('team_roles')->insert($insertTeamRoles);
                }

            }

            
        }



        dd('Success');


    }

    private function getApiUrl($url, $headerArray = [])
    {
        $headerArray = $headerArray ?? array("Content-type:application/json;charset='utf-8'","Accept:application/json");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);

        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response, true);
        return $response;
    }

    private function getFirstDigit($string) {
        // 正则表达式：以一个或两个字符开头，后接三个数字
        $pattern = '/^[a-zA-Z]{1,2}(\d{3})$/';

        if (preg_match($pattern, $string, $matches)) {
            // 获取三位数字的第一位
            return in_array($matches[1][0], [1,2,3,4,5]) ? $matches[1][0] : 0; // 返回三位数字的第一位
        } else {
            return 0; // 不符合要求
        }
    }

    private function downloadImage($url, $fileName = '', $path = '')
    {
        $path = $path ?? 'public';
        $type = 1;
        if ($type == 1) {
            $options = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ];
        } else {
            // 设置代理的选项
            $options = [
                'http' => [
                    'proxy' => '127.0.0.1:7890', // 代理服务器和端口
                    'request_fulluri' => true,                 // 必须设置为 true
                    // 'header' => [
                    //     'Proxy-Authorization: Basic ' . base64_encode('username:password') // 如果代理需要认证
                    // ]
                ]
            ];
        }
        
        // 创建上下文
        $context = stream_context_create($options);
        if (empty($fileName)) {
            // 文件名称
            $fileName = rand(10000, 99999) . '.jpg';
        }
        // 本地保存路径
        $localFilePath = public_path('/' . $path . '/' . $fileName);
        $content = @file_get_contents($url, false, $context);
        if ($content) {
            file_put_contents($localFilePath, $content);
            return $fileName;
        }
        return false;
    }

    private function getFileExtension($url) {
        // 使用正则表达式匹配文件后缀
        preg_match('/\.([a-zA-Z0-9]+)(\?.*)?$/', $url, $matches);
        
        // 返回后缀，如果没有匹配则返回 null
        return isset($matches[1]) ? $matches[1] : null;
    }

}
