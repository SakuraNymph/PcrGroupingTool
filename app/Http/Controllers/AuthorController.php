<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Role;

class AuthorController extends Controller
{
    /**
     * [getAuthor 获取作者列表]
     * @return [type] [description]
     */
    public function getAuthor()
    {
        $authors = DB::table('authors')->get()->toArray();
        $status = $authors ? 1 : 0;
        $this->show_json($status, $authors);
    }

    /**
     * [addAuthor 添加作者]
     * @param Request $request [description]
     */
    public function addAuthor(Request $request)
    {
        $author = $request->input('author');
        $method = $request->method();
        if ($method == 'POST') {
            $count = DB::table('authors')->count();
            if ($count >= 4) {
                $this->show_json(0, '最多只能添加4名作者！');
            }
            $ok = DB::table('authors')->where('name', $author)->first();
            if ($ok) {
                $this->show_json(0, '作者:' . $author . '已存在，请勿重复添加！');
            }
            $res = DB::table('authors')->insert(['name' => $author]);
            if ($res) {
                $this->show_json(1, '作者添加成功！');
            }
            $this->show_json(0, '作者添加失败！');
        }
        return view('author');
    }





    public function show_json($status = 1, $return = NULL)
    {
        $ret = array('status' => $status, 'result' => $status == 1 ? array() : array());

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
                // $ret['result']['url'] = URL::current();
            }
        }

        exit(json_encode($ret, JSON_UNESCAPED_UNICODE));
    }
}
