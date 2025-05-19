<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
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

            // 6星开花
            if ($request->input('is_6') != '') {

                $condition .= ' and is_6 = :is_6';
                $params['is_6'] = (int)$request->input('is_6');
            }

            // 公会战角色
            if ($request->input('is_ghz') != '') {

                $condition .= ' and is_ghz = :is_ghz';
                $params['is_ghz'] = (int)$request->input('is_ghz');
            }

            // 实装状态
            if ($request->input('status') != '') {

                $not = (int)$request->input('status') ? 'not' : '';
                $condition .= ' and name IS ' . $not . ' NULL';
            }

            // 攻击类型
            if ($request->input('atk_type') != '') {

                $condition .= ' and atk_type = :atk_type';
                $params['atk_type'] = (int)$request->input('atk_type');
            }

            $list = DB::select('SELECT *, IF(`is_6` = 1, `role_id_6`, `role_id_3`) as `role_id` from `roles` where 1 ' . $condition . ' order by `id` limit ' . ($pindex - 1) * $psize . ',' . $psize, $params);

            $count = DB::select('SELECT count(1) as num from `roles` where 1 ' . $condition, $params);

            return json_encode(['code' => 0, 'msg' => '', 'count' => $count[0]->num, 'data' => $list]);
        }

        // 角色信息
        $roles = Role::get()->toArray();
        return view('list', ['roles' => $roles]);
    }

    /**
     * [is_6 是否是6星角色修改接口]
     * @param  Request $request [description]
     * @return boolean          [description]
     */
    public function is_6(Request $request)
    {
        $id = (int)$request->input('id');
        if ($id) {
            // 修改单条数据状态
            $role = Role::find($id);
            if ($role) {
                $is_6 = (int)$role->is_6;
                $role->is_6 = 1 - $is_6;
                $role->save();
            }
        }
        $this->show_json();
    }

    /**
     * [is_ghz 是否是公会战角色修改接口]
     * @param  Request $request [description]
     * @return boolean          [description]
     */
    public function is_ghz(Request $request)
    {
        $id = (int)$request->input('id');
        if ($id) {
            // 修改单条数据状态
            $role = Role::find($id);
            if ($role) {
                $is_ghz = (int)$role->is_ghz;
                $role->is_ghz = 1 - $is_ghz;
                $role->save();
            }
        }
        $this->show_json();
    }

    public function magicAtk(Request $request)
    {
        $id = (int)$request->input('id');
        if ($id) {
            // 修改单条数据状态
            $role = Role::find($id);
            if ($role) {
                $magicAtk = (int)$role->magic_atk;
                $role->magic_atk = 1 - $magicAtk;
                $role->save();
            }
        }
        $this->show_json();
    }

    public function changeAtkType(Request $request)
    {
        $id = (int)$request->input('id');
        if ($id) {
            // 修改单条数据状态
            $role = Role::find($id);
            if ($role) {
                $atkType = (int)$role->atk_type;
                $role->atk_type = $atkType - 1;
                $role->save();
            }
        }
        $this->show_json();
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
