<?php

namespace App\Http\Controllers;

use App\Exports\TamuExport;
use Illuminate\Http\Request;
use App\Models\Area;
use App\Models\Tamu;
use App\Models\Gedung;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Auth;
use DB;

class TamuController extends Controller
{
    public function index()
    {
        //
    }

    public function confirm(Request $request, $gedung, $lobi, $id)
    {
        if (!$request->no_visitor) {
            $tamu = Tamu::where('id_tamu', $id)->first();
            return view('tamu.confirm', compact('gedung', 'id', 'lobi', 'tamu'));
        } else {
            Tamu::where('id_tamu', $id)->update([
                'nomor_visitor' => $request->no_visitor
            ]);
            return redirect()->route('tamu.create', ['gedung' => $gedung, 'lobi' => $lobi])->with('success', 'Selamat Datang!');
        }
    }

    public function create($id, $lobi)
    {
        $gedung = $id == 'adhyatma' ? 1 : 2;
        $area   = Area::where('gedung_id', $gedung)->get();

        return view('tamu.create', compact('area', 'id', 'gedung', 'lobi'));
    }

    public function store(Request $request, $id)
    {
        $total   = str_pad(Tamu::withTrashed()->count() + 1, 4, 0, STR_PAD_LEFT);
        $id_tamu = Carbon::now()->format('ymdHis') . $total;
        $lobi    = $request->lokasi_datang;

        $tambah = new Tamu();
        $tambah->id_tamu = $id_tamu;
        $tambah->area_id = $request->area_id;
        $tambah->lokasi_datang  = $lobi;
        $tambah->jam_masuk      = Carbon::now();
        $tambah->nama_tamu      = $request->nama;
        $tambah->nik_nip        = $request->nik_nip;
        $tambah->alamat_tamu    = $request->alamat;
        $tambah->no_telpon      = $request->no_telp;
        $tambah->nama_instansi  = $request->instansi;
        $tambah->nama_tujuan    = $request->nama_tujuan;
        $tambah->keperluan      = $request->keperluan;
        $tambah->created_at     = Carbon::now();
        $tambah->save();

        return redirect()->route('tamu.confirm', ['gedung' => $id, 'lobi' => $lobi, 'id' => $id_tamu])->with('success', 'Berhasil Mengisi Form');
    }

    public function show()
    {
        $tanggal  = '';
        $bulan    = Carbon::now()->format('m');
        $tahun    = Carbon::now()->format('Y');
        $dataArea = [];
        $gedung   = '';
        $area     = '';
        $query    = Tamu::orderBy('id_tamu', 'DESC')->where(DB::raw("DATE_FORMAT(jam_masuk, '%m')"), $bulan)->where(DB::raw("DATE_FORMAT(jam_masuk, '%Y')"), $tahun);

        if (Auth::user()->id == 3) {
            $tamu = $query->where('lokasi_datang', 'lobi')->get();
        } elseif (Auth::user()->id == 4) {
            $tamu = $query->where('lokasi_datang', 'lobi-a')->get();
        }  elseif (Auth::user()->id == 5) {
            $tamu = $query->where('lokasi_datang', 'lobi-c')->get();
        } else {
            $tamu = $query->get();
        }

        return view('dashboard.pages.tamu.show', compact('tanggal', 'bulan', 'tahun', 'tamu', 'gedung', 'area', 'dataArea'));
    }

    public function filter(Request $request)
    {
        $dataArea = [];
        $tanggal  = $request->get('tanggal');
        $bulan    = $request->get('bulan');
        $tahun    = $request->get('tahun');
        $gedung   = $request->get('gedung');
        $area     = $request->get('area');
        $data     = Tamu::orderBy('id_tamu', 'DESC')->join('t_gedung_area', 'id_area', 'area_id');
        $cekArea  = Area::where('id_area', $area)->where('gedung_id', $gedung)->first();

        if ($tanggal || $bulan || $tahun || $gedung || $cekArea) {
            if ($tanggal) {
                $res  = $data->where(DB::raw("DATE_FORMAT(jam_masuk, '%d')"), $tanggal);
            }

            if ($bulan) {
                $res  = $data->where(DB::raw("DATE_FORMAT(jam_masuk, '%m')"), $bulan);
            }

            if ($tahun) {
                $res  = $data->where(DB::raw("DATE_FORMAT(jam_masuk, '%Y')"), $tahun);
            }

            if ($gedung) {
                $res  = $data->where('gedung_id', $gedung);
                $dataArea = Area::where('gedung_id', $gedung)->get();
            }

            if ($cekArea) {
                $res = $data->where('area_id', $area);
            } else {
                $area = '';
            }
        } else {
            $res    = $data;
        }

        $tamu = $res->get();

        if ($request->downloadFile == 'pdf') {
            return view('dashboard.pages.tamu.pdf', compact('tamu'));
        } elseif ($request->downloadFile == 'excel') {
            return Excel::download(new TamuExport($request->all()), 'tamu.xlsx');
        }

        return view('dashboard.pages.tamu.show', compact('tanggal', 'bulan', 'tahun', 'gedung', 'area', 'dataArea', 'tamu'));
    }

