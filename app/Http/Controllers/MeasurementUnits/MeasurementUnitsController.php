<?php

namespace App\Http\Controllers\MeasurementUnits;

use App\Http\Controllers\ApiController;
use App\Models\MeasurementUnit;
use App\Http\Requests\MeasurementUnitRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MeasurementUnitsController extends ApiController
{
    /**
     * Display a listing of the resource.
     * 
     * @OA\Get(
     *     path="/api/measurement-units",
     *     summary="Get list of measurement units",
     *     tags={"Measurement Units"},
     *     @OA\Response(response=200, description="Successful operation")
     * )
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $t = MeasurementUnit::query()->first();
            $query = MeasurementUnit::query();
            $query = $this->filterData($query, $t);
            $datos = $query->get();
            $from = request()->wantsJson() ? 'api' : 'web';
            return $this->showAll($datos, $from, 'Measurment/index', 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo obtener los datos']);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @OA\Post(
     *     path="/api/measurement-units",
     *     summary="Create a new measurement unit",
     *     tags={"Measurement Units"},
     *     @OA\Response(response=201, description="Measurement unit created successfully")
     * )
     *
     * @param  \App\Http\Requests\MeasurementUnitRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(MeasurementUnitRequest $request)
    {
        try {
            $measurementUnit = MeasurementUnit::create($request->validated());
            
            return response()->json([
                'message' => 'Unidad de medida creada con éxito',
                'data' => $measurementUnit
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'No se pudo crear la unidad de medida'
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function show($id)
    {
        try {
            $measurementUnit = MeasurementUnit::findOrFail($id);
            $audits = $measurementUnit->audits;
            
            if (request()->wantsJson()) {
                return $this->showOne($measurementUnit, $audits, 200);
            }
            
            return Inertia::render('Measurment/show', [
                'measurementUnit' => $measurementUnit,
                'audits' => $audits
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'No se pudo obtener la unidad de medida'
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\MeasurementUnitRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(MeasurementUnitRequest $request, $id)
    {
        try {
            $measurementUnit = MeasurementUnit::findOrFail($id);
            
            $measurementUnit->update($request->validated());
            
            return response()->json([
                'message' => 'Unidad de medida actualizada con éxito',
                'data' => $measurementUnit
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'No se pudo actualizar la unidad de medida'
            ]);
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
            $measurementUnit = MeasurementUnit::findOrFail($id);
            
            // Check if the unit can be deleted (no products using it)
            if (!$measurementUnit->canBeDeleted()) {
                return response()->json([
                    'error' => 'No se puede eliminar la unidad de medida',
                    'message' => 'Existen productos que utilizan esta unidad de medida. Desactívela en su lugar.'
                ], 422);
            }
            
            $measurementUnit->delete();
            
            return response()->json(['message' => 'Unidad de medida eliminada con éxito']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'No se pudo eliminar la unidad de medida'
            ]);
        }
    }

    /**
     * Activate a measurement unit.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function activate($id)
    {
        try {
            $measurementUnit = MeasurementUnit::findOrFail($id);
            
            $measurementUnit->update(['is_active' => true]);
            
            return response()->json([
                'message' => 'Unidad de medida activada con éxito',
                'data' => $measurementUnit
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'No se pudo activar la unidad de medida'
            ]);
        }
    }

    /**
     * Deactivate a measurement unit.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deactivate($id)
    {
        try {
            $measurementUnit = MeasurementUnit::findOrFail($id);
            
            $measurementUnit->update(['is_active' => false]);
            
            return response()->json([
                'message' => 'Unidad de medida desactivada con éxito',
                'data' => $measurementUnit
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'No se pudo desactivar la unidad de medida'
            ]);
        }
    }

    /**
     * Get only active measurement units.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function active()
    {
        try {
            $datos = MeasurementUnit::active()->get();
            $from = request()->wantsJson() ? 'api' : 'web';
            return $this->showAll($datos, $from, 'Measurment/active', 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'No se pudo obtener las unidades de medida activas'
            ]);
        }
    }
}