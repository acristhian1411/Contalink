<?php

namespace App\Http\Controllers\Refunds;

use App\Models\Refunds;
use App\Models\RefundDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ApiController;
use App\Models\Products;
use App\Models\SalesDetails;
use Inertia\Inertia;

class RefundsController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $t = Refunds::query()->first();
            $query = Refunds::with([
                'sale.sales_details.product.measurementUnit',
                'refundDetails.product.measurementUnit'
            ]);
            $query = $this->filterData($query, $t);
            return $this->showAll($query->get(), 200);
        }

        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
            'sort_by' => 'nullable|in:id,refund_date,refund_status',
            'order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|in:5,10,20,50,100',
        ]);

        $items = Refunds::with(['sale.person'])
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->whereHas('sale', function ($s) use ($v) {
                    $s->where('sale_number', 'ilike', "%{$v}%")
                        ->orWhereHas('person', function ($p) use ($v) {
                            $p->where('person_fname', 'ilike', "%{$v}%")
                                ->orWhere('person_lastname', 'ilike', "%{$v}%");
                        });
                });
            })
            ->orderBy($filters['sort_by'] ?? 'id', $filters['order'] ?? 'asc')
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return Inertia::render('Refunds/index', [
            'items' => $items,
            'filters' => $request->only(['search', 'sort_by', 'order', 'per_page']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Refunds/form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'required|integer|exists:sales,id',
            'refund_date' => 'required|date',
            'refund_obs' => 'nullable|string',
            'refund_details' => 'required|array|min:1',
            'refund_details.*.product_id' => 'required|integer|exists:products,id',
            'refund_details.*.sd_qty_devuelto' => 'required|numeric|min:1',
        ]);

        try {
            DB::beginTransaction();

            // Validate refund quantities against original sale quantities
            $this->validateRefundQuantities($validated['sale_id'], $validated['refund_details']);

            $refund = Refunds::create([
                'sale_id' => $validated['sale_id'],
                'refund_date' => $validated['refund_date'],
                'refund_obs' => $validated['refund_obs'] ?? null,
                'refund_status' => 'pending',
            ]);

            // Process refund details and update inventory
            $refundDetailsData = [];
            foreach ($validated['refund_details'] as $detail) {
                $product = Products::with('measurementUnit')->findOrFail($detail['product_id']);
                $refundQuantity = $detail['sd_qty_devuelto'];

                // Validate quantity according to measurement unit
                if ($product->measurementUnit && !\App\Validators\QuantityValidator::validate($refundQuantity, $product->measurementUnit)) {
                    $errorMessage = \App\Validators\QuantityValidator::getErrorMessage($refundQuantity, $product->measurementUnit);
                    throw new \Exception("Producto {$product->product_name}: {$errorMessage}");
                }

                // Update product inventory (add back the refunded quantity)
                $product->increment('product_quantity', $refundQuantity);

                $refundDetailsData[] = [
                    'refund_id' => $refund->id,
                    'product_id' => $detail['product_id'],
                    'quantity' => $refundQuantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            RefundDetails::insert($refundDetailsData);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo crear el registro'], 422);
            }

            return back()->withErrors(['general' => $e->getMessage()]);
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Registro creado con exito', 'data' => $refund], 201);
        }

        return redirect()->route('refunds.index')->with('success', 'Devolución creada con éxito');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $refund = Refunds::with([
            'sale.sales_details.product.measurementUnit',
            'refundDetails.product.measurementUnit'
        ])->findOrFail($id);

        if ($request->wantsJson()) {
            return $this->showOne($refund, $refund->audits, 200);
        }

        return Inertia::render('Refunds/show', [
            'refund' => $refund,
            'audits' => $refund->audits,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'sale_id' => 'required|integer',
            'refund_date' => 'required|date',
            'refund_obs' => 'nullable|string',
            'refund_status' => 'required|string',
        ]);

        $refund = Refunds::findOrFail($id);
        $refund->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Registro Actualizado con exito', 'data' => $refund], 200);
        }

        return back()->with('success', 'Devolución actualizada con éxito');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $refund = Refunds::with('refundDetails.product')->findOrFail($id);

            // Reverse inventory updates before deleting
            foreach ($refund->refundDetails as $detail) {
                $product = $detail->product;
                $product->decrement('product_quantity', $detail->quantity);
            }

            $refund->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo eliminar el registro'], 500);
            }

            return back()->withErrors(['general' => $e->getMessage()]);
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Eliminado con exito']);
        }

        return back()->with('success', 'Devolución eliminada con éxito');
    }

    /**
     * Validate that refund quantities don't exceed original sale quantities
     */
    private function validateRefundQuantities($saleId, $refundDetails)
    {
        // Get original sale details
        $saleDetails = SalesDetails::where('sale_id', $saleId)
            ->with('product.measurementUnit')
            ->get()
            ->keyBy('product_id');

        // Get existing refund quantities for this sale
        $existingRefunds = DB::table('refunds')
            ->join('refund_details', 'refunds.id', '=', 'refund_details.refund_id')
            ->where('refunds.sale_id', $saleId)
            ->whereNull('refunds.deleted_at')
            ->whereNull('refund_details.deleted_at')
            ->select('refund_details.product_id', DB::raw('SUM(refund_details.quantity) as total_refunded'))
            ->groupBy('refund_details.product_id')
            ->pluck('total_refunded', 'product_id')
            ->toArray();

        foreach ($refundDetails as $detail) {
            $productId = $detail['product_id'];
            $refundQuantity = $detail['sd_qty_devuelto'];

            // Check if product exists in original sale
            if (!isset($saleDetails[$productId])) {
                throw new \Exception("El producto ID {$productId} no existe en la venta original");
            }

            $originalQuantity = $saleDetails[$productId]->sd_qty;
            $alreadyRefunded = $existingRefunds[$productId] ?? 0;
            $totalRefundQuantity = $alreadyRefunded + $refundQuantity;

            // Validate that total refund doesn't exceed original quantity
            if ($totalRefundQuantity > $originalQuantity) {
                $product = $saleDetails[$productId]->product;
                $unitName = $product->measurementUnit ? $product->measurementUnit->unit_name : 'Unidad';

                throw new \Exception(
                    "Cantidad de devolución excede la cantidad original para {$product->product_name}. " .
                    "Original: {$originalQuantity} {$unitName}, " .
                    "Ya devuelto: {$alreadyRefunded} {$unitName}, " .
                    "Intentando devolver: {$refundQuantity} {$unitName}"
                );
            }
        }
    }
}
