<?php
/**
 * done
 */
namespace App\Http\Controllers;

use App\Customer;
use App\Ledger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class  CustomerController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');

//        $this->middleware('access_check:customer#list', ['only' => ['index']]);
//        $this->middleware('access_check:customer#detail', ['only' => ['view']]);
//        $this->middleware('access_check:customer#create', ['only' => ['create']]);
//        $this->middleware('access_check:customer#edit', ['only' => ['update']]);
//        $this->middleware('access_check:customer#delete', ['only' => ['delete']]);

    }

    public function index(Request $request)
    {
        $response = Customer::search($request);
        return response()->json($response, 200, [], JSON_PRETTY_PRINT);
    }

    public function create(Request $request)
    {


        $this->validate($request, Customer::rules() );

        $data_to_insert = $request->all();

        $data_to_insert['created_by'] = $request->user()->id;

        $model = Customer::create($data_to_insert);

        $response = [
            'status' => 1,
            'data' => $model,
            'message' => 'success'
        ];

        return response()->json($response, 200, [], JSON_PRETTY_PRINT);
    }

    public function view(Request $request, $id)
    {
        $model = $this->findModel($id);
        //echo print_r( json($model, 200, [], JSON_PRETTY_PRINT) ); die()
        return response()->json($model, 200, [], JSON_PRETTY_PRINT);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, Customer::rules($id) );

        $data_to_insert = $request->all();

        $model = Customer::where("id", $id)
            ->update($data_to_insert);

        return response()->json($model, 200, [], JSON_PRETTY_PRINT);
    }

    public function delete(Request $request, $id)
    {
        $model = $this->findModel($id);
        $model->delete();

        $response = [
            'status' => 1,
            'data' => $model,
            'message'=>'Removed successfully.'
        ];
        return response()->json($response, 200, [], JSON_PRETTY_PRINT);
    }

    public function getLedger(Request $request, $id){
        $response = Ledger::search($request);
        return response()->json($response, 200, [], JSON_PRETTY_PRINT);
    }

    public function findModel($id)
    {
        //$model = Area::filterAccess($request, $id);
        $model = Customer::find($id);

        if (!$model) {
            $response = [

                'status' => 0,
                'errors' => [],
                'message' => "Invalid Record",
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
                'message' => "Error",
            ];
            response()->json($response, 400, [], JSON_PRETTY_PRINT)->header('Access-Control-Allow-Origin', '*')->send();
            die();
        }
        return true;
    }
}
