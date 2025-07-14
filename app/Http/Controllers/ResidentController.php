<?php

namespace App\Http\Controllers;


use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResidentController extends Controller
{
    
    public function create(Request $request){

        
        //membuat kumpulan rule (aturan) untuk validasi request
        $rules = [
            "salutation" => ['required','in:Bapak,Ibu'],
            "full_name" => ['required','string'],
            "address" => ['required','string']
        ];

        //membuat kumpulan atribut awal untuk melakukan pengisan data di masing masing kolom sesuai atribut
        $attributes = [
            "salutation" => $request->salutation,
            "full_name" => $request->full_name,
            "address" => $request->address
        ];

        // membuat validasi terhadap request dari user berdasakan rules yang telah dibuat
        $validator = Validator::make($request->all(),$rules);

        // pengkondisian jika hasilnya tidak valid maka akan mereturn respon json berupa error dari objek validator
        if($validator->fails()){
            return response()->json(["errors" => $validator->errors()],422);
        }

        $existing_resident = Resident::select(['salutation','full_name','address'])->where('address',$request->address)->where('full_name',$request->full_name)->first();

        if($existing_resident){
            return response()->json(["errors" =>  "$existing_resident->salutation $existing_resident->full_name ($existing_resident->address) is already exists"],422);
        }
        else{
            // jika hasilnya valid, selanjutnya membuat objek resident sekaligus membuat data baru di tabel residents berdasarkan attribute
            $resident = Resident::create($attributes);

            //mereturn response json berupa data dari objek resident
            return response()->json(["residentData" => $resident,"message" => 'Resident data has been added'],201);
        }
    }

     public function read_all_resident(){
        //mengambil data dari beberapa kolom pada semua baris dan mereturn dalam objek residents
        $residents = Resident::select(columns: ['id','salutation','full_name','address'])->get();
        
        //mereturn response json berupa data dari objek residents
        return response()->json(['residentsData'=>$residents]);
    }

    public function read_paginate_resident($dataPerPage){
        //mengambil data dari beberapa kolom pada semua baris dan mereturn dalam objek residents
        $residents = Resident::select(columns: ['id','salutation','full_name','address'])->paginate($dataPerPage);
        
        //mereturn response json berupa data dari objek residents
        return response()->json(['residentsData'=>$residents]);
    }

    public function update(Request $request,$id){

        //membuat kumpulan rule (aturan) untuk validasi request
        $rules = [
            "salutation" => ['required','in:Bapak,Ibu'],
            "full_name" => ['required','string'],
            "address" => ['required','string']
        ];

        
        //membuat kumpulan atribut awal untuk melakukan pengisan data di masing masing kolom sesuai atribut
        $attributes = [
            "salutation" => $request->salutation,
            "full_name" => $request->full_name,
            "address" => $request->address
        ];

        // membuat validasi terhadap request dari user berdasakan rules yang telah dibuat
        $validator = Validator::make($request->all(),$rules);

        
        // pengkondisian jika hasilnya tidak valid maka akan mereturn respon json berupa error dari objek validator
        if($validator->fails()){
            return response()->json(["errors"=>$validator->errors()],422);
        }

        // jika hasilnya valid, selanjutnya mencari baris pada tabel berdasarkan id yang sesuai dan mereturn dalam bentuk objek resident
        $resident = Resident::select('*')->where('id',$id)->first();
        
        //mengupdate semua data pada masing-masing kolom pada baris yang memiliki id sesuai dengan yang dipilih
        $resident->update($attributes);

        //mereturn response json berupa data dari objek resident
        return response()->json(["residentData"=>$resident],200);
    }

    public function delete($id){

        $resident = Resident::select("*")->where("id",$id);

        if($resident->exists()){
            
            $resident->first()->delete();
            //mereturn response json berupa message data pada baris dengan id tersebut berhasil dihapus
            return response()->json(["message"=>"Resident has been deleted"]);
        
        }
        else{

            return response()->json(["errors"=>"This resident is not exist, please refresh the page"],422);
        
        }
    }

}
