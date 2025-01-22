<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\User;
use App\Services\CustomMailer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use PDO;



class PcrController extends Controller
{
    public function __construct()
    {
        // $this->middleware('web');
    }

    public function index(Request $request)
    {
        $uid = Auth::guard('user')->id();
        return view('welcome', ['uid' => $uid]);
    }

    private function createDatabase()
    {
        $databaseName = 'pcr';

        $dbHost = env('DB_HOST', '127.0.0.1');
        $dbPort = env('DB_PORT', '3306');
        $dbUsername = env('DB_USERNAME', 'root');
        $dbPassword = env('DB_PASSWORD', 'root');



        // 连接数据库服务器
        $pdo = new PDO("mysql:host=$dbHost;port=$dbPort", $dbUsername, $dbPassword);

        // 设置错误模式
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 创建数据库
        $pdo->exec("CREATE DATABASE `$databaseName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");





        $file = base_path('pcr.sql');

        if (!File::exists($file)) {
            return response()->json(['error' => 'File does not exist.'], 404);
        }

        $sql = File::get($file);
        $queries = explode(';', $sql);

        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                try {
                    DB::statement($query);
                } catch (\Exception $e) {
                    return response()->json(['error' => 'Error executing query: ' . $e->getMessage()], 500);
                }
            }
        }
    }

    public function getData2()
    {
        $apiUrl = 'https://www.caimogu.cc/gzlj/data/icon?date=&lang=zh-cn';

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

        $send = false;
        foreach ($data as $key => $value) {
            foreach ($value as $k => $val) {
                // boss信息
                if ($key == 3) {
                    $info = ['id' => $val['id'], 'name' => $val['iconValue']];
                    $res = DB::table('boss')->where($info)->exists();
                    if (!$res) {
                        $ex = $this->getFileExtension($val['iconFilePath']);
                        $fileName = md5(rand(100000, 999999)) . '.' . $ex;
                        $ok = $this->downloadImage($val['iconFilePath'], $fileName, 'boss');
                        if ($ok) {
                            $info['file_path'] = $fileName;
                            $info['status']    = 0;
                            DB::table('boss')->insert($info);
                        }
                    }
                } else {
                    // 角色信息
                    if (trim($val['iconValue'])) {
                        $info = ['id' => $val['id'], 'icon_id' => $val['iconId'], 'icon_value' => $val['iconValue']];
                        $res = DB::table('data_roles')->where($info)->exists();
                        if (!$res) {
                            $info['type'] = 1;
                            $info['icon_file_path'] = $val['iconFilePath'];
                            DB::table('data_roles')->insert($info);
                            $send = true;
                        }
                    }
                }
            }
        }

        if ($send) {
            $customMailer = new CustomMailer;
            $customMailer->send('664990597@qq.com', '公主连结分刀助手', 'PCR有新的角色信息');
        }
        
        return 'Success';
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

        $roles = DB::table('data_roles')->join('roles', 'data_roles.role_id', '=', 'roles.id')->select('data_roles.id as hid', 'roles.role_id')->get();
        $roles = $roles ? $roles->toArray() : [];
      
        $mapRoles = [];
        foreach ($roles as $key => $value) {
            $mapRoles[$value->hid] = $value->role_id;
        }

        
        $send = false; // 发送邮件开关 
        $emailTeam = [];    
        foreach ($data as $key => &$homework) {
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
                $oldVideoJsonStr = $oldJsonStr ? json_encode(json_decode($oldJsonStr, 1)['video']) : '';
                $videoJsonStr = json_encode($homework['video']);
                $homeworkInfo = [
                    'sn'     => $sn,
                    'uid'    => 0,
                    'boss'   => $this->getFirstDigit($sn),
                    'score'  => (int)$homework['damage'],
                    'open'   => 2,
                    'status' => 1,
                    'auto'   => $homework['auto'],
                    'remark' => $homework['info']
                ];

                $tid = DB::table('teams')->where('sn', $sn)->whereYear('created_at', Carbon::now()->year)->whereMonth('created_at', Carbon::now()->month)->value('id');

                $team_titles_map = [];
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
                        if ($tid) {
                            $ok = DB::table('team_titles')->where(['team_id' => $tid, 'title' => $video['text']])->exists();
                            if (!$ok) {
                                $team_titles_map[] = $video['text'];
                            }
                        } else {
                            $team_titles_map[] = $video['text'];
                        }
                    }
                    $homeworkInfo['link'] = json_encode($homework['video']);
                }
                if ($type == 1) { // 写入
                    if ($tid) { // 有值修改
                        $homeworkInfo['updated_at'] = Carbon::now();
                        DB::table('teams')->where('id', $tid)->update($homeworkInfo);
                    } else { // 无值写入
                        $homeworkInfo['created_at'] = Carbon::now();
                        $tid = DB::table('teams')->insertGetId($homeworkInfo);
                    }
                }
                if ($type == 2) { // 修改
                    $homeworkInfo['updated_at'] = Carbon::now();
                    DB::table('teams')->where('id', $tid)->update($homeworkInfo);

                    // 判断是否需要修改角色
                    $roles    = $homework['unit'];
                    $oldRoles = json_decode($oldJsonStr, 1)['unit'];
                    if ($roles != $oldRoles) {
                        $type = 1;
                    }
                }

                // 记录作业标题
                if ($team_titles_map) {
                    $send             = true;
                    $insertTeamTitles = [];
                    foreach ($team_titles_map as $k => $text) {
                        $emailTeam[] = ['sn' => $sn, 'title' => $text];
                        $insertTeamTitles[] = ['team_id' => $tid, 'title' => $text, 'created_at' => Carbon::now()];
                        DB::table('team_titles')->insert($insertTeamTitles);
                    }
                }

                if ($type == 1) {
                    $insertTeamRoles = [];
                    foreach ($homework['unit'] as $kkk => $val) {
                        $insertTeamRoles[] = [
                            'team_id' => $tid,
                            'role_id' => $mapRoles[$val],
                            'status'  => 1,
                        ];
                    }
                    DB::table('team_roles')->where('team_id', $tid)->delete();
                    DB::table('team_roles')->insert($insertTeamRoles);
                }

            }

            Cache::put($sn, $jsonStr, 7200);
            Cache::put('data_huawu', '');
        }

        if ($send) {
            $customMailer = new CustomMailer;
            $html = '<h1>您有新的作业</h1></br>';
            $sn_map = [];
            foreach ($emailTeam as $key => $value) {
                if (!in_array($value['sn'], $sn_map)) {
                    $sn_map[] = $value['sn'];
                    $html .= '<h3>' . $value['sn'] . '</h3>';   
                }
                $html .= '<p>' . $value['title'] . '</p>';
            }
            $html .= '<a href="' . url('/') . '">点击前往分刀工具</a>';
            $emails = User::where(['status' => 1, 'is_subscribe' => 1])->pluck('email');
            foreach ($emails as $key => $email) {
                $customMailer->send($email, '公主连结分刀工具', $html);
            }
        }
        return 'Success';
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

    public function bbbb()
    {
        return view('test');
    }

    public function aaaa()
    {

        $a = 'cache1';
        Cache::put('test_cache', $a);
        $res = Cache::get('test_cache');
        dd($res);

    }

    public function cccc()
    {
        $a = [1,2];
        $b = [1,1,1];

        $c = array_intersect($a, $b);
        $d = array_intersect($b, $a);




        dump($c);
        dump($d);
    }

    public function dddd()
    {

        $data = DB::table('accounts')->get()->toArray();

        // dd($data);


        foreach ($data as $key => $value) {
            $account = Account::where(['id' => $value->id, 'uid' => $value->uid])->first();
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
            // $account->save();
        }


        
        show_json(1);
        





    }
}
