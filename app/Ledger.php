<?php
namespace App;
use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    protected $guarded = ['id', 'updated_at', 'created_at'];

    static public function search($request)
    {
    	$params = $request->all();
        $limit  = isset($params['limit']) ? $params['limit'] : 10;
        $query  = isset($params['fields'])? Ledger::select(explode(",", $params['fields'])):Ledger::select();

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
