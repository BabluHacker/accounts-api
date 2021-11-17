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

            ];
        else
            return [

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
