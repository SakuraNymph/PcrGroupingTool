<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Models\UserGuideConfig;
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
        $uid = $this->uid();
        $switch = UserGuideConfig::getUserGuideConfig($uid, 'welcome');
        return view('welcome', ['uid' => $uid, 'switch' => $switch]);
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
                    $sort = $k + 1;
                    $info = ['id' => $val['id'], 'name' => $val['iconValue']];
                    $res = DB::table('boss')->where($info)->exists();
                    if (!$res) {
                        $ex = $this->getFileExtension($val['iconFilePath']);
                        $fileName = md5(rand(100000, 999999)) . '.' . $ex;
                        $ok = $this->downloadImage($val['iconFilePath'], $fileName, 'boss');
                        if ($ok) {
                            $info['file_path'] = $fileName;
                            $info['status']    = 1;
                            $info['sort']      = $sort;
                            DB::table('boss')->where('sort', $sort)->update(['status' => 0]);
                            DB::table('boss')->insert($info);
                        }
                    } else {
                        // 修改boss状态
                        DB::table('boss')->where('sort', $sort)->update(['status' => 0]);
                        DB::table('boss')->where('id', $val['id'])->update(['status' => 1]);
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

        $res  = $this->getApiUrl($apiUrl, $headerArray);
        $data = $res['status'] ? $res['data'] : [];
        $map  = [];
        $send = false;

        foreach ($data as $key => $value) {
            $map[] = $value['homework'];
        }

        $data = [];

        foreach ($map as $key => $bossTeams) {
            foreach ($bossTeams as $k => $team) {
                if ($team['remain'] == 0) { // 0是整刀 1是尾刀
                    $data[] = $team;
                }
            }
        }

        $rolesMap = DB::table('roles')->select('id', 'atk_type')->get()->keyBy('id')->toArray();
        foreach ($data as $key => &$homework) {
            $sn         = $homework['sn'];
            $oldJsonStr = Cache::get($sn);
            $jsonStr    = json_encode($homework);
            if (empty($oldJsonStr)) {
                $type = 1; // 写入
                $send = true;
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

                $stage = $this->getStageBySn($sn);
                $open = 0;
                if ($stage == 2 || $homework['auto'] == 1) {
                    $open = 0;
                }
                $status = 1;
                if ($stage != 5) {
                    $status = 0;
                }

                // 按 search_area_width 降序排序 A
                usort($homework['unit'], function($x, $y) use ($rolesMap) {
                    $widthX = $rolesMap[$x]->search_area_width ?? 0;  // ID 不存在用 0
                    $widthY = $rolesMap[$y]->search_area_width ?? 0;
                    return $widthY <=> $widthX;  // 降序：y 的宽度 > x 的宽度，返回负数（y 先）
                    // 或者用: return $widthX - $widthY;  // 简单减法，也实现降序（但数字大时注意溢出）
                });

                // rsort($homework['unit'], SORT_NUMERIC);
                $teamKey = implode(',', $homework['unit']);

                $atk_value = 0;
                $team = [];
                foreach ($homework['unit'] as $roleId) {
                    $team[] = ['role_id' => $roleId, 'status' => 1];
                    if (isset($rolesMap[$roleId])) {
                        $atk_value += $rolesMap[$roleId]->atk_type; 
                    }
                }

                $homeworkInfo = [
                    'stage'     => $stage,
                    'sn'        => $sn,
                    'uid'       => 0,
                    'boss'      => $this->getFirstDigit($sn),
                    'damage'    => (int)$homework['damage'],
                    'team_key'  => $teamKey,
                    'team'      => json_encode($team, JSON_UNESCAPED_UNICODE),
                    'status'    => $status,
                    'auto'      => $homework['auto'],
                    'atk_value' => $atk_value,
                    'remark'    => $homework['info'],
                ];

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

                        // 寻找该sn下该作业是否已经存在
                        $ok = DB::table('team_titles')
                                    ->where('title', $video['text'])
                                    ->whereYear('created_at', Carbon::now()->year)
                                    ->whereMonth('created_at', Carbon::now()->month)
                                    ->exists();
                        if (!$ok) {
                            $team_titles_map[] = $video['text'];
                        }
                        // 获取作业中最高伤害
                        preg_match_all('/(\d+)[wW]/', $video['text'], $matches_w);
                        $damage_w = !empty($matches_w[1]) ? array_map('intval', $matches_w[1]) : [];
                        // 新增：匹配 xxxe 或 xxx.xxxe 格式（xxx 可为整数或浮点数，小数位数不确定），提取数字部分并乘以 10000
                        preg_match_all('/(\d+(?:\.\d+)?)[eE]/', $video['text'], $matches_e);
                        $damage_e = [];
                        if (!empty($matches_e[1])) {
                            $damage_e = array_map(function($value) {
                                return floatval($value) * 10000;
                            }, $matches_e[1]);
                        }
                        $damage = array_merge($damage_w, $damage_e);
                        $max = !empty($damage) ? max($damage) : 0;
                        if ($max > $homeworkInfo['damage']) {
                            $homeworkInfo['damage'] = $max;
                        }
                    }
                }

                $homeworkInfo['link'] = $homework['video'];

                // 查找作业是否存在
                $updateData = Team::where(['stage' => $homeworkInfo['stage'], 'boss' => $homeworkInfo['boss'], 'auto' => $homeworkInfo['auto'], 'team_key' => $homeworkInfo['team_key'], 'uid' => 0])
                            ->whereYear('created_at', Carbon::now()->year)
                            ->whereMonth('created_at', Carbon::now()->month)
                            ->first();

                if ($updateData) {
                    $type = 2;
                }

                if ($type == 1) { // 写入
                    $homeworkInfo['created_at'] = Carbon::now();
                    $homeworkInfo['link'] = json_encode($homeworkInfo['link'], JSON_UNESCAPED_UNICODE);
                    DB::table('teams')->insert($homeworkInfo);
                }
                if ($type == 2) { // 修改
                    $toUpdate = ['sn' => $homeworkInfo['sn'], 'updated_at' => Carbon::now()]; // 修改时间
                    if ($homeworkInfo['damage'] > $updateData->damage) { // 最大伤害
                        $toUpdate['damage'] = $homeworkInfo['damage'];
                    }

                    $textMap = array_column($updateData->link, 'text');
                    $toUpdate['link'] = $updateData->link;
                    foreach ($homeworkInfo['link'] as $video) {
                        if (!in_array($video['text'], $textMap)) {
                            $toUpdate['link'] = array_merge($toUpdate['link'], [$video]);
                        }
                    }
                    $toUpdate['link'] = json_encode($toUpdate['link'], JSON_UNESCAPED_UNICODE);
                    Team::where('id', $updateData->id)->update($toUpdate);
                }

                // 记录作业标题
                if ($team_titles_map) {
                    $insertTeamTitles = [];
                    foreach ($team_titles_map as $text) {
                        $insertTeamTitles[] = ['sn' => $sn, 'title' => $text, 'created_at' => Carbon::now()];
                    }
                    DB::table('team_titles')->insert($insertTeamTitles);
                }
            }

            Cache::put($sn, $jsonStr, 2);
        }
        unset($homework);

        Cache::put('data_huawu', '');

        if ($send) {
            $emails = User::select('id', 'email')->where(['status' => 1, 'is_subscribe' => 1])->whereRaw('CURTIME() BETWEEN `sub_start` AND `sub_end`')->get()->toArray();
            foreach ($emails as $email) {
                $html = $this->makeHtml($email['id']);
                if ($html) {
                    $customMailer = new CustomMailer;
                    $customMailer->send($email['email'], '公主连结分刀工具', $html);
                }
            }
        }
            
        return 'Success';
    }

    public function test3()
    {
        $locations = ['welcome', 'list', 'post', 'team', 'group'];
        dump($locations);

    }

    private function makeHtml($uid)
    {
        // 已经发送的team_id
        $already = DB::table('user_send_team_titles')
                        ->where('uid', $uid)
                        ->whereYear('created_at', Carbon::now()->year)
                        ->whereMonth('created_at', Carbon::now()->month)
                        ->orderBy('role_combo_id')
                        ->orderBy('team_id')
                        ->pluck('team_id')
                        ->toArray();

        // 获取本月全部的作业数据
        $data = Cache::get('data_huawu');
        $data = [];
        if (empty($data)) {
            $data = DB::table('team_titles')
                        ->whereYear('created_at', Carbon::now()->year)
                        ->whereMonth('created_at', Carbon::now()->month)
                        // ->orderBy('team_id')
                        ->orderBy('id')
                        ->get()
                        ->toArray();
            Cache::put('data_huawu', $data);
        }
        $html = '<h1>您有新的作业</h1></br>';
        $sn_map = [];
        $insert = [];
        foreach ($data as $team) {
            if (!in_array($team->id, $already)) {
                if (!in_array($team->sn, $sn_map)) {
                    $sn_map[] = $team->sn;
                    $html .= '<h3>' . $team->sn . '</h3>';
                }
                $html .= '<p>' . $team->title . '</p>';
                $insert[] = ['uid' => $uid, 'team_id' => $team->id, 'created_at' => Carbon::now()];
            }
        }
        $html .= '<a href="' . url('/') . '">点击前往分刀工具</a>';
        if ($insert) {
            DB::table('user_send_team_titles')->insert($insert);
            return $html;
        }
        return false;
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

    private function postApiUrl($url, $data = [], $headerArray = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  // 直接传数组，cURL 会自动编码为 `application/x-www-form-urlencoded`
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 忽略 SSL 证书（如有需要）

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

    private function getStageBySn($sn)
    {
        $map = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5];
        return $map[$sn[0]];
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
        $html = file_get_contents(public_path('a.txt'));

        // HTML 内容


        // 正则表达式匹配 <a> 标签的 href 属性
        preg_match_all('/<a\s+[^>]*?href="([^"]+)"/i', $html, $matches);

        $insert = [];

        // 输出结果
        if (!empty($matches[1])) {
            foreach ($matches[1] as $href) {
                $insert[] = ['image_url' => $href, 'status' => 0];
                // echo "提取到的 href: " . $href . "\n";
            }
        } else {
            echo "未找到任何 href 属性。";
        }

        DB::table('images')->insert($insert);

        dd('Success');
    }

    public function aaaa()
    {

        $emails = User::where(['status' => 1, 'is_subscribe' => 1])->whereRaw('CURTIME() BETWEEN `sub_start` AND `sub_end`')->pluck('id')->toArray();

        dd($emails);

    }

    public function cccc()
    {




        $data = User::whereNull('city')->get()->toArray();
        foreach ($data as $key => $value) {
            $res = $this->getIpLocation($value['ip']);
            User::where('id', $value['id'])->update($res);
        }

        dd('Success');
    }

    public function getIpLocation($ip) {
        $key = '3963783c76554a332b882a49a961df3a';
        $apiUrl = "https://restapi.amap.com/v3/ip?ip=$ip&key=$key";

        $response = json_decode(file_get_contents($apiUrl), true);

        if ($response['status'] == '1' && $response['province'] !== []) {
            return [
                'country' => '中国',
                'province' => $response['province'],
                'city' => $response['city']
            ];
        } else {
            $foreignApi = "http://ip-api.com/json/$ip?lang=zh-CN";
            $foreignResponse = json_decode(file_get_contents($foreignApi), true);
            return [
                'country' => $foreignResponse['country']
            ];
        }
    }

    public function eeee()
    {
        $id                = 1295;
        $name              = '千爱瑠（冬日）';
        // $name           = '若菜';
        $search_area_width = '247';
        $position          = '1'; // 前卫1 中卫2 后卫3
        $atk_type          = 1; // -1魔法攻击 0辅助 1物理攻击 
        $element           = 2; // 1火 2水 3风 4光 5暗
        
        $obtain            = 4; // 1抽卡获取2活动获取3兑换获取
        $probability       = 1; // 抽卡概率 1(0.7)2(1.4)
        $up_time           = '2026-02-28'; // 卡池开始时间

        $role_id           = $id * 100 + 1;
        $role_id_1         = $role_id + 10;
        $role_id_3         = $role_id + 30;
        
        $sql = "INSERT INTO `pcr`.`roles` (`id`, `role_id`, `role_id_1`, `role_id_3`, `role_id_6`, `element`, `atk_type`, `physical_atk`, `magic_atk`, `is_6`, `is_ghz`, `obtain`, `probability`, `up_time`, `name`, `nickname`, `search_area_width`, `position`, `is_download`, `use_times`, `status`, `created_at`, `updated_at`) VALUES ($id, $role_id, $role_id_1, $role_id_3, NULL, $element, $atk_type, 0, 0, 0, 0, $obtain, $probability, '$up_time', '$name', '', $search_area_width, $position, 3, 0, 1, NULL, '2025-03-31 21:13:10')";

        echo  $sql;
    }


    public function dddd()
    {

        $info = DB::table('images')->where('status', 0)->first();

        $fileName = $info->image_url;
        $id = $info->id;


        $url = 'https://redive.estertion.win/icon/unit/' . $fileName;

        $res = $this->downloadImage($url, $fileName, 'images');

        if ($res) {
            DB::table('images')->where('id', $id)->update(['status' => 1]);
            show_json(1, 'Success');
        }

        show_json(0, 'Error');

    }

    public function aabb()
    {
        $map = [
            0 => ['白骑', '克总', '513', '火猫', '黑m', '流沙', '水剑', '圣电', '瓜智', '水猫剑', '猫剑', '姬塔', '野锤', '611', '超吃', '黑姐', '智', '圣锤', '海中二', '狼'],
            1 => ['魔姬', '礼伊', '晶', '圣妹', '但丁', '解望', '松鼠', '蝶妈', '春花', '黄骑', '火电', '水电', '机娘', '蛋黄', '水兔', '魔二力', '天姐', '水白', '水望', '野骑', '毛二力', '礼雪', '水鬼', '水千', '工菜', '水田', '斑比酱', '狼布丁', '春月', '圣吃'],
            2 => ['爱梅斯', '水兰法', '莱莱', '白猫', '水花', '似似花', '真步', '江雪', '坏女人', '富婆', '春母', '猫咖', '屁狐', '学猫', '舞铃', '灰狐', '星栞', '圣优妮', '车眼', '栞', '绿魔', '游栞', '圣婆']
        ];

        // 手动纠正词典：错误 => 正确
        $corrections = [
            '品' => '晶',
            '最' => '晶',
            '葉' => '栞',
            '水免' => '水兔',
        ];

        // 🔥 把所有关键词合并成一个总列表，并按长度降序排序
        $allKeywords = [];
        foreach ($map as $groupKey => $keywords) {
            foreach ($keywords as $keyword) {
                $allKeywords[$keyword] = $groupKey; // 记录关键词属于哪个组
            }
        }
        uksort($allKeywords, function ($a, $b) {
            return mb_strlen($b) - mb_strlen($a);
        });

        $input = <<<EOD
            D1-黑姐水免机娘晶春母-6090w
            D1-灰狐春母星栞圣优妮猫咖-5943w
            D1-机娘蛋黄晶爱梅斯富婆-4843w
            D1-机娘蛋黄晶爱梅斯富婆-5511w
            D1-水白晶春母星葉富婆-4586w
            D1-水鬼水兔机娘蝶妈晶-4261w
            D2-611机娘蝶妈爱梅斯圣优妮-4328w
            D2-春花水花坏女人真步学猫-4670w
            D2-春花水兰法坏女人爱梅斯学猫-5096w
            D2-火电水兔机娘蝶妈晶-4406w
            D2-似似花坏女人车眼爱梅斯学猫-3849w
            D2-水免机娘蝶妈最爱梅斯-4509w
            D2-智水兔机娘蝶妈舞铃-4258w
            D3-R31-0爱梅斯魔姬礼雪礼伊似似花-5411w
            D3-蝶妈晶狼布丁春月栞-3789w
            D3-蝶妈晶狼布丁栞圣婆-3993w
            D3-蝶妈晶狼布丁栞游栞-4131w
            D3-海中二魔姬礼伊爱梅斯白猫-3720w
            D3-礼伊绿魔水兰法莱莱白猫-4092w
            D3-魔姬礼雪水田斑比酱礼伊-4437w
            D3-魔姬天姐礼伊水花似似花-4173w
            D4-白骑蝶妈晶栞游栞-4305w
            D4-白骑工菜蝶妈晶栞-3683w
            D4-黑m魔姬莱莱白猫江雪-3921w
            D4-黑m魔姬莱莱白猫圣优妮-4246w
            D4-黑m魔姬礼伊莱莱白猫-4605w
            D5-513瓜智解望水千晶-5342w
            D5-513水剑瓜智水千晶-5259w
            D5-超吃圣锤瓜智水千晶-5484w
            D5-超吃圣锤水剑水千晶-5125w
            D5-圣吃圣妹魔姬礼雪礼伊-4500w
        EOD;

        $res = explode("\n", trim($input));

        // 初始化 use 数组
        $use = array_map(function () {
            return [];
        }, $map);

        // 处理每一行文本
        foreach ($res as &$line) {
            $line = strtr($line, $corrections);

            foreach ($allKeywords as $keyword => $groupKey) {
                if (mb_strpos($line, $keyword) !== false) {
                    $line = str_replace($keyword, '', $line);
                    if (!in_array($keyword, $use[$groupKey])) {
                        $use[$groupKey][] = $keyword;
                    }
                }
            }
        }
        unset($line);

        dump($res);
        dd($use);
    }
}
