<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    
    public function create(Request $request){

        //membuat kumpulan rule (aturan) untuk validasi request
        $rules = [           
            "expense_date" => ["required","date"],
            "description" => ["required","string"],
            "amount" => ["required","integer"],
        ];

        //membuat kumpulan atribut awal untuk melakukan pengisan data di masing masing kolom sesuai atribut
        $attributes = [
            "expense_date" => $request->expense_date,
            "description" => $request->description,
            "amount" => $request->amount,
            "recorded_by" => Auth::id()
        ];

        // membuat validasi terhadap request dari user berdasakan rules yang telah dibuat
        $validator = Validator::make($request->all(),$rules);

        // pengkondisian jika hasilnya tidak valid maka akan mereturn respon json berupa error dari objek validator
        if($validator->fails()){
            return response()->json(["errors" => $validator->errors()],422);
        }

        // jika hasilnya valid, selanjutnya membuat objek expense sekaligus membuat data baru di tabel expenses berdasarkan attribute
        $expense = Expense::create($attributes);

        //mereturn response json berupa data dari objek expense
        return response()->json(["expenseData" => $expense, "message" => 'Expense data has been added'],201);
    }

    public function read($dataPerPage,$targetYear){
        //mengambil semua data di kolom pada semua baris dan mereturn dalam bentuk objek expenses
        $expenses = Expense::select('*')->with('user')->where('expense_date','LIKE',$targetYear . '%')->paginate($dataPerPage);

        //mereturn response json berupa data dari objek expenses
        return response()->json(["expensesData" => $expenses]);
    }

    public function delete($id){
         $expense = Expense::select("*")->where("id",$id);

        if($expense->exists()){
            
            $expense->first()->delete();
            //mereturn response json berupa message data pada baris dengan id tersebut berhasil dihapus
            return response()->json(["message"=>"Expense has been  deleted"]);
        
        }
        else{

            return response()->json(["errors"=>"This expense is not exist, please refresh the page"],422);
        
        }
    }

}
