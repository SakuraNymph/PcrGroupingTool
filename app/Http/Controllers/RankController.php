<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\RankInfo;
use App\Models\Role;
use App\Models\User;
use App\Services\RankService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class RankController extends Controller
{
    public function index()
    {
        $map = [];
        $num = 12;
        $authors = [];
        $data = DB::table('rank_infos')->orderByRaw('LENGTH(role_ids) DESC')->get()->toArray();
        foreach ($data as $key => $value) {
            $map[$value->author_id][] = [
                'id' => $value->id,
                'rank_name' => $value->rank_name,
                'role_ids' => explode(',', $value->role_ids)
            ];
        }
        ksort($map);
        $count = count($map);
        if ($count > 0) {
            if ($count > 4) {
                $count = 4;
            }
            $num = ceil(12 / $count);
        }
        $authors = Author::orderBy('id')->get()->toArray();
        return view('index', ['map' => $map, 'num' => $num, 'authors' => $authors]);
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
        if ($params['method'] == 'POST') {
            $author_id = (int)$params['author_id'];
            if (empty($author_id)) {
                $this->show_json(0, '作者不能为空！');
            }
            $ok = Author::find($author_id);
            if (!$ok) {
                $this->show_json(0, '参数错误，请刷新重试！');
            }
            if (!empty($params['id'])) {
                $update              = [];
                $update['author_id'] = $author_id;
                $update['rank_name'] = $params['rank_name'];
                $update['role_ids']  = implode(',', $params['role_ids']);

                // 先查询是否存在重名数据
                $has = DB::table('rank_infos')->where('author_id', $update['author_id'])->where('rank_name', $update['rank_name'])->where('id', '<>', $params['id'])->first();
                if ($has) {
                    $this->show_json(0, 'RANK信息已存在');
                }
                DB::table('rank_infos')->where('id', (int)$params['id'])->update($update);
                $this->show_json(1, '修改成功');
            } else {
                $this->addUseTimes($params['role_ids']);
                $insert              = [];
                $insert['author_id'] = $author_id;
                $insert['rank_name'] = $params['rank_name'];
                $insert['role_ids']  = implode(',', $params['role_ids']);

                // 先查询是否存在重名数据
                $has = DB::table('rank_infos')->where('author_id', $insert['author_id'])->where('rank_name', $insert['rank_name'])->first();
                if ($has) {
                    $this->show_json(0, 'RANK信息已存在');
                }
                $res = DB::table('rank_infos')->insert($insert);
                if ($res) {
                    $this->show_json(1, '添加成功');
                } else {
                    $this->show_json(0, '添加失败');
                }
            }
        }

        $id           = 0;
        $data         = [];
        $author_id    = isset($params['author_id']) ? (int)$params['author_id'] : 0;
        $authors      = [];
        $role_ids_use = [];
        $ids_not_in   = [];
        $ids_json = '';

        if ($author_id == 0) {
            $au_info = DB::table('authors')->orderBy('id')->first();
            if ($au_info) {
                $author_id = $au_info->id;
            }
        }


        if (!empty($params['id'])) {
            $id             = (int)$params['id'];
            $data           = DB::table('rank_infos')->where('id', $id)->first();
            $data->role_ids = explode(',', $data->role_ids);
            $role_ids_use   = $data->role_ids;
            $ids_json = json_encode($role_ids_use);
            $author_id     = (int)$data->author_id;
        }

        $role_id_ok = DB::table('rank_infos')->where('author_id', $author_id)->get();
        foreach ($role_id_ok as $key => $value) {
            $ids_arr = explode(',', $value->role_ids);
            foreach ($ids_arr as $k => $v) {
                $ids_not_in[] = $v;
            }
        }


        if (!empty($params['id'])) {
            $ids_not_in = array_diff($ids_not_in, $role_ids_use);
        }

        $ids_not_in = Role::where('status', 1)->where(function ($query) use ($ids_not_in) {
            $query->whereIn('role_id_3', $ids_not_in)->orWhereIn('role_id_6', $ids_not_in);
        })->get('role_id')->toArray();
        $ids_not_in = array_column($ids_not_in, 'role_id');

        // 角色
        $roles = DB::table('roles')->select(DB::raw(' CASE WHEN `is_6` = 1 THEN `role_id_6` ELSE `role_id_3` END as `role_id`, `position` '))->where('status', 1)->where('is_ghz', 1)->where(function ($query) use ($ids_not_in) {
            $query->whereNotIn('role_id', $ids_not_in);
        })->orderBy('use_times', 'DESC')->orderBy('role_id')->get();
        $roles_map = [];
        foreach ($roles as $key => $value) {
            $roles_map[$value->position][] = $value;
        }

        // 作者
        $authors = DB::table('authors')->orderBy('id')->get()->toArray();
        return view('post', ['data' => $data, 'id' => $id, 'roles_map' => $roles_map, 'author_id' => $author_id, 'ids_not_in' => $ids_not_in, 'authors' => $authors, 'ids_json' => $ids_json]);
    }

    public function getCanUseRoles(Request $request)
    {
        $id           = (int)$request->input('id');
        $author_id    = $request->input('author_id');
        $role_ids_use = [];
        $roles_map    = [];
        $ids_not_in   = [];
        $switch       = 0;

        if ($id) {
            $data           = DB::table('rank_infos')->where('id', $id)->where('author_id', $author_id)->first();
            if ($data) {
                $data->role_ids = explode(',', $data->role_ids);
                $role_ids_use   = $data->role_ids;
            } else {
                $id = 0;
            }
        }

        $role_id_ok = DB::table('rank_infos')->where('author_id', $author_id)->get()->toArray();

        foreach ($role_id_ok as $key => $value) {
            $ids_arr = explode(',', $value->role_ids);
            foreach ($ids_arr as $k => $v) {
                $ids_not_in[] = $v;
            }
        }

        if ($id) {
            $ids_not_in = array_diff($ids_not_in, $role_ids_use);
        }

        $ids_not_in = Role::where('status', 1)->where(function ($query) use ($ids_not_in) {
            $query->whereIn('role_id_3', $ids_not_in)->orWhereIn('role_id_6', $ids_not_in);
        })->get('role_id')->toArray();
        $ids_not_in = array_column($ids_not_in, 'role_id');

        // 角色
        $roles = DB::table('roles')->select(DB::raw(' CASE WHEN `is_6` = 1 THEN `role_id_6` ELSE `role_id_3` END as `role_id`, `position`, `nickname` '))->where('status', 1)->where(function ($query) use ($ids_not_in) {
            $query->whereNotIn('role_id', $ids_not_in);
        })->orderBy('use_times', 'DESC')->orderBy('role_id')->get()->toArray();

        foreach ($roles as $key => $value) {
            $switch = in_array($value->role_id, $role_ids_use) ? 1 : 0;
            $roles_map[$value->position][] = ['role_id' => $value->role_id, 'switch' => $switch, 'nickname' => $value->nickname];
        }
        $this->show_json(1, $roles_map);
    }

    /**
     * [addUseTimes 提高角色优先级（排名）]
     * @param array $role_ids [description]
     */
    private function addUseTimes($role_ids = [])
    {
        if (is_array($role_ids)) {
            foreach ($role_ids as $key => $value) {
                DB::table('roles')->where('role_id_6', (int)$value)->increment('use_times');
                DB::table('roles')->where('role_id_3', (int)$value)->increment('use_times');
            }
        }
    }

    public function delete(Request $request)
    {
        $id = (int)$request->input('id');
        DB::table('rank_infos')->delete($id);
        $this->show_json(1);
    }

    public function result()
    {
        $role_ids_map       = [];
        $rank_info_map      = [];
        $rank_info_data     = [];
        $rank_info_data_tmp = [];
        $colors             = ['', 'red', 'blue', 'green', 'purple'];
        $author_colors      = [];

        $data = DB::table('rank_infos')->orderBy('id')->get()->toArray();
        foreach ($data as $key => $value) {
            if (empty($rank_info_map[$value->author_id][$value->rank_name])) {
                $rank_info_map[$value->author_id][$value->rank_name] = explode(',', $value->role_ids);
            } else {
                $rank_info_map[$value->author_id][$value->rank_name] = array_merge($rank_info_map[$value->author_id], explode(',', $value->role_ids));
            }
        }

        // 按攻击力降序排序
        $roles = DB::table('roles')
            ->select(DB::raw("IF(is_6 = 1, role_id_6, role_id_3) AS role_id"))
            ->orderBy(DB::raw("GREATEST(physical_atk, magic_atk)"), 'desc')
            ->pluck('role_id')->toArray();

        foreach ($rank_info_map as $author_id => $rank_info) {
            foreach ($rank_info as $rank_name => $role_ids) {
                foreach ($role_ids as $key => $role_id) {
                    if (!in_array($role_id, $role_ids_map)) {
                        $role_ids_map[array_search($role_id, $roles)] = $role_id;
                    }
                }
            }
            $author_colors[$author_id] = next($colors);
        }

        // ksort($role_ids_map);

        foreach ($role_ids_map as $key => $role_id) {
            foreach ($rank_info_map as $author_id => $rank_info) {
                foreach ($rank_info as $rank_name => $role_ids) {
                    foreach ($role_ids as $key => $role) {
                        if ($role_id == $role) {
                            $rank_info_data_tmp[$role_id][] = [
                                'author_id' => $author_id,
                                'rank_name' => $rank_name,
                                'color'     => $author_colors[$author_id]
                            ];
                        }
                    }
                }
            }
        }

        foreach ($rank_info_data_tmp as $role_id => $value) {
            $rank_info_data[count($value)][$role_id] = $value;
        }

        foreach ($rank_info_data as $key => $role) {
            uasort($rank_info_data[$key], function ($a, $b) {
                // return $b[0]['author_id'] <=> $a[0]['author_id']; 
            });
            foreach ($role as $k => $v) {
                usort($rank_info_data[$key][$k], function ($a, $b) {
                    // return $a['author_id'] <=> $b['author_id'];
                });
            }
        }
        krsort($rank_info_data); // 按使用频率排序
// dd($rank_info_data);

        $authors = DB::table('authors')->orderBy('id')->get()->toArray();
        foreach ($authors as $key => $value) {
            if (isset($author_colors[$value->id])) {
                $authors[$key]->color = $author_colors[$value->id];
            }
        }

        return view('result', ['data' => $rank_info_data, 'colors' => $authors]);
    }


    public function teach()
    {
        $html = '';
        for ($i=1; $i < 9; $i++) { 
            $html .= '<img src="' . asset('rank') . '/step'. $i .'.jpg" alt="" style="width: 100%; height: auto;" loading="lazy">';
        }
        echo $html;
    }

    public function support()
    {
        $html = '';
        $html .= '<img src="' . asset('rank') . '/vx.png" alt="" style="width: 100%; height: auto;" loading="lazy">';
        $html .= '<img src="' . asset('rank') . '/zfb.jpg" alt="" style="width: 100%; height: auto;" loading="lazy">';
        echo $html;
    }

    public function bug()
    {
        $html = '<img src="' . asset('rank') . '/qun.jpg" alt="" style="width: 100%; height: auto;" loading="lazy">';
        echo $html;
    }

    public function subscribe(Request $request)
    {
        $uid = Auth::guard('user')->id();
        $status = User::where('id', $uid)->value('is_subscribe');
        if ($request->method() == 'POST') {
            User::where('id', $uid)->update(['is_subscribe' => ( 1 - (int)$status )]);
            $msg = $status ? '关闭成功' : '开启成功';
            return json_encode(['msg' => $msg]);
        }
        return view('subscribe', ['status' => $status]);
    }

    

    public function toupiao(Request $request)
    {
        if ($request->method() == 'POST') {
            $uid = session('id');
            $star = (int)$request->input('star');
            if ($star == 1 || $star == 3) {
                $ok = DB::table('toupiao')->where('uid', $uid)->first();
                if ($ok) {
                    show_json(0, '您已投过票，请勿重复投票');
                }
                DB::table('toupiao')->insert(['uid' => $uid, 'star' => $star]);
                show_json(1);
            }
            show_json(0, '投票失败');
        }
        return view('toupiao');
    }

    public function deleteAuthor(Request $request)
    {
        $id = (int)$request->input('id');
        DB::transaction(function () use ($id) {
            DB::table('authors')->delete($id);
            DB::table('rank_infos')->where('author_id', $id)->delete();
        });
        $this->show_json(1);
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

    public function ip(Request $request)
    {
        $uid = session('id');
        $ip = $request->ip();
        $aid = Auth::guard('user')->id();
        dump('aid:' . $aid);
        dump('uid:' . $uid);
        dump('IP:' . $ip);
    }

    public function aaaa()
    {
        $res = RankService::testCache();

        dd($res);
    }
}