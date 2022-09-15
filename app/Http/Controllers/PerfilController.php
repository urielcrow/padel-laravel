<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PerfilController extends Controller
{

    public function __construct()
    {
        $this->middleware('jwt.verify');//nuestro middleware personalizado
    }

    public function avatar(Request $request){

        $validator = Validator::make($request->all(),[
            'img'=>
            array(
                'required',
                'mimes:jpg,jpeg')
        ]);

        if($validator->fails()){
            return response()->json([
                'message' => 'Data fail',
                'error'=>$validator->errors()
            ],400);
        }

        deleteImage("users", auth()->user()->id);//eliminamos si es que ya existia una
        $file = setImage("users", auth()->user()->id ,$request->file('img'));//cargamos la nueva imagen

        return response()->json($file,200);
    }

    public function passwd(Request $request){

        $validator = Validator::make($request->all(),[
            'passwd'=>
            array(
                'required',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?_])[A-Za-z\d@$!%*?_.]{10,15}$/')
        ]);

        if($validator->fails()){
            return response()->json([
                'message' => 'Data fail',
                'error'=>$validator->errors()
            ],400);
        }

        $user = DB::table('users')->where([['id',auth()->user()->id],['status',1]])->update(['password'=>bcrypt($request->passwd)]);

        if($user > 0)
            return response()->json([ auth()->user()->id => "change password" ],201);
        else
            return response()->json($user,404);

    }


}
