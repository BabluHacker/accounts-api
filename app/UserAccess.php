<?php
/**
 * Created by PhpStorm.
 * User: mehedi
 * Date: 9/5/18
 * Time: 5:41 PM
 */

namespace App;
use Illuminate\Database\Eloquent\Model;

class UserAccess extends Model
{
    protected $guarded = ['id','created_at','updated_at'];
    static public function rules($id=NULL)
    {
        if ($id == NULL) {
            return [
                'access_name' => 'required|unique:user_accesses,access_name',
                'access_code' => 'required|unique:user_accesses,access_code',
                'access' => 'required'
            ];
        }
        else{
            return [
                'access_name' => 'required|unique:user_accesses,access_name,'.$id,
                'access_code' => 'required|unique:user_accesses,access_code,'.$id,
                'access' => 'required'
            ];
        }
    }

    static public function search($request)
    {
        $params = $request->all();
        $limit  = isset($params['limit']) ? $params['limit'] : 10;
        $query  = isset($params['fields'])? UserAccess::select(explode(",", $params['fields'])):UserAccess::select();

        if(isset($params['access_code'])) {
            $query->where('access_code','like', '%'.$params['access_code'].'%');
        }
        if(isset($params['access_name']) and $params['access_name'] != "" and $params['access_name'] != "NULL"){
            $query->where('access_name', 'like', '%'.$params['access_name'].'%');
        }

        $data = $query->paginate($limit);

        return [
            'status'=>1,
            'data' => $data,
            'message'=> 'success',
        ];
    }
}
