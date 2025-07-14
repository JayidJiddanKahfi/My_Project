<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use App\Models\Payment;
use App\Models\Resident;
use DateTime;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use phpDocumentor\Reflection\Types\Self_;
use stdClass;


class PaymentController extends Controller
{
    
    public function create_payment_first_version(Request $request){
        //membuat kumpulan rule (aturan) awal untuk validasi request
        $rules = [           
            "resident_id" => ["required","exists:residents,id"],
            "payment_date" => ["required","date"],
            "number_of_payment_month" => ["required","integer","min:1","max:48"],
        ];

        //membuat kumpulan atribut awal untuk melakukan pengisan data di masing masing kolom sesuai atribut
        $attributes = [
            "resident_id" => $request->resident_id,
            "payment_date" => $request->payment_date,
            "number_of_payment_month" => $request->number_of_payment_month,
            // "contribution" => 50_000.00,
            "recorded_by" => Auth::id()
        ];

        //membuat mencari kolom payment_month dari setiap baris yang memiliki resident_id sesuai input user dan membuatnya ke dalam objek payment_month
        $payment_month = Payment::select("payment_month")->where("resident_id",$request->resident_id)->get();

        //pengkondisian untuk mengecek apakah payment dengan resident_id sesuai input user sudah pernah diinput setidaknya 1 kali.
        //jika belum pernah diinput maka ke kondisi else dimana harus menginputkan secara manual payment_month
        //jika sudah maka cukup memasukan input number_of_payment_month, dan payment_month akan terisi otomatis sesuai input number_of_payment_month 
        if($payment_month->isNotEmpty()){
        
            // membuat validasi terhadap request dari user berdasakan rules yang telah dibuat
            $validator = Validator::make($request->all(),$rules);

            // pengkondisian jika hasilnya tidak valid maka akan mereturn respon json berupa error dari objek validator
            if($validator->fails()){
                return response()->json(["errors"=>$validator->errors()],422);
            }

            //mengisi attribute contribution dari kolom total_amount pada tabel fees yang dikalikan dengan number_of_payment_month dari hasil request
            $attributes["contribution"] = Fee::select("total_amount")->first()->total_amount * $request->number_of_payment_month;

            //menseleksi data terakhir dari kumpulan data payment_month dalam bentuk string dan disimpan di variabel last_payment_in_string
            $last_payment_in_string = $payment_month->last()->payment_month;

            // return print(strlen($last_payment_in_string)); untuk debugging

            //pengkondisian untuk mengecek panjang string last_payment_month_in_string
            //jika panjangnya = 7 (hanya pembayaran 1 bulan) maka akan masuk ke kondisi yang pertama
            //jika panjangnya > 7 (pembayaran lebih dari 1 bulan) maka akan masuk ke kondisi yang kedua
            if(strlen($last_payment_in_string) === 7){
                // print('masuk_1');  untuk debugging
                
                //memecah string yang berformat tahun-bulan menjadi array [tahun,bulan]
                $month_parts = explode('-',$last_payment_in_string);

                //mengubah string tahun pada array [tahun,bulan] menjadi integer
                $year_in_int = (int) $month_parts[0];

                //mengubah string bulan pada array [tahun,bulan] menjadi integer
                $month_in_int = (int) $month_parts[1];

                //menghitung  dan mendefinisikan total pembayaran dari bulan terakhir dalam integer (month_in_int) ditambah dengan jumlah bulan baru yang akan dibayar
                $total_month_of_payment = $month_in_int + $request->number_of_payment_month;
                
                //menghitung dan mendefinisikan untuk besar penambahan tahun
                $number_of_year_added = ceil($total_month_of_payment / 12) - 1;

                //menghitung dan mendefinisikan tahun terakhir pembayaran baru
                $new_last_payment_year = $year_in_int + $number_of_year_added;

                //menghitung dan mendefinisikan bulan terakhir pembayaran baru  
                $new_last_payment_month = sprintf("%02d",$total_month_of_payment - ($number_of_year_added * 12));

                //pengkondisian untuk mengecek jumlah bulan pembayaran
                //jika number_of_payment_month = 1 (hanya membayar 1 bulan) maka akan masuk ke kondisi pertama
                //jika number_of_payment_month > 1 (membayar lebih dari 1 bulan) maka akan masuk ke kondisi kedua
                if($request->number_of_payment_month === 1){
                    // print('masuk_1_1'); untuk debugging
                    
                    //pengkondisian untuk mengecek apakah total pembayaran (bulan terakhir bayar + jumlah bulan baru yang akan dibayarkan) lebih besar dari 12 atau tidak
                    // jika lebih kecil atau sama dengan 12 maka masuk ke kondisi pertama, jika lebih besar maka masuk ke kondisi kedua
                    if($total_month_of_payment <= 12){
                        // print('masuk_1_1_1'); untuk debugging
                        $attributes["payment_month"] = "$year_in_int-" .sprintf("%02d",$month_in_int + 1);
                    }
                    else{
                        // print('masuk_1_1_2'); untuk debugging
                        $attributes["payment_month"] = $year_in_int + 1 . "-01";
                    }
                }
                else{
                    // print('masuk_1_2'); untuk debugging

                    //pengkondisian untuk mengecek apakah total pembayaran (bulan terakhir bayar + jumlah bulan baru yang akan dibayarkan) lebih besar dari 12 atau tidak, dan bulan terakhir bayar adalah bulan 12 atau tidak
                    //jika lebih kecil atau sama dengan12 maka masuk ke kondisi pertama, jika lebih besar masuk ke kondisi ketiga, dan jika bulan terakhir yang dibayarkan adalah bulan 12 maka masuk ke kondisi kedua
                    if($total_month_of_payment <= 12){
                        print('masuk_1_2_1');
                        $attributes["payment_month"] = "$year_in_int-" . sprintf("%02d",$month_in_int + 1) . " to " . "$year_in_int-" . $new_last_payment_month ;
                    }
                    elseif($month_in_int === 12){
                        // print('masuk_1_2_2'); untuk debugging
                        $attributes["payment_month"] = $year_in_int + 1 . "-01" . " to " . "$new_last_payment_year-" . $new_last_payment_month;
                    }
                    else{
                        // print('masuk_1_2_3'); untuk debugging
                           $attributes["payment_month"] = "$year_in_int-" . sprintf("%02d",$month_in_int + 1) . " to " . "$new_last_payment_year-" . $new_last_payment_month;
                    }
                }
                
            }
            else{
                // print('masuk_2'); untuk debugging

                //memecah string yang berformat tahun-bulan to tahun-bulan menjadi array [tahun-bulan,tahun-bulan]
                $months_parts = explode(' to ',$last_payment_in_string);

                //memecah string yang berformat tahun-bulan index [0] pada array [tahun-bulan,tahun-bulan] menjadi array [tahun,bulan]
                // $start_parts = explode('-',$months_parts[0]); // tidak digunakan

                //memecah string yang berformat tahun-bulan index [1] pada array [tahun-bulan,tahun-bulan] menjadi array [tahun,bulan]
                $end_parts = explode('-',$months_parts[1]); // yang digunakan adalah bulan terakhir

                //mengubah string tahun pada array [tahun,bulan] menjadi integer
                $end_year_in_int = (int) $end_parts[0];

                //mengubah string bulan pada array [tahun,bulan] menjadi integer
                $end_month_in_int = (int) $end_parts[1];

                //menghitung  dan mendefinisikan total pembayaran dari bulan terakhir dalam integer (end_month_in_int) ditambah dengan jumlah bulan baru yang akan dibayar
                $total_month_of_payment = $end_month_in_int + $request->number_of_payment_month;

                //menghitung dan mendefinisikan untuk besar penambahan tahun
                $number_of_year_added = ceil($total_month_of_payment / 12) - 1;

                //menghitung dan mendefinisikan tahun terakhir pembayaran baru
                $new_last_payment_year = $end_year_in_int + $number_of_year_added;

                //menghitung dan mendefinisikan bulan terakhir pembayaran baru
                $new_last_payment_month = sprintf("%02d",$total_month_of_payment - ($number_of_year_added * 12));

                //pengkondisian untuk mengecek jumlah bulan pembayaran
                //jika number_of_payment_month = 1 (hanya membayar 1 bulan) maka akan masuk ke kondisi pertama
                //jika number_of_payment_month > 1 (membayar lebih dari 1 bulan) maka akan masuk ke kondisi kedua
                if($request->number_of_payment_month === 1){
                    // print('masuk_2_1'); untuk debugging

                    //pengkondisian untuk mengecek apakah total pembayaran (bulan terakhir bayar + jumlah bulan baru yang akan dibayarkan) lebih besar dari 12 atau tidak
                    // jika lebih kecil atau sama dengan 12 maka masuk ke kondisi pertama, jika lebih besar maka masuk ke kondisi kedua
                    if($total_month_of_payment <= 12){
                        // print('masuk_2_1_1'); untuk debugging
                        $attributes["payment_month"] = "$end_year_in_int-" . sprintf("%02d",$end_month_in_int + 1);
                    }
                    else{
                        // print('masuk_2_1_2'); untuk debugging
                        $attributes["payment_month"] = $end_year_in_int + 1 . "-01";
                    }
                }
                else{
                    // print('masuk_2_2'); untuk debugging

                    //pengkondisian untuk mengecek apakah total pembayaran (bulan terakhir bayar + jumlah bulan baru yang akan dibayarkan) lebih besar dari 12 atau tidak, dan bulan terakhir bayar adalah bulan 12 atau tidak
                    //jika lebih kecil atau sama dengan12 maka masuk ke kondisi pertama, jika lebih besar masuk ke kondisi ketiga, dan jika bulan terakhir yang dibayarkan adalah bulan 12 maka masuk ke kondisi kedua
                    if($total_month_of_payment <= 12){
                        // print('masuk_2_2_1'); untuk debugging
                        $attributes["payment_month"] = "$end_year_in_int-" . sprintf("%02d",$end_month_in_int + 1) . " to " . "$end_year_in_int-" . $new_last_payment_month ;
                    }
                    elseif($end_month_in_int === 12){
                        // print('masuk_2_2_2'); untuk debugging
                        $attributes["payment_month"] = $end_year_in_int + 1 . "-01" . " to " . "$new_last_payment_year-" . $new_last_payment_month;
                    }
                    else{
                        print('masuk_2_2_3');
                        $attributes["payment_month"] = "$end_year_in_int-" . sprintf("%02d",$end_month_in_int + 1) . " to " . "$new_last_payment_year-" . $new_last_payment_month;
                    }
                }

            }
        
        }
        else{
            //membuat pengkondisian untuk melakukan pengisan rule tambahan dan attribute tambahan
            if($request->number_of_payment_month === 1){
                $rules["payment_month"] = ["required","string","size:7"];
            }
            else{
                $rules["payment_month"] = ["required","string","size:18"];
            }

            // membuat validasi terhadap request dari user berdasakan rules yang telah dibuat
            $validator = Validator::make($request->all(),$rules);

            // pengkondisian jika hasilnya tidak valid maka akan mereturn respon json berupa error dari objek validator
            if($validator->fails()){
                return response()->json(["errors"=>$validator->errors()],422);
            }

            $attributes["contribution"] = Fee::select("total_amount")->first()->total_amount * $request->number_of_payment_month;

            if($request->number_of_payment_month === 1){

                $attributes["payment_month"] = $request->payment_month;
            
            }
            else{
                
                //mengubah nilai string hasil input atribut payment_month ke integer untuk mendapatkan jumlah selisih bulan 
                $month_parts = explode(" to ",$request->payment_month); // mendefinisikan pemisahan untuk start_parts dan end_parts pada month

                $start_parts = explode("-",$month_parts[0]); //mendefinisikan pemisahan antara bulan dan tahun pada start_parts

                $end_parts = explode("-",$month_parts[1]); //mendefinisikan pemisahan antara bulan dan tahun pada end_parts

                $start_year = (int) $start_parts[0];  //mendefinisikan start_year dari hasil pemisahan pada start_parts

                $end_year = (int) $end_parts[0]; //mendefiniskan end_year dari hasil pemisahan pada end_parts

                $start_month = (int) $start_parts[1]; //mendefinisikan start_month dari hasil pemisahan pada start_parts

                $end_month = (int) $end_parts[1]; //mendefinisikan end_month dari hasil pemisahan pada end_parts

                //mendefinisikan total_month_of_payment sebagai hasil kalkulasi jumlah pembayaran berdasarkan banyaknya bulan
                $total_month_of_payments = (($end_year - $start_year) * 12 + $end_month) - $start_month + 1;

                if($total_month_of_payments >= 1 && $total_month_of_payments === $request->number_of_payment_month){
                    $attributes["payment_month"] = $request->payment_month;
                }
                elseif($total_month_of_payments < 1){
                    return response()->json(["message" => "the end payment must be greater than the start payment"],422);
                }
                elseif ( $total_month_of_payments !== $request->number_of_payment_month) {
                    return response()->json(["message" => "you have to select the month as the value in number of payment column"],422);
                }
            }
        }
        // jika hasilnya valid, selanjutnya membuat objek payment sekaligus membuat data baru di tabel payments berdasarkan attribute
        $payment = Payment::create($attributes);

        //mereturn response json berupa data dari objek payment
        return response()->json(["paymentData" => $payment],201);
    }