    public function edit($id)
    {
        $gedung = Gedung::get();
        $tamu   = Tamu::where('id_tamu', $id)->first();
        return view('dashboard.pages.tamu.edit', compact('id', 'gedung', 'tamu'));
    }

    public function update(Request $request, $id)
    {
        $jamKeluar = !$request->jam_keluar ? null : Carbon::parse($request->jam_keluar)->format('Y-m-d H:i:s');
        Tamu::where('id_tamu', $id)->update([
            'area_id'        => $request->area_id,
            'jam_masuk'      => $request->jam_masuk,
            'jam_keluar'     => $jamKeluar,
            'nama_tamu'      => $request->nama_tamu,
            'nik_nip'        => $request->nik_nip,
            'alamat_tamu'    => $request->alamat,
            'no_telpon'      => $request->no_telepon,
            'nama_instansi'  => $request->instansi,
            'nama_tujuan'    => $request->nama_tujuan,
            'keperluan'      => $request->keperluan,
            'nomor_visitor'  => $request->nomor_visitor
        ]);

        return back()->with('success', 'Berhasil Menyimpan Perubahan');
    }

    public function createByAdmin()
    {
        $lobi = '';
        $dataGedung = Gedung::orderBy('nama_gedung', 'ASC');
        $dataArea   = Area::orderBy('id_area', 'ASC');

        if (Auth::user()->id == 3) {
            $lobi   = 'lobi';
            $gedung = $dataGedung->where('id_gedung', 2)->get();
            $area   = $dataArea->where('gedung_id', 2)->get();
        } elseif (Auth::user()->id == 4) {
            $lobi   = 'lobi-a';
            $gedung = $dataGedung->where('id_gedung', 1)->get();
            $area   = $dataArea->where('gedung_id', 1)->get();
        }  elseif (Auth::user()->id == 5) {
            $lobi   = 'lobi-c';
            $gedung = $dataGedung->where('id_gedung', 1)->get();
            $area   = $dataArea->where('id_gedung', 1)->get();
        }

        return view('dashboard.pages.tamu.create', compact('gedung', 'area', 'lobi'));
    }

    public function storeByAdmin(Request $request)
    {
        $total   = str_pad(Tamu::withTrashed()->count() + 1, 4, 0, STR_PAD_LEFT);
        $id_tamu = Carbon::now()->format('ymdHis') . $total;
        $jamKeluar = !$request->jam_keluar ? null : Carbon::parse($request->jam_keluar)->format('Y-m-d H:i:s');

        $tambah = new Tamu();
        $tambah->id_tamu = $id_tamu;
        $tambah->area_id = $request->area_id;
        $tambah->lokasi_datang  = $request->lobi;
        $tambah->jam_masuk      = Carbon::parse($request->jam_masuk)->format('Y-m-d H:i:s');
        $tambah->jam_keluar     = $jamKeluar;
        $tambah->nama_tamu      = $request->nama_tamu;
        $tambah->nik_nip        = $request->nik_nip;
        $tambah->alamat_tamu    = $request->alamat;
        $tambah->no_telpon      = $request->no_telepon;
        $tambah->nama_instansi  = $request->instansi;
        $tambah->nama_tujuan    = $request->nama_tujuan;
        $tambah->keperluan      = $request->keperluan;
        $tambah->nomor_visitor  = $request->nomor_visitor;
        $tambah->created_at     = Carbon::now();
        $tambah->save();

        return redirect()->route('dashboard')->with('success', 'Berhasil Menambah Tamu');
    }

    public function destroy($id)
    {
        Tamu::where('id_tamu', $id)->delete();
        return back()->with('success', 'Berhasil Menghapus');
    }

    public function leave($id)
    {
        Tamu::where('id_tamu', $id)->update([
            'jam_keluar' => Carbon::now()
        ]);

        return back()->with('success', 'Tamu Sudah Keluar');
    }

    public function grafik(Request $request, $id, $bulan, $tahun)
    {
        if ($id == 'bulan') {
            $dataTahun = $tahun ? $tahun : Carbon::now()->format('Y');
            $result = Tamu::select(DB::raw("(DATE_FORMAT(jam_masuk, '%M %Y')) as month"), DB::raw("count(id_tamu) as total_tamu "))
                ->groupBy('month')
                ->where(DB::raw("DATE_FORMAT(jam_masuk, '%Y')"), $dataTahun)
                ->get();
        } else if ($id == 'hari') {
            $dataBulan = $bulan ? $bulan : Carbon::now()->format('m');
            $dataTahun = $tahun ? $tahun : Carbon::now()->format('Y');
            $result = Tamu::select(DB::raw("(DATE_FORMAT(jam_masuk, '%d/%m/%y')) as month"), DB::raw("count(id_tamu) as total_tamu "))
                ->groupBy('month')
                ->where(DB::raw("DATE_FORMAT(jam_masuk, '%m')"), $dataBulan)
                ->where(DB::raw("DATE_FORMAT(jam_masuk, '%Y')"), $tahun)
                ->get();
        }

        return response()->json($result);
    }
}
