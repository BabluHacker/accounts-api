<?php

namespace App\Http\Controllers;

use Auth;
use App\User;
use App\AuthorizationCodes;
use App\AccessTokens;
use App\UserAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;



class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth',
            ['except' => ['accesstoken', 'auth']]);
//        $this->middleware('access_check:user#list', ['only' => ['index']]);
//        $this->middleware('access_check:user#detail', ['only' => ['view']]);
//        $this->middleware('access_check:user#create', ['only' => ['create']]);
//        $this->middleware('access_check:user#edit', ['only' => ['update']]);
//        $this->middleware('access_check:user#delete', ['only' => ['delete']]);
//        $this->middleware('access_check:user#change_password', ['only' => ['change_own_password']]);
    }


    public function ping(Request $request){

        $response = [
            'status' => 1,
            'message'=> 'success',
            'is_stage'=> env('IS_STAGE')
        ];
        return response()->json($response, 200, [], JSON_PRETTY_PRINT);
    }

    public function auth(Request $request){

        $this->validate($request, User::authorizeRules(), User::authorizeRulesMsg());

        if ($model = User::authorize($request->all())) {

            $auth_code = $this->createAuthorizationCode($model->id);

            $data = [];
            $data['authorization_code'] = $auth_code->code;
            $data['expires_at']         = $auth_code->expires_at;

            $response = [
                'status' => 1,
                'data' => $data,
                'message'=> 'success'
            ];

            return response()->json($response, 200, [], JSON_PRETTY_PRINT);

        } else {
            $response = [
                'status' => 0,
                'errors' => [],
                'message'=>"username or Password is wrong",
            ];
            return response()->json($response, 400, [], JSON_PRETTY_PRINT);
        }
    }

    public function me(Request $request)
    {

        $data = Auth::user()->getAttributes();

        unset($data['password']);
        unset($data['password_reset_token']);

        $dataAccess = UserAccess::where('id', '=', $data['user_access_id'])
                ->first();
        $data['access'] = $dataAccess;
        $response = [
            'status' => 1,
            'data' => $data,
            'message'=> 'success'
        ];

        return response()->json($response, 200, [], JSON_PRETTY_PRINT);
    }

    public function accesstoken(Request $request)
    {
        $this->validate($request, User::accessTokenRules());

        $attributes = $request->all();

        $auth_code = AuthorizationCodes::isValid($attributes['authorization_code']);

        if (!$auth_code) {
            $response = [
                'status' => 0,
                'errors' => [],
                'message'=> "Invalid Authorization Code",
            ];
            return response()->json($response, 401, [], JSON_PRETTY_PRINT);
        }

        $model = $this->createAccesstoken($attributes['authorization_code']);

        $data = [];
        $data['access_token'] = $model->token;
        $data['expires_at']   = $model->expires_at;

        $response = [
            'status' => 1,
            'data' => $data,
            'message'=> 'success'
        ];

        return response()->json($response, 200, [], JSON_PRETTY_PRINT);
    }

    public function logout(Request $request)
    {
        $headers = $request->headers->all();

        if (!empty($headers['x-access-token'][0])) {
            $token = $headers['x-access-token'][0];
        } else if ($request->input('access_token')) {
            $token = $request->input('access_token');
        }

        $model = AccessTokens::where(['token' => $token])->first();

        if (!$model){ // not found token
            return response()->json(['message'=> 'Already Logged out'], 400, [], JSON_PRETTY_PRINT);
        }

        if ($model->delete()) {

            $response = [
                'status' => 1,
                'message' => "Logged Out Successfully",
            ];
            return response()->json($response, 200, [], JSON_PRETTY_PRINT);

        } else {
            $response = [
                'status' => 0,
                'message' => "Invalid request"
            ];
            return response()->json($response, 400, [], JSON_PRETTY_PRINT);
        }
    }

    public function index(Request $request)
    {
        $response = User::search($request);
        return response()->json($response, 200, [], JSON_PRETTY_PRINT);
    }


    public function view(Request $request, $id)
    {
        $model = $this->findModel($id);
        return response()->json($model, 200, [], JSON_PRETTY_PRINT);
    }

    public function create(Request $request)
    {
        $this->validate($request, User::rules(), User::messages());

        $attributes = $request->all();
        $attributes['username'] = trim($attributes['username']);

        $attributes['password'] = Hash::make($attributes['password']);
        unset($attributes['confirm_password']);
        $attributes['created_by'] = $request->user()->id;

        $model = User::create($attributes);

        $response = [
            'status' => 1,
            'data' => $model,
            'message'=> 'success'
        ];

        return response()->json($response, 200, [], JSON_PRETTY_PRINT);

    }

    public function update(Request $request, $id)
    {
        $model = $this->findModel($id);

        if($request->has('is_active')){
            /*check if user's employee is active*/
            $model->is_active = $request->input('is_active');
            $this->deleteAllOtherSessions($id);
        }
        else {
            $this->validate($request, User::rules($id), User::messages($id));
            $model->username = $request->input('username');
            $model->email = $request->input('email');

            $model->user_access_id = $request->input('user_access_id');
        }
        $model->save();

        return response()->json($model, 200, [], JSON_PRETTY_PRINT);
    }


    public function delete(Request $request, $id)
    {
        $model = $this->findModel($id);
        $model->delete();
        $response = [
            'status' => 1,
            'data' => $model,
            'message' => 'Removed successfully.'
        ];
        return response()->json($response, 200, [], JSON_PRETTY_PRINT);
    }

    /*
     * change all the user's password*/
    public static function change_user_password( $attributes, $id){

        $attributes['password'] = Hash::make($attributes['password']);
        $user = User::find($id);
        if($user){
            $user->update(['password'=>$attributes['password']]);
        }
        else{
            return false;
        }
        AccessTokens::where('user_id', '=', $id)->delete();
        return true;
    }

    /*
     * change own password*/
    public function change_own_password(Request $request){
        $this->validate($request, User::change_pass_rules(), User::change_pass_message());
        $user_id = $request->user()->id;
        $data_all = $request->all();
        $res = self::change_user_password($data_all, $user_id);
        if($res){
            return response()->json('success', 200, [], JSON_PRETTY_PRINT);

        }
        return response()->json('error', 400, [], JSON_PRETTY_PRINT);
    }

    public function findModel($id)
    {
        $model = User::find($id);

        if (!$model) {
            $response = [
                'status' => 0,
                'errors' => "Invalid Record",
                'message'=> 'error'
            ];

            response()->json($response, 400, [], JSON_PRETTY_PRINT)->header('Access-Control-Allow-Origin', '*')->send();
            die;
        }
        return $model;
    }

    public function validate(Request $request, array $rules, array $messages = [], array $customAttributes = [])
    {

        $validator = $this->getValidationFactory()->make($request->all(), $rules, $messages, $customAttributes);

        if ($validator->fails()) {
            $response = [
                'status' => 0,
                'errors' => $validator->errors(),
                'message' => 'error'
            ];

            response()->json($response, 400, [], JSON_PRETTY_PRINT)

                ->header('Access-Control-Allow-Origin', '*')->send();
            die();

        }

        return true;
    }

    public function createAuthorizationCode($user_id)
    {
        if (isset($_SERVER['HTTP_X_APPLICATION_ID']))
            $app_id = $_SERVER['HTTP_X_APPLICATION_ID'];
        else
            $app_id = null;

        $model             = new AuthorizationCodes;
        $model->code       = md5(uniqid());
        $model->expires_at = time() + env('AUTH_TOKEN_EXP');
        $model->user_id    = $user_id;
        $model->app_id     = $app_id;
        $model->ip         = $_SERVER['REMOTE_ADDR'];
        $model->user_agent = $_SERVER['HTTP_USER_AGENT'];
        $model->created_at = time();
        $model->updated_at = time();

        $model->save();

        return ($model);
    }

    public function createAccesstoken($authorization_code)
    {
        $auth_code = AuthorizationCodes::where(['code' => $authorization_code])->first();
        $this->deleteAllOtherSessions($auth_code->user_id);
        $model             = new AccessTokens();
        $model->token      = md5(uniqid());
        $model->auth_code  = $auth_code->code;
        $model->expires_at = time() + env('ACCESS_TOKEN_EXP'); // 60 days
        $model->user_id    = $auth_code->user_id;
        $model->created_at = time();
        $model->updated_at = time();
        $model->save();
        return ($model);
    }

    private function deleteAllOtherSessions($user_id){
        AccessTokens::where('user_id', '=', $user_id)->delete();
    }
}

?>
