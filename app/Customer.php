<?php
namespace App;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $guarded = ['id', 'updated_at', 'created_at'];

    static public function rules($id=NULL)
    {
        if( $id == NULL )
            return [
                'name' => 'required|unique:customers,name',
                'phone_no' => array('required', 'regex:/^01(1|3|4|5|6|7|8|9)\d{8}$/', 'unique:customers,phone_no'),
                'second_phone_no' => array( 'regex:/^01(1|3|4|5|6|7|8|9)\d{8}$/'),
                'address' => 'required',
                'country' => 'required',
                'dob' => 'required|date_format:Y-m-d',
            ];
        else
            return [
                'name' => 'required|unique:customers,name,'.$id,
                'phone_no' => array('required', 'regex:/^01(1|3|4|5|6|7|8|9)\d{8}$/', 'unique:customers,phone_no,'.$id),
                'second_phone_no' => array( 'regex:/^01(1|3|4|5|6|7|8|9)\d{8}$/'),
                'address' => 'required',
                'country' => 'required',
                'dob' => 'required|date_format:Y-m-d',
            ];
    }


    static public function search($request)
    {
    	$params = $request->all();
        $limit  = isset($params['limit']) ? $params['limit'] : 10;
        $query  = isset($params['fields'])? Customer::select(explode(",", $params['fields'])):Customer::select();


        if(isset($params['name'])) {
            $query->where('name','like', '%'.$params['name'].'%');
        }

        if(isset($order)){
            $query->orderBy($order);
        }

        $data = $query->paginate($limit);

        return [
            'status'=>1,
            'data' => $data,
            'message'=> 'success',
        ];
    }

}
?>
