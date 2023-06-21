<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class KaryawanController extends Controller
{
    public function index(Request $request) {
        $karyawan = DB::table('karyawan')->orderBy('nama_lengkap')->paginate(10);

        return view('karyawan.index', compact('karyawan'));
    }

    public function store(Request $request) {
        $nik = $request->nik;
        $nama_lengkap = $request->nama_lengkap;
        $jabatan = $request->jabatan;
        $no_telp = $request->no_telp;
        $password = Hash::make('123');

        if($request->hasFile('foto')){
            $foto = $nik.".".$request->file('foto')->getClientOriginalExtension();
        }else{
            $foto = null;
        }

        try{
            $data = [
                'nik'=>$nik,
                'nama_lengkap'=>$nama_lengkap,
                'jabatan'=>$jabatan,
                'no_telp'=>$no_telp,
                'foto'=>$foto,
                'password'=>$password
            ];

            $simpan = DB::table('karyawan')->insert($data);

            if($simpan) {
                if($request->hasFile('foto')){
                    $folderPath = "public/uploads/karyawan/";
                    $request->file('foto')->storeAs($folderPath,$foto);
                }
                return Redirect::back()->with(['success'=>'Data Berhasil di Simpan']);
            }
        }catch(\Exception $e) {
            // dd($e);
            return Redirect::back()->with(['warning' => 'Data Gagal di Simpan']);
        }
    }

    public function edit(Request $request) {
        $nik = $request->nik;
        $karyawan = DB::table('karyawan')->where('nik',$nik)->first();

        return view('karyawan.edit', compact('karyawan'));
    }

    public function update($nik, Request $request) {
        $nik = $request->nik;
        $nama_lengkap = $request->nama_lengkap;
        $jabatan = $request->jabatan;
        $no_telp = $request->no_telp;
        $password = Hash::make('123');
        $old_foto = $request->old_foto;

        if($request->hasFile('foto')){
            $foto = $nik.".".$request->file('foto')->getClientOriginalExtension();
        }else{
            $foto = $old_foto;
        }

        try{
            $data = [
                'nama_lengkap'=>$nama_lengkap,
                'jabatan'=>$jabatan,
                'no_telp'=>$no_telp,
                'foto'=>$foto,
                'password'=>$password
            ];

            $update = DB::table('karyawan')->where('nik',$nik)->update($data);

            if($update) {
                if($request->hasFile('foto')){
                    $folderPath = "public/uploads/karyawan/";
                    $folderPathOld = "public/uploads/karyawan/".$old_foto;
                    Storage::delete($folderPathOld);
                    $request->file('foto')->storeAs($folderPath,$foto);
                }
                return Redirect::back()->with(['success'=>'Data Berhasil di Update']);
            }
        }catch(\Exception $e) {
            // dd($e);
            return Redirect::back()->with(['warning' => 'Data Gagal di Update']);
        }
    }

    public function delete($nik) {
        $delete = DB::table('karyawan')->where('nik',$nik)->delete();

        if($delete) {
            return Redirect::back()->with(['success'=>'Data Berhasil di Hapus']);
        }else{
            return Redirect::back()->with(['warning'=>'Data Gagal di Hapus']);
        }
    }
}
