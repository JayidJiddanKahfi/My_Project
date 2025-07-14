<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use App\Models\Payment;

use App\Models\Resident;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\Browsershot\Browsershot;

class ReportController extends Controller
{

    public function report_for_contribution_pdf($targetYear){

        $months = collect(range(1,12))->map(function($month) use ($targetYear){return $targetYear . '-' . sprintf("%02d", $month);})->push($targetYear);

        // return $months;

        //mengambil semua data di kolom pada semua baris dengan menambahkan data relasi dan mereturn dalam bentuk objek payments
        $residents_with_payment = Resident::select('*')->with(['payments' => function ($query) use ($targetYear) {$query->where('payment_month','LIKE',$targetYear . "%");}])->get();

        // return response($residents_with_payment);

        $residents_with_payment->data = $residents_with_payment->map(function($resident) use ($months){
    
            $existing_payment_month = $resident->payments->pluck('payment_month');

            // return $existing_payment_month;

            $unpaid_payment = $months->diff($existing_payment_month)->map(function($unpaid_month) use ($resident){

                return [
                    'id' => null,
                    'resident_id' => $resident->id,
                    'payment_date' => null,
                    'payment_month' => $unpaid_month,
                    'payment_type' => strlen($unpaid_month) === 4 ? 'thr':'iuran',
                    'payment_method' => null,
                    'contribution' => 0,
                    'recorded_by' => null,
                    'created_at' => null,
                    'updated_at' => null,
                    'contribution_amount' => 0,
                    'social_fund' => 0,
                ];

            });

            $payments = collect($resident["payments"])->merge($unpaid_payment)->sortBy('payment_month')->sortBy(  function ($payment){return $payment["payment_type"] === 'thr' ? 1 : 0;})->values();

            $resident->setRelation('payments',$payments);

            return $resident;

        });


        // return response($residents_with_payment);

        $fee = Fee::select(["contribution_amount","social_fund"])->first();

        $total_resident_with_all_payment = count($residents_with_payment);

        for($i = 0; $i < $total_resident_with_all_payment; $i++){

            $total_payment_record = count($residents_with_payment[$i]["payments"]);

            for($j = 0; $j < $total_payment_record; $j++){

                $isPaid =  $residents_with_payment[$i]["payments"][$j]["payment_date"] !== null;
                
                // print($isPaid);
                
                if($isPaid){

                $residents_with_payment[$i]["payments"][$j]["contribution_amount"] = $fee->contribution_amount;

                $residents_with_payment[$i]["payments"][$j]["social_fund"] = $fee->social_fund;

                }
            }

        }

        
        // //mereturn response json berupa data dari objek payments
        // return response()->json(["contributionsData" => $residents_with_payment]);

        Carbon::setLocale('id');

        $today = Carbon::now()->translatedFormat('d F Y');

        // return $today;

        $pdf = Pdf::loadView('pdf/contribution',compact('residents_with_payment','targetYear','today'));

        $pdf->setPaper('A4','landscape') ;

        return $pdf->download("Rekapitulasi Iuran Warga Tahun " . $targetYear . ".pdf");

    }

