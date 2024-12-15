<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produk;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Promo;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Notification;
class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/products",
     *     summary="Get All Products",
     *     description="Retrieve all products",
     *     operationId="getProducts",
     *     security={{"bearerAuth": {}}},
     *     tags={"Products"},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved products",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Product"))
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized, invalid token"
     *     )
     * )
     */
    public function index(Request $request)
{
    // Initialize the query builder with relationships
    $query = Produk::with(['kategori', 'promo']);

    // Check if the 'search' query parameter exists
    if ($request->has('search')) {
        $query->where('nama_produk', 'like', '%' . $request->search . '%');
    }

    // Order by the latest created date
    $query->orderBy('created_at', 'desc');

    // Menambahkan pagination dengan 5 item per halaman
    $products = $query->paginate(5);

    return response()->json([
        'status' => 'success',
        'data' => $products,
    ]);
}



    public function productsAll(Request $request)
    {
        // Mengambil semua produk dengan relasi kategori dan promo
        $products = Produk::with(['kategori', 'promo'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $products,
        ]);
    }

    public function productsView(Request $request)
    {
        // Mengambil semua produk tanpa relasi kategori dan promo
        $products = Produk::all();

        return response()->json([
            'status' => 'success',
            'data' => $products,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/search-products",
     *     summary="Cari produk berdasarkan nama",
     *     description="Endpoint untuk mencari produk berdasarkan nama produk dengan pencocokan sebagian (like query).",
     *     tags={"Produk"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="nama_produk",
     *                 type="string",
     *                 description="Nama produk yang ingin dicari",
     *                 example="Pupuk"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Daftar produk yang cocok dengan pencarian",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 example="success"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="produk_id", type="string", example="2263d2bb-1834-4649-bf76-699eae6033f2"),
     *                     @OA\Property(property="kategori_id", type="string", example="b5b8abb7-8ab7-4407-96d6-2ca680d8b3ae"),
     *                     @OA\Property(property="nama_produk", type="string", example="Pupuk Gold"),
     *                     @OA\Property(property="merk", type="string", example="Gopupuk"),
     *                     @OA\Property(property="deskripsi_produk", type="string", example="Pupuk bagus"),
     *                     @OA\Property(property="berat", type="string", example="10.00"),
     *                     @OA\Property(property="harga_satuan", type="string", example="10000.00"),
     *                     @OA\Property(property="stok", type="integer", example=9),
     *                     @OA\Property(property="status_produk", type="string", example="aktif"),
     *                     @OA\Property(property="gambar", type="string", example="storage/produk/oGxYL72YiUgZjRwVBQruQqIn8p2JaN60A64ZiqLY.png"),
     *                     @OA\Property(property="promo_id", type="string", example=null),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-27T09:26:27.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-27T09:26:27.000000Z"),
     *                     @OA\Property(property="deleted_at", type="string", example=null),
     *                     @OA\Property(property="after_diskon", type="string", example="10000.00")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Produk tidak ditemukan",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 example="error"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="No products found with the given name."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Kesalahan validasi input",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 example="error"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="The nama_produk field is required."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Kesalahan server",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 example="error"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="An error occurred: [error details]"
     *             )
     *         )
     *     )
     * )
     */
    public function searchProduct(Request $request)
    {
        try {
            // Validasi input
            $request->validate([
                'nama_produk' => 'required|string|max:255',
            ]);

            // Ambil nama_produk dari request body
            $namaProduk = $request->input('nama_produk');

            // Cari produk berdasarkan nama_produk (ubah 'name' menjadi nama kolom yang sesuai)
            $products = Produk::where('nama_produk', 'like', '%' . $namaProduk . '%')->get();

            // Jika produk tidak ditemukan
            if ($products->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No products found with the given name.',
                ], 404);
            }

            // Jika ditemukan, kembalikan data produk
            return response()->json([
                'status' => 'success',
                'data' => $products,
            ], 200);
        } catch (\Exception $e) {
            // Jika terjadi kesalahan
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/products/{id}",
     *     summary="Get Product by ID",
     *     description="Retrieve a product by its ID",
     *     operationId="getProductById",
     *     security={{"bearerAuth": {}}},
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Product ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved the product",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized, invalid token"
     *     )
     * )
     */
    public function show($id)
    {
        $product = Produk::with(['kategori', 'promo'])->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    /**
     * @OA\Post(
     *     path="/api/products",
     *     summary="Create a Product",
     *     description="Create a new product",
     *     operationId="createProduct",
     *     security={{"bearerAuth": {}}},
     *     tags={"Products"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"kategori_id", "nama_produk", "berat", "harga_satuan", "stok", "status_produk"},
     *             @OA\Property(property="kategori_id", type="integer", description="Category ID"),
     *             @OA\Property(property="nama_produk", type="string", description="Product name"),
     *             @OA\Property(property="merk", type="string", description="Product brand"),
     *             @OA\Property(property="deskripsi_produk", type="string", description="Product description"),
     *             @OA\Property(property="berat", type="number", format="float", description="Product weight"),
     *             @OA\Property(property="harga_satuan", type="number", format="float", description="Unit price"),
     *             @OA\Property(property="stok", type="integer", description="Product stock"),
     *             @OA\Property(property="status_produk", type="string", enum={"aktif", "nonaktif"}, description="Product status"),
     *             @OA\Property(property="gambar", type="string", description="Product image URL"),
     *             @OA\Property(property="promo_id", type="integer", description="Promo ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized, invalid token"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     */
    public function store(Request $request)
{
    Log::info('Memulai proses tambah produk baru.');

    // Validasi input
    $validator = Validator::make($request->all(), [
        'kategori_id' => 'required|exists:kategori,kategori_id',
        'nama_produk' => 'required|string|max:255',
        'merk' => 'nullable|string|max:255',
        'deskripsi_produk' => 'nullable|string',
        'berat' => 'required|numeric',
        'harga_satuan' => 'required|numeric',
        'stok' => 'required|integer|min:0',
        'status_produk' => 'required|in:aktif,nonaktif',
        'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'promo_id' => [
            'nullable',
            'exists:promo,promo_id',
            function ($attribute, $value, $fail) {
                $promo = Promo::find($value);
                if ($promo && $promo->jenis_promo !== 'diskon') {
                    $fail('Promo harus memiliki jenis_promo "diskon".');
                }
            },
        ],
    ]);

    if ($validator->fails()) {
        Log::warning('Validasi gagal.', ['errors' => $validator->errors()]);
        return response()->json(['errors' => $validator->errors()], 422);
    }

    Log::info('Validasi input berhasil.');

    // Ambil detail promo jika ada
    $promo = $request->promo_id ? Promo::find($request->promo_id) : null;

    // Hitung harga setelah diskon
    $harga_satuan = $request->harga_satuan;
    $after_diskon = $promo ? max($harga_satuan - ($harga_satuan * $promo->nilai_promo / 100), 0) : $harga_satuan;

    // Upload file gambar
    $gambarPath = $request->hasFile('gambar') ? $request->file('gambar')->store('produk', 'public') : null;

    // Simpan produk dengan harga setelah diskon
    $product = Produk::create(array_merge($request->all(), [
        'produk_id' => (string) Str::uuid(),
        'after_diskon' => $after_diskon,
        'gambar' => $gambarPath ? 'storage/' . $gambarPath : null,
    ]));

    Log::info('Produk berhasil disimpan.', ['produk_id' => $product->produk_id]);

    // Kirim notifikasi ke semua pengguna dengan role 'customer'
   // Kirim notifikasi ke semua pengguna dengan role 'customer'
$customers = User::role('customer')->get();

if ($customers->isEmpty()) {
    Log::warning('Tidak ada user dengan role customer ditemukan.');
    return response()->json([
        'status' => 'warning',
        'message' => 'Produk berhasil ditambahkan, namun tidak ada pelanggan untuk menerima notifikasi.',
    ], 201);
}

foreach ($customers as $customer) {
    if (empty($customer->user_id)) {
        Log::warning('Customer dengan data tidak valid ditemukan tetapi tetap mengirim notifikasi.', [
            'customer' => $customer->toArray()
        ]);
    }

    try {
        Notification::create([
            'user_id' => $customer->user_id,
            'message' => "Produk baru telah ditambahkan: {$product->nama_produk}. Jangan lewatkan kesempatan untuk mendapatkannya!",
            'status' => 'unread',
        ]);

        Log::info('Notifikasi dikirim ke user customer.', ['user_id' => $customer->user_id]);
    } catch (\Exception $e) {
        Log::error('Gagal mengirim notifikasi.', [
            'user_id' => $customer->user_id,
            'error' => $e->getMessage(),
        ]);
    }
}

return response()->json([
    'status' => 'success',
    'message' => 'Produk berhasil ditambahkan dan notifikasi telah dikirim.',
    'data' => $product,
], 201);


}

    



    /**
     * @OA\Put(
     *     path="/api/products/{id}",
     *     summary="Update a Product",
     *     description="Update an existing product",
     *     operationId="updateProduct",
     *     security={{"bearerAuth": {}}},
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Product ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="kategori_id", type="integer", description="Category ID"),
     *             @OA\Property(property="nama_produk", type="string", description="Product name"),
     *             @OA\Property(property="merk", type="string", description="Product brand"),
     *             @OA\Property(property="deskripsi_produk", type="string", description="Product description"),
     *             @OA\Property(property="berat", type="number", format="float", description="Product weight"),
     *             @OA\Property(property="harga_satuan", type="number", format="float", description="Unit price"),
     *             @OA\Property(property="stok", type="integer", description="Product stock"),
     *             @OA\Property(property="status_produk", type="string", enum={"aktif", "nonaktif"}, description="Product status"),
     *             @OA\Property(property="gambar", type="string", description="Product image URL"),
     *             @OA\Property(property="promo_id", type="integer", description="Promo ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized, invalid token"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     */

     public function update(Request $request, $id)
     {
         try {
             // Validasi input
             $validator = Validator::make($request->all(), [
                 'kategori_id' => 'sometimes|required|exists:kategori,kategori_id',
                 'nama_produk' => 'sometimes|required|string|max:255',
                 'merk' => 'nullable|string|max:255',
                 'deskripsi_produk' => 'nullable|string',
                 'berat' => 'sometimes|required|numeric',
                 'harga_satuan' => 'sometimes|required|numeric',
                 'stok' => 'sometimes|required|integer|min:0',
                 'status_produk' => 'sometimes|required|in:aktif,nonaktif',
                 'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
             ]);
     
             if ($validator->fails()) {
                 \Log::error('Validation failed:', $validator->errors()->toArray());
                 return response()->json(['errors' => $validator->errors()], 422);
             }
     
             // Cek apakah produk ada
             $product = Produk::find($id);
             if (!$product) {
                 \Log::error("Product not found: ID $id");
                 return response()->json(['error' => 'Produk tidak ditemukan'], 404);
             }
     
             // Ambil semua input kecuali gambar
             $data = $request->except(['gambar']);
     
             // Jika ada file gambar baru, handle penghapusan gambar lama
             if ($request->hasFile('gambar')) {
                 // Hapus gambar lama jika ada
                 if ($product->gambar && Storage::disk('public')->exists(str_replace('storage/', '', $product->gambar))) {
                     Storage::disk('public')->delete(str_replace('storage/', '', $product->gambar));
                 }
     
                 // Simpan gambar baru
                 $gambarPath = $request->file('gambar')->store('produk', 'public');
                 $data['gambar'] = 'storage/' . $gambarPath;
             }
     
             // Update produk
             $product->update($data);
     
             \Log::info("Product updated successfully: ID $id", $data);
     
             return response()->json($product, 200);
         } catch (Exception $e) {
             \Log::error("Error updating product: " . $e->getMessage(), [
                 'id' => $id,
                 'data' => $request->all(),
             ]);
     
             return response()->json(['error' => 'Terjadi kesalahan saat memperbarui produk.'], 500);
         }
     }
     



    /**
     * @OA\Delete(
     *     path="/api/products/{id}",
     *     summary="Delete a Product",
     *     description="Delete a product by ID",
     *     operationId="deleteProduct",
     *     security={{"bearerAuth": {}}},
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Product ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized, invalid token"
     *     )
     * )
     */
    public function destroy($id)
    {
        $product = Produk::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
