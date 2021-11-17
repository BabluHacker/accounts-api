<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

use Illuminate\Support\Facades\Hash;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    protected $table="users";
    protected $guarded = ['id','created_at','updated_at'];


    static public function rules($id=NULL)
    {
        if($id == NULL)
            return [

                'username' => 'required|unique:users,username',
                'password' => 'required|min:6',
                'confirm_password' => 'required|same:password',
//                'email' => 'email|unique:users,email,'.$id,
                'user_access_id' => 'required|not_in:0',
                'is_active' => 'required|in:Yes,No',
//                'allowed_ids' => 'required', required if employee is on HQ

            ];
        else
            return [
                'username' => 'required|unique:users,username,'.$id,
                'user_access_id' => 'required|not_in:0',
            ];
    }


    static public function messages($id=NULL)
    {
        return [
            'username.required'                   => 'ইউজারনেম লিখুন',
            'password.required'                   => 'পাসওয়ার্ড লিখুন',
            'password.min'                        => 'পাসওয়ার্ড অন্তত ৬ ডিজিট দিন',
            'password.regex'                      => 'Password must contain at least an uppercase letter, a lowercase letter, number and a special character at least',
            'confirm_password.required'           => 'পাসওয়ার্ড কনফার্ম করুন',
            'confirm_password.same'               => 'কনফার্ম পাসওয়ার্ড ভুল',
            'email.required'                      => 'ইমেইল লিখুন',
            'user_access_id.required'                  => 'ইউজারের ধরণ লিখুন',
            'employee_id.check_is_active'            => 'Employee is not Active or Valid',
            'employee_id.check_is_exists_user'        => 'User Exists for this User',
//            'allowed_ids.required'                => 'একসেস দিন',
        ];
    }

    static public function authorizeRules()
    {
        return [
            'username'    => 'required',
            'password' => 'required|min:6',
        ];
    }

    static public function authorizeRulesMsg()
    {
        return [
            'username.required'    => 'আপনার ইউজারনেম ইনপুট দিন',
            'password.required' => 'আপনার পাসওয়ার্ড ইনপুট দিন',
            'password.min'      => 'আপনার পাসওয়ার্ড অন্তত ৬ ডিজিটের'
        ];
    }

    static public function accessTokenRules()
    {
        return [
            'authorization_code' => 'required',
        ];
    }
    static public function change_pass_rules(){
        return [
            'password' => 'required|min:6|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!@$^&*<>?#%]).*$/',
            'confirm_password' => 'required|same:password',
        ];
    }
    static public function change_pass_message(){
        return [
            'password.required'                   => 'পাসওয়ার্ড লিখুন',
            'password.min'                        => 'পাসওয়ার্ড অন্তত ৬ ডিজিট দিন',
            'password.regex'                      => 'Password must contain at least an uppercase letter, a lowercase letter, number and a special character at least',
            'confirm_password.required'           => 'পাসওয়ার্ড কনফার্ম করুন',
            'confirm_password.same'               => 'কনফার্ম পাসওয়ার্ড ভুল',
        ];
    }

    protected $hidden = [
        'password',
        'password_reset_token'
    ];

    static public function search($request)
    {
        $params = $request->all();
        $limit  = isset($params['limit']) ? $params['limit'] : 10;
        $query  = isset($params['fields'])? User::select(explode(",", $params['fields'])):User::select();





        /**
        Eager Loading by foreign keys*/
        if(isset($params['with']) and $params['with'] != "" and $params['with'] != "null"){
            $withs = explode('!', $params['with']);
            foreach ($withs as $with){
                $query->with($with);
            }
        }
        if(isset($params['user_access_id']) and $params['user_access_id']!='' and $params['user_access_id']!="null") {
            $query->where('user_access_id','=', $params['user_access_id']);
        }
        if(isset($params['created_at']) and $params['created_at']!='' and $params['created_at']!="null") {
            $query->where('created_at', '=', $params['created_at']);
        }
        if(isset($params['is_active']) and $params['is_active']!='' and $params['is_active']!="null") {
            $query->where('is_active','=', $params['is_active']);
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


    public static function authorize($attributes){

        $model=User::where(['username'=>$attributes['username']])
            ->where('is_active', '=', 'Yes')
            ->select(['id', 'username','password'])->first();
        if(!$model)
            return false;

        if(Hash::check($attributes['password'],$model->password)) {
            return $model;
            // Right password
        } else {
            // Wrong one
        }

        return false;
    }

}
