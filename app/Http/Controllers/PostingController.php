<?php
/**
 * done
 */
namespace App\Http\Controllers;

use App\Customer;
use App\Ledger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class  PostingController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');

//        $this->middleware('access_check:posting#list', ['only' => ['index']]);
//        $this->middleware('access_check:posting#detail', ['only' => ['view']]);
//        $this->middleware('access_check:posting#create', ['only' => ['create']]);
//        $this->middleware('access_check:posting#edit', ['only' => ['update']]);
//        $this->middleware('access_check:posting#delete', ['only' => ['delete']]);

    }


    public function transaction(Request $request){
        $this->validate($request, [
            'customer_id' => 'required',
            'type' => 'required|in:sale,payment,refund',
            'medium' => 'required|in:bank,cash,cheque',
            'description' => 'required',
            'amount' => 'required'
        ]);
        $data_all = $request->all();
        $customer_model = Customer::find($data_all['customer_id']);
        $data_ledger = $data_all;
        unset($data_ledger['amount']);
        if ($data_all['type'] == 'sale'){
            $data_ledger['credit'] = $data_all['amount'];
            $data_ledger['debit'] = 0;
            $customer_model->balance -= $data_all['amount'];

        }
        elseif ($data_all['type'] == 'payment'){
            $data_ledger['credit'] = 0;
            $data_ledger['debit'] = $data_all['amount'];
            $customer_model->balance += $data_all['amount'];
        }
        elseif ($data_all['type'] == 'refund'){
            $data_ledger['credit'] = $data_all['amount'];
            $data_ledger['debit'] = 0;
            $customer_model->balance -= $data_all['amount'];
        }else{
            return response()->json(['message' => 'invalid transaction'], 400, [], JSON_PRETTY_PRINT);
        }

        $data_ledger['balance'] = $customer_model->balance;
        DB::beginTransaction();
        Ledger::create($data_ledger);
        $customer_model->save();
        DB::commit();

        return response()->json(['message' => 'success'], 200, [], JSON_PRETTY_PRINT);
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
