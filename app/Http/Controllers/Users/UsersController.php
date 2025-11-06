<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\ApiController;
use App\Http\Requests\SecureUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UsersController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $t = User::query()->first();
            $query = User::query();
            $query = $this->filterData($query, $t);
            $datos = $query->get();
            $from = request()->wantsJson() ? 'api' : 'web';

            // dd($datos);
            return $this->showAll($datos, $from, 'Users/index', 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo obtener los datos'], 500);
        }
    }

    /**
     * Asignar un rol a un usuario.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignRole(Request $request, $id)
    {
        try {
            // Validar la solicitud
            $validatedData = $request->validate([
                'role' => 'required|string|exists:roles,name', // Asegúrate de que el rol exista
            ]);

            // Encontrar el usuario por ID
            $user = User::findOrFail($id);

            // Asignar el rol al usuario
            $user->assignRole($validatedData['role']);

            // Retornar una respuesta de éxito
            return response()->json(['message' => 'Rol asignado con éxito', 'data' => $user]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null,
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo asignar el rol'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(SecureUserRequest $request)
    {
        try {
            $user = User::create($request->validated());

            return response()->json(['message' => 'Registro creado con exito', 'data' => $user]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // dd($e);
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null,
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo crear registro'], 400);
        }
    }

    /**
     * @brief Display the specified user with audit history
     *
     * Retrieves a user by ID and returns either JSON response for API requests
     * or renders the Inertia view for web requests with user data and audit trail.
     *
     * @param  int  $id  The user ID to retrieve
     * @return \Illuminate\Http\JsonResponse|\Inertia\Response JSON response for API requests or Inertia response for web requests
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When user is not found
     * @throws \Exception When database or system errors occur
     */
    public function show($id)
    {
        try {
            $user = User::findOrFail($id);
            $audits = $user->audits;
            if (request()->wantsJson()) {
                return $this->showOne($user, $audits, 200);
            }

            return Inertia::render('Users/show', [
                'users' => $user,
                'audits' => $audits,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo obtener los datos'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(SecureUserRequest $request, $id)
    {
        try {
            // Update the user
            $User = User::findOrFail($id);
            $User->update($request->validated());

            // Return a success response
            return response()->json(['message' => 'Registro Actualizado con exito', 'data' => $User]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null,
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo actualizar los datos'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $User = User::findOrFail($id);
            $User->delete();

            return response()->json(['message' => 'Eliminado con exito']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo eliminar los datos'], 500);
        }
    }
}
