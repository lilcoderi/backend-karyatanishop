<?php

namespace App\Http\Controllers;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{



    /**
     * @OA\Get(
     *     path="/api/users",
     *     summary="Get a list of users",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of users",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/User")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $data = User::with('roles')->latest()->paginate(5);

        return response()->json([
            'data' => $data
        ]);
    }

    public function create(): JsonResponse
    {
        $roles = Role::pluck('name', 'name')->all();

        return response()->json([
            'roles' => $roles
        ]);
    }

/**
 * @OA\Post(
 *     path="/api/users-search",
 *     summary="Search users by name",
 *     description="Mencari pengguna berdasarkan nama. Jika parameter `name` tidak diberikan, maka akan mengembalikan semua pengguna.",
 *     tags={"Users"},
 *     security={{ "bearerAuth": {} }},
 *     @OA\Parameter(
 *         name="name",
 *         in="query",
 *         required=false,
 *         description="Nama pengguna yang akan dicari",
 *         @OA\Schema(
 *             type="string",
 *             example="Riana"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Hasil pencarian pengguna",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="current_page", type="integer", example=1),
 *                 @OA\Property(property="data", type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="user_id", type="string", example="d9b68e60-ad24-4599-9ca4-8840f921bb6e"),
 *                         @OA\Property(property="nama", type="string", example="Riana"),
 *                         @OA\Property(property="email", type="string", example="riananur28@gmail.com"),
 *                         @OA\Property(property="no_tlp", type="string", example=null),
 *                         @OA\Property(property="is_verified", type="integer", example=1),
 *                         @OA\Property(property="status", type="integer", example=1),
 *                         @OA\Property(property="foto", type="string", example=null),
 *                         @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-24T13:31:41.000000Z"),
 *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-24T13:34:53.000000Z"),
 *                         @OA\Property(property="deleted_at", type="string", example=null),
 *                         @OA\Property(property="role", type="string", example="kasir"),
 *                         @OA\Property(
 *                             property="roles",
 *                             type="array",
 *                             @OA\Items(
 *                                 type="object",
 *                                 @OA\Property(property="id", type="integer", example=1),
 *                                 @OA\Property(property="name", type="string", example="kasir"),
 *                                 @OA\Property(property="guard_name", type="string", example="api"),
 *                                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-20T15:17:36.000000Z"),
 *                                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-21T10:18:13.000000Z"),
 *                                 @OA\Property(
 *                                     property="pivot",
 *                                     type="object",
 *                                     @OA\Property(property="model_id", type="string", example="d9b68e60-ad24-4599-9ca4-8840f921bb6e"),
 *                                     @OA\Property(property="role_id", type="integer", example=1)
 *                                 )
 *                             )
 *                         )
 *                     )
 *                 ),
 *                 @OA\Property(property="total", type="integer", example=10),
 *                 @OA\Property(property="per_page", type="integer", example=5),
 *                 @OA\Property(property="last_page", type="integer", example=2),
 *                 @OA\Property(property="from", type="integer", example=1),
 *                 @OA\Property(property="to", type="integer", example=5)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad request",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="The name must be a string.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Unauthorized.")
 *         )
 *     )
 * )
 */



    public function searchByName(Request $request): JsonResponse
{
    // Validasi input pencarian
    $request->validate([
        'name' => 'nullable|string|max:255'
    ]);

    // Ambil parameter pencarian dari request
    $name = $request->input('name');

    // Query user dengan filter berdasarkan nama
    $query = User::with('roles')->latest();

    if ($name) {
        $query->where('nama', 'LIKE', '%' . $name . '%');
    }

    $data = $query->paginate(5);

    // Kembalikan hasil pencarian
    return response()->json([
        'data' => $data,
    ]);
}


    /**
     * @OA\Post(
     *     path="/api/users",
     *     summary="Create a new user",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"nama", "email", "password", "roles"},
     *                 @OA\Property(property="nama", type="string", example="User"),
     *                 @OA\Property(property="email", type="string", example="user@gmail.com"),
     *                 @OA\Property(property="password", type="string", example="password123"),
     *                 @OA\Property(property="confirm-password", type="string", example="password123"),
     *                 @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"admin"})
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
{
    // Validasi input
    $validator = Validator::make($request->all(), [
        'nama' => 'required',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:8', // Menghilangkan validasi 'same:confirm-password'
        'roles' => 'required'
    ]);

    // Jika validasi gagal, return error
    if ($validator->fails()) {
        return response()->json([
            'error' => $validator->errors()
        ], 422);
    }

    // Mengambil data input dari request
    $input = $request->all();
    // Hash password
    $input['password'] = Hash::make($input['password']);
    // Menambahkan nilai default untuk kolom is_verified
    $input['is_verified'] = 1;

    // Membuat pengguna baru
    $user = User::create($input);
    // Menetapkan roles ke pengguna
    $user->assignRole($request->input('roles'));

    // Mengembalikan response sukses
    return response()->json([
        'success' => true,
        'message' => 'User created successfully',
        'data' => $user
    ], 201);
}



    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     summary="Get user by ID",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User found",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function show($id): JsonResponse
{
    $user = User::with('roles')->find($id); // Load the user along with their roles

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    return response()->json([
        'data' => $user
    ]);
}


    /**
     * Show the form for editing the specified user (For API, this will return the user data).
     */
    public function edit($id): JsonResponse
    {
        $user = User::where('user_id', $id)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $roles = Role::pluck('name', 'name')->all();
        $userRole = $user->roles->pluck('name', 'name')->all();

        return response()->json([
            'user' => $user,
            'roles' => $roles,
            'user_role' => $userRole
        ]);
    }


