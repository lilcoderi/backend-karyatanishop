<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\Keranjang;
use App\Models\ItemKeranjang;
use App\Models\Transaksi;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\OrderDetail;
use App\Models\Promo;
use App\Models\ProdukItem;

class OrderController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/orders",
     *     summary="Membuat order baru",
     *     operationId="createOrder",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"item_keranjang_id"},
     *             @OA\Property(
     *                 property="item_keranjang_id",
     *                 type="array",
     *                 description="Array of item keranjang UUIDs to include in the order",
     *                 @OA\Items(type="string", format="uuid")
     *             ),
     *             @OA\Property(
     *                 property="kode_voucher",
     *                 type="string",
     *                 description="Optional promo code to apply for the order",
     *                 example="SUMMER2024"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Order and transaction created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(ref="#/components/schemas/Order"),
     *                 @OA\Property(property="total_bayar", type="number", format="float", example=120000),
     *                 @OA\Property(property="promo_applied", type="string", nullable=true, example="SUMMER2024")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="No valid items found for this order")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized access")
     *         )
     *     )
     * )
     */
    public function create(Request $request)
    {
        // Autentikasi pengguna dengan JWT
        $user = JWTAuth::parseToken()->authenticate();
        $userId = $user->user_id;

        // Validasi input
        $request->validate([
            'item_keranjang_id' => 'required|array|min:1',
            'item_keranjang_id.*' => 'uuid|exists:item_keranjang,item_keranjang_id',
            'kode_voucher' => 'nullable|string|exists:promo,kode_voucher',
        ]);

        // Ambil item keranjang yang valid
        $items = ItemKeranjang::whereIn('item_keranjang_id', $request->item_keranjang_id)
            ->whereHas('keranjang', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No valid items found for this order',
            ], 400);
        }

        // Hitung total bayar
        $total_bayar = $items->sum('subtotal');

        // Cek promo jika ada
        $promo = null;
        if ($request->kode_voucher) {
            $promo = Promo::where('kode_voucher', $request->kode_voucher)->first();

            if (!$promo || now()->lt($promo->tgl_mulai) || now()->gt($promo->tgl_berakhir)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Voucher tidak valid atau sudah kadaluarsa.',
                ], 400);
            }

            if ($promo->jenis_promo === 'voucher') {
                $total_bayar = max($total_bayar - $promo->nilai_promo, 0);
            }
        }

        // Buat order
        $order = Order::create([
            'order_id' => Str::uuid(),
            'user_id' => $userId,
            'tgl_order' => now(),
            'status_order' => 'pending',
        ]);

        foreach ($items as $item) {
            // Simpan ke tabel order_detail
            OrderDetail::create([
                'detail_id' => Str::uuid(),
                'order_id' => $order->order_id,
                'produk_id' => $item->produk_id,
                'quantity' => $item->kuantitas,
            ]);

            // Simpan ke tabel produk_item
            ProdukItem::create([
                'produk_item_id' => Str::uuid(),
                'order_id' => $order->order_id,
                'produk_id' => $item->produk_id,
                'kuantitas' => $item->kuantitas,
                'harga_satuan' => $item->harga_satuan,
                'subtotal' => $item->subtotal,
            ]);

            // Update item_keranjang dengan order_id
            $item->update([
                'order_id' => $order->order_id,
            ]);
        }

        // Buat transaksi
        Transaksi::create([
            'transaksi_id' => Str::uuid(),
            'order_id' => $order->order_id,
            'total_pembayaran' => $total_bayar,
            'status_pembayaran' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Order and transaction created successfully',
            'data' => [
                'order' => $order,
                'total_bayar' => $total_bayar,
                'promo_applied' => $promo ? $promo->kode_voucher : null,
            ],
        ], 201);
    }

    /**
 * @OA\Post(
 *     path="/api/orders-search",
 *     summary="Cari Order Berdasarkan Order ID",
 *     description="Melakukan pencarian order berdasarkan Order ID, termasuk data user dan detail produk yang terkait.",
 *     tags={"Order"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="order_id", type="string", format="uuid", example="fcf57f40-7bdd-4e4b-9d2b-c1bd7120d6c8")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Order ditemukan.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Order found successfully"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="order_id", type="string", format="uuid", example="fcf57f40-7bdd-4e4b-9d2b-c1bd7120d6c8"),
 *                 @OA\Property(property="user_id", type="string", format="uuid", example="2ee7ef07-430c-4049-9b07-eb29550f1cb1"),
 *                 @OA\Property(property="tgl_order", type="string", format="date", example="2024-11-28"),
 *                 @OA\Property(property="status_order", type="string", example="pending"),
 *                 @OA\Property(property="created_at", type="string", format="datetime", example="2024-11-28T07:24:35.000000Z"),
 *                 @OA\Property(property="updated_at", type="string", format="datetime", example="2024-11-28T07:24:35.000000Z"),
 *                 @OA\Property(property="deleted_at", type="string", format="datetime", nullable=true, example=null),
 *                 @OA\Property(property="user", type="object",
 *                     @OA\Property(property="user_id", type="string", format="uuid", example="2ee7ef07-430c-4049-9b07-eb29550f1cb1"),
 *                     @OA\Property(property="nama", type="string", example="riana"),
 *                     @OA\Property(property="email", type="string", example="email@gmail.com"),
 *                     @OA\Property(property="no_tlp", type="string", example="12345678923"),
 *                     @OA\Property(property="is_verified", type="integer", example=1),
 *                     @OA\Property(property="status", type="integer", example=1),
 *                     @OA\Property(property="foto", type="string", nullable=true, example=null),
 *                     @OA\Property(property="created_at", type="string", format="datetime", example="2024-11-25T07:04:43.000000Z"),
 *                     @OA\Property(property="updated_at", type="string", format="datetime", example="2024-11-29T14:58:01.000000Z"),
 *                     @OA\Property(property="deleted_at", type="string", format="datetime", nullable=true, example=null),
 *                     @OA\Property(property="role", type="string", example="customer")
 *                 ),
 *                 @OA\Property(property="produk_items", type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="produk_item_id", type="string", format="uuid", example="75659919-3a12-4c2c-a219-32c212ba2241"),
 *                         @OA\Property(property="order_id", type="string", format="uuid", example="fcf57f40-7bdd-4e4b-9d2b-c1bd7120d6c8"),
 *                         @OA\Property(property="produk_id", type="string", format="uuid", example="29398d11-f66e-4378-9a31-d91c46966533"),
 *                         @OA\Property(property="kuantitas", type="integer", example=2),
 *                         @OA\Property(property="harga_satuan", type="string", format="decimal", example="79500.00"),
 *                         @OA\Property(property="subtotal", type="string", format="decimal", example="159000.00"),
 *                         @OA\Property(property="deleted_at", type="string", format="datetime", nullable=true, example=null),
 *                         @OA\Property(property="created_at", type="string", format="datetime", example="2024-11-28T07:24:35.000000Z"),
 *                         @OA\Property(property="updated_at", type="string", format="datetime", example="2024-11-28T07:24:35.000000Z"),
 *                         @OA\Property(property="produk", type="object",
 *                             @OA\Property(property="produk_id", type="string", format="uuid", example="29398d11-f66e-4378-9a31-d91c46966533"),
 *                             @OA\Property(property="kategori_id", type="string", format="uuid", example="b5b8abb7-8ab7-4407-96d6-2ca680d8b3ae"),
 *                             @OA\Property(property="nama_produk", type="string", example="Produk Bagus"),
 *                             @OA\Property(property="merk", type="string", example="Merk Bagus"),
 *                             @OA\Property(property="deskripsi_produk", type="string", example="Ini adalah deskripsi produk contoh."),
 *                             @OA\Property(property="berat", type="string", format="decimal", example="1.50"),
 *                             @OA\Property(property="harga_satuan", type="string", format="decimal", example="100000.00"),
 *                             @OA\Property(property="stok", type="integer", example=48),
 *                             @OA\Property(property="status_produk", type="string", example="aktif"),
 *                             @OA\Property(property="gambar", type="string", example="storage/image.jpg"),
 *                             @OA\Property(property="promo_id", type="string", format="uuid", example="2328a556-6e99-47f6-8370-7ac9f8b663cf"),
 *                             @OA\Property(property="created_at", type="string", format="datetime", example="2024-11-27T09:05:21.000000Z"),
 *                             @OA\Property(property="updated_at", type="string", format="datetime", example="2024-12-01T10:16:25.000000Z"),
 *                             @OA\Property(property="deleted_at", type="string", format="datetime", nullable=true, example=null),
 *                             @OA\Property(property="after_diskon", type="string", format="decimal", example="100000.00")
 *                         )
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Parameter Order ID tidak ditemukan.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="message", type="string", example="Order ID is required for search")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Order tidak ditemukan.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="message", type="string", example="Order not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Kesalahan server.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="message", type="string", example="Terjadi kesalahan pada server.")
 *         )
 *     )
 * )
 */
    public function searchOrder(Request $request)
{
    // Ambil input pencarian dari request body
    $orderId = $request->input('order_id');

    // Pastikan hanya pencarian berdasarkan Order ID
    if (!$orderId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Order ID is required for search',
        ], 400);
    }

    // Lakukan pencarian hanya berdasarkan Order ID
    $order = Order::where('order_id', $orderId)
                  ->with(['user', 'orderDetails.produk']) // Memuat relasi untuk 'user' dan 'produk' dari orderDetails
                  ->first();

    // Jika tidak ada hasil ditemukan
    if (!$order) {
        return response()->json([
            'status' => 'error',
            'message' => 'Order not found',
        ], 404);
    }

    // Jika ditemukan, kembalikan hasilnya
    return response()->json([
        'status' => 'success',
        'message' => 'Order found successfully',
        'data' => $order,
    ], 200);
}




    /**
     * @OA\Get(
     *     path="/api/orders",
     *     summary="Mengambil daftar order",
     *     operationId="getOrders",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of orders",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Order"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized access")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
{
    // Memuat relasi 'user' dan 'produkItems.produk' dengan pagination
    $orders = Order::with(['user', 'produkItems.produk'])->paginate(5);

    return response()->json([
        'status' => 'success',
        'data' => $orders,
    ]);
}





    /**
     * @OA\Get(
     *     path="/api/orders/{id}",
     *     summary="Mengambil detail order",
     *     operationId="getOrderDetail",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order detail",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", ref="#/components/schemas/Order")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Order not found")
     *         )
     *     )
     * )
     */
    public function show($id, Request $request)
    {
        // Muat data order beserta user dan produk items-nya
        $order = Order::with(['user', 'produkItems.produk']) // Menambahkan relasi produk_items dan produk di dalamnya
                     ->where('order_id', $id)
                     ->first();

        // Jika order tidak ditemukan
        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found',
            ], 404);
        }

        // Cek otorisasi customer (untuk memastikan user hanya bisa melihat data order mereka sendiri)
        // if ($request->user()->role === 'customer' && $order->user_id !== $request->user()->user_id) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Unauthorized access',
        //     ], 403);
        // }

        // Jika order ditemukan dan otorisasi berhasil, kembalikan data order beserta produk items
        return response()->json([
            'status' => 'success',
            'data' => $order,
        ], 200);
    }


    /**
     * @OA\Put(
     *     path="/api/orders/{id}",
     *     summary="Mengupdate status order (kasir)",
     *     description="Kasir dapat mengupdate status order. Status yang valid: pending, proses, selesai, batal.",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid"),
     *         description="ID order yang ingin diperbarui"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status_order"},
     *             @OA\Property(property="status_order", type="string", enum={"pending", "proses", "selesai", "batal"}, description="Status order yang baru")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order berhasil diupdate",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Order updated successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Order")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Input tidak valid",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Order not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Akses tidak sah",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized access")
     *         )
     *     ),
     *     security={{"Bearer": {}}},
     * )
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'status_order' => 'required|in:pending,proses,selesai,batal',
        ]);

        $order = Order::where('order_id', $id)->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found',
            ], 404);
        }

        if ($request->user()->role !== 'kasir') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
            ], 403);
        }

        $order->update(['status_order' => $request->status_order]);

        return response()->json([
            'status' => 'success',
            'message' => 'Order updated successfully',
            'data' => $order,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/orders/{id}",
     *     summary="Menghapus order (kasir)",
     *     description="Kasir dapat menghapus order yang ada.",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid"),
     *         description="ID order yang ingin dihapus"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order berhasil dihapus",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Order deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Order tidak ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Order not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Akses tidak sah",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized access")
     *         )
     *     ),
     *     security={{"Bearer": {}}},
     * )
     */
    public function destroy($id, Request $request)
    {
        $order = Order::where('order_id', $id)->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found',
            ], 404);
        }

        // if ($request->user()->role !== 'kasir') {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Unauthorized access',
        //     ], 403);
        // }

        $order->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Order deleted successfully',
        ]);
    }
}
