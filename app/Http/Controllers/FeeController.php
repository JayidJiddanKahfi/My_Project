<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeeController extends Controller
{
    public function create(Request $request){
        
        //membuat kumpulan rule (aturan) untuk validasi request
        $rules = [
            "contribution_amount" => ["required","integer"],
            "social_fund" => ["required","integer"]
        ];

        //membuat kumpulan atribut untuk melakukan pengisan data di masing masing kolom sesuai atribut
        $attributes = [
            "contribution_amount" => $request->contribution_amount,
            "social_fund" => $request->social_fund,
            "total_amount" => $request->contribution_amount + $request->social_fund
        ];

        //mendapatkan jumlah baris pada tabel Fees di database
        $feeRowCount = Fee::count();

        //pengkondisian untuk memastikan tabel Fees memiliki jumlah baris tidak lebih dari 1
        if($feeRowCount === 1){
            return response()->json(["errors" => "the maximum limit of fee data is only one"],422);
        }

        // membuat validasi terhadap request dari user berdasakan rules yang telah dibuat
        $validator = Validator::make($request->all(),$rules);
        
        // pengkondisian jika hasilnya tidak valid maka akan mereturn respon json berupa error dari objek validator
        if($validator->fails()){
            return response()->json(["errors"=>$validator->errors()],422);
        }

        // jika hasilnya valid, selanjutnya membuat objek fee sekaligus membuat data baru di tabel fees berdasarkan attribute
        $fee = Fee::create($attributes);

        //mereturn response json berupa data dari objek fee
        return response()->json(["feeData" => $fee],201);

    }

    public function read(){
        //menseleksi 1 baris di tabel fees (karena memang hanya ada satu baris di tabel fees)
        $fee = Fee::select('*')->first();

        //mereturn response json berupa data dari objek fee
        return response()->json(["feeData" => $fee]);

    }

    public function update(Request $request){
        
        //membuat kumpulan rule (aturan) untuk validasi request
        $rules = [
            "contribution_amount" => ["required","integer"],
            "social_fund" => ["required","integer"]
        ];

        //membuat kumpulan atribut untuk melakukan pengisan data di masing masing kolom sesuai atribut
        $attributes = [
            "contribution_amount" => $request->contribution_amount,
            "social_fund" => $request->social_fund,
            "total_amount" => $request->contribution_amount + $request->social_fund
        ];

        // membuat validasi terhadap request dari user berdasakan rules yang telah dibuat
        $validator = Validator::make($request->all(),$rules);

        // pengkondisian jika hasilnya tidak valid maka akan mereturn respon json berupa error dari objek validator
        if($validator->fails()){
            return response()->json(["errors"=>$validator->errors()],422);
        }

        // jika hasilnya valid, selanjutnya mencari 1 baris fee pada tabel fees dan membuat objek fee 
        $fee = Fee::select('*')->first();

        // melakukan update sesuai attribute yang  telah dibuat
        $fee->update($attributes);

        //mereturn response json berupa data dari object fee
        return response()->json(["feeData" => $fee],201);
    }

    public function delete(){
         $fee = Fee::select("*")->first();

        if($fee->count() !== 0){
            
            $fee->delete();
            //mereturn response json berupa message data pada baris dengan id tersebut berhasil dihapus
            return response()->json(["message"=>"Fee has been deleted"]);
        
        }
        else{

            return response()->json(["message"=>"Fee is not exist"]);
        
        }
    }
}
