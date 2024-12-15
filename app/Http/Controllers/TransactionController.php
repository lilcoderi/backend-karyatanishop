<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi;

class TransactionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/transactions",
     *     summary="Get All Transactions",
     *     description="Retrieve all transactions (admin, kasir)",
     *     operationId="getTransactions",
     *     security={{"bearerAuth": {}}},
     *     tags={"Transactions"},
     *     @OA\Response(
     *         response=200,
     *         description="Transactions retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 ref="#/components/schemas/Transaction"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized, invalid token"
     *     )
     * )
     */
    public function index(Request $request)
    {
        // Mengambil data transaksi dengan relasi 'order', diurutkan berdasarkan tanggal terbaru, paginasi 5 item per halaman
        $transactions = Transaksi::with('order')
            ->orderBy('created_at', 'desc') // Mengurutkan berdasarkan tanggal terbaru
            ->paginate(5);
    
        return response()->json([
            'status' => 'success',
            'data' => $transactions,
        ]);
    }
    


    /**
     * @OA\Get(
     *     path="/api/transactions/{order_id}",
     *     summary="Get Transaction by Order ID",
     *     description="Retrieve transaction details based on order ID",
     *     operationId="getTransactionByOrder",
     *     security={{"bearerAuth": {}}},
     *     tags={"Transactions"},
     *     @OA\Parameter(
     *         name="order_id",
     *         in="path",
     *         required=true,
     *         description="The order ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Transaction")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transaction not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized, invalid token"
     *     )
     * )
     */
    public function showByTransaction($transaksi_id, Request $request)
{
    // Mengubah pencarian berdasarkan transaksi_id
    $transaction = Transaksi::where('transaksi_id', $transaksi_id)->with('order')->first();

    if (!$transaction) {
        return response()->json([
            'status' => 'error',
            'message' => 'Transaction not found',
        ], 404);
    }

    return response()->json([
        'status' => 'success',
        'data' => $transaction,
    ]);
}


    /**
     * @OA\Put(
     *     path="/api/transactions/{id}",
     *     summary="Update Transaction Status",
     *     description="Update the payment status of a transaction",
     *     operationId="updateTransactionStatus",
     *     security={{"bearerAuth": {}}},
     *     tags={"Transactions"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The transaction ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"status_pembayaran"},
     *             @OA\Property(property="status_pembayaran", type="string", description="Payment status", enum={"pending", "selesai", "batal"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Transaction")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request, validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transaction not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized, invalid token"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'status_pembayaran' => 'required|in:pending,selesai,batal',
        ]);

        $transaction = Transaksi::where('transaksi_id', $id)->first();

        if (!$transaction) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaction not found',
            ], 404);
        }

        $transaction->update(['status_pembayaran' => $request->status_pembayaran]);

        return response()->json([
            'status' => 'success',
            'message' => 'Transaction updated successfully',
            'data' => $transaction,
        ]);
    }
}