    public function create_payment_second_version(Request $request){
        //membuat kumpulan rule (aturan) awal untuk validasi request
        $rules = [           
            "resident_id" => ["required","exists:residents,id"],
            "payment_date" => ["required","date"],
            "number_of_payment_month" => ["required","integer","min:1","max:48"],
        ];

        //membuat kumpulan atribut awal untuk melakukan pengisan data di masing masing kolom sesuai atribut
        $attributes = [
            "resident_id" => $request->resident_id,
            "payment_date" => $request->payment_date,
            "number_of_payment_month" => 1,
            // "contribution" => 50_000.00,
            "recorded_by" => Auth::id()
        ];

        //membuat mencari kolom payment_month dari setiap baris yang memiliki resident_id sesuai input user dan membuatnya ke dalam objek payment_month
        $payment_month = Payment::select("payment_month")->where("resident_id",$request->resident_id)->get();

        //pengkondisian untuk mengecek apakah payment dengan resident_id sesuai input user sudah pernah diinput setidaknya 1 kali.
        //jika belum pernah diinput maka ke kondisi else dimana harus menginputkan secara manual payment_month
        //jika sudah maka cukup memasukan input number_of_payment_month, dan payment_month akan terisi otomatis sesuai input number_of_payment_month 
        if($payment_month->isNotEmpty()){
        
            // membuat validasi terhadap request dari user berdasakan rules yang telah dibuat
            $validator = Validator::make($request->all(),$rules);

            // pengkondisian jika hasilnya tidak valid maka akan mereturn respon json berupa error dari objek validator
            if($validator->fails()){
                return response()->json(["errors"=>$validator->errors()],422);
            }

            //mengisi attribute contribution dari kolom total_amount pada tabel fees
            $attributes["contribution"] = Fee::select("total_amount")->first()->total_amount;

            //menseleksi data terakhir dari kumpulan data payment_month dalam bentuk string dan disimpan di variabel last_payment_in_string
            $last_payment_in_string = $payment_month->last()->payment_month;
                
            //memecah string yang berformat tahun-bulan menjadi array [tahun,bulan]
            $month_parts = explode('-',$last_payment_in_string);

            //mengubah string tahun pada array [tahun,bulan] menjadi integer
            $year_in_int = (int) $month_parts[0];

            //mengubah string bulan pada array [tahun,bulan] menjadi integer
            $month_in_int = (int) $month_parts[1];

            //menghitung  dan mendefinisikan total pembayaran dari bulan terakhir dalam integer (month_in_int) ditambah dengan jumlah bulan baru yang akan dibayar
            $total_month_of_payment = $month_in_int + $request->number_of_payment_month;

            //pengkondisian untuk mengecek jumlah bulan pembayaran
            //jika number_of_payment_month = 1 (hanya membayar 1 bulan) maka akan masuk ke kondisi pertama
            //jika number_of_payment_month > 1 (membayar lebih dari 1 bulan) maka akan masuk ke kondisi kedua
            if($request->number_of_payment_month === 1){
                // print('masuk_1_1'); untuk debugging
                
                //pengkondisian untuk mengecek apakah total pembayaran (bulan terakhir bayar + jumlah bulan baru yang akan dibayarkan) lebih besar dari 12 atau tidak
                // jika lebih kecil atau sama dengan 12 maka masuk ke kondisi pertama, jika lebih besar maka masuk ke kondisi kedua
                if($total_month_of_payment <= 12){
                    // print('masuk_1_1_1'); untuk debugging
                    $attributes["payment_month"] = "$year_in_int-" .sprintf("%02d",$month_in_int + 1);
                }
                else{
                    // print('masuk_1_1_2'); untuk debugging
                    $attributes["payment_month"] = $year_in_int + 1 . "-01";
                }

                $payment = Payment::create($attributes);

                //mereturn response json berupa data dari objek payment
                return response()->json(["paymentData" => $payment],201);
            
            }
            else{
                // print('masuk_1_2'); untuk debugging
                $payment = [];

                for ($i = 1 ; $i <= $request->number_of_payment_month; $i++) { 

                    //menghitung dan mendefinisikan untuk besar penambahan tahun
                    $number_of_year_added = ceil(($month_in_int + $i) / 12) - 1;
    
                    $attributes["payment_month"] = $year_in_int + $number_of_year_added . "-" . sprintf("%02d",($month_in_int + $i) - ($number_of_year_added * 12));

                    $payment[$i - 1] = Payment::create($attributes);

                }
                
                //mereturn response json berupa data dari objek payment
                return response()->json(["paymentData" => $payment],201);
            }
        }
        else{
            //membuat pengkondisian untuk melakukan pengisan rule tambahan dan attribute tambahan
            if($request->number_of_payment_month === 1){
                $rules["payment_month"] = ["required","string","size:7"];
            }
            else{
                $rules["payment_month"] = ["required","string","size:18"];
            }

            // membuat validasi terhadap request dari user berdasakan rules yang telah dibuat
            $validator = Validator::make($request->all(),$rules);

            // pengkondisian jika hasilnya tidak valid maka akan mereturn respon json berupa error dari objek validator
            if($validator->fails()){
                return response()->json(["errors"=>$validator->errors()],422);
            }

            $attributes["contribution"] = Fee::select("total_amount")->first()->total_amount;

            if($request->number_of_payment_month === 1){

                $attributes["payment_month"] = $request->payment_month;

                $payment = Payment::create($attributes);

                //mereturn response json berupa data dari objek payment
                return response()->json(["paymentData" => $payment],201);
            
            }
            else{
                
                //mengubah nilai string hasil input atribut payment_month ke integer untuk mendapatkan jumlah selisih bulan 
                $month_parts = explode(" to ",$request->payment_month); // mendefinisikan pemisahan untuk start_parts dan end_parts pada month

                $start_parts = explode("-",$month_parts[0]); //mendefinisikan pemisahan antara bulan dan tahun pada start_parts

                $end_parts = explode("-",$month_parts[1]); //mendefinisikan pemisahan antara bulan dan tahun pada end_parts

                $start_year = (int) $start_parts[0];  //mendefinisikan start_year dari hasil pemisahan pada start_parts

                $end_year = (int) $end_parts[0]; //mendefiniskan end_year dari hasil pemisahan pada end_parts

                $start_month = (int) $start_parts[1]; //mendefinisikan start_month dari hasil pemisahan pada start_parts

                $end_month = (int) $end_parts[1]; //mendefinisikan end_month dari hasil pemisahan pada end_parts

                //mendefinisikan total_month_of_payment sebagai hasil kalkulasi jumlah pembayaran berdasarkan banyaknya bulan
                $total_month_of_payments = (($end_year - $start_year) * 12 + $end_month) - $start_month + 1;

                if($total_month_of_payments > 1 && $total_month_of_payments === $request->number_of_payment_month){
                    $payment = [];
                    
                    for($i = 0; $i < $request->number_of_payment_month ; $i++){

                        $number_of_year_added = ceil(($start_month + $i) / 12) - 1;

                        $attributes["payment_month"] = $start_year + $number_of_year_added . "-" . sprintf("%02d",($start_month + $i) - ($number_of_year_added * 12));
                        
                        $payment[$i] = Payment::create($attributes);

                    }
                    //mereturn response json berupa data dari objek payment
                    return response()->json(["paymentData" => $payment],201);
                }
                elseif($total_month_of_payments < 1){
                    return response()->json(["message" => "the end payment must be greater than the start payment"],422);
                }
                elseif ( $total_month_of_payments !== $request->number_of_payment_month) {
                    return response()->json(["message" => "you have to select the month as the value in number of payment column"],422);
                }
                else{
                    return response()->json(["message" => "you have to select the month more than 1"],422);
                }
                
            }
        }

    }

