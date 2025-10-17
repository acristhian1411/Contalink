<?php

namespace App\Http\Controllers\Products;

use App\Models\Products;
use App\Models\MeasurementUnit;
use App\Validators\QuantityValidator;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

class ProductsController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try{
            $t = Products::query()->first();
            $query = Products::query();
            $query = $this->filterData($query, $t);
            $datos = $query->join('categories','products.category_id','=','categories.id')
            ->join('iva_types','products.iva_type_id','=','iva_types.id')
            ->join('brands','products.brand_id','=', 'brands.id')
            ->leftJoin('measurement_units','products.measurement_unit_id','=','measurement_units.id')
            ->select('products.*','iva_types.iva_type_desc','iva_types.iva_type_percent',
                     'categories.cat_desc', 'brands.brand_name',
                     'measurement_units.unit_name', 'measurement_units.unit_abbreviation',
                     'measurement_units.allows_decimals')
            ->get();
            $from = request()->wantsJson() ? 'api' : 'web';
            return $this->showAll($datos,$from,'Products/index', 200);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos'],500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try{
            $rules = [
                'product_name' => 'required|string|max:255',
                'product_desc' => 'nullable|string',
                'product_cost_price' => 'required|numeric|min:0',
                'product_quantity' => 'required|numeric|min:0',
                'product_selling_price' => 'required|numeric|min:0',
                'category_id' => 'required',
                'iva_type_id' => 'required',
                'brand_id' => 'required',
                'measurement_unit_id' => 'nullable|exists:measurement_units,id',
            ];
            
            $request->validate($rules);
            
            // Validar cantidad según la unidad de medida
            if ($request->has('measurement_unit_id') && $request->measurement_unit_id) {
                $unit = MeasurementUnit::find($request->measurement_unit_id);
                if ($unit && !QuantityValidator::validate($request->product_quantity, $unit)) {
                    $errorMessage = QuantityValidator::getErrorMessage($request->product_quantity, $unit);
                    return response()->json([
                        'error' => $errorMessage,
                        'message' => 'La cantidad no es válida para la unidad de medida seleccionada'
                    ], 422);
                }
            }
            
            $products = Products::create($request->all());
            
            // Cargar la relación de unidad de medida para la respuesta
            $products->load('measurementUnit');
            
            return response()->json(['message'=>'Registro creado con exito','data'=>$products]);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'error'=>$e->getMessage(),
                'message'=>'Los datos no son correctos',
                'details' => method_exists($e, 'errors') ? $e->errors() : null
            ],422);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo crear registro'],400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Products  $products
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function show($id)
    {
        try{
            $product = Products::where('products.id',$id)
            ->join('categories','products.category_id','=','categories.id')
            ->join('iva_types','products.iva_type_id','=','iva_types.id')
            ->join('brands','products.brand_id','=','brands.id')
            ->leftJoin('measurement_units','products.measurement_unit_id','=','measurement_units.id')
            ->select('products.*','iva_types.iva_type_desc','iva_types.iva_type_percent',
                     'categories.cat_desc', 'brands.brand_name',
                     'measurement_units.unit_name', 'measurement_units.unit_abbreviation',
                     'measurement_units.allows_decimals')
            ->first();
            $audits = $product->audits;
            if(request()->wantsJson()){
                return $this->showOne($product,$audits, 200);
            }
            return Inertia::render('Products/show',['product'=>$product,'audits'=>$audits]);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo obtener los datos'],500);
        }
    }

    public function updatePriceAndQty(Request $req){
        try{
            $rules = [
                'controller'=>'required|string',
                'details'=>'required|array',
                'details.*.product_cost_price' => 'required|numeric',
                'details.*.product_quantity'=> 'required|numeric',
            ];
            $req->validate($rules);
            
            foreach ($req->details as $key => $value) {
                $product = Products::with('measurementUnit')->findOrFail($value['id']);
                
                // Validar cantidad según la unidad de medida
                if ($product->measurementUnit && !QuantityValidator::validate($value['product_quantity'], $product->measurementUnit)) {
                    $errorMessage = QuantityValidator::getErrorMessage($value['product_quantity'], $product->measurementUnit);
                    throw new \Exception("Producto {$product->product_name}: {$errorMessage}");
                }
                
                $product->product_cost_price = $value['product_cost_price'];
                
                // Usar el valor decimal directamente en lugar de intval para soportar unidades decimales
                $quantity = floatval($value['product_quantity']);
                
                if($req->controller == 'purchase'){
                    $product->product_quantity += $quantity;
                }else if($req->controller == 'sales'){
                    $product->product_quantity -= $quantity;
                }else if($req->controller == 'sales_reversal'){
                    $product->product_quantity += $quantity;
                }else if($req->controller == 'purchases_reversal'){
                    $product->product_quantity -= $quantity;
                }
                $product->save();
            }
            if($req->has('fromController') && $req->fromController == true){
                return true;
            }
            return response()->json(['message'=>'Actualizado con éxito']);
        }catch (\Exception $e){
            if($req->has('fromController') && $req->fromController == true){
                return false;
            }
            return response()->json([
                'error' => $e->getMessage(), 
                'message'=>'Ocurrió un error mientras se procesaba lo datos'
            ], 400);
        }catch(\Illuminate\Validation\ValidationException $e){
            if($req->has('fromController') && $req->fromController == true){
                return false;
            }
            return response()->json([
                'error' => $e->errors(), 
                'message'=>'Ocurrió un error mientras se procesaba lo datos',
                'details'=>  method_exists($e, 'errors') ? $e->errors() : null 
            ], 400);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request the fields that will be updated
     * @param  int $id the id of the product
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request,int $id): JsonResponse
    {
        try{
            $reglas = [
                'product_name' => 'required|string|max:255',
                'product_desc' => 'required|string',
                'product_cost_price' => 'required|numeric|min:0',
                'product_quantity' => 'required|numeric|min:0',
                'product_selling_price' => 'required|numeric|min:0',
                'category_id' => 'required|integer',
                'iva_type_id' => 'required|integer',
                'brand_id' => 'required|integer',
                'measurement_unit_id' => 'nullable|exists:measurement_units,id'
            ];
            $request->validate($reglas);
            
            // Validar cantidad según la unidad de medida
            if ($request->has('measurement_unit_id') && $request->measurement_unit_id) {
                $unit = MeasurementUnit::find($request->measurement_unit_id);
                if ($unit && !QuantityValidator::validate($request->product_quantity, $unit)) {
                    $errorMessage = QuantityValidator::getErrorMessage($request->product_quantity, $unit);
                    return response()->json([
                        'error' => $errorMessage,
                        'message' => 'La cantidad no es válida para la unidad de medida seleccionada'
                    ], 422);
                }
            }
            
            $products = Products::findOrFail($id);
            $products->update($request->all());
            
            // Cargar la relación de unidad de medida para la respuesta
            $products->load('measurementUnit');
            
            return response()->json(['message'=>'Registro Actualizado con exito','data'=>$products]);
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
     * @param  \App\Models\Products  $products
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try{
            $products = Products::findOrFail($id);
            $products->delete();
            return response()->json(['message'=>'Eliminado con exito']);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo eliminar los datos'],500);
        }
    }
}
