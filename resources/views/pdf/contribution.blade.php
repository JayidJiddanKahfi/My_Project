<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Kas RT</title>
    <style>
        body { 
            font-family: sans-serif; 
            text-align: center;
        }

        h2.judul {
            text-align: center;
            font-size: 14px; 
        }

        table.data {
            margin: 50px auto; 
            width: fit-content;
            border-collapse: collapse;
            font-size: 11px; 
        }

        table.data th, table.data td { 
            padding: 6px; 
            border: 1px solid #000; 
            text-align: center; 
        }

        table.footer_container {
            width: 100%; 
            margin-top: 40px; 
            font-size: 12px;
        }

        table.footer_container td.notes_column {
            width: 20%;
            vertical-align: top;
        }

        table.footer_container td.null_column {
            width: 60%;
        }

        table.footer_container td.signature_column{
            width: 20%;
        }

        table.footer_container td.notes_column ul{
            list-style: none;
        }

         table.footer_container td.notes_column ul li{
            margin-bottom: 5px;
         }

        .merah_muda{
            display: inline-block;
            width: 12px;
            height: 12px;
            background-color: lightcoral;
        }

        .hijau_muda{
            display: inline-block;
            width: 12px;
            height: 12px;
            background-color: lightgreen;
        }
        
    </style>
</head>
<body>
    <h2 class="judul">Laporan Rekapitulasi Iuran Warga RT 02 / RW 24 Pura Bojong Gede Tahun {{ $targetYear }}</h2>
    <table class="data">
        <thead>
            <tr>
                <th>no</th>
                <th>Nama</th>
                <th>Alamat</th>
                <th>Januari</th>
                <th>Februari</th>
                <th>Maret</th>
                <th>April</th>
                <th>Mei</th>
                <th>Juni</th>
                <th>Juli</th>
                <th>Agustus</th>
                <th>September</th>
                <th>Oktober</th>
                <th>November</th>
                <th>Desember</th>
                <th>THR</th>
                <th>Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($residents_with_payment as $index => $resident_with_payment)
                <tr>
                    <td>{{$index + 1}}</td>
                    <td style="text-align: left">{{ "$resident_with_payment->salutation $resident_with_payment->full_name" }}</td>
                    <td style="text-align: left">{{$resident_with_payment->address}}</td>
                    <?php
                        $total_contribution = 0;

                        foreach ($resident_with_payment->payments as $payment){

                        if($payment["contribution"] === 0){

                            $total_contribution = $total_contribution + 0;

                    ?>
                    
                            <td style="background-color: lightcoral">Rp - </td>

                    <?php
                        }
                        else{

                            $total_contribution = $total_contribution + $payment["contribution"];
                    ?>
                            <td style="background-color: lightgreen">Rp {{$payment["contribution"]}}</td>
                    <?php
                        } 
                        } 
                    ?>

                    <?php if ($total_contribution === 0){?>

                        <td style="text-align: left">Rp -</td>

                    <?php } else{?>

                        <td style="text-align: left">Rp {{ $total_contribution}}</td>

                    <?Php }?>
                </tr>
            @endforeach
        </tbody>
    </table>

    
    <table class="footer_container">
        <tr>
            <td class="notes_column">

                <ul style="list-style: none">
                    <h4>Keterangan warna</h4>
                    <li>
                        <div class="merah_muda"></div> <span> : belum dibayar</span>
                    </li>
                     <li>
                        <div class="hijau_muda"></div> <span> : Sudah dibayar</span>
                    </li>
                </ul>
            
            </td>

            <td class="null_column">
            
            </td>
            
            <td class="signature_column">

                <p>Bogor, {{ $today }}</p>

                <p>Bendahara RT 02 / RW 24</p>

                <img src="{{ public_path('storage/img/ttd_mahfuddin_zuhri.jpg') }}" width="60" height="60">
                
                <p>Mahfuddin Zuhri</p>

            </td>

        </tr>

    </table>

</body>
</html>
