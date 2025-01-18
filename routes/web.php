<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/', [App\Http\Controllers\PcrController::class, 'index']);





Route::post('list', [App\Http\Controllers\RoleController::class, 'list']);

Route::post('is_6', [App\Http\Controllers\RoleController::class, 'is_6']);

Route::post('is_ghz', [App\Http\Controllers\RoleController::class, 'is_ghz']);

Route::post('magic_atk', [App\Http\Controllers\RoleController::class, 'magicAtk']);

Route::get('add_author', [App\Http\Controllers\AuthorController::class, 'addAuthor']);

Route::post('add_author', [App\Http\Controllers\AuthorController::class, 'addAuthor']);

Route::get('get_author', [App\Http\Controllers\AuthorController::class, 'getAuthor']);

Route::get('rank_info', [App\Http\Controllers\RankController::class, 'index']);

Route::get('add', [App\Http\Controllers\RankController::class, 'add']);

Route::post('add', [App\Http\Controllers\RankController::class, 'add']);

Route::get('edit', [App\Http\Controllers\RankController::class, 'edit']);

Route::post('delete', [App\Http\Controllers\RankController::class, 'delete']);

Route::post('delete_author', [App\Http\Controllers\RankController::class, 'deleteAuthor']);

Route::get('result', [App\Http\Controllers\RankController::class, 'result']);

Route::post('get_can_use_roles', [App\Http\Controllers\RankController::class, 'getCanUseRoles']);

Route::get('rank_image', [App\Http\Controllers\RankController::class, 'rankImage']);

Route::get('teach', [App\Http\Controllers\RankController::class, 'teach']);

Route::get('support', [App\Http\Controllers\RankController::class, 'support']);

Route::get('bug', [App\Http\Controllers\RankController::class, 'bug']);

Route::get('role_6', [App\Http\Controllers\RankController::class, 'role6']);


// 花凛投票页面
Route::get('toupiao', [App\Http\Controllers\RankController::class, 'toupiao']);

// 花凛投票接口
Route::post('toupiao', [App\Http\Controllers\RankController::class, 'toupiao']);

// 测试IP接口
Route::get('ip', [App\Http\Controllers\RankController::class, 'ip']);

