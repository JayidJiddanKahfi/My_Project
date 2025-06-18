<!-- resources/views/invoice.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Invoice</title>
    <style>
        body { 
         font-family: sans-serif; 
        }

        .invoice-box { 
            text-align: justify;
            width: 500px; 
            padding: 20px; 
            border: 1px solid black; 
            border-radius: 10px;
            font-size: 12px;    
        }

         table.signature_container {
            width: 100%; 
            margin-top: 40px; 
            font-size: 12px;
        }

        table.signature_container td.null_column {
            width: 65%;
        }

        table.signature_container td.signature_column{
            width: 35%;
        }
    </style>
</head>
<body>
    <div class="invoice-box">
       <div class="header">
         <h2>Bukti Pembayaran</h2>
       </div>
       
        <div class="body">
            <p> 
                Terimakasih kepada {{$payment->resident->salutation}} {{$payment->resident->full_name}}
                dengan alamat, Pura Bojong Gede {{$payment->resident->address}} yang telah melakukan pembayaran
                {{$payment->payment_type === 'iuran' ? 'iuran bulanan untuk ' . $payment->month : 'iuran thr tahun ' . $payment->month}}
                pada tanggal {{$payment->payment_date}}.
            </p>

            <p> Semoga {{$payment->resident->salutation}} {{$payment->resident->full_name}} dalam keadaan sehat selalu. </p>
       
        </div>

       
       <table class="signature_container">
            <tr>
                <td class="null_column">
                
                </td>
                
                <td class="signature_column">

                    <p>Bogor, {{ $payment->payment_date }}</p>

                    <p>Bendahara RT 02 / RW 24</p>

                    <img src="{{ public_path('storage/img/ttd_mahfuddin_zuhri.jpg') }}" width="60" height="60">
                    
                    <p>Mahfuddin Zuhri</p>

                </td>

            </tr>

        </table>

    </div>
</body>
</html>
