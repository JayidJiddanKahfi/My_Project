<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request){
        
        //membuat kumpulan rule (aturan) untuk validasi request
        $rules = ['email'=>'required|email','password'=>'string'];

        // membuat validasi terhadap request dari user berdasakan rules yang telah dibuat
        $validator = Validator::make($request->all(),$rules);
        
        // pengkondisian jika hasilnya tidak valid maka akan mereturn respon json berupa error dari objek validator
        if($validator->fails()){
            return response()->json(['errors'=>$validator->errors()],422);
        }

        // membuat credential dari request 
        $credentials = $request->only('email','password');

        // pengkondisian jika user yang mencoba login datanya terdapat di database maka mendapatkan token 
        // serta mendapat response json berupa data dari objek user
        if(Auth::attempt($credentials)){
            $user = $request->user();
            $user->token = $user->createToken('authToken')->plainTextToken;
            return response()->json(['userData'=> $user->only(["id","name","email","role","token"])]);
        }

        // jika user yang mencoba login datanya tidak ada di database maka akan mereturn message invalid login credentials
        return response()->json(['message' => 'Invalid login credentials'], 401);
    }

    public function tokenCheck($userTokenWithTokenID){

        $userTokenWithTokenIDSeparator = '|';

        if(!str_contains($userTokenWithTokenID,$userTokenWithTokenIDSeparator)){
            $validationMessage = 'token tidak valid'; 
        }
        else{

        $userTokenWithTokenID_parts = explode('|',$userTokenWithTokenID);

        $userToken = $userTokenWithTokenID_parts[1];
        
        $tokenHash = hash('sha256',$userToken);

        // return $tokenHash;

        $isUserTokenValid = PersonalAccessToken::select('token')->where('token',$tokenHash)->exists();

        $validationMessage = '';

        if($isUserTokenValid){
            $validationMessage = 'token valid';
        }
        else{
            $validationMessage = 'token tidak valid';
        }
     }

        return response()->json(['tokenValidationMessage'=>$validationMessage],200);

    }

    public function me(Request $request){
        
        //mereturn response json berupa data user yang login
        return response()->json(["userDataDetails" => $request->user()]);
    }

    public function logout(Request $request){

        // menghapus token user
        $request->user()->tokens()->delete();

        //mereturm response json berupa message logged out
        return response()->json(["message"=>"Logged out"]);
    }
}
