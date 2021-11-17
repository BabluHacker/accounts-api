<?php
/**
 * Created by PhpStorm.
 * User: mehedi
 * Date: 9/5/18
 * Time: 5:41 PM
 */

namespace App\Http\Controllers;
use App\AuthorizationCodes;
use App\User;
use App\UserAccess;
use Illuminate\Http\Request;

class UserAccessController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->middleware('access_check:user_access#list', ['only' => ['index']]);
        $this->middleware('access_check:user_access#detail', ['only' => ['view']]);
        $this->middleware('access_check:user_access#create', ['only' => ['create']]);
        $this->middleware('access_check:user_access#edit', ['only' => ['update']]);
        $this->middleware('access_check:user_access#delete', ['only' => ['delete']]);

    }

    public function index(Request $request)
    {
        $response = UserAccess::search($request);
        return response()->json($response, 200, [], JSON_PRETTY_PRINT);
    }

    public function create(Request $request)
    {
        $this->validate($request, UserAccess::rules());
        $data_to_insert = $request->all();

//        $header                       = $request->header('x-access-token');
        $data_to_insert['created_by'] = $request->user()->id;
        $data_to_insert['access'] = json_encode($data_to_insert['access']);

        $model = UserAccess::create($data_to_insert);

        $response = [
            'status' => 1,
            'data' => $model
        ];
        return response()->json($response, 200, [], JSON_PRETTY_PRINT);
    }

    public function view(Request $request, $id)
    {
        $model = $this->findModel($id);
        return response()->json($model, 200, [], JSON_PRETTY_PRINT);
    }

    public function update(Request $request, $id)
    {

        $model = $this->findModel($id);
        $this->validate($request, UserAccess::rules($id));

        $model->access_name = $request->input('access_name');
        $model->access_code  = $request->input('access_code');
        $model->access      = json_encode($request->input('access'));
        $model->save();

        return response()->json($model, 200, [], JSON_PRETTY_PRINT);
    }

    public function delete(Request $request, $id)
    {

        /* check if any user has this access id */
        $user_count = User::where('user_access_id', '=', $id)->get()->count();
        if ($user_count > 0){
            $response = [
                'status' => 1,
                'message'=>'This User access is assigned to some Users. Please re-assign user access to them and then delete.'
            ];

            return response()->json($response, 400, [], JSON_PRETTY_PRINT);
        }

        $model = $this->findModel($id);
        $model->delete();

        $response = [
            'status' => 1,
            'data' => $model,
            'message'=>'Removed successfully.'
        ];

        return response()->json($response, 200, [], JSON_PRETTY_PRINT);
    }


    public function validate(Request $request, array $rules, array $messages = [], array $customAttributes = [])
    {

        $validator = $this->getValidationFactory()->make($request->all(), $rules, $messages, $customAttributes);

        if ($validator->fails()) {
            $response = [
                'status' => 0,
                'errors' => $validator->errors()
            ];

            response()->json($response, 400, [], JSON_PRETTY_PRINT)->header('Access-Control-Allow-Origin', '*')->send();
            die();

        }

        return true;
    }
    public function findModel($id)
    {
        $model = UserAccess::find($id);

        if (!$model) {
            $response = [
                'status' => 0,
                'errors' => "Invalid Record"
            ];

            response()->json($response, 400, [], JSON_PRETTY_PRINT)->header('Access-Control-Allow-Origin', '*')->send();
            die;
        }
        return $model;
    }


    public function defaultUserAccessJson( Request $request){
        $array = array(
            'customer'   => [
                'access' => [
                    'list'=>[
                        'value' => false,
                    ],
                    'detail'=>[
                        'value' => false,
                    ],
                    'create'=>[
                        'value' => false,
                    ],
                    'edit'=>[
                        'value' => false,
                    ],
                    'delete'=>[
                        'value' => false,
                    ],
                ]
            ],
            'user_access' => [
                'access'=> [
                    'list'  =>[
                        'value' => false,
                    ],
                    'detail' =>[
                        'value' => false,
                    ],
                    'create' =>[
                        'value' => false,
                    ],
                    'edit' =>[
                        'value' => false,
                    ],
                    'delete' =>[
                        'value' => false,
                    ],
                ]
            ],

            'user'          => [
                'access'=> [
                    'list'=>[
                        'value' => false,
                    ],'detail'=>[
                        'value' => false,
                    ],'create'=>[
                        'value' => false,
                    ],'edit'=>[
                        'value' => false,
                    ],'delete'=>[
                        'value' => false,
                    ],'change_password'=> [
                        'value' => false,
                    ],
                ],
            ],


        );
        return response()->json($array, 200, [], JSON_PRETTY_PRINT);
    }
}
