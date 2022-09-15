<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\User;

class UserController extends Controller
{

    public function __construct()
    {
        //$this->middleware('auth:api', ['except' => ['login','register']]);
        $this->middleware( ['jwt.verify','role.admin'] );//nuestro middleware personalizado
    }

    public function addUser(Request $request){

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->number = $request->number;
        $user->password =  bcrypt(passwdCrow(14)); 
        $user->save(); 
        $user->img = asset("assets/no-image.jpg");

        //En este punto mandar el correo al usaurio------------------------------>

        return response()->json($user->only(['name','email','id','img']), 200);
    }

    public function deleteUser(Request $request){
        //validamos la existencia del id
        $user = DB::table("users")->select('name','id','email')->where([
            ['id','=',$request->id],
            ['email','!=',NULL],
        ])->get();

        //ejecutamos la eliminación
        if(count($user) > 0){
            $resp = DB::table('users')->where('id', '=', $request->id)->update(['status'=>0,'email'=>NULL]);
            return $resp ? response()->json($user, 200) : response()->json($user, 500);
        }
        else
            return response()->json([], 200);
    }
    
    public function showAllUsers(){
        $users =DB::table("users")->select('id','name','email','number')->where('status',1)->get();
        foreach($users as $user)
            $user->img = getImage("users",$user->id,"users");
        return response()->json($users, 200);
    }
    
    public function updateUser(Request $request){

        $data = [];

        if($request->has('name'))
            $data['name'] = $request->name;
        else if($request->has('email'))
            $data['email'] = $request->email;
        else if($request->has('number'))
            $data['number'] = $request->number;
        
        if(count($data) > 0){
            $user = DB::table('users')->where([['id',$request->id],['status',1]])->update($data);
            if($user)
                $data['id'] = $request->id;
        }
       
        return response()->json($data, 200);
    }

    /*public function caca(Request $request){

        //actualizacion masiva

        $validator = Validator::make($request->all(),[
            'name'=>'required',
            'email'=>'required|email',
            'number'=>'required',
            'access'=>'required',
            'id'=>'required'
        ]);

        if($validator->fails()){
            return response()->json([
                'message' => 'Registro incorrecto',
                'error'=>$validator->errors()
            ],400);
        }

        
        $resp = [];

        if(User::find($request->id)->update($validator->validate()))//Importante utilizar find en lugar de where en la actualización para que aplique mutadores
            $resp = User::find($request->id);

        return response()->json($resp, 200);
    }*/
}
