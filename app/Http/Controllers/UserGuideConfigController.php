<?php

namespace App\Http\Controllers;

use App\Models\UserGuideConfig;
use Illuminate\Http\Request;

class UserGuideConfigController extends Controller
{
    public function update(Request $request)
    {
        $uid       = $this->uid();
        $locations = UserGuideConfig::LOCATIONS;
        $location  = $request->input('location');
        if ($location == 'reset') {
            UserGuideConfig::where('uid', $uid)->update(['configuration' => array_fill_keys($locations, false)]);
        }
        if (in_array($location, $locations)) {
            UserGuideConfig::where('uid', $uid)->update(['configuration->' . $location => true]);
        }
    }
}
