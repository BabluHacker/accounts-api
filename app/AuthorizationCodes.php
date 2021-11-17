<?php
namespace App;

use Illuminate\Database\Eloquent\Model;


class AuthorizationCodes extends Model
{
    protected $fillable = ['code', 'expires_at','user_id','app_id', 'ip', 'user_agent'];
    static public function rules_auth(){

    }

    static public function rules($id=NULL)
    {
        return [
            'user_id' => 'required',
            'code' => 'required|unique:authorization_codes,code,'.$id,
        ];
    }
    public static function getUserId($code){
        $model = AccessTokens::select('user_id')
                    ->where('token','=' , $code)
                    ->first();
        $userid      = $model->user_id;
        return $userid;
    }


    public static function isValid($code)
    {
        $model=AuthorizationCodes::where(['code'=>$code])->first();

        if(!$model||$model->expires_at<time())
        {
            return(false);
        }
        else
            return($model);
    }
}
?>
