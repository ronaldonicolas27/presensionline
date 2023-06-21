<?php

namespace App\Http\Controllers;

use App\Models\Pengajuanizin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class PresensiController extends Controller
{
    public function create() {
        $hariini = date("Y-m-d");
        $nik = Auth::guard('karyawan')->user()->nik;
        $cek = DB::table('presensi')->where('tgl_presensi',$hariini)->where('nik',$nik)->count();
        $lok_sekolah = DB::table('konfigurasi_lokasi')->where('id',2)->first();

        return view('presensi.create',compact('cek','lok_sekolah'));
    }

    //input data ke database
    public function store(Request $request) {
        $nik = Auth::guard('karyawan')->user()->nik;
        $tgl_presensi = date("Y-m-d");
        $jam = date("H:i:s");
        $lok_sekolah = DB::table('konfigurasi_lokasi')->where('id',2)->first();
        $lok = explode(",",$lok_sekolah->lokasi_sekolah);
        $latitudesekolah = $lok[0];
        $longitudesekolah = $lok[1];
        $lokasi = $request->lokasi;
        $lokasiuser = explode(",", $lokasi);
        $latitudeuser = $lokasiuser[0];
        $longitudeuser = $lokasiuser[1];

        $jarak = $this->distance($latitudesekolah,$longitudesekolah,$latitudeuser,$longitudeuser);
        $radius = round($jarak["meters"]);

        $cek = DB::table('presensi')->where('tgl_presensi',$tgl_presensi)->where('nik',$nik)->count();
        if($cek > 0){
            $ket = "out";
        }else{
            $ket = "in";
        }

        $image = $request->image;
        $folderPath = "public/uploads/absensi/";
        $formatName = $nik . "-" . $tgl_presensi . "-" . $ket;
        $image_parts = explode(";base64",$image);
        $image_base64 = base64_decode($image_parts[1]);
        $fileName = $formatName . ".png";
        $file = $folderPath . $fileName;        
        
        if($radius > $lok_sekolah->radius){
            echo "error|Anda Berada di Luar Radius, Jarak Anda ".$radius." Dengan Sekolah|radius";
        }else {
            if($cek > 0){
                $data_pulang = [
                    'jam_out' => $jam,
                    'foto_out' => $fileName,
                    'lokasi_out' => $lokasi
                ];
                $update = DB::table('presensi')->where('tgl_presensi',$tgl_presensi)->where('nik',$nik)->update($data_pulang);
                if($update) {
                    echo "success|Terimakasih dan Hati-Hati Di Jalan|out";
                    Storage::put($file , $image_base64);
                }else{
                    echo "error|Tidak Dapat Absen Pulang, Silahkan Hubungi Admin IT|out";
                }
            }else {
                $data = [
                    'nik' => $nik,
                    'tgl_presensi' => $tgl_presensi,
                    'jam_in' => $jam,
                    'foto_in' => $fileName,
                    'lokasi_in' => $lokasi
                ];
                $simpan = DB::table('presensi')->insert($data);
                if($simpan) {
                    echo "success|Terimakasih dan Selamat Bekerja|in";
                    Storage::put($file , $image_base64);
                }else{
                    echo "error|Tidak Dapat Absen Masuk, Silahkan Hubungi Admin IT|in";
                }
            }   
        }             
    }

    //menghitung jarak
    function distance($lat1, $lon1, $lat2, $lon2){
        $theta = $lon1 - $lon2;
        $miles = (sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + (cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta))));
        $miles = acos($miles);
        $miles = rad2deg($miles);
        $miles = $miles * 60 * 1.1515;
        $feet = $miles *5280;
        $yards = $feet / 3;
        $kilometers = $miles * 1.609344;
        $meters = $kilometers * 1000;
        return compact('meters');
    }

    public function editprofile() {
        $nik = Auth::guard('karyawan')->user()->nik;
        $karyawan = DB::table('karyawan')->where('nik',$nik)->first();
        return view('presensi.editprofile', compact('karyawan'));
    }

    public function updateprofile(Request $request) {
        $nik = Auth::guard('karyawan')->user()->nik;
        $nama_lengkap = $request->nama_lengkap;
        $no_telp = $request->no_telp;
        $password = Hash::make($request->password);
        $karyawan = DB::table('karyawan')->where('nik',$nik)->first();

        if($request->hasFile('foto')){
            $foto = $nik.".".$request->file('foto')->getClientOriginalExtension();
        }else{
            $foto = $karyawan->foto;
        }

        if(empty($request-> password)){
            $data = [
                'nama_lengkap' => $nama_lengkap,
                'no_telp' => $no_telp,
                'foto' => $foto
            ];
        }else{
            $data = [
                'nama_lengkap' => $nama_lengkap,
                'no_telp' => $no_telp,
                'password' => $password,
                'foto' => $foto
            ];
        }

        $update = DB::table('karyawan')->where('nik',$nik)->update($data);
        if($update) {
            if($request->hasFile('foto')){
                $folderPath = "public/uploads/karyawan/";
                $request->file('foto')->storeAs($folderPath,$foto);
            }
            return Redirect::back()->with(['success' => 'Data Berhasil di Update']);
        }else{
            return Redirect::back()->with(['error' => 'Data Gagal di Update']);
        }
    }

    public function histori() {
        $namabulan = ["","Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];

        return view('presensi.histori', compact('namabulan'));
    }
    
    public function gethistori(Request $request) {
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $nik = Auth::guard('karyawan')->user()->nik;

        $histori = DB::table('presensi')->whereRaw('MONTH(tgl_presensi)="'.$bulan.'"')
        ->whereRaw('YEAR(tgl_presensi)="'.$tahun.'"')->where('nik',$nik)->orderBy('tgl_presensi')->get();
        
        return view('presensi.gethistori', compact('histori'));
    }

    public function izin() {
        $nik = Auth::guard('karyawan')->user()->nik;
        $dataizin = DB::table('pengajuan_izin')->where('nik',$nik)->get();
        return view('presensi.izin', compact('dataizin'));
    }

    public function buatizin() {
        return view('presensi.buatizin');
    }

    public function storeizin(Request $request) {
        $nik = Auth::guard('karyawan')->user()->nik;
        $tgl_izin = $request->tgl_izin;
        $status = $request->status;
        $keterangan = $request->keterangan;

        $data = [
            'nik' => $nik,
            'tgl_izin' => $tgl_izin,
            'status' => $status,
            'keterangan' => $keterangan
        ];

        $simpan = DB::table('pengajuan_izin')->insert($data);
        if($simpan) {
            return Redirect('/presensi/izin')->with(['success'=>'Data Berhasil di Simpan']);
        }else{
            return Redirect('/presensi/izin')->with(['error'=>'Data Gagal di Simpan']);
        }

    }

    public function cekpengajuanizin(Request $request) {
        $tgl_izin = $request->tgl_izin;
        $nik = Auth::guard('karyawan')->user()->nik;
        $cek = DB::table('pengajuan_izin')->where('nik',$nik)->where('tgl_izin',$tgl_izin)->count();
        
        return $cek;
    }

    //monitoring presensi
    public function monitoring() {
        return view('presensi.monitoring');
    }

    public function getpresensi(Request $request) {
        $tanggal = $request->tanggal;
        $presensi = DB::table('presensi')->select('presensi.*','nama_lengkap','jabatan')
        ->join('karyawan','presensi.nik','=','karyawan.nik')->where('tgl_presensi',$tanggal)->get();

        return view('presensi.getpresensi', compact('presensi'));
    }

    public function tampilpeta(Request $request) {
        $id = $request->id;
        $presensi = DB::table('presensi')->where('id',$id)->join('karyawan','presensi.nik','=','karyawan.nik')->first();

        return view('presensi.showmap', compact('presensi'));

    }

    public function laporan() {
        $namabulan = ["","Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
        $karyawan = DB::table('karyawan')->orderBy('nama_lengkap')->get();

        return view('presensi.laporan', compact('namabulan','karyawan'));
    }

    public function cetaklaporan(Request $request) {
        $nik = $request->nik;
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $namabulan = ["","Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
        $karyawan = DB::table('karyawan')->where('nik',$nik)->first();
        $presensi = DB::table('presensi')->where('nik',$nik)->whereRaw('MONTH(tgl_presensi)="'.$bulan.'"')
        ->whereRaw('YEAR(tgl_presensi)="'.$tahun.'"')->orderBy('tgl_presensi')->get();

        return view('presensi.cetaklaporan', compact('bulan','tahun','namabulan','karyawan','presensi'));
    }

    public function rekap() {
        $namabulan = ["","Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
        

        return view('presensi.rekap', compact('namabulan'));
    }

    public function cetakrekap(Request $request) {
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $namabulan = ["","Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
        $rekap = DB::table('presensi')->selectRaw('presensi.nik,nama_lengkap,
        MAX(IF(day(tgl_presensi) = 1,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_1,
        MAX(IF(day(tgl_presensi) = 2,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_2,
        MAX(IF(day(tgl_presensi) = 3,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_3,
        MAX(IF(day(tgl_presensi) = 4,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_4,
        MAX(IF(day(tgl_presensi) = 5,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_5,
        MAX(IF(day(tgl_presensi) = 6,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_6,
        MAX(IF(day(tgl_presensi) = 7,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_7,
        MAX(IF(day(tgl_presensi) = 8,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_8,
        MAX(IF(day(tgl_presensi) = 9,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_9,
        MAX(IF(day(tgl_presensi) = 10,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_10,
        MAX(IF(day(tgl_presensi) = 11,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_11,
        MAX(IF(day(tgl_presensi) = 12,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_12,
        MAX(IF(day(tgl_presensi) = 13,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_13,
        MAX(IF(day(tgl_presensi) = 14,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_14,
        MAX(IF(day(tgl_presensi) = 15,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_15,
        MAX(IF(day(tgl_presensi) = 16,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_16,
        MAX(IF(day(tgl_presensi) = 17,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_17,
        MAX(IF(day(tgl_presensi) = 18,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_18,
        MAX(IF(day(tgl_presensi) = 19,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_19,
        MAX(IF(day(tgl_presensi) = 20,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_20,
        MAX(IF(day(tgl_presensi) = 21,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_21,
        MAX(IF(day(tgl_presensi) = 22,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_22,
        MAX(IF(day(tgl_presensi) = 23,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_23,
        MAX(IF(day(tgl_presensi) = 24,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_24,
        MAX(IF(day(tgl_presensi) = 25,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_25,
        MAX(IF(day(tgl_presensi) = 26,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_26,
        MAX(IF(day(tgl_presensi) = 27,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_27,
        MAX(IF(day(tgl_presensi) = 28,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_28,
        MAX(IF(day(tgl_presensi) = 29,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_29,
        MAX(IF(day(tgl_presensi) = 30,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_30,
        MAX(IF(day(tgl_presensi) = 31,concat(jam_in,"-",IFnull(jam_out,"00:00:00")),"")) AS tgl_31')
        ->join('karyawan','presensi.nik','=','karyawan.nik')->whereRaw('MONTH(tgl_presensi)="'.$bulan.'"')
        ->whereRaw('YEAR(tgl_presensi)="'.$tahun.'"')->groupByRaw('presensi.nik,nama_lengkap')->get();

        return view('presensi.cetakrekap', compact('bulan','tahun','namabulan','rekap'));
    }

    public function izinsakit(Request $request) {
        $query = Pengajuanizin::query();
        $query->select('id','tgl_izin','pengajuan_izin.nik','nama_lengkap','jabatan','status','status_approved','keterangan');
        $query->join('karyawan','pengajuan_izin.nik','=','karyawan.nik');
        if(!empty($request->dari) && !empty($request->sampai)) {
            $query->whereBetween('tgl_izin',[$request->dari,$request->sampai]);
        }

        if(!empty($request->nik)) {
            $query->where('pengajuan_izin.nik',$request->nik);
        }

        if(!empty($request->nama_lengkap)) {
            $query->where('nama_lengkap','like','%'.$request->nama_lengkap.'%');
        }

        if(!empty($request->status_approved)) {
            $query->where('status_approved',$request->status_approved);
        }
        $query->orderBy('tgl_izin','desc');
        $izinsakit = $query->paginate(10);
        $izinsakit->appends($request->all());

        return view('presensi.izinsakit', compact('izinsakit'));
    }

    public function approveizinsakit(Request $request) {
        $status_approved = $request->status_approved;
        $izinsakit_form = $request->izinsakit_form;
        $update = DB::table('pengajuan_izin')->where('id',$izinsakit_form)->update(['status_approved' => $status_approved]);
        if($update) {
            return Redirect::back()->with(['success' => 'Data Berhasil di Update']);
        }else{
            return Redirect::back()->with(['warning' => 'Data Gagal di Update']);
        }
    }

    public function batalkanizinsakit($id) {
        $update = DB::table('pengajuan_izin')->where('id',$id)->update(['status_approved' => 'p']);
        if($update) {
            return Redirect::back()->with(['success' => 'Data Berhasil di Update']);
        }else{
            return Redirect::back()->with(['warning' => 'Data Gagal di Update']);
        }
    }
}