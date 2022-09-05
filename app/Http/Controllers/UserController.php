<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use App\User;

class UserController extends Controller
{
    public function addUser(Request $request){
        $user = new User();
        $user->name = $request->name;
        $user->mail = $request->mail;
        $user->passwd = "123456789"; 
        $user->save(); 
        $user->img = 'http://localhost/diversos/apis/backend-cliente-almer/img/no-image.jpg';
        return response()->json($user->only(['name','mail','id','img']), 200);
    }

    public function deleteUser(Request $request){

        //validamos la existencia del id
        $user = DB::table("users")->select('name','id','mail')->where([
            ['id','=',$request->id],
            ['mail','!=',NULL],
        ])->get();

        //ejecutamos la eliminación
        if(count($user) > 0){
            $resp = DB::table('users')->where('id', '=', $request->id)->update(['status'=>0,'mail'=>NULL]);
            return $resp ? response()->json($user, 200) : response()->json($user, 500);
        }
        else
            return response()->json([], 200);

    }
    
    public function showAllUsers(){
        $users =DB::table("users")->select('id','name','mail')->where('status',1)->get();
        foreach($users as $user)
            $user->img = 'http://localhost/diversos/apis/backend-cliente-almer/img/no-image.jpg';
        return response()->json($users, 200);
    }
    
    public function updateUser(Request $request){

        $data = [];

        if($request->has('name'))
            $data['name'] = $request->name;
        else if($request->has('mail'))
            $data['mail'] = $request->mail;
        
        if(count($data) > 0){
            $user = DB::table('users')->where([['id',$request->id],['status',1]])->update($data);
            if($user)
                $data['id'] = $request->id;
        }
       
        return response()->json($data, 200);
    }
}
