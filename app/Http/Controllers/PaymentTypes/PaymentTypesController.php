<?php

namespace App\Http\Controllers\PaymentTypes;

use App\Models\PaymentTypes;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Inertia\Inertia;

class PaymentTypesController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $t = PaymentTypes::query()->first();
            $query = PaymentTypes::query();
            $query = $this->filterData($query, $t);
            $datos = $query->with('proofPayments')
                ->get();
            // dd($datos);
            $from = request()->wantsJson() ? 'api' : 'web';
            return $this->showAll($datos, $from, 'PaymentTypes/index', 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo obtener los datos']);
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
            $reglas = [
                'payment_type_desc' => 'required'
            ];
            $request->validate($reglas);
            $paymentTypes = PaymentTypes::create($request->all());
            return response()->json(['message' => 'Registro creado con exito', 'data' => $paymentTypes], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => $e,
                'message' => 'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo crear el registro'],400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PaymentTypes  $paymentTypes
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function show($id)
    {
        try {
            $paymentTypes = PaymentTypes::findOrFail($id);
            $audits = $paymentTypes->audits;
            if (request()->wantsJson()) {
                return $this->showOne($paymentTypes, $audits, 200);
            }
            return Inertia::render('PaymentTypes/show', ['paymentTypes' => $paymentTypes, 'audits' => $audits]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo obtener los datos'],500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PaymentTypes  $paymentTypes
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function update(Request $request, $id)
    {
        try {
            $reglas = [
                'payment_type_desc' => 'required'
            ];
            $request->validate($reglas);
            $paymentTypes = PaymentTypes::findOrFail($id);
            $paymentTypes->update($request->all());
            if (request()->wantsJson()) {
                return response()->json(['message' => 'Registro Actualizado con exito', 'data' => $paymentTypes], 200);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo actualizar el registro'],400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PaymentTypes  $paymentTypes
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $paymentTypes = PaymentTypes::findOrFail($id);
            $paymentTypes->delete();
            return response()->json(['message' => 'Eliminado con exito']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo eliminar el registro'],500);
        }
    }
}
