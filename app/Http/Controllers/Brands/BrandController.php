<?php

namespace App\Http\Controllers\Brands;
use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use App\Models\Brand;
use Inertia\Inertia;

class BrandController extends ApiController
{
    /**
     * @brief Retrieve and display a paginated listing of brands with filtering and sorting capabilities
     * 
     * Fetches all brands from the database with support for filtering, sorting, and pagination.
     * Returns data in JSON format for API requests or renders Inertia view for web requests.
     * 
     * @return \Illuminate\Http\JsonResponse|\Inertia\Response JSON response for API requests or Inertia response for web
     * @throws \Exception When database query fails or data retrieval encounters errors
     */
    public function index()
    {
        try{
            $t = Brand::query()->first();
            $query = Brand::query();
            $query = $this->filterData($query, $t);
            $datos = $query->get();
            $from = request()->wantsJson() ? 'api' : 'web';
            return $this->showAll($datos,$from, 'Brands/index',200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos']);
        }
    }

    /**
     * @brief Create and store a new brand record in database
     * 
     * Validates and creates a new brand with the provided brand name.
     * Returns success message with created data or validation errors.
     * 
     * @param \Illuminate\Http\Request $request HTTP request containing brand_name (required, max 255 chars)
     * @return \Illuminate\Http\JsonResponse JSON response with success message and created brand data
     * @throws \Illuminate\Validation\ValidationException When validation rules fail for required fields
     * @throws \Exception When database creation fails or other unexpected errors occur
     */
    public function store(Request $request)
    {
        try{
            $rules = [
                'brand_name' => 'required|string|max:255',
            ];
            $request->validate($rules);
            $brand = Brand::create($request->all());
            return response()->json(['message'=>'Registro creado con exito','data'=>$brand],201);
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
     * @brief Display a specific brand record with its audit history
     * 
     * Retrieves a single brand by ID along with its audit trail. Returns JSON response
     * for API requests or renders Inertia view for web interface display.
     * 
     * @param int|string $id The unique identifier of the brand to retrieve
     * @return \Illuminate\Http\JsonResponse|\Inertia\Response JSON response for API or Inertia response for web
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When brand with given ID is not found
     * @throws \Exception When data retrieval fails or other unexpected errors occur
     */
    public function show($id)
    {
        try{
            $brand = Brand::findOrFail($id);
            $audits = $brand->audits;
            if(request()->wantsJson()){
                return $this->showOne($brand,$audits,200);
            }
            return Inertia::render('Brands/show', ['brand' => $brand,'audits'=>$audits]);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos']);
        }
    }

    /**
     * @brief Update an existing brand record with validated data
     * 
     * Validates the incoming request data and updates the specified brand record
     * with new brand name. Returns success response with updated data.
     * 
     * @param \Illuminate\Http\Request $request HTTP request containing updated brand_name (required, max 255 chars)
     * @param int|string $id The unique identifier of the brand to update
     * @return \Illuminate\Http\JsonResponse JSON response with success message and updated brand data
     * @throws \Illuminate\Validation\ValidationException When validation rules fail for required fields
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When brand with given ID is not found
     * @throws \Exception When database update fails or other unexpected errors occur
     */
    public function update(Request $request, $id)
    {
        try{
            $rules = [
                'brand_name' => 'required|string|max:255',
            ];
            $request->validate($rules);
            $brand = Brand::findOrFail($id);
            $brand->update($request->all());
            return response()->json(['message'=>'Registro Actualizado con exito','data'=>$brand],200);
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
     * @brief Delete a brand record from the database
     * 
     * Soft deletes the specified brand record by ID. Returns success message
     * upon successful deletion or error message if deletion fails.
     * 
     * @param int|string $id The unique identifier of the brand to delete
     * @return \Illuminate\Http\JsonResponse JSON response with success or error message
     * @throws \Exception When database deletion fails or brand record cannot be found
     */
    public function destroy($id)
    {
        try{
            $brand = Brand::findOrFail($id);
            $brand->delete();
            return response()->json(['message'=>'Eliminado con exito']);
        }
        catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo eliminar el registro']);
        }
    }
}
