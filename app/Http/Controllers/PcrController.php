<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
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

        // dd(Auth::guard('admin')->user());
        // dd(Auth::guard('user')->user());

        // Auth::logout();
        // $request->session()->invalidate();
        // $request->session()->regenerateToken();

        return view('welcome');
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
                    $homeworkInfo['link'] = json_encode($homework['video']);
                }
                if ($type == 1) { // 写入
                    $homeworkInfo['created_at'] = Carbon::now();
                    $tid = DB::table('teams')->insertGetId($homeworkInfo);
                }
                if ($type == 2) { // 修改
                    $homeworkInfo['updated_at'] = Carbon::now();
                    $tid = DB::table('teams')->where('sn', $sn)->whereYear('created_at', Carbon::now()->year)->whereMonth('created_at', Carbon::now()->month)->value('id');
                    DB::table('teams')->where('id', $tid)->update($homeworkInfo);

                    // 判断是否需要修改角色
                    $roles = $homework['unit'];
                    $oldRoles = json_decode($oldJsonStr, 1)['unit'];
                    if ($roles != $oldRoles) {
                        DB::table('team_roles')->where('team_id', $tid)->delete();
                        $type = 1;
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
                    DB::table('team_roles')->insert($insertTeamRoles);
                }

            }

            Cache::put($sn, $jsonStr, 7200);
        }


        return 'Success';

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

    public function aaaa()
    {

        for ($i=0; $i < 5; $i++) { 
            if ($i == 3) {
                return 6666;
            }
            dump($i);
        }



    }
}
