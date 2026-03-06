<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GuideController extends Controller
{
    public function list(Request $request)
    {
        if ($request->isMethod('post')) {

            // 分页
            $pindex     = max(1, (int)$request->input('page'));
            $psize      = max(10, (int)$request->input('limit'));

            $condition  = '';
            $params     = [];

            $condition1 = '';
            $params1    = [];

            // 用户名
            if ($request->input('nickname')) {

                $condition .= ' and name like :nickname';
                $params['nickname'] = '%' . trim(htmlspecialchars($request->input('nickname'))) . '%';
            }

            // 位置
            if ($request->input('position') != '') {

                $condition .= ' and position = :position';
                $params['position'] = (int)$request->input('position');
            }

            // 状态
            if ($request->input('status') != '') {

                $condition .= ' and status = :status';
                $params['status'] = (int)$request->input('status');
            }

            // 公会战角色
            if ($request->input('is_ghz') != '') {

                $condition .= ' and is_ghz = :is_ghz';
                $params['is_ghz'] = (int)$request->input('is_ghz');
            }

            $list = DB::select('SELECT * from `guide` where 1 ' . $condition . ' order by `id` DESC limit ' . ($pindex - 1) * $psize . ',' . $psize, $params);

            $count = DB::select('SELECT count(1) as num from `guide` where 1 ' . $condition, $params);

            return json_encode(['code' => 0, 'msg' => '', 'count' => $count[0]->num, 'data' => $list]);
        }
        return view('admin.guide.list');
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
        $id = 0;
        if (isset($params['id'])) {
            $id = (int)$params['id'] ?? 0;
        }

        if ($params['method'] == 'POST') {
            // 验证请求参数
            $rules = [
                'title' => 'required|max:255',
                'url'   => 'required|max:255',
            ];

            $messages = [
                'title.required'        => '缺少:attribute信息',
                'title.max:255'         => ':attribute长度最大为255',
                'url.required'          => '缺少:attribute信息',
                'url.max:255'           => ':attribute长度最大为255',
                'score.required'        => '缺少:attribute信息',
                'score.integer'         => ':attribute参数错误',
                'score.min'             => '最低:attribute为1',
                // 'difficulty.integer' => ':attribute参数错误',
                // 'difficulty.min'     => '最低:attribute为1',
                // 'difficulty.max'     => '最大:attribute为99',
                'auto.required'         => '参所错误',
                'auto.min'              => '参所错误',
                'auto.max'              => '参所错误',
                'remark.max:255'        => '备注信息长度最大为255',
                'teams.required'        => '缺少阵容信息',
                'teams.array'           => '阵容参数错误',
                'teams.size'            => '阵容参数错误',
            ];

            $paramsName = [
                'title' => '标题',
                'url'   => '链接地址',
            ];

            // 创建验证器
            $validator = Validator::make($params, $rules, $messages, $paramsName);

            if ($validator->fails()) {
                $errorMessages = '';
                $errorArr      = $validator->errors()->toArray();
                foreach ($errorArr as $key => $value) {
                    $errorMessages = $value[0];
                }
                return json_encode(['status' => 0, 'msg' => $errorMessages]);
            }



            // 标题
            $title = htmlspecialchars($params['title']);

            // 链接
            $url   = htmlspecialchars($params['url']);


            $uid = Auth::guard('user')->id();
            if (!$uid) {
                $uid = session('id');
            }
            if (empty($uid)) {
                return json_encode(['status' => 0, 'msg' => '系统错误请刷新']);
            }
            
            $info = [
                'title'      => $title,
                'url'        => $url,
            ];
            if ($id) {
                // 修改
                $info['updated_at'] = Carbon::now();
            } else {
                // 查询sort
                $lastSort = DB::table('guide')->where('sort', '<', 999)->max('sort');
                $sort = $lastSort + 1;

                // 添加
                $info['uid']        = $uid;
                $info['sort']       = $sort;
                $info['type']       = 1;
                $info['status']     = 0;
                $info['created_at'] = Carbon::now();
            }

            if ($id) {
                // 修改
                DB::table('guide')->where('id', $id)->update($info);
                return json_encode(['status' => 1]);
            } else {
                // 添加
                $ok = DB::table('guide')->insert($info);
                return $ok ? json_encode(['status' => 1]) : json_encode(['status' => 0, 'msg' => '添加失败']);
            }
        }

        $data = DB::table('guide')->where('id', $id)->first();
        return view('admin.guide.add', ['id' => $id, 'data' => $data]);
    }

    public function status(Request $request)
    {
        $id = (int)$request->input('id');
        if ($id) {
            $status = DB::table('guide')->where('id', $id)->value('status');
            DB::table('guide')->where('id', $id)->update(['status' => (1 - $status)]);
            return json_encode(['status' => 1]);
        }
        return json_encode(['status' => 0, 'msg' => '参数错误']);
    }

    public function type(Request $request)
    {
        $id = (int)$request->input('id');
        $type = (int)$request->input('type');
        if ($id && $type) {
            DB::table('guide')->where('id', $id)->update(['type' => $type]);
        }
        return json_encode(['status' => 1]);
    }

    public function delete(Request $request)
    {
        $id = (int)$request->input('id');
        DB::table('guide')->where('id', $id)->delete();
        return json_encode(['status' => 1]);
    }

    // 攻略数据接口
    public function getData(Request $request)
    {
        $type = (int)$request->input('type');
        $data = DB::table('guide')->where(['status' => 1, 'type' => $type])->orderBy('sort', 'DESC')->get();
        $data = $data ? $data->toArray() : [];
        return json_encode(['status' => 1, 'data' => $data]);
    }

    // 攻略页视图
    public function guide()
    {
        return view('guide');
    }
}