     public function report_for_payment_image($residentId,$paymentDate,$paymentType){
        //mengambil semua data di kolom pada semua baris dengan menambahkan data relasi dan mereturn dalam bentuk objek payments
        $payment = 
        Payment::select([
            'resident_id',
            'payment_date',
            'recorded_by',
            'payment_type',
            'payment_method',
            Payment::raw(
                '
                    CASE
                        WHEN MIN(payment_month) = MAX(payment_month) THEN MAX(payment_month) 
                        ELSE CONCAT_WS(" to ",MIN(payment_month),MAX(payment_month)) 
                    END as month, 
                    SUM(contribution) as total')])
        ->where('resident_id',$residentId)->where('payment_date',$paymentDate)->where('payment_type',$paymentType)
        ->groupBy(['resident_id','payment_date','recorded_by','payment_type','payment_method'])
        ->with('resident')->with('user')
        ->first();

        Carbon::setLocale('id');

       if($payment->payment_type === 'iuran'){

         if(strlen($payment->month) === 18){
            $monthStringArray = explode(' to ',$payment->month);

            $newMonthStringFormat = '';

            foreach($monthStringArray as $index => $monthString){

                $date = Carbon::createFromFormat('Y-m',$monthString);

                if($index === 0){
                    $newMonthStringFormat = $newMonthStringFormat . "bulan ". $date->translatedFormat('F Y');
                }
                else{
                    $newMonthStringFormat = $newMonthStringFormat . " sampai dengan bulan " . $date->translatedFormat('F Y');
                }

            }

        }
        else if(strlen($payment->month) === 7){
             $newMonthStringFormat = '';

             $date = Carbon::createFromFormat('Y-m',$payment->month);

             $newMonthStringFormat = $newMonthStringFormat . "bulan ". $date->translatedFormat('F Y');
        }
        
          $payment->month = $newMonthStringFormat;

       }

        $dateStringFormat = $payment->payment_date;

        $date = Carbon::createFromFormat('Y-m-d',$dateStringFormat);

        $newDateStringFormat = $date->translatedFormat('d F Y');

        $payment["payment_date"] = $newDateStringFormat;

        // //mereturn response json berupa data dari objek payments
        // return response()->json(["paymentData" => $payment]);

        $html = view('invoice/payment', compact('payment'))->render();

        $imagePath = storage_path("app/public/img/Bukti Pembayaran_" . $payment["resident"]["salutation"] . " " . $payment["resident"]["full_name"]  . "_" . $paymentDate . "_" . $paymentType . ".png");
        
        if($payment["payment_type"] === 'iuran'){
            
            Browsershot::html($html)
            ->windowSize(558, 390)
            ->save($imagePath);

        }
        else{

            Browsershot::html($html)
            ->windowSize(558, 375)
            ->save($imagePath);

        }

        return response()->download($imagePath);
    }

     public function report_for_payment_pdf($residentId,$paymentDate,$paymentType){
        //mengambil semua data di kolom pada semua baris dengan menambahkan data relasi dan mereturn dalam bentuk objek payments
        $payment = 
        Payment::select([
            'resident_id',
            'payment_date',
            'recorded_by',
            'payment_type',
            'payment_method',
            Payment::raw(
                '
                    CASE
                        WHEN MIN(payment_month) = MAX(payment_month) THEN MAX(payment_month) 
                        ELSE CONCAT_WS(" to ",MIN(payment_month),MAX(payment_month)) 
                    END as month, 
                    SUM(contribution) as total')])
        ->where('resident_id',$residentId)->where('payment_date',$paymentDate)->where('payment_type',$paymentType)
        ->groupBy(['resident_id','payment_date','recorded_by','payment_type','payment_method'])
        ->with('resident')->with('user')
        ->first();

        Carbon::setLocale('id');

       if($payment->payment_type === 'iuran'){

         if(strlen($payment->month) === 18){
            $monthStringArray = explode(' to ',$payment->month);

            $newMonthStringFormat = '';

            foreach($monthStringArray as $index => $monthString){

                $date = Carbon::createFromFormat('Y-m',$monthString);

                if($index === 0){
                    $newMonthStringFormat = $newMonthStringFormat . "bulan ". $date->translatedFormat('F Y');
                }
                else{
                    $newMonthStringFormat = $newMonthStringFormat . " sampai dengan bulan " . $date->translatedFormat('F Y');
                }

            }

        }
        else if(strlen($payment->month) === 7){
             $newMonthStringFormat = '';

             $date = Carbon::createFromFormat('Y-m',$payment->month);

             $newMonthStringFormat = $newMonthStringFormat . "bulan ". $date->translatedFormat('F Y');
        }

          $payment->month = $newMonthStringFormat;

       }


        $dateStringFormat = $payment->payment_date;

        $date = Carbon::createFromFormat('Y-m-d',$dateStringFormat);

        $newDateStringFormat = $date->translatedFormat('d F Y');

        $payment["payment_date"] = $newDateStringFormat;

        // //mereturn response json berupa data dari objek payments
        // return response()->json(["paymentData" => $payment]);

       $pdf = Pdf::loadView('invoice/payment',compact('payment'));

        if($payment["payment_type"] === 'iuran'){
            
            $pdf->setPaper([0,0,480, 360],'potrait') ;

        }
        else{

            $pdf->setPaper([0,0,480, 350],'potrait') ;

        }

        return $pdf->download( "Bukti Pembayaran_" . $payment["resident"]["salutation"] . " " . $payment["resident"]["full_name"]  . "_" . $paymentDate . "_" . $paymentType . ".pdf");
    }
}
