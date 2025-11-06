<?php

namespace App\Http\Controllers\Persons;

use App\Models\Persons;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Inertia\Inertia;

class PersonsController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try{
            $t = Persons::query()->first();
            $query = Persons::query();
            $query = $this->filterData($query, $t);
            $datos = $query
            ->join('person_types','person_types.id','=','persons.p_type_id')
            ->join('countries','countries.id','=','persons.country_id')
            ->join('cities','cities.id','=','persons.city_id')
            ->select('persons.*','person_types.p_type_desc','countries.country_name','cities.city_name')
            ->get();
            return $this->showAll($datos,'api','', 200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),
        'message'=>'No se pudo obtener los datos'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try{
            $reglas = [
                'person_fname' => $request->p_type_id != 1 ? 'required|string|max:255':'',
                'person_lastname' => $request->p_type_id != 1 ? 'required|string|max:255':'',
                'person_corpname' => 'nullable|string|max:255',
                'person_idnumber' => $request->p_type_id != 1 ? 'required|string|max:50':'',
                'person_ruc' => 'nullable|string|max:50',
                'person_birtdate' => $request->p_type_id != 1 ? 'required|date':'',
                'person_address' => 'required|string|max:255',
                'p_type_id' => 'required|integer',
                'country_id' => 'required|integer',
                'city_id' => 'required|integer',
            ];
            $request->validate($reglas);
            $dato = Persons::create($request->all());
            return response()->json(['message'=>'Registro creado con exito','data'=>$dato]);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'error'=>$e->getMessage(),
                'message'=>'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null 
            ],422);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),
        'message'=>'No se pudo crear los datos'], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Persons  $persons
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try{
            $persons = Persons::findOrFail($id)
            ->join('person_types','person_types.id','=','persons.p_type_id')
            ->join('countries','countries.id','=','persons.country_id')
            ->join('cities','cities.id','=','persons.city_id')
            ->select('persons.*','person_types.p_type_desc','countries.country_name','cities.city_name')
            ->first();
            $audit = $persons->audits;
            if(request()->wantsJson()){
                return $this->showOne($persons, $audit, 200);
            }
            return Inertia::render('Persons/show', ['persons' => $persons, 'audit' => $audit]);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos'],500);
        }
    }

    //
    public function personByType($id){
        try{
        $persons = Persons::where('p_type_id', $id)->get();
        return $this->showAll($persons,'api','', 200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos'],500);
        }
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Persons  $persons
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try{
            $reglas = [
                'person_fname' => $request->p_type_id != 1 ? 'required|string|max:255':'',
                'person_lastname' => $request->p_type_id != 1 ? 'required|string|max:255':'',
                'person_corpname' => 'nullable|string|max:255',
                'person_idnumber' => $request->p_type_id != 1 ? 'required|string|max:50':'',
                'person_ruc' => 'nullable|string|max:50',
                'person_birtdate' => 'required|date',
                'person_address' => 'required|string|max:255',
                'p_type_id' => 'required|integer',
                'country_id' => 'required|integer',
                'city_id' => 'required|integer',
            ];
            $request->validate($reglas);
            $persons = Persons::find($id);
            $persons->update($request->all());
            return response()->json(['message'=>'Registro Actualizado con exito','data'=>$persons]);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'error'=>$e->getMessage(),
                'message'=>'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null 
            ],422);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo actualizar los datos'],500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Persons  $persons
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try{
            $persons = Persons::find($id);
            $persons->delete();
            return response()->json(['message'=>'Eliminado con exito']);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo eliminar los datos'],500);
        }
    }

    public function searchPerType(Request $request, $type){
        try{
            $person = Persons::where('p_type_id', $type)
            ->where(function ($query) use ($request) {
                $query->where('person_fname', 'ilike', "%$request->search%")
                    ->orWhere('person_ruc','ilike',"%$request->search%")
                    ->orWhere('person_idnumber','ilike',"%$request->search%")
                    ->orWhere('person_lastname','ilike', "%$request->search%");
            })
            ->get();
            return $this->showAll($person,'api','',200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos'],500);
        }
    }

    /**
     * @brief Search for persons (clients/providers) with dynamic filtering and authentication
     * 
     * Provides a secure search endpoint for finding persons by name, RUC, ID number, or corporate name.
     * Supports filtering by person type and result limiting. Requires proper authentication and permissions.
     *
     * @param  \Illuminate\Http\Request  $request Request containing search parameters (q, type, limit)
     * @return \Illuminate\Http\JsonResponse JSON response with search results, count, and success message
     * @throws \Illuminate\Validation\ValidationException When search parameters are invalid
     * @throws \Exception When database query fails or other system errors occur
     */
    public function search(Request $request)
    {
        try {
            $request->validate([
                'q' => 'required|string|min:2|max:100',
                'type' => 'nullable|integer|exists:person_types,id',
                'limit' => 'nullable|integer|min:1|max:50'
            ]);

            $query = Persons::query()
                ->join('person_types', 'person_types.id', '=', 'persons.p_type_id')
                ->join('countries', 'countries.id', '=', 'persons.country_id')
                ->join('cities', 'cities.id', '=', 'persons.city_id')
                ->select(
                    'persons.*',
                    'person_types.p_type_desc',
                    'countries.country_name',
                    'cities.city_name'
                );

            // Filter by person type if provided
            if ($request->has('type') && $request->type) {
                $query->where('persons.p_type_id', $request->type);
            }

            // Search across multiple fields
            $searchTerm = $request->q;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('persons.person_fname', 'ilike', "%{$searchTerm}%")
                  ->orWhere('persons.person_lastname', 'ilike', "%{$searchTerm}%")
                  ->orWhere('persons.person_corpname', 'ilike', "%{$searchTerm}%")
                  ->orWhere('persons.person_ruc', 'ilike', "%{$searchTerm}%")
                  ->orWhere('persons.person_idnumber', 'ilike', "%{$searchTerm}%");
            });

            $limit = $request->get('limit', 20);
            $results = $query->limit($limit)->get();

            return response()->json([
                'data' => $results,
                'message' => 'Búsqueda completada exitosamente',
                'count' => $results->count()
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Los parámetros de búsqueda no son válidos',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'No se pudo realizar la búsqueda'
            ], 500);
        }
    }
}
