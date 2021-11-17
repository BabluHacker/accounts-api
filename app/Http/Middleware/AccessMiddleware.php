<?php

namespace App\Http\Middleware;

use App\UserAccess;
use Closure;
use Mockery\Exception;

class AccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    public function handle($request, Closure $next, $access_name)
    {
        /*
         * ACCESS_NAME#ACTION
         * */
        $access_name = explode('#', $access_name);
        $access_id = $request->user()->user_access_id;
        $access = UserAccess::find($access_id);
        $access_json = json_decode($access->access, true);
        /*dd($access_json);*/

        if(!isset($access_json[$access_name[0]]['access'][$access_name[1]]['value'])){
            $err_msg = "Not set access for ".$access_name[0]." action: ".$access_name[1];
            $response = [
                'data'  => '',
                'message'=> $err_msg
            ];

            return response()->json($response, 403, [], JSON_PRETTY_PRINT)
                ->header('Access-Control-Allow-Origin', '*');
        }
        if ($access_json[$access_name[0]]['access'][$access_name[1]]['value'] == "true") {
            return $next($request);
        } else {
            $err_msg = "No access for ".$access_name[0]." action: ".$access_name[1];
            $response = [
                'data'  => '',
                'message'=> $err_msg
            ];

            return response()->json($response, 403, [], JSON_PRETTY_PRINT)
                ->header('Access-Control-Allow-Origin', '*');

        }
    }
}
