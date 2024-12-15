<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Address;

class AddressController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/address",
     *     summary="Get User Address",
     *     description="Retrieve the address of the authenticated user",
     *     operationId="getAddress",
     *     security={{"bearerAuth": {}}},
     *     tags={"Address"},
     *     @OA\Response(
     *         response=200,
     *         description="Address retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user_id", type="integer", description="The user ID"),
     *             @OA\Property(property="alamat", type="string", description="User's address"),
     *             @OA\Property(property="kota", type="string", description="City"),
     *             @OA\Property(property="provinsi", type="string", description="Province"),
     *             @OA\Property(property="kecamatan", type="string", description="District"),
     *             @OA\Property(property="kelurahan", type="string", description="Subdistrict"),
     *             @OA\Property(property="kode_pos", type="string", description="Postal code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized, invalid token"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Address not found"
     *     )
     * )
     */
    public function getAddress()
    {
        $user = JWTAuth::parseToken()->authenticate();

        $address = Address::where('user_id', $user->user_id)->first();

        return response()->json($address);
    }

    /**
     * @OA\Put(
     *     path="/api/address",
     *     summary="Update User Address",
     *     description="Update the address of the authenticated user",
     *     operationId="updateAddress",
     *     security={{"bearerAuth": {}}},
     *     tags={"Address"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="alamat", type="string", description="User's address"),
     *             @OA\Property(property="kota", type="string", description="City"),
     *             @OA\Property(property="provinsi", type="string", description="Province"),
     *             @OA\Property(property="kecamatan", type="string", description="District"),
     *             @OA\Property(property="kelurahan", type="string", description="Subdistrict"),
     *             @OA\Property(property="kode_pos", type="string", description="Postal code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Address updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Address updated successfully"),
     *             @OA\Property(property="address", type="object", ref="#/components/schemas/Address")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request, validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized, invalid token"
     *     )
     * )
     */
    public function updateAddress(Request $request)
    {
        $validated = $request->validate([
            'alamat' => 'required|string',
            'kota' => 'required|string',
            'provinsi' => 'required|integer', // or 'exists:provinces,id' if it's an ID
            'kecamatan' => 'nullable|string',
            'kelurahan' => 'nullable|string',
            'kode_pos' => 'nullable|string|max:10',
        ]);
    
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (Exception $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        $address = Address::firstOrNew(['user_id' => $user->user_id]);
    
        $address->fill($validated);
    
        if ($address->save()) {
            return response()->json(['message' => 'Address updated successfully', 'address' => $address]);
        } else {
            return response()->json(['error' => 'Failed to update address'], 500);
        }
    }

    public function getAllAddresses()
{
    $addresses = Address::with('user')->get();

    return response()->json([
        'status' => 'success',
        'data' => $addresses,
    ]);
}

public function getAddressByUserId(Request $request, $userId)
{
    // Mencari address berdasarkan user_id
    $address = Address::where('user_id', $userId)->get();

    // Cek apakah address ditemukan
    if ($address->isEmpty()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Address not found for the given user_id.',
        ], 404);
    }

    return response()->json([
        'status' => 'success',
        'data' => $address,
    ]);
}



    
}
