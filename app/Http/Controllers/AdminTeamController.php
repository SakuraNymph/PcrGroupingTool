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
    public function list()
    {
        return view('admin.team.list');
    }

    public function getPublicTeams(Request $request)
    {
        $boss = (int)$request->input('boss');
        if (!in_array($boss, [1,2,3,4,5])) {
            $boss = 1;
        }
        $data = TeamInfoService::getTeams($boss, 3);
        return json_encode(['status' => 1, 'result' => $data]);
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
            $count = Role::where(function ($query) use ($role_ids) {
                $query->whereIn('role_id_3', $role_ids)
                    ->orWhereIn('role_id_6', $role_ids);
            })->count();
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
        $team->open = 2;
        $team->save();
        show_json(1);
    }

    public function delete(Request $request)
    {
        $id = (int)$request->input('id');
        $team = Team::find($id);
        if (!$team) {
            show_json(0);
        }
        $team->open = 0;
        $team->save();
        show_json(1);
    }

    /**
     * [addUseTimes 提高角色优先级（排名）]
     * @param array $role_ids [description]
     */
    private function addUseTimes($role_ids = [])
    {
        if (is_array($role_ids)) {
            foreach ($role_ids as $key => $value) {
                Role::where('role_id_6', (int)$value)->increment('use_times');
                Role::where('role_id_3', (int)$value)->increment('use_times');
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
                $info = ['id' => $boss['id'], 'name' => $boss['iconValue']];
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
