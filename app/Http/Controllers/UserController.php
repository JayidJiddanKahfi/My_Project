<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    
    public function create(Request $request){

        //membuat kumpulan rule (aturan) untuk validasi request
        $rules = [
            "name" => ["required","string"],
            "email" => ["required","email","unique:users,email"],
            "password" => ["required","string","min:8"],
            "role" => ["required","in:admin,user"]
        ];

        
        //membuat kumpulan atribut untuk melakukan pengisan data di masing masing kolom sesuai atribut
        $attributes = [
            "name" => $request->name,
            "email" => $request->email,
            "password" => Hash::make($request->password),
            "role" => $request->role,
        ];
       
        // membuat validasi terhadap request dari user berdasakan rules yang telah dibuat
        $validator = Validator::make($request->all(),$rules);

        // pengkondisian jika hasilnya tidak valid maka akan mereturn respon json berupa error dari objek validator
        if($validator->fails()){
            return response()->json(["errors" => $validator->errors()],422);
        }

        
        // jika hasilnya valid, selanjutnya membuat objek payment sekaligus membuat data baru di tabel payment berdasarkan attribute
        $user = User::create($attributes);

        //mereturn response json berupa data dari objek user
        return response()->json(["userData" => $user],201);
    }

    public function read(){
        //mengambil data dari beberapa kolom pada semua baris dan mereturn dalam objek users
        $users = User::select(["id","name","email","password","role"])->get();
        
        //mereturn response json berupa data dari objek users
        return response()->json(["usersData" => $users]);
    }

    public function update(Request $request,$id){
       //membuat kumpulan rule (aturan) untuk validasi request
       $rules = [
        "name" => ["required","string"],
        "email" => ["required","email",Rule::unique('users')->ignore($id)],
        "password" => ["required","string","min:8"],
        "role" => ["required","in:admin,user"]
       ];

       //membuat kumpulan atribut untuk melakukan pengisan data di masing masing kolom sesuai atribut
       $attributes = [
            "name" => $request->name,
            "email" => $request->email,
            "password" => Hash::make($request->password),
            "role" => $request->role,
       ];

        // membuat validasi terhadap request dari user berdasakan rules yang telah dibuat
        $validator = Validator::make($request->all(),$rules);

        // pengkondisian jika hasilnya tidak valid maka akan mereturn respon json berupa error dari objek validator
        if($validator->fails()){
            return response()->json(["errors"=>$validator->errors()],422);
        }

        // jika hasilnya valid, selanjutnya mencari baris pada tabel berdasarkan id yang sesuai dan mereturn dalam bentuk objek user
        $user = User::select("*")->where("id",$id)->first();
        
        
        //mengupdate semua data pada masing-masing kolom pada baris yang memiliki id sesuai dengan yang dipilih
        $user->update($attributes);

        //mereturn response json berupa data dari objek user
        return response()->json(["userData"=>$user],200);
    }

    public function delete($id){
        //mencari baris pada tabel berdasarkan id yang sesuai lalu menghapus baris tersebut
        $user = User::select("*")->where("id",$id)->first();

        if($user->count() !== 0){
            
            $user->delete();
            //mereturn response json berupa message data pada baris dengan id tersebut berhasil dihapus
            return response()->json(["message"=>"User has been deleted"]);
        
        }
        else{

            return response()->json(["message"=>"User is not exist"]);
        
        }
    
    }

}

