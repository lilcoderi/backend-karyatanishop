<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\KeranjangOffline;
use App\Models\ItemKeranjangOffline;
use App\Models\NotaBelanja;
use App\Models\ItemNotaBelanja;
use App\Models\Produk;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Validation\ValidationException;
class KeranjangOfflineController extends Controller
{

    /**
 * @OA\Get(
 *     path="/api/keranjang-offline",
 *     summary="Ambil data keranjang offline pengguna yang sedang login",
 *     description="Mengambil data keranjang offline termasuk item dan detail produk berdasarkan user yang sedang login.",
 *     tags={"Keranjang Offline"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Data keranjang offline berhasil ditemukan",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Data keranjang offline berhasil ditemukan"),
 *             @OA\Property(
 *                 property="keranjang_offline",
 *                 type="object",
 *                 @OA\Property(property="keranjang_offline_id", type="string", format="uuid", example="13a2231c-5190-4f4d-bff2-9ee9cdcd6a32"),
 *                 @OA\Property(property="user_id", type="string", format="uuid", example="2ee7ef07-430c-4049-9b07-eb29550f1cb1"),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-28T08:12:09.000000Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-28T08:12:09.000000Z"),
 *                 @OA\Property(
 *                     property="items",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="item_keranjang_offline_id", type="string", format="uuid", example="dd0681f8-c767-49e1-8df7-d24bf3be338d"),
 *                         @OA\Property(property="keranjang_offline_id", type="string", format="uuid", example="13a2231c-5190-4f4d-bff2-9ee9cdcd6a32"),
 *                         @OA\Property(property="produk_id", type="string", format="uuid", example="2263d2bb-1834-4649-bf76-699eae6033f2"),
 *                         @OA\Property(property="kuantitas", type="integer", example=3),
 *                         @OA\Property(property="harga_satuan", type="string", example="10000.00"),
 *                         @OA\Property(property="subtotal", type="string", example="30000.00"),
 *                         @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-29T06:50:49.000000Z"),
 *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-29T06:50:49.000000Z"),
 *                         @OA\Property(
 *                             property="produk",
 *                             type="object",
 *                             @OA\Property(property="produk_id", type="string", format="uuid", example="2263d2bb-1834-4649-bf76-699eae6033f2"),
 *                             @OA\Property(property="kategori_id", type="string", format="uuid", example="b5b8abb7-8ab7-4407-96d6-2ca680d8b3ae"),
 *                             @OA\Property(property="nama_produk", type="string", example="Pupuk Gold"),
 *                             @OA\Property(property="merk", type="string", example="Gopupuk"),
 *                             @OA\Property(property="deskripsi_produk", type="string", example="Pupuk bagus"),
 *                             @OA\Property(property="berat", type="string", example="10.00"),
 *                             @OA\Property(property="harga_satuan", type="string", example="10000.00"),
 *                             @OA\Property(property="stok", type="integer", example=9),
 *                             @OA\Property(property="status_produk", type="string", example="aktif"),
 *                             @OA\Property(property="gambar", type="string", example="storage/produk/oGxYL72YiUgZjRwVBQruQqIn8p2JaN60A64ZiqLY.png"),
 *                             @OA\Property(property="promo_id", type="string", nullable=true, example=null),
 *                             @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-27T09:26:27.000000Z"),
 *                             @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-27T09:26:27.000000Z"),
 *                             @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *                             @OA\Property(property="after_diskon", type="string", example="10000.00")
 *                         )
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Keranjang offline tidak ditemukan",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Keranjang offline tidak ditemukan")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Token tidak valid atau tidak ditemukan",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Unauthorized")
 *         )
 *     )
 * )
 */
    public function getAllKeranjangOffline(Request $request)
{
    // Ambil user yang sedang login menggunakan JWT
    $user = JWTAuth::parseToken()->authenticate();

    // Ambil data keranjang offline berdasarkan user_id
    $keranjangOffline = KeranjangOffline::with('items.produk') // Mengambil keranjang dan item beserta produk terkait
        ->where('user_id', $user->user_id) // Filter berdasarkan user_id
        ->first();

    // Jika keranjang offline tidak ditemukan
    if (!$keranjangOffline) {
        return response()->json(['message' => 'Keranjang offline tidak ditemukan'], 404);
    }

    // Return response dengan data keranjang offline beserta item
    return response()->json([
        'message' => 'Data keranjang offline berhasil ditemukan',
        'keranjang_offline' => $keranjangOffline
    ], 200);
}

public function countKeranjangOfflineItems(Request $request)
{
    // Ambil user yang sedang login menggunakan JWT
    $user = JWTAuth::parseToken()->authenticate();

    // Ambil data keranjang offline berdasarkan user_id
    $keranjangOffline = KeranjangOffline::with('items') // Hanya mengambil items
        ->where('user_id', $user->user_id) // Filter berdasarkan user_id
        ->first();

    // Jika keranjang offline tidak ditemukan
    if (!$keranjangOffline) {
        return response()->json(['message' => 'Keranjang offline tidak ditemukan'], 404);
    }

    // Hitung jumlah item_keranjang_offline_id
    $totalItems = $keranjangOffline->items->count();

    // Return response dengan jumlah total item
    return response()->json([
        'message' => 'Total item keranjang offline berhasil dihitung',
        'total_items' => $totalItems,
    ], 200);
}

/**
 * @OA\Post(
 *     path="/api/keranjang-offline/add",
 *     summary="Tambahkan produk ke keranjang offline",
 *     description="Menambahkan item ke keranjang offline berdasarkan user yang sedang login.",
 *     tags={"Keranjang Offline"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             required={"produk_id", "kuantitas"},
 *             @OA\Property(property="produk_id", type="string", format="uuid", example="2263d2bb-1834-4649-bf76-699eae6033f2", description="ID produk yang akan ditambahkan ke keranjang"),
 *             @OA\Property(property="kuantitas", type="integer", example=3, description="Jumlah produk yang ditambahkan")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Produk berhasil ditambahkan ke keranjang",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Produk berhasil ditambahkan ke keranjang"),
 *             @OA\Property(
 *                 property="item",
 *                 type="object",
 *                 @OA\Property(property="item_keranjang_offline_id", type="string", format="uuid", example="dd0681f8-c767-49e1-8df7-d24bf3be338d"),
 *                 @OA\Property(property="keranjang_offline_id", type="string", format="uuid", example="13a2231c-5190-4f4d-bff2-9ee9cdcd6a32"),
 *                 @OA\Property(property="produk_id", type="string", format="uuid", example="2263d2bb-1834-4649-bf76-699eae6033f2"),
 *                 @OA\Property(property="kuantitas", type="integer", example=3),
 *                 @OA\Property(property="harga_satuan", type="string", example="10000.00"),
 *                 @OA\Property(property="subtotal", type="string", example="30000.00"),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-29T06:50:49.000000Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-29T06:50:49.000000Z")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Validasi gagal",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="object", additionalProperties={"type": "string"}, example={"produk_id": {"Produk ID tidak ditemukan"}, "kuantitas": {"Kuantitas harus minimal 1"}})
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Token tidak valid atau tidak ditemukan",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Unauthorized")
 *         )
 *     )
 * )
 */
public function addToKeranjangOffline(Request $request)
{
    $user = JWTAuth::parseToken()->authenticate(); // Dapatkan user dari JWT token
    $validator = Validator::make($request->all(), [
        'produk_id' => 'required|exists:produk,produk_id',
        'kuantitas' => 'required|integer|min:1',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    // Cek apakah keranjang offline sudah ada untuk user
    $keranjangOffline = KeranjangOffline::firstOrCreate(
        ['user_id' => $user->user_id]
    );

    // Dapatkan produk yang dipilih
    $produk = Produk::findOrFail($request->produk_id);

    // Pastikan harga setelah diskon ada (misalnya, harga setelah diskon tidak null)
    $hargaSatuan = $produk->after_diskon ?? $produk->harga;

    // Cari item di keranjang offline yang sudah ada untuk produk yang sama
    $itemKeranjangOffline = ItemKeranjangOffline::where('keranjang_offline_id', $keranjangOffline->keranjang_offline_id)
        ->where('produk_id', $produk->produk_id)
        ->first();

    if ($itemKeranjangOffline) {
        // Jika produk sudah ada, tambahkan kuantitasnya
        $itemKeranjangOffline->kuantitas += $request->kuantitas;
        $itemKeranjangOffline->subtotal = $itemKeranjangOffline->kuantitas * $hargaSatuan;
        $itemKeranjangOffline->save();

        Log::info('Kuantitas produk di keranjang berhasil diperbarui', ['item' => $itemKeranjangOffline]);

        return response()->json(['message' => 'Kuantitas produk berhasil diperbarui di keranjang', 'item' => $itemKeranjangOffline], 200);
    } else {
        // Jika produk belum ada, tambahkan sebagai item baru
        $subtotal = $hargaSatuan * $request->kuantitas;

        $itemKeranjangOffline = ItemKeranjangOffline::create([
            'keranjang_offline_id' => $keranjangOffline->keranjang_offline_id,
            'produk_id' => $produk->produk_id,
            'kuantitas' => $request->kuantitas,
            'harga_satuan' => $hargaSatuan,
            'subtotal' => $subtotal,
        ]);

        Log::info('Produk berhasil ditambahkan ke keranjang', ['item' => $itemKeranjangOffline]);

        return response()->json(['message' => 'Produk berhasil ditambahkan ke keranjang', 'item' => $itemKeranjangOffline], 201);
    }
}

/**
 * @OA\Delete(
 *     path="/api/keranjang-offline/{id}",
 *     summary="Hapus item dari keranjang offline",
 *     description="Menghapus item dari keranjang offline berdasarkan ID item.",
 *     tags={"Keranjang Offline"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID item keranjang yang akan dihapus",
 *         @OA\Schema(type="string", format="uuid", example="dd0681f8-c767-49e1-8df7-d24bf3be338d")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Item berhasil dihapus dari keranjang",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Item berhasil dihapus dari keranjang.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Item tidak ditemukan",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Item tidak ditemukan.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Kesalahan server",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Terjadi kesalahan saat menghapus item dari keranjang.")
 *         )
 *     )
 * )
 */
public function removeItem($itemId)
{
    try {
        // Cari item keranjang berdasarkan ID
        $item = ItemKeranjangOffline::findOrFail($itemId);

        // Hapus item dari database
        $item->delete();

        // Kembalikan respons sukses
        return response()->json([
            'message' => 'Item berhasil dihapus dari keranjang.',
        ], 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // Jika item tidak ditemukan
        return response()->json([
            'error' => 'Item tidak ditemukan.',
        ], 404);
    } catch (\Exception $e) {
        // Tangani error lainnya
        return response()->json([
            'error' => 'Terjadi kesalahan saat menghapus item dari keranjang.',
        ], 500);
    }
}

/**
 * @OA\Get(
 *     path="/api/nota",
 *     summary="Ambil semua item nota belanja",
 *     description="Mengambil semua data item nota belanja beserta relasi ke produk.",
 *     tags={"Nota Belanja"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Data berhasil diambil",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Data berhasil diambil."),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="item_nota_belanja_id", type="string", format="uuid", example="018f6d2b-f92b-4f00-bf74-9c39d6cc55a8"),
 *                     @OA\Property(property="nota_belanja_id", type="string", format="uuid", example="a6dcea51-15f3-4eb5-b38e-47cd67e8f30f"),
 *                     @OA\Property(property="produk_id", type="string", format="uuid", example="2263d2bb-1834-4649-bf76-699eae6033f2"),
 *                     @OA\Property(property="kuantitas", type="integer", example=1),
 *                     @OA\Property(property="harga_satuan", type="string", example="10000.00"),
 *                     @OA\Property(property="subtotal", type="string", example="10000.00"),
 *                     @OA\Property(property="created_at", type="string", format="datetime", example="2024-12-01T08:41:11.000000Z"),
 *                     @OA\Property(property="updated_at", type="string", format="datetime", example="2024-12-01T08:41:11.000000Z"),
 *                     @OA\Property(
 *                         property="produk",
 *                         type="object",
 *                         @OA\Property(property="produk_id", type="string", format="uuid", example="2263d2bb-1834-4649-bf76-699eae6033f2"),
 *                         @OA\Property(property="kategori_id", type="string", format="uuid", example="b5b8abb7-8ab7-4407-96d6-2ca680d8b3ae"),
 *                         @OA\Property(property="nama_produk", type="string", example="Pupuk Gold"),
 *                         @OA\Property(property="merk", type="string", example="Gopupuk"),
 *                         @OA\Property(property="deskripsi_produk", type="string", example="Pupuk bagus"),
 *                         @OA\Property(property="berat", type="string", example="10.00"),
 *                         @OA\Property(property="harga_satuan", type="string", example="10000.00"),
 *                         @OA\Property(property="stok", type="integer", example=9),
 *                         @OA\Property(property="status_produk", type="string", example="aktif"),
 *                         @OA\Property(property="gambar", type="string", example="storage/produk/oGxYL72YiUgZjRwVBQruQqIn8p2JaN60A64ZiqLY.png"),
 *                         @OA\Property(property="promo_id", type="string", nullable=true, example=null),
 *                         @OA\Property(property="created_at", type="string", format="datetime", example="2024-11-27T09:26:27.000000Z"),
 *                         @OA\Property(property="updated_at", type="string", format="datetime", example="2024-11-27T09:26:27.000000Z"),
 *                         @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *                         @OA\Property(property="after_diskon", type="string", example="10000.00")
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Kesalahan server",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Terjadi kesalahan saat mengambil data."),
 *             @OA\Property(property="error", type="string", example="Deskripsi error.")
 *         )
 *     )
 * )
 */
public function itemNota()
    {
        try {
            // Ambil semua data item_nota_belanja beserta relasi ke produk
            $items = ItemNotaBelanja::with('produk')->get();

            // Return data dalam format JSON
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diambil.',
                'data' => $items
            ], 200);
        } catch (\Exception $e) {
            // Tangani error
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
 * @OA\Get(
 *     path="/api/item-nota/{id}",
 *     summary="Ambil item nota belanja berdasarkan ID",
 *     description="Mengambil data item nota belanja berdasarkan ID beserta relasi ke produk.",
 *     tags={"Nota Belanja"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID dari item nota belanja",
 *         @OA\Schema(type="string", format="uuid", example="018f6d2b-f92b-4f00-bf74-9c39d6cc55a8")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Data berhasil diambil",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Data berhasil diambil."),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="item_nota_belanja_id", type="string", format="uuid", example="018f6d2b-f92b-4f00-bf74-9c39d6cc55a8"),
 *                 @OA\Property(property="nota_belanja_id", type="string", format="uuid", example="a6dcea51-15f3-4eb5-b38e-47cd67e8f30f"),
 *                 @OA\Property(property="produk_id", type="string", format="uuid", example="2263d2bb-1834-4649-bf76-699eae6033f2"),
 *                 @OA\Property(property="kuantitas", type="integer", example=1),
 *                 @OA\Property(property="harga_satuan", type="string", example="10000.00"),
 *                 @OA\Property(property="subtotal", type="string", example="10000.00"),
 *                 @OA\Property(property="created_at", type="string", format="datetime", example="2024-12-01T08:41:11.000000Z"),
 *                 @OA\Property(property="updated_at", type="string", format="datetime", example="2024-12-01T08:41:11.000000Z"),
 *                 @OA\Property(
 *                     property="produk",
 *                     type="object",
 *                     @OA\Property(property="produk_id", type="string", format="uuid", example="2263d2bb-1834-4649-bf76-699eae6033f2"),
 *                     @OA\Property(property="kategori_id", type="string", format="uuid", example="b5b8abb7-8ab7-4407-96d6-2ca680d8b3ae"),
 *                     @OA\Property(property="nama_produk", type="string", example="Pupuk Gold"),
 *                     @OA\Property(property="merk", type="string", example="Gopupuk"),
 *                     @OA\Property(property="deskripsi_produk", type="string", example="Pupuk bagus"),
 *                     @OA\Property(property="berat", type="string", example="10.00"),
 *                     @OA\Property(property="harga_satuan", type="string", example="10000.00"),
 *                     @OA\Property(property="stok", type="integer", example=9),
 *                     @OA\Property(property="status_produk", type="string", example="aktif"),
 *                     @OA\Property(property="gambar", type="string", example="storage/produk/oGxYL72YiUgZjRwVBQruQqIn8p2JaN60A64ZiqLY.png"),
 *                     @OA\Property(property="promo_id", type="string", nullable=true, example=null),
 *                     @OA\Property(property="created_at", type="string", format="datetime", example="2024-11-27T09:26:27.000000Z"),
 *                     @OA\Property(property="updated_at", type="string", format="datetime", example="2024-11-27T09:26:27.000000Z"),
 *                     @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
 *                     @OA\Property(property="after_diskon", type="string", example="10000.00")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Data tidak ditemukan",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Data tidak ditemukan.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Kesalahan server",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Terjadi kesalahan saat mengambil data."),
 *             @OA\Property(property="error", type="string", example="Deskripsi error.")
 *         )
 *     )
 * )
 */

 public function getItemsByNotaId($nota_belanja_id)
{
    try {
        // Ambil NotaBelanja berdasarkan nota_belanja_id dan relasi ke produk (ItemNotaBelanja)
        $nota = NotaBelanja::with('items.produk') // Menggunakan relasi 'items' untuk mengambil data ItemNotaBelanja dan relasi 'produk'
                            ->where('nota_belanja_id', $nota_belanja_id)
                            ->first();

        // Cek jika nota ditemukan
        if (!$nota) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        // Return data dalam format JSON
        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diambil.',
            'data' => $nota->items, // Menampilkan item terkait dengan nota
        ], 200);
    } catch (\Exception $e) {
        // Tangani error
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan saat mengambil data.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
    public function itemNotaById($id)
{
    try {
        // Ambil data item_nota_belanja berdasarkan ID dan relasi ke produk
        $item = ItemNotaBelanja::with('produk')->find($id);

        // Cek jika data ditemukan
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        // Return data dalam format JSON
        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diambil.',
            'data' => $item
        ], 200);
    } catch (\Exception $e) {
        // Tangani error
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan saat mengambil data.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

/**
 * @OA\Post(
 *     path="/api/post-nota",
 *     summary="Checkout multiple items in the cart",
 *     description="Proses checkout untuk beberapa item di keranjang offline, membuat nota belanja baru, dan menghapus item yang telah diproses dari keranjang.",
 *     tags={"Nota Belanja"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             required={"item_keranjang_offline_id"},
 *             @OA\Property(
 *                 property="item_keranjang_offline_id",
 *                 type="array",
 *                 description="Array berisi ID item keranjang offline yang akan di-checkout",
 *                 @OA\Items(type="string", format="uuid", example="bd91a7ff-42d6-4a16-b40f-3e1a16f0d978")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Checkout berhasil",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Checkout berhasil"),
 *             @OA\Property(
 *                 property="nota_belanja",
 *                 type="object",
 *                 @OA\Property(property="nota_belanja_id", type="string", format="uuid", example="f2b1f5e3-8e49-4b6b-90fa-3b90a5b1b6f8"),
 *                 @OA\Property(property="user_id", type="string", format="uuid", example="d5f4a31f-986f-4d62-8764-e3db7c2bf0fa"),
 *                 @OA\Property(property="total_harga", type="number", format="float", example=25000.00),
 *                 @OA\Property(property="keranjang_offline_id", type="string", format="uuid", example="c0b3a74b-29e8-41b7-967b-9c65bc123a6f"),
 *                 @OA\Property(property="created_at", type="string", format="datetime", example="2024-12-01T09:00:00.000000Z"),
 *                 @OA\Property(property="updated_at", type="string", format="datetime", example="2024-12-01T09:00:00.000000Z")
 *             ),
 *             @OA\Property(
 *                 property="item_nota_belanja_ids",
 *                 type="array",
 *                 description="Array ID item nota belanja yang berhasil dibuat",
 *                 @OA\Items(type="string", format="uuid", example="d4c7e9a4-fb84-4f2b-8b4d-d97e4b3e8b6d")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Format item_keranjang_offline_id tidak valid",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Format item_keranjang_offline_id tidak valid")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Keranjang tidak ditemukan atau kosong",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Keranjang tidak ditemukan atau kosong")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Kesalahan validasi input",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="The item_keranjang_offline_id field is required.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Kesalahan server",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Terjadi kesalahan saat memproses checkout")
 *         )
 *     )
 * )
 */
public function checkoutMultipleKeranjang(Request $request)
{
    try {
        // Mendapatkan user yang terautentikasi
        $user = JWTAuth::parseToken()->authenticate();
        $userId = $user->user_id;

        // Validasi input
        $request->validate([
            'item_keranjang_offline_id' => 'required|array|min:1',
            'item_keranjang_offline_id.*' => 'string|exists:item_keranjang_offline,item_keranjang_offline_id',
        ]);

        $itemIds = $request->input('item_keranjang_offline_id', []);

        // Pastikan itemIds adalah array flat (bukan nested array)
        if (!is_array($itemIds) || array_filter($itemIds, 'is_array')) {
            return response()->json(['error' => 'Format item_keranjang_offline_id tidak valid'], 400);
        }

        // Ambil item berdasarkan itemIds yang dimiliki oleh user
        $items = ItemKeranjangOffline::whereIn('item_keranjang_offline_id', $itemIds)
            ->whereHas('keranjangOffline', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->get();

        // Validasi jika tidak ada item yang ditemukan
        if ($items->isEmpty()) {
            return response()->json(['error' => 'Keranjang tidak ditemukan atau kosong'], 404);
        }

        // Hitung total harga
        $totalHarga = $items->sum(function ($item) {
            return $item->subtotal;
        });

        // Buat nota belanja
        $notaBelanja = NotaBelanja::create([
            'user_id' => $userId,
            'total_harga' => $totalHarga,
            'keranjang_offline_id' => $items->first()->keranjangOffline->keranjang_offline_id,
        ]);

        // Simpan detail item dalam nota belanja dan kumpulkan ID-nya
        $itemNotaBelanjaIds = []; // Menyimpan semua ID
        foreach ($items as $item) {
            $itemNota = ItemNotaBelanja::create([
                'nota_belanja_id' => $notaBelanja->nota_belanja_id,
                'produk_id' => $item->produk_id,
                'kuantitas' => $item->kuantitas,
                'harga_satuan' => $item->harga_satuan,
                'subtotal' => $item->subtotal,
            ]);

            // Tambahkan item_nota_belanja_id ke array
            $itemNotaBelanjaIds[] = $itemNota->item_nota_belanja_id;
        }

        // Hapus item yang sudah diproses dari keranjang
        $items->each->delete();

        // Kembalikan respons sukses
        return response()->json([
            'message' => 'Checkout berhasil',
            'nota_belanja' => $notaBelanja,
            'item_nota_belanja_ids' => $itemNotaBelanjaIds, // Tambahkan array ID
        ], 200);
    } catch (ValidationException $e) {
        // Tangani error validasi
        return response()->json(['error' => $e->getMessage()], 422);
    } catch (\Exception $e) {
        // Tangani error umum lainnya
        return response()->json(['error' => 'Terjadi kesalahan saat memproses checkout'], 500);
    }
}



    /**
 * @OA\Post(
 *     path="/api/keranjang-offline/checkout",
 *     summary="Checkout keranjang offline",
 *     description="Memproses checkout keranjang offline user, membuat nota belanja baru, memindahkan item ke nota belanja, dan menghapus keranjang offline.",
 *     tags={"Nota Belanja"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Checkout berhasil",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Checkout berhasil"),
 *             @OA\Property(
 *                 property="nota_belanja",
 *                 type="object",
 *                 @OA\Property(property="nota_belanja_id", type="string", format="uuid", example="f2b1f5e3-8e49-4b6b-90fa-3b90a5b1b6f8"),
 *                 @OA\Property(property="user_id", type="string", format="uuid", example="d5f4a31f-986f-4d62-8764-e3db7c2bf0fa"),
 *                 @OA\Property(property="total", type="number", format="float", example=25000.00),
 *                 @OA\Property(property="created_at", type="string", format="datetime", example="2024-12-01T09:00:00.000000Z"),
 *                 @OA\Property(property="updated_at", type="string", format="datetime", example="2024-12-01T09:00:00.000000Z")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Keranjang kosong",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Keranjang kosong")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Token JWT tidak valid atau tidak ada",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Unauthenticated")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Kesalahan server",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Terjadi kesalahan pada server")
 *         )
 *     )
 * )
 */

    public function checkout(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate(); // Dapatkan user dari JWT token
        $keranjangOffline = KeranjangOffline::where('user_id', $user->user_id)->first();

        if (!$keranjangOffline) {
            return response()->json(['error' => 'Keranjang kosong'], 400);
        }

        // Membuat nota belanja
        $notaBelanja = NotaBelanja::create([
            'user_id' => $user->user_id,
            'total' => $this->calculateTotalKeranjang($keranjangOffline->keranjang_offline_id),
        ]);

        // Menambahkan item ke nota belanja
        foreach ($keranjangOffline->itemKeranjangOffline as $item) {
            ItemNotaBelanja::create([
                'nota_belanja_id' => $notaBelanja->nota_belanja_id,
                'produk_id' => $item->produk_id,
                'kuantitas' => $item->kuantitas,
                'harga_satuan' => $item->harga_satuan,
                'subtotal' => $item->subtotal,
            ]);
        }

        // Menghapus keranjang offline setelah checkout
        $keranjangOffline->delete();

        return response()->json(['message' => 'Checkout berhasil', 'nota_belanja' => $notaBelanja], 200);
    }

    // Menghitung total harga keranjang
    private function calculateTotalKeranjang($keranjangId)
    {
        $keranjangOffline = KeranjangOffline::findOrFail($keranjangId);
        return $keranjangOffline->itemKeranjangOffline->sum('subtotal');
    }

    // Mencetak nota belanja
    public function printNota($notaId)
    {
        $nota = NotaBelanja::with('itemNotaBelanja.produk')->findOrFail($notaId);
        return response()->json([
            'nota' => $nota,
            'total' => $nota->total,
            'items' => $nota->itemNotaBelanja
        ]);
    }
}
