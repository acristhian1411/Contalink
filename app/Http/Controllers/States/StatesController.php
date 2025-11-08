<?php

namespace App\Http\Controllers\States;

use App\Models\States;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Inertia\Inertia;


class StatesController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $t = States::query()->first();
            $query = States::query();
            $query = $this->filterData($query, $t)
                ->join('countries', 'states.country_id', '=', 'countries.id')
                ->select('states.*', 'countries.country_name');
            $datos = $query->get();
            $from = request()->wantsJson() ? 'api' : 'web';
            return $this->showAll($datos, $from, 'States/index', 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'mesage' => 'No se pudo obtener los datos']);
        }
    }

    /**
     * @brief Search for states by name or code with filtering capabilities
     * @param Request $req The HTTP request containing search parameters
     * @return \Illuminate\Http\JsonResponse|\Inertia\Response
     */
    public function search(Request $req)
    {
        try {
            $t = States::query()->first();
            $query = States::query();
            $query = $this->filterData($query, $t);
            if (empty($searchTerm)) {
                $datos = $query
                    ->get();
                $from = 'api';
                // dd($from);
                return $this->showAll($datos, $from, 'State/index', 200);
            }
            $query->where(function ($q) use ($searchTerm) {
                $q->where('state_name', 'LIKE', "%{$searchTerm}%")
                ;
            });
            $datos = $query
                ->get();
            $from = 'api';
            // dd($from);
            return $this->showAll($datos, $from, 'State/index', 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'mesage' => 'No se pudo obtener los datos']);
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
        try {
            $rules = [
                'state_name' => 'required',
                'country_id' => 'required',
            ];
            $validated = $request->validate($rules);

            $states = States::create($validated);
            return $this->showAfterAction($states, 'create', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo crear registro'], 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\States  $states
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $states = States::where('states.id', $id)
                ->join('countries', 'states.country_id', '=', 'countries.id')
                ->first();
            $audits = $states->audits;
            if (request()->wantsJson()) {
                return $this->showOne($states, $audits, 200);
            }
            return Inertia::render('States/show', ['state' => $states, 'audits' => $audits]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'mesage' => 'No se pudo obtener los datos'
            ]);
        }
    }

    //
    public function getStatesByCountry($id)
    {
        try {
            $states = States::where('country_id', $id)->get();
            return $this->showAll($states, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'mesage' => 'No se pudo obtener los datos'
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\States  $states
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $rules = [
                'state_name' => 'required',
                'country_id' => 'required',
            ];
            $validated = $request->validate($rules);
            $states = States::findOrFail($id);
            $states->update($validated);
            return $this->showAfterAction($states, 'update', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'mesage' => 'No se pudo actualizar los datos']);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\States  $states
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $states = States::findOrFail($id);
            $states->delete();
            return response()->json(['message' => 'Eliminado con exito']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'mesage' => 'No se pudo eliminar los datos']);
        }
    }
}
