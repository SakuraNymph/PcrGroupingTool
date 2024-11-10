<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamRole;
use App\Services\TeamInfoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BossController extends Controller
{
    public function getThisMonthBossList()
    {
        $bossList = DB::table('boss')->where('status', 1)->orderBy('sort')->get();
        $bossList = $bossList ? $bossList->toArray() : [];
        return json_encode(['status' => 1, 'data' => $bossList]);
    }
}
