<?php

namespace App\Http\Controllers;

use App\Models\TransaksiDetail;
use Illuminate\Http\Request;

use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;

class TransaksiDetailController extends Controller
{
    public function index()
    {
        $transaksidetail = TransaksiDetail::with('transaksi')->orderBy('id','DESC')->get();

        return view('transaksidetail.index', compact('transaksidetail') );
    }

    public function detail(Request $request)
    {
        $transaksi = Transaksi::with('transaksidetail')->findOrFail($request->id_transaksi);

        return view('transaksidetail.detail', compact('transaksi'));
    }

    public function edit($id)
    {
        $transaksidetail = TransaksiDetail::findOrFail($id);
        return view('transaksidetail.edit', compact('transaksidetail'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_produk' => 'required|string',
            'harga_satuan' => 'required|numeric',
            'jumlah' => 'required|numeric',
        ]);
    
        try {
            $transaksidetail = TransaksiDetail::findOrFail($id);
            $transaksidetail->nama_produk = $request->input('nama_produk');
            $transaksidetail->harga_satuan = $request->input('harga_satuan');
            $transaksidetail->jumlah = $request->input('jumlah');
            $transaksidetail->subtotal = $transaksidetail->harga_satuan * $transaksidetail->jumlah;
            $transaksidetail->save();
    
            // Update total harga pada transaksi terkait
            $transaksi = Transaksi::findOrFail($transaksidetail->id_transaksi);
            $transaksi->total_harga = TransaksiDetail::where('id_transaksi', $transaksi->id)
                                                     ->sum('subtotal'); // Sum subtotal dari transaksi detail
            $transaksi->kembalian = $transaksi->bayar - $transaksi->total_harga;
            $transaksi->save();
    
            return redirect()->route('transaksidetail.index', $transaksi->id)
                             ->with('pesan', 'Berhasil mengubah data');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors(['Transaction' => 'Gagal mengubah data'])->withInput();
        }
    }

    public function destroy($id)
    {
        $transaksidetail = TransaksiDetail::findOrFail($id);
        $transaksi = Transaksi::findOrFail($transaksidetail->id_transaksi);
        $transaksidetail->delete();
    
        $transaksi->total_harga = TransaksiDetail::where('id_transaksi', $transaksi->id)
                                                 ->sum('subtotal'); // Sum subtotal dari transaksi detail
        $transaksi->kembalian = $transaksi->bayar - $transaksi->total_harga;
        $transaksi->save();
    
        return redirect()->route('transaksidetail.index', $transaksi->id)
                         ->with('pesan', 'Berhasil menghapus data');
    }
}