// Admin 登录路由
Route::get('admin/login', [App\Http\Controllers\AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('admin/login', [App\Http\Controllers\AdminAuthController::class, 'login']);

// User 登录路由
Route::get('user/login', [App\Http\Controllers\UserAuthController::class, 'showLoginForm'])->name('user.login');
Route::post('user/login', [App\Http\Controllers\UserAuthController::class, 'login']);


// 在中间件中
Route::middleware('auth:admin')->group(function () {
	// 管理员后台首页
	Route::get('admin/index', [App\Http\Controllers\AdminAuthController::class, 'index']);
	// 作业列表
	Route::get('admin/team/list', [App\Http\Controllers\AdminTeamController::class, 'list']);
	Route::get('admin/team/get_public_teams', [App\Http\Controllers\AdminTeamController::class, 'getPublicTeams']);
	Route::post('admin/team/open', [App\Http\Controllers\AdminTeamController::class, 'open']);
	Route::post('admin/team/delete', [App\Http\Controllers\AdminTeamController::class, 'delete']);
	Route::get('admin/team/add', [App\Http\Controllers\AdminTeamController::class, 'add']);
	Route::post('admin/team/add', [App\Http\Controllers\AdminTeamController::class, 'add']);
	Route::get('admin/team/edit', [App\Http\Controllers\AdminTeamController::class, 'edit']);
	Route::post('admin/team/edit', [App\Http\Controllers\AdminTeamController::class, 'edit']);

	// 角色列表页面
	Route::get('list', [App\Http\Controllers\RoleController::class, 'list']);

	Route::get('admin/team/get_boss_images', [App\Http\Controllers\AdminTeamController::class, 'getBossImages']);
	Route::get('admin/team/get_boss_list', [App\Http\Controllers\AdminTeamController::class, 'getBossList']);

	// 攻略信息
	Route::get('admin/guide/list', [App\Http\Controllers\GuideController::class, 'list']);
	Route::post('admin/guide/list', [App\Http\Controllers\GuideController::class, 'list']);

	// 修改攻略页面
	Route::get('/admin/guide/edit', [App\Http\Controllers\GuideController::class, 'edit']);
	// 修改攻略接口
	Route::post('/admin/guide/edit', [App\Http\Controllers\GuideController::class, 'edit']);

	// 修改攻略状态接口
	Route::post('/admin/guide/status', [App\Http\Controllers\GuideController::class, 'status']);

	// 修改攻略类型接口
	Route::post('/admin/guide/type', [App\Http\Controllers\GuideController::class, 'type']);

	// 删除攻略接口
	Route::post('/admin/guide/delete', [App\Http\Controllers\GuideController::class, 'delete']);
});

// 获取攻略数据
Route::get('/guide/get_data', [App\Http\Controllers\GuideController::class, 'getData']);

// 添加攻略页面
Route::get('guide/add', [App\Http\Controllers\GuideController::class, 'add']);
// 添加攻略接口
Route::post('guide/add', [App\Http\Controllers\GuideController::class, 'add']);

// 攻略首页页面
Route::get('guide', [App\Http\Controllers\GuideController::class, 'guide']);

Route::middleware('auth:user')->group(function () {

    // 游戏账号
	Route::get('user/account/list', [App\Http\Controllers\AccountController::class, 'list']);
	Route::post('user/account/list', [App\Http\Controllers\AccountController::class, 'list']);
	Route::get('user/account/add', [App\Http\Controllers\AccountController::class, 'add']);
	Route::post('user/account/add', [App\Http\Controllers\AccountController::class, 'add']);
	Route::get('user/account/edit', [App\Http\Controllers\AccountController::class, 'edit']);
	Route::post('user/account/edit', [App\Http\Controllers\AccountController::class, 'edit']);

	Route::get('user/account/get_can_use_roles', [App\Http\Controllers\AccountController::class, 'getCanUseRoles']);
	Route::get('user/account/team', [App\Http\Controllers\AccountController::class, 'team']);
	Route::get('user/account/get_team_groups', [App\Http\Controllers\AccountController::class, 'getTeamGroups']);
	Route::post('user/account/delete', [App\Http\Controllers\AccountController::class, 'delete']);
	Route::post('user/account/fox', [App\Http\Controllers\AccountController::class, 'fox']);
	// 大师币
	Route::get('user/account/coin', [App\Http\Controllers\AccountController::class, 'coin']);
	Route::post('user/account/coin', [App\Http\Controllers\AccountController::class, 'coin']);
});




// 注册页面
Route::get('register', [App\Http\Controllers\RegisterController::class, 'index']);

// 登录页面
Route::get('login', [App\Http\Controllers\LoginController::class, 'index'])->name('login');

// 登录
Route::post('login', [App\Http\Controllers\LoginController::class, 'doLogin'])->name('login');


// ---------------------
Route::get('res_team', [App\Http\Controllers\TeamInfoController::class, 'resTeam']);

Route::get('team', [App\Http\Controllers\TeamInfoController::class, 'team']);

// 获取当月作业数量接口
Route::get('get_team_num', [App\Http\Controllers\TeamInfoController::class, 'getTeamNum']);

// 添加队伍页面
Route::get('add_team', [App\Http\Controllers\TeamInfoController::class, 'add']);

// 添加队伍接口
Route::post('add_team', [App\Http\Controllers\TeamInfoController::class, 'add']);

// 删除单个队伍
Route::post('delete_team', [App\Http\Controllers\TeamInfoController::class, 'delete']);

// 删除全部队伍
Route::post('delete_all', [App\Http\Controllers\TeamInfoController::class, 'deleteAll']);

// 获取用户添加的阵容信息
Route::get('get_user_teams', [App\Http\Controllers\TeamInfoController::class, 'getUserTeams']);

// 获取用户分刀结果
Route::get('get_team_groups', [App\Http\Controllers\TeamInfoController::class, 'getTeamGroups']);

// 获取角色信息
Route::get('get_all_roles', [App\Http\Controllers\TeamInfoController::class, 'getAllRoles']);

// 作业列表页面
Route::get('team_list', [App\Http\Controllers\TeamInfoController::class, 'teamList']);

// 作业列表数据
Route::get('get_public_teams', [App\Http\Controllers\TeamInfoController::class, 'getPublicTeams']);

// 添加别人的作业
Route::post('add_other_team', [App\Http\Controllers\TeamInfoController::class, 'addOtherTeam']);


// 获取本月boss信息
Route::get('get_this_month_boss_list', [App\Http\Controllers\BossController::class, 'getThisMonthBossList']);


Route::get('get_data', [App\Http\Controllers\PcrController::class, 'getData']);

Route::get('get_data2', [App\Http\Controllers\PcrController::class, 'getData2']);

Route::get('aaaa', [App\Http\Controllers\PcrController::class, 'aaaa']);

Route::get('bbbb', [App\Http\Controllers\PcrController::class, 'bbbb']);

Route::get('cccc', [App\Http\Controllers\PcrController::class, 'cccc']);

Route::get('dddd', [App\Http\Controllers\PcrController::class, 'dddd']);

Route::get('eeee', [App\Http\Controllers\RankController::class, 'aaaa']);