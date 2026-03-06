<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamRole;
use App\Models\UserGuideConfig;
use App\Services\TeamInfoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VisitorController extends Controller
{
    public function team(Request $request)
    {
        $uid    = $this->uid();
        $switch = UserGuideConfig::getUserGuideConfig($uid, 'team');
        $switch = 0;
        return view('user.visitor.team', ['id' => 0, 'select_is_show' => 1, 'switch' => $switch]);
    }

    public function group(Request $request)
    {
        $uid    = $this->uid();
        $switch = UserGuideConfig::getUserGuideConfig($uid, 'group');
        $switch = 0;
        return view('user.visitor.group', ['id' => 0, 'select_is_show' => 1, 'switch' => $switch]);
    }

    public function getTeamGroups(Request $request)
    {
        $uid       = 0;
        $id        = 0;
        $type      = (int)$request->input('type') ?? 1;
        $atkType   = (int)$request->input('atk') ?? 0;

        $row1      = in_array((int)$request->input('row1'), [1,2,3,4,5]) ? (int)$request->input('row1') : 0;
        $row2      = in_array((int)$request->input('row2'), [1,2,3,4,5]) ? (int)$request->input('row2') : 0;
        $row3      = in_array((int)$request->input('row3'), [1,2,3,4,5]) ? (int)$request->input('row3') : 0;
        $lockedIds = is_array($request->input('lockedIds')) ? $request->input('lockedIds') : [];
        $hiddenIds = is_array($request->input('hiddenIds')) ? $request->input('hiddenIds') : [];

        $teamsRes  = TeamInfoService::getTeamGroups($uid, [$row1, $row2, $row3], $id, $type, $atkType, $lockedIds, $hiddenIds);
        return json_encode(['status' => 1, 'result' => $teamsRes]);
    }

    public function firstDay(Request $request)
    {
        $uid     = 0;
        $id      = 0;
        $type    = (int)$request->input('type') ?? 1;
        $stage   = (int)$request->input('stage') ?? 1;
        $atkType = (int)$request->input('atk') ?? 0;

        $row1       = in_array((int)$request->input('row1'), [1,2,3,4,5]) ? (int)$request->input('row1') : 0;
        $row2       = in_array((int)$request->input('row2'), [1,2,3,4,5]) ? (int)$request->input('row2') : 0;
        $row3       = in_array((int)$request->input('row3'), [1,2,3,4,5]) ? (int)$request->input('row3') : 0;
        $row4       = in_array((int)$request->input('row4'), [1,2,3,4,5]) ? (int)$request->input('row4') : 0;
        $row5       = in_array((int)$request->input('row5'), [1,2,3,4,5]) ? (int)$request->input('row5') : 0;
        $row6       = in_array((int)$request->input('row6'), [1,2,3,4,5]) ? (int)$request->input('row6') : 0;
        $lockedIdsB = is_array($request->input('lockedIdsB')) ? $request->input('lockedIdsB') : [];
        $lockedIdsD = is_array($request->input('lockedIdsD')) ? $request->input('lockedIdsD') : [];
        $hiddenIds  = is_array($request->input('hiddenIds')) ? $request->input('hiddenIds') : [];

        $teamsRes   = TeamInfoService::firstDayHomework($uid, [$row1, $row2, $row3], [$row4, $row5, $row6], $id, $type, $stage, $atkType, $lockedIdsB, $lockedIdsD, $hiddenIds);
        return json_encode(['status' => 1, 'result' => $teamsRes]);
    }
}
