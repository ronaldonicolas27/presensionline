<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>A4</title>

    <!-- Normalize or reset CSS with your favorite library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/7.0.0/normalize.min.css">

    <!-- Load paper.css for happy printing -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/paper-css/0.4.1/paper.css">

    <!-- Set page size here: A5, A4 or A3 -->
    <!-- Set also "landscape" if you need -->
    <style>
        @page {
            size: A4
        }

        #title {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 18px;
            font-weight: bold;
        }

        .tabelkaryawan {
            margin-top: 40px;
        }

        .tabelkaryawan tr td {
            padding: 5px;
        }

        .tabelpresensi {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .tabelpresensi tr th {
            border: 1px solid #000000;
            padding: 8px;
            background-color: #909090;
            font-size: 10px;
        }

        .tabelpresensi tr td {
            border: 1px solid #000000;
            padding: 5px;
            font-size: 12px;
            /* background-color: #909090; */
        }

        .foto {
            width: 40px;
            height: 30px;
        }
    </style>
</head>

<!-- Set "A5", "A4" or "A3" for class name -->
<!-- Set also "landscape" if you need -->

<body class="A4 landscape">
    {{-- @php
        function selisih($jam_masuk, $jam_keluar)
        {
            [$h, $m, $s] = explode(':', $jam_masuk);
            $dtAwal = mktime($h, $m, $s, '1', '1', '1');
            [$h, $m, $s] = explode(':', $jam_keluar);
            $dtAkhir = mktime($h, $m, $s, '1', '1', '1');
            $dtSelisih = $dtAkhir - $dtAwal;
            $totalmenit = $dtSelisih / 60;
            $jam = explode('.', $totalmenit / 60);
            $sisamenit = $totalmenit / 60 - $jam[0];
            $sisamenit2 = $sisamenit * 60;
            $jml_jam = $jam[0];
            return $jml_jam . ':' . round($sisamenit2);
        }
    @endphp --}}


    <!-- Each sheet element should have the class "sheet" -->
    <!-- "padding-**mm" is optional: you can set 10, 15, 20 or 25 -->
    <section class="sheet padding-10mm">

        <!-- Write HTML just like a web page -->
        <table style="width: 100%">
            <tr>
                <td style="width: 30px">
                    <img src="{{ asset('assets/img/logo_sekolah.png') }}" width="80" height="80" alt="">
                </td>
                <td>
                    <span id="title">
                        REKAP ABSENSI <br>
                        PERIODE {{ strtoupper($namabulan[$bulan]) }} / {{ $tahun }} <br>
                        SEKOLAH DASAR ISLAM TERPADU CENDEKIA SATRIA <br>
                    </span>
                    <span><i>Taman Edelweis RT 01/013, Satriajaya, Kec. Tambun Utara, Kab. Bekasi Prov. Jawa
                            Barat</i></span>
                </td>
            </tr>
        </table>
        <table class="tabelpresensi">
            <tr>
                <th rowspan="2">NIK</th>
                <th rowspan="2">Nama Karyawan</th>
                <th colspan="31">Tanggal</th>
                <th rowspan="2">Total Hadir</th>
                <th rowspan="2">Total Telat</th>
            </tr>
            <tr>
                <?php
                    for ($i=1; $i <= 31; $i++) { 
                        ?>
                <th>{{ $i }}</th>
                <?php
                    }
                ?>
            </tr>
            <tr>
                @foreach ($rekap as $d)
            <tr>
                <td>{{ $d->nik }}</td>
                <td>{{ $d->nama_lengkap }}</td>
                <?php
                $totalhadir = 0;
                $totaltelat = 0;
                        for ($i=1; $i <= 31; $i++) { 
                            $tgl = "tgl_".$i;
                            if (empty($d->$tgl)) {
                                $hadir = ['',''];
                                $totalhadir += 0;
                            }else{
                                $hadir = explode("-",$d->$tgl);
                                $totalhadir += 1;
                                if ($hadir[0] > "07:00:00") {
                                    $totaltelat += 1;
                                }
                            }
                    ?>
                <td>
                    <span style="color: {{ $hadir > '07:00:00' ? 'red' : '' }}">{{ $hadir[0] }}</span> <br>
                    <span style="color: {{ $hadir < '16:00:00' ? 'red' : '' }}">{{ $hadir[1] }}</span>
                </td>
                <?php
                        }
                        ?>
                <td>{{ $totalhadir }}</td>
                <td>{{ $totaltelat }}</td>
            </tr>
            @endforeach
        </table>
        <table width="100%" style="margin-top: 100px">
            <tr>
                <td></td>
                <td style="text-align: center">Bekasi, {{ date('d-m-Y') }}</td>
            </tr>
            <tr>
                <td style="text-align: center; vertical-align:bottom" height="100px">
                    <u>Ema Resmiati</u> <br>
                    <i><b>Kepala Sekolah</b></i>
                </td>
                <td style="text-align: center; vertical-align:bottom" height="100px">
                    <u>ibu Anjar</u> <br>
                    <i><b>Kepala Tata Usaha</b></i>
                </td>
            </tr>
        </table>

    </section>

</body>

</html>
