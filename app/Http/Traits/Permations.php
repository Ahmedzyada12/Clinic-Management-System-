<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait Permations
{
    public function permation($parent,$child)
    {
        $userPer= Auth::guard('api')->user()->permission;
        if(empty($userPer)){
            return response()->json('access denied',401);
        }
        elseif(isset($userPer[$parent][$child]) && $userPer[$parent][$child] == 1){
            return response()->json('access denied',401);
        }
        return true;
     }
}
