<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

use App\Model\App;


if (!function_exists('customDump')) {
    function customDump(...$vars) {
        echo '<pre style="background: #242424; color: #39ff14; padding: 10px; margin: 0;">';
        foreach ($vars as $var) {
            ob_start();
            echo $var;
            // var_dump($var);
            $output = ob_get_clean();
            // 修改 var_dump 输出的样式以符合绿色字体
            echo preg_replace('/\bnull\b/', '<span style="color: #ff69b4;">null</span>', $output); // 将 null 变成粉红色作为示例
        }
        echo '</pre>';
    }
}


if (!function_exists('successMSG')) {
	function successMSG($data = [])
	{
		return [
			'code' => 1,
			'msg'  => 'success',
			'data' => $data
        ];
	}
}

if (!function_exists('errMSG')) {
	function errMSG($msg = 'failure')
	{
		$msg = getErrorMsg($msg);
        return ['code' => 0, 'msg' => $msg];
	}
}

if (!function_exists('getErrorMsg')) {
	function getErrorMsg($arr = [])
	{
		while (is_array($arr)){
            $arr = array_shift($arr);
        }
        return $arr;
	}
}

if (!function_exists('jsonPost')) {
	function jsonPost(Request $request)
	{
		if ($request->isMethod('post')) {
            $post = file_get_contents('php://input');
            $request_data = json_decode($post, true);
        }

        if ($request->isMethod('get')) {
            $request_data = $request->all();
        }

        // checkData($request_data);
        return $request_data;
	}
}

if (!function_exists('checkData')) {
	function checkData($data = [])
	{
		$app = App::find('device_app_id');

        if (!$app) {

            abort(500, 'wrong params');
        }

		// if ($data['t'] - 1800 > time() || $data['t'] + 1800 < time()) {

		//     abort(500, 'expires data');
		// }

        $inc_data = $data;
        unset($inc_data['device_sign']);
        ksort($inc_data);
        $t = '';
        foreach ($inc_data as $k => $v) {
            $t .= $k . '=' . $v . '&';
        }
        $t = substr($t, 0, -1);
        $_sign = md5($t . $app->app_key);
        if ($_sign != $data['device_sign']) {

            Log::warning($data);
            abort(500, 'sign error');
        }

        return true;
	}
}

if (!function_exists('awdadwa')) {
	function awdadwa($params = [])
	{
		return false;
	}
}

if (!function_exists('check_book')) {
	function check_book()
	{
		// $redis = Config::get('system.redis');

		$set = DB::table('ch_system_set')->find(1);

		$redis = (int)$set->redis;

		if ($redis) {

			$redis_time = (int)$set->redis_time;

			$book = Redis::get('book');

			$book = unserialize($book);

			if (empty($book)) {

				$book = DB::select('SELECT distinct(title) from `ims_book` where status = 1');

				Redis::set('book', serialize($book));

				Redis::expire('book', $redis_time * 60);
			}

		} else {
			$book = DB::select('SELECT distinct(title) from `ims_book` where status = 1');
		}

		return $book;
	}
}

if (!function_exists('extension_data_user')) {

}

if (!function_exists('white_list')) {

}

if (!function_exists('check_member')) {

}

if (!function_exists('user_array')) {

}

if (!function_exists('check_user')) {

}

if (!function_exists('is_edit')) {

}

if (!function_exists('is_admin')) {

}

if (!function_exists('master')) {
	function master($num = 0)
	{
		$self = Auth::user();
		if (empty($self)) {
			return false;
		}

        $range = DB::table('ch_admin_category')->where('id', (int)$self->cid)->value('range');
        if (empty($range)) {
            return false;
        }

        $range = explode(',', $range);
        if (!in_array((int)$num, $range)) {
            return false;
        }
        return true;
	}
}

if (!function_exists('writeLog')) {
	function writeLog($arr = [])
	{
		if (empty($arr['type']) || empty($arr['where']) || empty($arr['user']) || empty($arr['do']) || empty($arr['content']) || empty($arr['ip'])) {
			// return false;
			show_json(0, '参数错误');
		}
		$data = [
			'type'       => (int)$arr['type'],
			'where'      => $arr['where'],
			'user'       => $arr['user'],
			'do'         => $arr['do'],
			'content'    => $arr['content'],
			'ip'         => $arr['ip'],
			'data'       => serialize($arr),
			'createtime' => timeToStr()
		];
		if (isset($arr['wnid'])) {
			$data['wnid'] = (int)$arr['wnid'];
		}
		DB::table('ch_admin_log')->insert($data);
	}
}

if (!function_exists('show_json')) {
	function show_json($status = 1, $return = NULL)
	{
		$ret = array('status' => $status, 'result' => $status == 1 ? array('url' => URL::current()) : array());

		if (!is_array($return)) {
			if ($return) {
				$ret['result']['message'] = $return;
			}

			exit(json_encode($ret, JSON_UNESCAPED_UNICODE));
		}
		else {
			$ret['result'] = $return;
		}

		if (isset($return['url'])) {
			$ret['result']['url'] = $return['url'];
		}
		else {
			if ($status == 1) {
				$ret['result']['url'] = URL::current();
			}
		}

		exit(json_encode($ret, JSON_UNESCAPED_UNICODE));
	}
}

if (!function_exists('getUserIp')) {
	function getUserIp()
	{
	    $ip = false;
	    //客户端IP 或 NONE
	    if(!empty($_SERVER["HTTP_CLIENT_IP"])) {
	        $ip = $_SERVER["HTTP_CLIENT_IP"];
	    }
	    //多重代理服务器下的客户端真实IP地址（可能伪造）,如果没有使用代理，此字段为空
	    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	        $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
	        if ($ip) { array_unshift($ips, $ip); $ip = FALSE; }
	        for ($i = 0; $i < count($ips); $i++) {
	            if (!eregi ("^(10│172.16│192.168).", $ips[$i])) {
	                $ip = $ips[$i];
	                break;
	            }
	        }
	    }
	    //客户端IP 或 (最后一个)代理服务器 IP
	    return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
	}
}

if (!function_exists('timeToStr')) {
	function timeToStr($num = 6)
	{
		switch ($num) {
			case 5:
				return date('Y-m-d H:i', time());
				break;
			case 6:
				return date('Y-m-d H:i:s', time());
				break;
			default:
				# code...
				break;
		}
	}
}

if (!function_exists('array2level')) {
	function array2level($arr = [], $pid = 0, $level = 1)
	{
		static $list = [];

		foreach ($arr as $key => $value) {

			if ($value->pid == $pid) {

				$value->level = $level;
				$list[] = $value;
				array2level($arr, $value->id, $level + 1);
			}
		}
		return $list;
	}
}

if (!function_exists('getUrl')) {
	function getUrl($url)
    {
    	if (empty($url)) {
    		return false;
    	}
        $headerArray = array("Content-type:application/json;charset='utf-8'","Accept:application/json");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output, true);
        return $output;
    }
}

if (!function_exists('postUrl')) {
	function postUrl($url, $data = [])
	{
        $data  = json_encode($data);
        $headerArray = array("Content-type:application/json;charset='utf-8'","Accept:application/json");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headerArray);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        $output = json_decode($output, true);
        return $output;
	}
}

if (!function_exists('postFile')) {
	function postFile($url, $data = [])
	{
        $data  = json_encode($data);
        $headerArray = array("Content-type:application/json;charset='utf-8'","Accept:application/json");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headerArray);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        $output = json_decode($output,true);
        return $output;
	}
}
