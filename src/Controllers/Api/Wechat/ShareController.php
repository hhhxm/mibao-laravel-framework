<?php

namespace Mibao\LaravelFramework\Controllers\Api\Wechat;

use Illuminate\Http\Request;
// use App\Http\Controllers\Controller;
use Mibao\LaravelFramework\Models\Share;
use Auth;

class ShareController
{
    /**
     * 分享
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user       = Auth::user();
        $share      = new Share();
        $share->fill($request->all());
        $share->wechat_user_id = $user->id;
        $share->ip  = $request->getClientIp();
        $share->save();
        return $this->success($share->id);
    }
}
