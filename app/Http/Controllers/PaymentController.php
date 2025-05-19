<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use phpDocumentor\Reflection\Types\Integer;

class PaymentController extends Controller
{
    
    public function create(Request $request){
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

    public function read(){
        //mengambil semua data di kolom pada semua baris dengan menambahkan data relasi dan mereturn dalam bentuk objek payments
        $payments = Payment::select('*')->with('resident')->get();

        //mereturn response json berupa data dari objek payments
        return response()->json(["paymentsData" => $payments]);
    }

}
