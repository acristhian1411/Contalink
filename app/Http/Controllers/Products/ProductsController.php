<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Web\WebController;
use App\Models\Products;
use App\Models\MeasurementUnit;
use App\Validators\QuantityValidator;
use Illuminate\Http\Request;
use App\Http\Requests\SecureProductRequest;
use Inertia\Inertia;

class ProductsController extends WebController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            try {
                $t = Products::query()->first();
                $query = $this->filterData(Products::query(), $t);
                $datos = $query->join('categories', 'products.category_id', '=', 'categories.id')
                    ->join('iva_types', 'products.iva_type_id', '=', 'iva_types.id')
                    ->join('brands', 'products.brand_id', '=', 'brands.id')
                    ->leftJoin('measurement_units', 'products.measurement_unit_id', '=', 'measurement_units.id')
                    ->select('products.*', 'iva_types.iva_type_desc', 'iva_types.iva_type_percent',
                        'categories.cat_desc', 'brands.brand_name',
                        'measurement_units.unit_name', 'measurement_units.unit_abbreviation',
                        'measurement_units.allows_decimals')
                    ->get();

                return $this->showAll($datos, 'api', '', 200);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo obtener los datos'], 500);
            }
        }

        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
            'sort_by' => 'nullable|in:id,product_name,product_desc,category_id,brand_id,product_quantity,product_cost_price,product_selling_price',
            'order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|in:5,10,20,50,100',
        ]);

        $items = Products::query()
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('iva_types', 'products.iva_type_id', '=', 'iva_types.id')
            ->join('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('measurement_units', 'products.measurement_unit_id', '=', 'measurement_units.id')
            ->select('products.*', 'iva_types.iva_type_desc', 'iva_types.iva_type_percent',
                'categories.cat_desc', 'brands.brand_name',
                'measurement_units.unit_name', 'measurement_units.unit_abbreviation',
                'measurement_units.allows_decimals')
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('products.product_name', 'ilike', "%{$v}%")
                        ->orWhere('products.product_barcode', 'ilike', "%{$v}%")
                        ->orWhere('products.product_desc', 'ilike', "%{$v}%");
                });
            })
            ->orderBy('products.' . ($filters['sort_by'] ?? 'id'), $filters['order'] ?? 'asc')
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return Inertia::render('Products/index', [
            'items' => $items,
            'filters' => $request->only(['search', 'sort_by', 'order', 'per_page']),
            'staticData' => $this->getStaticData(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SecureProductRequest $request)
    {
        try {
            $quantityError = $this->validateQuantity($request->measurement_unit_id, $request->product_quantity);
            if ($quantityError) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'error' => $quantityError,
                        'message' => 'La cantidad no es válida para la unidad de medida seleccionada',
                    ], 422);
                }

                return back()->withErrors(['product_quantity' => $quantityError]);
            }

            $product = Products::create($request->validated());
            $product->load('measurementUnit');

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Registro creado con exito', 'data' => $product]);
            }

            return back()->with('success', 'Producto creado con éxito');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo crear registro'], 400);
            }

            return back()->withErrors(['general' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $product = Products::where('products.id', $id)
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->join('iva_types', 'products.iva_type_id', '=', 'iva_types.id')
                ->join('brands', 'products.brand_id', '=', 'brands.id')
                ->leftJoin('measurement_units', 'products.measurement_unit_id', '=', 'measurement_units.id')
                ->select('products.*', 'iva_types.iva_type_desc', 'iva_types.iva_type_percent',
                    'categories.cat_desc', 'brands.brand_name',
                    'measurement_units.unit_name', 'measurement_units.unit_abbreviation',
                    'measurement_units.allows_decimals')
                ->firstOrFail();

            $audits = $product->audits;

            if (request()->wantsJson()) {
                return $this->showOne($product, $audits, 200);
            }

            return Inertia::render('Products/show', ['product' => $product, 'audits' => $audits]);
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo obtener los datos'], 500);
            }

            return back()->withErrors(['general' => $e->getMessage()]);
        }
    }

    public function updatePriceAndQty(Request $req)
    {
        try {
            $rules = [
                'controller' => 'required|string',
                'details' => 'required|array',
                'details.*.product_cost_price' => 'required|numeric',
                'details.*.product_quantity' => 'required|numeric',
            ];
            $req->validate($rules);

            foreach ($req->details as $key => $value) {
                $product = Products::with('measurementUnit')->findOrFail($value['id']);

                if ($product->measurementUnit && !QuantityValidator::validate($value['product_quantity'], $product->measurementUnit)) {
                    $errorMessage = QuantityValidator::getErrorMessage($value['product_quantity'], $product->measurementUnit);
                    throw new \Exception("Producto {$product->product_name}: {$errorMessage}");
                }

                $product->product_cost_price = $value['product_cost_price'];

                $quantity = floatval($value['product_quantity']);

                if ($req->controller == 'purchase') {
                    $product->product_quantity += $quantity;
                } else if ($req->controller == 'sales') {
                    $product->product_quantity -= $quantity;
                } else if ($req->controller == 'sales_reversal') {
                    $product->product_quantity += $quantity;
                } else if ($req->controller == 'purchases_reversal') {
                    $product->product_quantity -= $quantity;
                }
                $product->save();
            }
            if ($req->has('fromController') && $req->fromController == true) {
                return true;
            }

            return response()->json(['message' => 'Actualizado con éxito']);
        } catch (\Exception $e) {
            if ($req->has('fromController') && $req->fromController == true) {
                return false;
            }

            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Ocurrió un error mientras se procesaba lo datos',
            ], 400);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SecureProductRequest $request, int $id)
    {
        try {
            $quantityError = $this->validateQuantity($request->measurement_unit_id, $request->product_quantity);
            if ($quantityError) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'error' => $quantityError,
                        'message' => 'La cantidad no es válida para la unidad de medida seleccionada',
                    ], 422);
                }

                return back()->withErrors(['product_quantity' => $quantityError]);
            }

            $product = Products::findOrFail($id);
            $product->update($request->validated());
            $product->load('measurementUnit');

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Registro Actualizado con exito', 'data' => $product]);
            }

            return back()->with('success', 'Producto actualizado con éxito');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo actualizar los datos'], 500);
            }

            return back()->withErrors(['general' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $product = Products::findOrFail($id);
            $product->delete();

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Eliminado con exito']);
            }

            return back()->with('success', 'Producto eliminado con éxito');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo eliminar los datos'], 500);
            }

            return back()->withErrors(['general' => $e->getMessage()]);
        }
    }

    /**
     * Search for products with authentication and permissions
     */
    public function search(Request $request)
    {
        try {
            $request->validate([
                'q' => 'required|string|min:2|max:100',
                'category_id' => 'nullable|integer|exists:categories,id',
                'brand_id' => 'nullable|integer|exists:brands,id',
                'in_stock' => 'nullable|boolean',
                'limit' => 'nullable|integer|min:1|max:50',
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

            if ($request->has('category_id') && $request->category_id) {
                $query->where('products.category_id', $request->category_id);
            }

            if ($request->has('brand_id') && $request->brand_id) {
                $query->where('products.brand_id', $request->brand_id);
            }

            if ($request->has('in_stock') && $request->in_stock) {
                $query->where('products.product_quantity', '>', 0);
            }

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
                'count' => $results->count(),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Los parámetros de búsqueda no son válidos',
                'details' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'No se pudo realizar la búsqueda',
            ], 500);
        }
    }

    /**
     * Returns the quantity validation error message, or null when valid.
     */
    private function validateQuantity($measurementUnitId, $quantity): ?string
    {
        if (!$measurementUnitId) {
            return null;
        }

        $unit = MeasurementUnit::find($measurementUnitId);

        if ($unit && !QuantityValidator::validate($quantity, $unit)) {
            return QuantityValidator::getErrorMessage($quantity, $unit);
        }

        return null;
    }
}
