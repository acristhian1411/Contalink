<?php

namespace App\Http\Controllers\Countries;

use App\Models\Countries;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
class CountriesController extends ApiController
{
    /**
     * @brief Retrieve and display a paginated listing of countries with filtering and sorting capabilities
     * 
     * Fetches all countries from the database with support for filtering, sorting, and pagination.
     * Returns data in JSON format for API requests or renders Inertia view for web requests.
     * 
     * @return \Illuminate\Http\JsonResponse|\Inertia\Response JSON response for API requests or Inertia response for web
     * @throws \Exception When database query fails or data retrieval encounters errors
     */
    public function index()
    {
        try{
            $t = Countries::query()->first();
            $query = Countries::query();
            $query = $this->filterData($query, $t);
            $datos = $query
            ->get();
            $from = request()->wantsJson() ? 'api' : 'web';
            // dd($from);
            return $this->showAll($datos, $from, 'Countries/index', 200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'mesage'=>'No se pudo obtener los datos']);
        }
    }


    /**
     * @brief Create and store a new country record in database
     * 
     * Validates and creates a new country with the provided country name and country code.
     * Returns success message with created data.
     * 
     * @param \Illuminate\Http\Request $request HTTP request containing country_name and country_code
     * @return \Illuminate\Http\JsonResponse JSON response with success message and created country data
     * @throws \Illuminate\Validation\ValidationException When validation rules fail for required fields
     * @throws \Exception When database creation fails or other unexpected errors occur
     */
   public function store(Request $request)
    {
        try{
            $reglas = [
                'country_name' => 'required|string|max:255',
                'country_code' => 'required|string|max:255',
            ];
            $validated = $request->validate( $reglas);
            $dato = Countries::create($validated);
            return response()->json(['message'=>'Registro creado con exito','data'=>$dato],201);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'error'=>$e,
                'message'=>'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null 
            ],422);
        }
        catch(\Exception $e){
            return response()->json(['error'=> $e->getMessage(),'message'=>'No se pudo crear registro'],400);
        }
    }

    /**
     * @brief Display a specific country record with its audit history
     * 
     * Retrieves a single country by ID along with its audit trail. Returns JSON response
     * for API requests or renders Inertia view for web interface display.
     * 
     * @param int|string $id The unique identifier of the country to retrieve
     * @return \Illuminate\Http\JsonResponse|\Inertia\Response JSON response for API or Inertia response for web
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When country with given ID is not found
     * @throws \Exception When data retrieval fails or other unexpected errors occur
     */
    public function show($id)
    {
        try{
            $dato = Countries::findOrFail($id);
            $audits = $dato->audits;
            if(request()->wantsJson()){
                return $this->showOne($dato,$audits,200);
            }
            return Inertia::render('Countries/show', ['country' => $dato,'audits'=>$audits]);
        }
        catch(\Exception $e){
            return response()->json([
                'error'=>$e,
                'mesage'=>'No se pudo obtener los datos'
            ]);
        }
    }

    /**
     * @brief Update an existing country record with validated data
     * 
     * Validates the incoming request data and updates the specified country record
     * with new country name and code. Returns success response for API requests.
     * 
     * @param \Illuminate\Http\Request $request HTTP request containing updated country_name and country_code
     * @param int|string $id The unique identifier of the country to update
     * @return \Illuminate\Http\JsonResponse|void JSON response with success message for API requests
     * @throws \Illuminate\Validation\ValidationException When validation rules fail for required fields
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When country with given ID is not found
     * @throws \Exception When database update fails or other unexpected errors occur
     */
    public function update(Request $request, $id)
    {
        try{
            $reglas = [
                'country_name' => 'required|string|max:255',
                'country_code' => 'required|string|max:255',
            ];
            $validated = $request->validate($reglas);
            $dato = Countries::findOrFail($id);
            $dato->update($validated);
            if(request()->wantsJson()){
                return response()->json(['message'=>'Registro Actualizado con exito','data'=>$dato],200);
            }
        }catch(\Illuminate\Validation\ValidationException $e){
            // dd($e);
            return response()->json([
                'error'=>$e->getMessage(),
                'message'=>'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null 
            ],422);
        }
        catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'mesage'=>'No se pudo actualizar los datos']);
        }
    }

    /**
     * @brief Delete a country record from the database
     * 
     * Soft deletes the specified country record by ID. Returns success message
     * upon successful deletion or error message if deletion fails.
     * 
     * @param int|string $id The unique identifier of the country to delete
     * @return \Illuminate\Http\JsonResponse JSON response with success or error message
     * @throws \Exception When database deletion fails or country record cannot be found
     */
    public function destroy($id)
    {
        try{
            $dato = Countries::query()->find($id);
            $dato->delete();
            return response()->json(['message'=>'Eliminado con exito']);
        }
        catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'mesage'=>'No se pudo eliminar los datos']);
        }
    }
}
