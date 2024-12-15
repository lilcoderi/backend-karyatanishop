<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Produk;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Order;
class ReviewController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/reviews",
     *     summary="Create a Review",
     *     description="Create a review for a product",
     *     operationId="storeReview",
     *     security={{"bearerAuth": {}}},
     *     tags={"Reviews"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"produk_id", "content", "rating"},
     *             @OA\Property(property="produk_id", type="integer", description="Product ID"),
     *             @OA\Property(property="content", type="string", description="Review content"),
     *             @OA\Property(property="rating", type="integer", description="Rating (1-5)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Review created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="review", ref="#/components/schemas/Review")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized, invalid token"
     *     )
     * )
     */
    public function store(Request $request)
{
    try {
        // Validasi input dari request
        $validated = $request->validate([
            'produk_id' => 'required|exists:produk,produk_id', // Pastikan produk_id ada dalam tabel produk
            'content' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'order_id' => 'required|exists:order,order_id', // Validasi order_id pada tabel 'orders'
        ]);
        
        

        // Autentikasi user menggunakan JWT
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized, please login first'], 401);
        }

        $userId = $user->user_id;

        // Cek apakah produk dengan produk_id ada
        $produk = Produk::find($validated['produk_id']);
        if (!$produk) {
            return response()->json(['message' => 'Produk not found'], 404);
        }

        // Cek apakah order_id terkait dengan user ini
        $order = Order::find($validated['order_id']);
        if (!$order || $order->user_id != $userId) {
            return response()->json(['message' => 'Order not found or not associated with this user'], 404);
        }

        // Menyimpan review ke database
        $review = Review::create([
            'produk_id' => $validated['produk_id'],
            'user_id' => $userId,
            'content' => $validated['content'],
            'rating' => $validated['rating'],
            'order_id' => $validated['order_id'], // Simpan order_id
        ]);

        // Mengirimkan respon sukses dengan data review yang baru
        return response()->json(['message' => 'Review created', 'review' => $review], 201);

    } catch (\Exception $e) {
        // Menangani error jika terjadi kesalahan di server
        return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
    }
}

public function getReviewsByOrderId(Request $request)
{
    try {
        // Ambil order_id dari query parameter
        $orderId = $request->query('order_id');

        // Validasi jika order_id ada
        if (!$orderId) {
            return response()->json(['message' => 'order_id parameter is required'], 400);
        }

        // Cari review berdasarkan order_id
        $reviews = Review::where('order_id', $orderId)->get();

        // Jika tidak ada review terkait order_id
        if ($reviews->isEmpty()) {
            return response()->json(['message' => 'No reviews found for this order'], 404);
        }

        // Kembalikan response dengan data review
        return response()->json(['reviews' => $reviews], 200);
    } catch (\Exception $e) {
        // Menangani error jika terjadi kesalahan di server
        return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
    }
}


    /**
     * @OA\Get(
     *     path="/api/reviews",
     *     summary="Get All Reviews",
     *     description="Retrieve all reviews for all products",
     *     operationId="getAllReviews",
     *     security={{"bearerAuth": {}}},
     *     tags={"Reviews"},
     *     @OA\Response(
     *         response=200,
     *         description="Reviews retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Review")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized, invalid token"
     *     )
     * )
     */
    public function index()
    {
        $reviews = Review::with(['user', 'produk'])->get();
        return response()->json($reviews);
    }


    /**
     * @OA\Get(
     *     path="/api/reviews/{produk_id}",
     *     summary="Get Reviews by Product ID",
     *     description="Retrieve reviews for a specific product",
     *     operationId="getReviewsByProduct",
     *     security={{"bearerAuth": {}}},
     *     tags={"Reviews"},
     *     @OA\Parameter(
     *         name="produk_id",
     *         in="path",
     *         required=true,
     *         description="The product ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reviews retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Review")
     *         )
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
    public function show($produk_id)
    {
        $produk = Produk::findOrFail($produk_id);

        $reviews = Review::with('user')->where('produk_id', $produk_id)->get();
        return response()->json($reviews);
    }

    /**
     * @OA\Put(
     *     path="/api/reviews/{id}",
     *     summary="Update a Review",
     *     description="Update an existing review by ID",
     *     operationId="updateReview",
     *     security={{"bearerAuth": {}}},
     *     tags={"Reviews"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The review ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content", "rating"},
     *             @OA\Property(property="content", type="string", description="Updated review content"),
     *             @OA\Property(property="rating", type="integer", description="Updated rating (1-5)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Review updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Review")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Review not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized, invalid token"
     *     )
     * )
     */
    public function update(Request $request, $order_id)
{
    // Cari review berdasarkan order_id
    $review = Review::where('order_id', $order_id)->first();

    // Jika review tidak ditemukan, kembalikan respons error
    if (!$review) {
        return response()->json(['error' => 'Review not found'], 404);
    }

    // Autentikasi pengguna
    $user = JWTAuth::parseToken()->authenticate();
    $userId = $user->user_id;

    // Periksa apakah review milik pengguna yang sedang login
    if ($review->user_id != $userId) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    // Validasi input
    $validated = $request->validate([
        'content' => 'required|string',
        'rating' => 'required|integer|min:1|max:5',
    ]);

    // Perbarui review
    $review->update($validated);

    // Kembalikan respons sukses
    return response()->json(['message' => 'Review updated', 'review' => $review]);
}

    /**
     * @OA\Delete(
     *     path="/api/reviews/{id}",
     *     summary="Delete a Review",
     *     description="Delete a review by ID",
     *     operationId="deleteReview",
     *     security={{"bearerAuth": {}}},
     *     tags={"Reviews"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The review ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Review deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Review not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized, invalid token"
     *     )
     * )
     */
    public function destroy($id)
    {
        $review = Review::findOrFail($id);

        $user = JWTAuth::parseToken()->authenticate();
        $userId = $user->user_id;

        if ($review->user_id != $userId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Review deleted']);
    }

    public function showReviewByOrderId($order_id)
    {
        // Mengambil review berdasarkan order_id
        $review = Review::with('user')->where('order_id', $order_id)->first();
    
        // Jika review tidak ditemukan, kembalikan respons error
        if (!$review) {
            return response()->json(['error' => 'Review not found'], 404);
        }
    
        // Mengembalikan review dalam format JSON
        return response()->json($review);
    }
    


    public function getAverageRating($produk_id)
{
    // Validasi apakah produk ID ada di tabel produk
    $produkExists = Produk::where('produk_id', $produk_id)->exists();
    if (!$produkExists) {
        return response()->json(['message' => 'Produk tidak ditemukan'], 404);
    }

    // Ambil rata-rata rating dari tabel reviews berdasarkan produk_id
    $averageRating = Review::where('produk_id', $produk_id)->avg('rating');

    return response()->json([
        'produk_id' => $produk_id,
        'average_rating' => round($averageRating, 2), // Membulatkan hingga 2 desimal
    ]);
}

}
