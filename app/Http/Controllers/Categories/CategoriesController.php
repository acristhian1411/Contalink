<?php

namespace App\Http\Controllers\Categories;

use App\Models\Categories;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use App\Http\Requests\SecureBasicRequest;
use Inertia\Inertia;

class CategoriesController extends ApiController
{
    /**
     * @brief Retrieve and display a paginated listing of categories with filtering and sorting capabilities
     * 
     * Fetches all categories from the database with support for filtering, sorting, and pagination.
     * Returns data in JSON format for API requests or renders Inertia view for web requests.
     * 
     * @return \Illuminate\Http\JsonResponse|\Inertia\Response JSON response for API requests or Inertia response for web
     * @throws \Exception When database query fails or data retrieval encounters errors
     */
    public function index()
    {
        try{
            $t = Categories::query()->first();
            $query = Categories::query();
            $query = $this->filterData($query, $t);
            $datos = $query->get();
            $from = request()->wantsJson() ? 'api' : 'web';
            return $this->showAll($datos,$from, 'Categories/index', 200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos']);
        }
    }

    /**
     * @brief Create and store a new category record in database
     * 
     * Validates and creates a new category with the provided category description.
     * Returns success message with created data or validation errors.
     * 
     * @param \App\Http\Requests\SecureBasicRequest $request HTTP request containing cat_desc (required, validated)
     * @return \Illuminate\Http\JsonResponse JSON response with success message and created category data
     * @throws \Illuminate\Validation\ValidationException When validation rules fail for required fields
     * @throws \Exception When database creation fails or other unexpected errors occur
     */
    public function store(SecureBasicRequest $request)
    {
        try{
            $categories = Categories::create($request->validated());
            return response()->json(['message'=>'Registro creado con exito','data'=>$categories],201);
        }
        catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'error'=>$e,
                'message'=>'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null 
            ], 422);
        }
        catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo crear el registro']);
        }
    }

    /**
     * @brief Display a specific category record with its audit history
     * 
     * Retrieves a single category by ID along with its audit trail. Returns JSON response
     * for API requests or renders Inertia view for web interface display.
     * 
     * @param int|string $id The unique identifier of the category to retrieve
     * @return \Illuminate\Http\JsonResponse|\Inertia\Response JSON response for API or Inertia response for web
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When category with given ID is not found
     * @throws \Exception When data retrieval fails or other unexpected errors occur
     */
    public function show($id)
    {
        try{
            $categories = Categories::findOrFail($id);
            $audits = $categories->audits;
            if(request()->wantsJson()){
                return $this->showOne($categories,$audits,200);
            }
            return Inertia::render('Categories/show', ['category' => $categories,'audits'=>$audits]);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos']);
        }
    }

    /**
     * @brief Update an existing category record with validated data
     * 
     * Validates the incoming request data and updates the specified category record
     * with new category description. Returns success response with updated data.
     * 
     * @param \App\Http\Requests\SecureBasicRequest $request HTTP request containing updated cat_desc (required, validated)
     * @param int|string $id The unique identifier of the category to update
     * @return \Illuminate\Http\JsonResponse JSON response with success message and updated category data
     * @throws \Illuminate\Validation\ValidationException When validation rules fail for required fields
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When category with given ID is not found
     * @throws \Exception When database update fails or other unexpected errors occur
     */
    public function update(SecureBasicRequest $request, $id)
    {
        try{
            $categories = Categories::findOrFail($id);
            $categories->update($request->validated());
            return response()->json(['message'=>'Registro Actualizado con exito','data'=>$categories],200);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'error'=>$e->getMessage(),
                'message'=>'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null 
            ],422);
        }
        catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo actualizar el registro']);
        }
    }

    /**
     * @brief Delete a category record from the database
     * 
     * Soft deletes the specified category record by ID. Returns success message
     * upon successful deletion or error message if deletion fails.
     * 
     * @param int|string $id The unique identifier of the category to delete
     * @return \Illuminate\Http\JsonResponse JSON response with success or error message
     * @throws \Exception When database deletion fails or category record cannot be found
     */
    public function destroy($id)
    {
        try{
            $categories = Categories::findOrFail($id);
            $categories->delete();
            return response()->json(['message'=>'Eliminado con exito']);
        }
        catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo eliminar el registro']);
        }
    }
}
