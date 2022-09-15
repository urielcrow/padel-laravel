<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\User;

use Illuminate\Support\Facades\DB;

use JWTAuth;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth:api', ['except' => ['login','register']]);
        $this->middleware('jwt.verify', ['except' => ['login','register'] ]);//nuestro middleware personalizado
    }

    public function redirectPath(){
        // dd('redirectPath');
        return response()->json( ['error' => 'Unauthorized'], 401);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {

        $validator = Validator::make($request->all(),[
            'email'=>'required|email',
            'password'=>'required|string|min:8'
        ]);

        if($validator->fails()){
            return response()->json([
                'error'=>$validator->errors()
            ],400);
        }

        $credentials = request(['email','pass'=>'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $resp = auth()->user()->only(['name','id','access']);
        $resp['img'] = getImage("users",auth()->user()->id,"users");
        return response()->json($resp);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL()
        ]);
    }

    public function register(Request $request){

        $validator = Validator::make($request->all(),[
            'name'=>'required',
            'email'=>'required|email|unique:users',
            'password'=>'required|string|min:8'
        ]);

        if($validator->fails()){
            return response()->json([
                'message' => 'Registro incorrecto',
                'error'=>$validator->errors()
            ],400);
        }

        $user = User::create(array_merge(
            $validator->validate(),
            ['password' => bcrypt($request->password)]
        ));

        return response()->json(['user'=>$user],201);
    }
}