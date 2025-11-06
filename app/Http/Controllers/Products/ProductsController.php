<?php

namespace App\Http\Controllers\Products;

use App\Models\Products;
use App\Models\MeasurementUnit;
use App\Validators\QuantityValidator;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use App\Http\Requests\SecureProductRequest;
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
    public function store(SecureProductRequest $request): JsonResponse
    {
        try{
            
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
            
            $products = Products::create($request->validated());
            
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
    public function update(SecureProductRequest $request,int $id): JsonResponse
    {
        try{
            
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
            $products->update($request->validated());
            
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

    /**
     * Search for products with authentication and permissions
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        try {
            $request->validate([
                'q' => 'required|string|min:2|max:100',
                'category_id' => 'nullable|integer|exists:categories,id',
                'brand_id' => 'nullable|integer|exists:brands,id',
                'in_stock' => 'nullable|boolean',
                'limit' => 'nullable|integer|min:1|max:50'
            ]);

            $query = Products::query()
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->join('iva_types', 'products.iva_type_id', '=', 'iva_types.id')
                ->join('brands', 'products.brand_id', '=', 'brands.id')
                ->leftJoin('measurement_units', 'products.measurement_unit_id', '=', 'measurement_units.id')
                ->select(
                    'products.*',
                    'iva_types.iva_type_desc',
                    'iva_types.iva_type_percent',
                    'categories.cat_desc',
                    'brands.brand_name',
                    'measurement_units.unit_name',
                    'measurement_units.unit_abbreviation',
                    'measurement_units.allows_decimals'
                );

            // Filter by category if provided
            if ($request->has('category_id') && $request->category_id) {
                $query->where('products.category_id', $request->category_id);
            }

            // Filter by brand if provided
            if ($request->has('brand_id') && $request->brand_id) {
                $query->where('products.brand_id', $request->brand_id);
            }

            // Filter by stock availability
            if ($request->has('in_stock') && $request->in_stock) {
                $query->where('products.product_quantity', '>', 0);
            }

            // Search across multiple fields
            $searchTerm = $request->q;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('products.product_name', 'ilike', "%{$searchTerm}%")
                  ->orWhere('products.product_desc', 'ilike', "%{$searchTerm}%")
                  ->orWhere('categories.cat_desc', 'ilike', "%{$searchTerm}%")
                  ->orWhere('brands.brand_name', 'ilike', "%{$searchTerm}%");
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
