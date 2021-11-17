<?php
namespace App;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $guarded = ['id', 'updated_at', 'created_at'];

    static public function rules($id=NULL)
    {
        if( $id == NULL )
            return [
                'name' => 'required|unique:customers,name',
                'type' => 'required|in:cash,bank',
                'is_active' => 'required|in:Yes,No',
            ];
        else
            return [
                'name' => 'required|unique:customers,name,'.$id,
                'type' => 'required|in:cash,bank',
                'is_active' => 'required|in:Yes,No',
            ];
    }


    static public function search($request)
    {
    	$params = $request->all();
        $limit  = isset($params['limit']) ? $params['limit'] : 10;
        $query  = isset($params['fields'])? Account::select(explode(",", $params['fields'])):Account::select();


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
