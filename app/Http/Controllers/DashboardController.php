<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\Resident;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function read(){

        $now = Carbon::now();

        // print($now . "\n");

        $this_year = $now->format('Y'); 
        
        // print($this_year . "\n");

        $this_month = $now->format('m');

        // print($this_month . "\n");

        $total_income = Payment::select("contribution")->get()->sum('contribution');

        // print($total_income . "\n");
        
        $total_expense =  Expense::select('amount')->get()->sum('amount');

        // print($total_expense . "\n");

        $total_finance = $total_income - $total_expense;

        // print($total_finance . "\n");

        $this_years_income = Payment::select("contribution")->where("payment_date",'LIKE',$this_year ."%")->get()->sum('contribution');

        // print($this_years_income . "\n");

        $this_years_expense = Expense::select('amount')->where("expense_date","LIKE",$this_year . "%")->get()->sum("amount");

        // print($this_years_expense . "\n");

        $this_months_income = Payment::select("contribution")->where("payment_date",'LIKE',"%" . $this_month . "%")->get()->sum('contribution');;

        // print($this_months_income . "\n");

        $this_months_expense = Expense::select('amount')->where("expense_date","LIKE","%" . $this_month . "%")->get()->sum("amount");

        // print($this_months_expense . "\n");
        
        $unpaid_residents = Resident::count() - Payment::select('payment_month')->where('payment_month', "$this_year-$this_month")->count();

        // print( $unpaid_residents . "\n");

        $dashboard = [
           "general_information" => [
                "total_finance" => $total_finance,
                "total_income" => $total_income,
                "total_expense" => $total_expense,
           ],

           "annual recap" => [
                "this_year_income" => $this_years_income,
                "this_year_expense" => $this_years_expense
           ],

           "monthly recap" => [
                "this_month_income" => $this_months_income,
                "this_month_expense" => $this_months_expense,
                "unpaid_residents" => $unpaid_residents
           ]

        ];

        return response()->json(["dashboardData" => $dashboard]);

    }
}