    public function create_payment_third_version(Request $request){
        //membuat kumpulan rule (aturan) awal untuk validasi request
        $rules = [           
            "resident_id" => ["required","exists:residents,id"],
            "payment_date" => ["required","date"],
            "payment_type" => ["required","in:thr,iuran"],
            "payment_method" => ["required","in:cash,transfer,e-wallet"],
        ];

        //membuat kumpulan atribut awal untuk melakukan pengisan data di masing masing kolom sesuai atribut
        $attributes = [
            "resident_id" => $request->resident_id,
            "payment_date" => $request->payment_date,
            "payment_type" => $request->payment_type,
            "payment_method" => $request->payment_method,
            "contribution" =>  Fee::select("total_amount")->first()->total_amount,
            "recorded_by" => Auth::id()
        ];

        if($request->payment_type === 'iuran'){
            $rules["payment_month"] = ["required","string",'size:18'];
        }
        elseif($request->payment_type === 'thr'){
            $rules["payment_month"] = ["required","string",'size:4'];
        }

    
        // membuat validasi terhadap request dari user berdasakan rules yang telah dibuat
        $validator = Validator::make($request->all(),$rules);

        // pengkondisian jika hasilnya tidak valid maka akan mereturn respon json berupa error dari objek validator
        if($validator->fails()){
            return response()->json(["errors"=>$validator->errors()],422);
        }

        if($request->payment_type === 'thr'){

            $existing_payment_year_for_thr = Payment::select('payment_month')->where('resident_id',$request->resident_id)->where('payment_month',$request->payment_month)->first();

            if($existing_payment_year_for_thr){

                return response()->json(["errors" => "Payment has paid for " . $existing_payment_year_for_thr->payment_month],422);
            }
            else{

                $attributes["payment_month"] = $request->payment_month;

                $payment = Payment::create($attributes);

                //mereturn response json berupa data dari objek payment
                return response()->json(["paymentData" => $payment, "message" => 'Payment data has been added'],201);
            }
           
        }

        //mengubah nilai string hasil input atribut payment_month ke integer untuk mendapatkan jumlah selisih bulan 
        $month_parts = explode(" to ",$request->payment_month); // mendefinisikan pemisahan untuk start_parts dan end_parts pada month

        $start_parts = explode("-",$month_parts[0]); //mendefinisikan pemisahan antara bulan dan tahun pada start_parts

        $end_parts = explode("-",$month_parts[1]); //mendefinisikan pemisahan antara bulan dan tahun pada end_parts

        $start_year = (int) $start_parts[0];  //mendefinisikan start_year dari hasil pemisahan pada start_parts

        $end_year = (int) $end_parts[0]; //mendefiniskan end_year dari hasil pemisahan pada end_parts

        $start_month = (int) $start_parts[1]; //mendefinisikan start_month dari hasil pemisahan pada start_parts

        $end_month = (int) $end_parts[1]; //mendefinisikan end_month dari hasil pemisahan pada end_parts

        // print($start_year . "\n");
        // print($end_year . "\n");
        // print($start_month . "\n");
        // print($end_month . "\n");
        // return;

        //mendefinisikan total_month_of_payment sebagai hasil kalkulasi jumlah pembayaran berdasarkan banyaknya bulan
        $total_month_of_payments = (($end_year - $start_year) * 12 + $end_month) - $start_month + 1;

        $existing_payment_month = Payment::select("payment_month")->where('resident_id',$request->resident_id)->where(Payment::raw("LENGTH(payment_month)"),"=",7)->whereBetween("payment_month",[$month_parts[0],$month_parts[1]])->orderBy('payment_month','asc')->get();
       
        // print($existing_payment_month);
        // return;

        //masih direvisi
        //ide 1 mengecek apakah ada payment_date di database yang lebih besar dari payment_date input tetapi memilih bulan yang lebih kecil dari bulan yang diinput user
        // $payment_month_with_same_payment_date = Payment::select(["payment_date","payment_month"])->where("resident_id",$request->resident_id)->where("payment_date",">",$request->payment_date)->where('payment_month','<',$month_parts[1])->get();

        //ide 2 mengecek apakah payment_month yang diinputkan user tidak berurut baik lebih kecil maupun lebih besar dari paymonth_month berdasarkan payment_date yang sama
        $payment_month_with_same_payment_date = Payment::selectRaw("MAX(payment_date) as payment_date, MIN(payment_month) as min_month, MAX(payment_month) as max_month")->where("resident_id",$request->resident_id)->where("payment_date",$request->payment_date)->where(Payment::raw("LENGTH(payment_month)"),"=",7)->first();

        // print($payment_month_with_same_payment_date);
        // return;

        if($existing_payment_month->count() !== 0){
            
            $existing_payment_month_in_string = '';

            for($i = 0; $i < $existing_payment_month->count(); $i++){

                if($start_month === $end_month || $existing_payment_month->count() === 1){
                    $existing_payment_month_in_string = $existing_payment_month_in_string . $existing_payment_month[$i]->payment_month;
                }
                elseif($i === $existing_payment_month->count() - 1 && $start_month !== $end_month){
                    $existing_payment_month_in_string = $existing_payment_month_in_string . "and " . $existing_payment_month[$i]->payment_month;
                }
                else{
                    $existing_payment_month_in_string = $existing_payment_month_in_string . $existing_payment_month[$i]->payment_month . ", ";
                }

            }

            return response()->json(["errors" => "Payment has paid for $existing_payment_month_in_string"],422);

        }
        elseif($payment_month_with_same_payment_date->min_month !== null && $payment_month_with_same_payment_date->max_month !== null){
            
            $payment_date = $payment_month_with_same_payment_date->payment_date;
            $min_payment_month = $payment_month_with_same_payment_date->min_month;
            $max_payment_month = $payment_month_with_same_payment_date->max_month;

            // print($max_payment_month . "\n");
            // print($min_payment_month . "\n");
            // return;

            $min_payment_month_in_date_Object = DateTime::createFromFormat('Y-m',$min_payment_month);
            $max_payment_month_in_date_Object = DateTime::createFromFormat('Y-m',$max_payment_month);

            $month_before_min_payment_month_in_date_format = $min_payment_month_in_date_Object->modify('-1 month');
            $month_after_max_payment_month_in_date_format = $max_payment_month_in_date_Object->modify('+1 month');

            $max_payment_month_accepted = $month_before_min_payment_month_in_date_format->format('Y-m');
            $min_payment_month_accepted = $month_after_max_payment_month_in_date_format->format('Y-m');

            // print($max_payment_month_accepted . "\n");
            // print($min_payment_month_accepted . "\n");
            // return;

            if($month_parts[0] !== $min_payment_month_accepted && $month_parts[1] !== $max_payment_month_accepted){
                if($min_payment_month === $max_payment_month){
                    $error = "Payment Month must be sequential according to the existing Payment Month for the same Payment Date that you entered. Note: the existing Payment Month in the same Payment Date is $min_payment_month according to Payment Date $payment_date";
                }
                else{
                    $error = "Payment Month must be sequential according to the existing Payment Month for the same Payment Date that you entered. Note: the existing Payment Month in the same Payment Date are $min_payment_month to $max_payment_month according to Payment Date $payment_date";
                }
                return response()->json(["errors" => $error],422);
            }
            else{
                if($total_month_of_payments >= 1){
                    $payment = [];
                    
                    for($i = 0; $i < $total_month_of_payments ; $i++){

                        $number_of_year_added = ceil(($start_month + $i) / 12) - 1;

                        $attributes["payment_month"] = $start_year + $number_of_year_added . "-" . sprintf("%02d",($start_month + $i) - ($number_of_year_added * 12));
                        
                        $payment[$i] = Payment::create($attributes);

                    }
                    //mereturn response json berupa data dari objek payment
                    return response()->json(["paymentData" => $payment,"message" => 'Payment data has been added'],201);
                }
                elseif($total_month_of_payments < 1){

                    return response()->json(["errors" => "the end payment must be greater than the start payment"],422);
                
                }
                
            }
        }
        else{

            if($total_month_of_payments >= 1){
                $payment = [];
                
                for($i = 0; $i < $total_month_of_payments ; $i++){

                    $number_of_year_added = ceil(($start_month + $i) / 12) - 1;

                    $attributes["payment_month"] = $start_year + $number_of_year_added . "-" . sprintf("%02d",($start_month + $i) - ($number_of_year_added * 12));
                    
                    $payment[$i] = Payment::create($attributes);

                }
                //mereturn response json berupa data dari objek payment
                return response()->json(["paymentData" => $payment,"message" => 'Payment data has been added'],201);
            }
            elseif($total_month_of_payments < 1){

                return response()->json(["message" => "the end payment must be greater than the start payment"],422);
            
            }
                
        }
            

    }