    /**
     * @OA\Put(
     *     path="/api/users/{id}",
     *     summary="Update an existing user",
     *     description="Update the details of an existing user. Requires JWT authentication.",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the user to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="nama", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="password", type="string"),
     *                 @OA\Property(property="roles", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="object")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required',
            'email' => 'required|email|unique:users,email,' . $id . ',user_id',
            'password' => 'string|min:8',
            'roles' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()
            ], 422);
        }

        $input = $request->all();

        if (!empty($input['password'])) {
            $input['password'] = Hash::make($input['password']);
        } else {
            $input = Arr::except($input, ['password']);
        }

        $user = User::where('user_id', $id)->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->update($input);

        $user->roles()->detach();
        $user->assignRole($request->input('roles'));

        // Ambil role pertama yang terkait dan simpan di kolom 'role' di tabel 'users'
        $assignedRole = $user->roles()->first();
        if ($assignedRole) {
            $user->role = $assignedRole->name;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     summary="Delete an existing user",
     *     description="Delete a user by ID. Requires JWT authentication.",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the user to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function destroy($id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/profile",
     *     summary="Get user profile",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     )
     * )
     */
    public function getProfile()
{
    try {
        // Ambil user yang sedang login
        $user = JWTAuth::parseToken()->authenticate();
        $user->load('roles'); // Muat relasi roles

        // Hitung jumlah roles
        $rolesCount = $user->roles->count();

        // Tentukan apakah admin icon ditampilkan
        $showAdminIcon = $rolesCount > 1 || !$user->roles->contains('name', 'customer');

        // Sertakan informasi tambahan dalam respons
        $profileData = $user->toArray();
        $profileData['show_admin_icon'] = $showAdminIcon;

        return response()->json($profileData);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Gagal mendapatkan profil pengguna.',
            'error' => $e->getMessage(),
        ], 500);
    }
}



    /**
     * @OA\Put(
     *     path="/api/profile",
     *     summary="Update user profile",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="nama", type="string", example="John Doe"),
     *                 @OA\Property(property="no_tlp", type="string", example="123456789"),
     *                 @OA\Property(property="foto", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     )
     * )
     */



     public function updateProfile(Request $request)
     {
         Log::info('Request to update profile received.', [
             'request_data' => $request->all(),
         ]);

         try {
             // Validasi input
             $validated = $request->validate([
                 'nama' => 'nullable|string|max:255',
                 'no_tlp' => 'nullable|string|max:15',
                 'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
             ]);

             Log::info('Validation successful.', [
                 'validated_data' => $validated,
             ]);

             // Ambil user yang sedang terautentikasi menggunakan JWT
             $user = JWTAuth::parseToken()->authenticate();
             Log::info('User authenticated.', ['user_id' => $user->id]);

             // Update data user kecuali foto
             $user->update([
                 'nama' => $validated['nama'] ?? $user->nama,
                 'no_tlp' => $validated['no_tlp'] ?? $user->no_tlp,
             ]);

             Log::info('User profile updated without photo.', ['user_id' => $user->id]);

             // Jika ada file foto yang diunggah
             if ($request->hasFile('foto')) {
                 // Simpan file di disk 'public/foto_profil'
                 $fotoPath = $request->file('foto')->store('foto_profil', 'public');
                 $user->update(['foto' => 'storage/' . $fotoPath]);

                 Log::info('Profile picture updated.', [
                     'user_id' => $user->id,
                     'foto_path' => $fotoPath,
                 ]);
             }

             // Kembalikan response dengan data user yang telah diperbarui
             return response()->json([
                 'message' => 'Profile updated successfully',
                 'user' => $user,
             ]);

         } catch (\Illuminate\Validation\ValidationException $e) {
             // Tangkap kesalahan validasi
             Log::error('Validation error during profile update.', [
                 'errors' => $e->errors(),
             ]);
             return response()->json([
                 'message' => 'Validation error',
                 'errors' => $e->errors(),
             ], 422);
         } catch (\Exception $e) {
             // Tangkap kesalahan umum
             Log::error('Error occurred during profile update.', [
                 'error_message' => $e->getMessage(),
                 'stack_trace' => $e->getTraceAsString(),
             ]);
             return response()->json([
                 'message' => 'An error occurred while updating profile.',
             ], 500);
         }
     }



}