    public function read_for_payments($targetYear,$dataPerPage,$targetMonth = ''){
        //mengambil semua data di kolom pada semua baris dengan menambahkan data relasi dan mereturn dalam bentuk objek payments
        $payments = 
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
        ->where('payment_month','LIKE',$targetYear . $targetMonth)
        ->groupBy(['resident_id','payment_date','recorded_by','payment_type','payment_method'])
        ->with('resident')->with('user')
        ->paginate($dataPerPage);

        //mereturn response json berupa data dari objek payments
        return response()->json(["paymentsData" => $payments]);
    }

    public function read_for_contributions($targetYear,$dataPerPage,$targetMonth = ''){

        $months = collect(range(1,12))->map(function($month) use ($targetYear){return $targetYear . '-' . sprintf("%02d", $month);})->push($targetYear);

        // return $months;

        //mengambil semua data di kolom pada semua baris dengan menambahkan data relasi dan mereturn dalam bentuk objek payments
        $residents_with_payment = Resident::select('*')->with(['payments' => function ($query) use ($targetYear,$targetMonth) {$query->where('payment_month','LIKE',$targetYear . $targetMonth);}])->paginate($dataPerPage);

        // return response($residents_with_payment);

        if($targetMonth === '%'){

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

                    $payments = collect($resident->payments)->merge($unpaid_payment)->sortBy('payment_month')->sortBy(  function ($payment){return $payment["payment_type"] === 'thr' ? 1 : 0;})->values();

                    $resident->setRelation('payments',$payments);

                    return $resident;

                });
        }
        elseif($targetMonth !== '%'){

            $residents_with_payment->data = $residents_with_payment->map(function($resident) use($targetYear,$targetMonth){
                $payments = [];
              if($resident->payments->count() === 0){
                //  print('tidak ada');

                    $payments = collect([[
                        'id' => null,
                        'resident_id' => $resident->id,
                        'payment_date' => null,
                        'payment_month' => $targetYear . $targetMonth,
                        'payment_type' => $targetMonth === '' ? 'thr':'iuran',
                        'payment_method' => null,
                        'contribution' => 0,
                        'recorded_by' => null,
                        'created_at' => null,
                        'updated_at' => null,
                        'contribution_amount' => 0,
                        'social_fund' => 0,

                ]]);
              }
              else{
                // print('ada');

                $payments = $resident->payments;

              }

                $resident->setRelation('payments',$payments);

                return $resident;

            });

        }

        // return response($residents_with_payment);

        $fee = Fee::select(["contribution_amount","social_fund"])->first();

        $total_resident_with_all_payment = count($residents_with_payment);

        for($i = 0; $i < $total_resident_with_all_payment; $i++){

            $total_payment_record = count($residents_with_payment[$i]->payments);

            for($j = 0; $j < $total_payment_record; $j++){

                $isPaid =  $residents_with_payment[$i]->payments[$j]["payment_date"] !== null;
                
                // print($isPaid);
                
                if($isPaid){

                $residents_with_payment[$i]->payments[$j]->contribution_amount = $fee->contribution_amount;

                $residents_with_payment[$i]->payments[$j]->social_fund = $fee->social_fund;

                }
            }

        }

        //mereturn response json berupa data dari objek payments
        return response()->json(["contributionsData" => $residents_with_payment]);

    }

    // public function update_payment(Request $request,$residentId,$paymentDate,$paymentType){
    //     //membuat kumpulan rule (aturan) awal untuk validasi request
    //     $rules = [           
    //         "resident_id" => ["required","exists:residents,id"],
    //         "payment_date" => ["required","date"],
    //         "payment_type" => ["required","in:thr,iuran"],
    //         "payment_method" => ["required","in:cash,transfer,e-wallet"],
    //     ];

    //     //membuat kumpulan atribut awal untuk melakukan pengisan data di masing masing kolom sesuai atribut
    //     $attributes = [
    //         "resident_id" => $request->resident_id,
    //         "payment_date" => $request->payment_date,
    //         "payment_type" => $request->payment_type,
    //         "payment_method" => $request->payment_method,
    //         "contribution" =>  Fee::select("total_amount")->first()->total_amount,
    //         "recorded_by" => Auth::id()
    //     ];

    //     if($request->payment_type === 'iuran'){
    //         $rules["payment_month"] = ["required","string",'size:18'];
    //     }
    //     elseif($request->payment_type === 'thr'){
    //         $rules["payment_month"] = ["required","string",'size:4'];
    //     }

    
    //     // membuat validasi terhadap request dari user berdasakan rules yang telah dibuat
    //     $validator = Validator::make($request->all(),$rules);

    //     // pengkondisian jika hasilnya tidak valid maka akan mereturn respon json berupa error dari objek validator
    //     if($validator->fails()){
    //         return response()->json(["errors"=>$validator->errors()],422);
    //     }

    //     if($request->payment_type === 'thr'){

    //         $existing_payment_year_for_thr = Payment::select('payment_month')->where('resident_id',$request->resident_id)->where('payment_month',$request->payment_month)->first();

    //         if($existing_payment_year_for_thr){

    //             return response()->json(["error" => "Payment has paid for " . $existing_payment_year_for_thr->payment_month],422);
    //         }
    //         else{

    //             $attributes["payment_month"] = $request->payment_month;

    //             Payment::select('*')->where('resident_id',$residentId)->where('payment_date',$paymentDate)->where('payment_type',$paymentType)->delete();

    //             $payment = Payment::create($attributes);

    //             //mereturn response json berupa data dari objek payment
    //             return response()->json(["paymentData" => $payment],201);
    //         }
           
    //     }

    //     //mengubah nilai string hasil input atribut payment_month ke integer untuk mendapatkan jumlah selisih bulan 
    //     $month_parts = explode(" to ",$request->payment_month); // mendefinisikan pemisahan untuk start_parts dan end_parts pada month

    //     $start_parts = explode("-",$month_parts[0]); //mendefinisikan pemisahan antara bulan dan tahun pada start_parts

    //     $end_parts = explode("-",$month_parts[1]); //mendefinisikan pemisahan antara bulan dan tahun pada end_parts

    //     $start_year = (int) $start_parts[0];  //mendefinisikan start_year dari hasil pemisahan pada start_parts

    //     $end_year = (int) $end_parts[0]; //mendefiniskan end_year dari hasil pemisahan pada end_parts

    //     $start_month = (int) $start_parts[1]; //mendefinisikan start_month dari hasil pemisahan pada start_parts

    //     $end_month = (int) $end_parts[1]; //mendefinisikan end_month dari hasil pemisahan pada end_parts

    //     // print($start_year . "\n");
    //     // print($end_year . "\n");
    //     // print($start_month . "\n");
    //     // print($end_month . "\n");
    //     // return;

    //     //mendefinisikan total_month_of_payment sebagai hasil kalkulasi jumlah pembayaran berdasarkan banyaknya bulan
    //     $total_month_of_payments = (($end_year - $start_year) * 12 + $end_month) - $start_month + 1;

    //     $existing_payment_month = Payment::select("payment_month")->where('resident_id',$request->resident_id)->where(Payment::raw("LENGTH(payment_month)"),"=",7)->whereBetween("payment_month",[$month_parts[0],$month_parts[1]])->orderBy('payment_month','asc')->get();
       
    //     // print($existing_payment_month);
    //     // return;

    //     //masih direvisi
    //     //ide 1 mengecek apakah ada payment_date di database yang lebih besar dari payment_date input tetapi memilih bulan yang lebih kecil dari bulan yang diinput user
    //     // $payment_month_with_same_payment_date = Payment::select(["payment_date","payment_month"])->where("resident_id",$request->resident_id)->where("payment_date",">",$request->payment_date)->where('payment_month','<',$month_parts[1])->get();

    //     //ide 2 mengecek apakah payment_month yang diinputkan user tidak berurut baik lebih kecil maupun lebih besar dari paymonth_month berdasarkan payment_date yang sama
    //     $payment_month_with_same_payment_date = Payment::selectRaw("MAX(payment_date) as payment_date, MIN(payment_month) as min_month, MAX(payment_month) as max_month")->where("resident_id",$request->resident_id)->where("payment_date",$request->payment_date)->where(Payment::raw("LENGTH(payment_month)"),"=",7)->first();

    //     // print($payment_month_with_same_payment_date);
    //     // return;

    //     if($existing_payment_month->count() !== 0){
            
    //         $existing_payment_month_in_string = '';

    //         for($i = 0; $i < $existing_payment_month->count(); $i++){

    //             if($start_month === $end_month || $existing_payment_month->count() === 1){
    //                   $existing_payment_month_in_string = $existing_payment_month_in_string . $existing_payment_month[$i]->payment_month;
    //             }
    //             elseif($i === $existing_payment_month->count() - 1 && $start_month !== $end_month){
    //                 $existing_payment_month_in_string = $existing_payment_month_in_string . "and " . $existing_payment_month[$i]->payment_month;
    //             }
    //             else{
    //                 $existing_payment_month_in_string = $existing_payment_month_in_string . $existing_payment_month[$i]->payment_month . ", ";
    //             }

    //         }

    //         return response()->json(["errors" => "Payment has paid for $existing_payment_month_in_string"],422);

    //     }
    //     elseif($payment_month_with_same_payment_date->min_month !== null && $payment_month_with_same_payment_date->max_month !== null){
            
    //         $payment_date = $payment_month_with_same_payment_date->payment_date;
    //         $min_payment_month = $payment_month_with_same_payment_date->min_month;
    //         $max_payment_month = $payment_month_with_same_payment_date->max_month;

    //         // print($max_payment_month . "\n");
    //         // print($min_payment_month . "\n");
    //         // return;

    //         $min_payment_month_in_date_Object = DateTime::createFromFormat('Y-m',$min_payment_month);
    //         $max_payment_month_in_date_Object = DateTime::createFromFormat('Y-m',$max_payment_month);

    //         $month_before_min_payment_month_in_date_format = $min_payment_month_in_date_Object->modify('-1 month');
    //         $month_after_max_payment_month_in_date_format = $max_payment_month_in_date_Object->modify('+1 month');

    //         $max_payment_month_accepted = $month_before_min_payment_month_in_date_format->format('Y-m');
    //         $min_payment_month_accepted = $month_after_max_payment_month_in_date_format->format('Y-m');

    //         // print($max_payment_month_accepted . "\n");
    //         // print($min_payment_month_accepted . "\n");
    //         // return;

    //         if($month_parts[0] !== $min_payment_month_accepted && $month_parts[1] !== $max_payment_month_accepted){
    //             if($min_payment_month === $max_payment_month){
    //                 $error = "Payment_month must be sequential according to the existing payment_month for the same payment_date that you entered. Note: the existing payment_month in the same payment_date is $min_payment_month according to payment_date $payment_date";
    //             }
    //             else{
    //                 $error = "Payment_month must be sequential according to the existing payment_month for the same payment_date that you entered. Note: the existing payment_month in the same payment_date are $min_payment_month to $max_payment_month according to payment_date $payment_date";
    //             }
    //             return response()->json(["error" => $error],422);
    //         }
    //         else{
    //             if($total_month_of_payments >= 1){
    //                 $payment = [];

    //                 Payment::select('*')->where('resident_id',$residentId)->where('payment_date',$paymentDate)->where('payment_type',$paymentType)->delete();
                    
    //                 for($i = 0; $i < $total_month_of_payments ; $i++){

    //                     $number_of_year_added = ceil(($start_month + $i) / 12) - 1;

    //                     $attributes["payment_month"] = $start_year + $number_of_year_added . "-" . sprintf("%02d",($start_month + $i) - ($number_of_year_added * 12));

    //                     $payment[$i] = Payment::create($attributes);

    //                 }
    //                 //mereturn response json berupa data dari objek payment
    //                 return response()->json(["paymentData" => $payment],201);
    //             }
    //             elseif($total_month_of_payments < 1){

    //                 return response()->json(["error" => "the end payment must be greater than the start payment"],422);
                
    //             }
                
    //         }
    //     }
    //     else{

    //         if($total_month_of_payments >= 1){
    //             $payment = [];

    //             Payment::select('*')->where('resident_id',$residentId)->where('payment_date',$paymentDate)->where('payment_type',$paymentType)->delete();
                
    //             for($i = 0; $i < $total_month_of_payments ; $i++){

    //                 $number_of_year_added = ceil(($start_month + $i) / 12) - 1;

    //                 $attributes["payment_month"] = $start_year + $number_of_year_added . "-" . sprintf("%02d",($start_month + $i) - ($number_of_year_added * 12));
                    
    //                 $payment[$i] = Payment::create($attributes);

    //             }
    //             //mereturn response json berupa data dari objek payment
    //             return response()->json(["paymentData" => $payment],201);
    //         }
    //         elseif($total_month_of_payments < 1){

    //             return response()->json(["message" => "the end payment must be greater than the start payment"],422);
            
    //         }
                
    //     }
            

    // }

    public function delete_payment($residentId,$paymentDate,$paymentType){
        
        $payments = Payment::select('*')->where('resident_id',$residentId)->where('payment_date',$paymentDate)->where('payment_type',$paymentType);

        if($payments->exists()){
            
            $payments->get()->each(function($payment){$payment->delete();});
            //mereturn response json berupa message data pada baris dengan id tersebut berhasil dihapus
            return response()->json(["message"=>"Payment has been deleted"],200);
        
        }
        else{

            return response()->json(["errors"=>"This payment is not exist, please refresh the page"],422);
        
        }

    

    }

}
