<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Web\WebController;
use App\Http\Controllers\Products\ProductsController;
use App\Http\Controllers\SalesDetails\SalesDetailsController;
use App\Http\Controllers\TillDetails\TillDetailsController;
use App\Http\Controllers\TillDetailProofPayments\TillDetailProofPaymentsController;
use App\Http\Requests\StoreSalesRequest;
use App\Models\Sales;
use App\Models\SalesDetails;
use App\Models\TillDetails;
use App\Models\TillDetailProofPayments;
use App\Models\Products;
use App\Validators\QuantityValidator;
use App\Utils\QuantityValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PDF;

class SalesController extends WebController
{
    public function index()
    {
        if (request()->wantsJson()) {
            $query = $this->filterData(Sales::query(), Sales::query()->first());
            return $this->showAll($query->with('person')->get(), 'api', '', 200);
        }

        return Inertia::render('Sales/index', [
            'userContext' => $this->getUserContext(),
            'filters' => $this->getSalesFilters(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Sales/form', [
            'staticData' => $this->getStaticData(),
            'userContext' => $this->getUserContext(),
            'formConfig' => $this->getSalesFormConfiguration(),
            'mode' => 'create',
        ]);
    }

    public function store(StoreSalesRequest $request)
    {
        try {
            DB::beginTransaction();

            foreach ($request->sale_details as $detail) {
                $product = Products::with('measurementUnit')->find($detail['product_id']);

                if (!$product) {
                    throw new QuantityValidationException("Producto con ID {$detail['product_id']} no encontrado");
                }

                if ($product->measurementUnit && !QuantityValidator::validate($detail['sd_qty'], $product->measurementUnit)) {
                    throw new QuantityValidationException("Producto {$product->product_name}: " . QuantityValidator::getErrorMessage($detail['sd_qty'], $product->measurementUnit));
                }

                if ($product->product_quantity < $detail['sd_qty']) {
                    $unitName = $product->measurementUnit ? $product->measurementUnit->unit_name : 'Unidad';
                    throw new QuantityValidationException("Stock insuficiente para {$product->product_name}. Disponible: {$product->product_quantity} {$unitName}");
                }
            }

            (new ProductsController())->updatePriceAndQty(new Request([
                'fromController' => true,
                'controller' => 'sales',
                'details' => collect($request->sale_details)->map(fn ($item) => [
                    'id' => $item['product_id'],
                    'product_cost_price' => $item['sd_amount'],
                    'product_quantity' => $item['sd_qty'],
                ])->toArray(),
            ]));

            $sale = Sales::create([
                'person_id' => $request->person_id,
                'sale_date' => $request->sale_date,
                'sale_number' => $request->sale_number,
            ]);

            (new SalesDetailsController())->storeMany(new Request([
                'details' => collect($request->sale_details)->map(fn ($item) => array_merge($item, [
                    'sale_id' => $sale->id,
                    'sd_desc' => "Venta {$request->sale_number}",
                ]))->toArray(),
            ]));

            foreach ($request->proofPayments as $payment) {
                $tillDetail = (new TillDetailsController())->store(new Request([
                    'till_id' => $request->till_id,
                    'account_p_id' => 1,
                    'ref_id' => $sale->id,
                    'person_id' => $request->user_id,
                    'td_desc' => "Venta {$request->sale_number}",
                    'td_date' => $request->sale_date,
                    'td_type' => true,
                    'td_amount' => $payment['amount'],
                ]));

                (new TillDetailProofPaymentsController())->store(new Request([
                    'till_detail_id' => $tillDetail->original['data']['id'],
                    'proof_payment_id' => empty($request->proofPayments) ? 1 : $payment['value'],
                    'td_pr_desc' => empty($request->proofPayments) ? 1 : ($payment['value'] === 1 ? 'Efectivo' : $payment['td_pr_desc']),
                ]));
            }

            DB::commit();

            return $this->showAfterAction([], 'create', 201);
        } catch (QuantityValidationException $e) {
            DB::rollBack();
            return response()->json(['errors' => $e->getErrors(), 'message' => 'Problemas con la venta'], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage(), 'message' => 'Ocurrió un error mientras se creaba la venta'], 500);
        }
    }

    public function show($id)
    {
        try {
            $sale = Sales::with([
                'person',
                'sales_details.product.measurementUnit',
                'sales_details.product.ivaType',
                'tills_details.till',
                'tills_details.tillproofPayments.proofPayments.paymentType',
            ])->findOrFail($id);

            if (request()->wantsJson()) {
                return $this->showOne($sale, $sale->audits, 200);
            }

            return Inertia::render('Sales/show', [
                'sale' => $sale,
                'userContext' => $this->getUserContext(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo obtener los datos'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'person_id' => 'required',
                'sale_desc' => 'required',
                'sale_date' => 'required',
                'sale_number' => 'required',
                'sale_status' => 'required',
                'sale_type' => 'required',
            ]);

            $sales = Sales::findOrFail($id);
            $sales->update($request->only(['person_id', 'sale_desc', 'sale_date', 'sale_number', 'sale_status', 'sale_type']));

            return $this->showAfterAction($sales, 'update', 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo actualizar los datos'], 500);
        }
    }

    public function destroy(Request $request, $saleId)
    {
        try {
            DB::beginTransaction();

            $sale = Sales::findOrFail($saleId);

            $details = SalesDetails::where('sale_id', $saleId)
                ->with('product')
                ->get();

            (new ProductsController())->updatePriceAndQty(new Request([
                'fromController' => true,
                'controller' => 'sales_reversal',
                'details' => $details->map(fn ($d) => [
                    'id' => $d->product_id,
                    'product_cost_price' => $d->sd_amount,
                    'product_quantity' => $d->sd_qty,
                ])->toArray(),
            ]));

            $tillDetails = TillDetails::where('ref_id', $saleId)
                ->where('td_desc', 'ilike', '%venta%')
                ->get();

            if ($tillDetails->isNotEmpty()) {
                TillDetailProofPayments::whereIn('till_detail_id', $tillDetails->pluck('id'))->delete();
                TillDetails::whereIn('id', $tillDetails->pluck('id'))->delete();
            }

            SalesDetails::where('sale_id', $saleId)->delete();
            $sale->delete();

            DB::commit();

            return response()->json([
                'message' => 'Venta eliminada con éxito',
                'data' => ['sale_id' => $saleId],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo eliminar la venta'], 500);
        }
    }

    public function searchByNumber($searchTerm)
    {
        try {
            $sales = Sales::where('sale_number', $searchTerm)
                ->with('person')
                ->with(['tills_details' => function ($query) {
                    $query->with('till');
                    $query->with('tillproofPayments.proofPayments.paymentType');
                }])
                ->with(['sales_details' => function ($query) {
                    $query->withTrashed()->with('product')->with('product.ivaType');
                }])
                ->first();

            if (!$sales) {
                return response()->json(['message' => 'No se encontro el registro', 'data' => []], 404);
            }

            return $this->showAfterAction($sales, 'show', 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo obtener los datos'], 500);
        }
    }

    public function getSalesReport(Request $request)
    {
        try {
            $subquery = DB::table('sales_details as sd')
                ->select('sd.sale_id', DB::raw('SUM(sd.sd_amount) as total'))
                ->groupBy('sd.sale_id');

            $sales = Sales::query()
                ->join('persons', 'sales.person_id', '=', 'persons.id')
                ->joinSub($subquery, 'd', fn ($join) => $join->on('d.sale_id', '=', 'sales.id'))
                ->where('sale_date', '>=', $request->start_date)
                ->where('sale_date', '<=', $request->end_date)
                ->select('sales.sale_date as date', 'sales.sale_number as number', 'persons.person_fname', 'persons.person_lastname', 'd.total');

            if ($request->has('entity') and $request->entity != null) {
                $sales = $sales->where('person_id', $request->entity);
            }

            if ($request->from_view != 'false') {
                return $this->showAll($sales->get(), 'api', '', 200);
            }

            $data = [
                'title' => 'Reporte de ventas',
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'date' => date('Y-m-d'),
                'sales' => $sales->get(),
            ];

            $pdf = PDF::loadView('Sales.SalesReport', $data);

            return response()->stream(function () use ($pdf) {
                echo $pdf->stream();
            }, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="sales_report.pdf"',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo obtener los datos'], 500);
        }
    }

    private function getSalesFormConfiguration(): array
    {
        return array_merge($this->getFormConfiguration(), [
            'saleNumber' => $this->generateTransactionNumber('VTA-'),
            'validationRules' => $this->getSalesValidationRules(),
            'businessRules' => $this->getSalesBusinessRules(),
        ]);
    }

    private function getSalesValidationRules(): array
    {
        return [
            'person_id' => 'required',
            'sale_date' => 'required|date',
            'sale_number' => 'required|unique:sales,sale_number',
            'till_id' => 'required',
            'sale_details' => 'required|array|min:1',
            'proofPayments' => 'required|array|min:1',
        ];
    }

    private function getSalesBusinessRules(): array
    {
        return [
            'maxItemsPerSale' => 50,
            'maxSaleAmount' => 1000000,
            'requiresApprovalAbove' => 100000,
            'allowFutureDates' => false,
        ];
    }

    private function getSalesFilters(): array
    {
        return [
            'dateRange' => [
                'start' => now()->startOfMonth()->format('Y-m-d'),
                'end' => now()->format('Y-m-d'),
            ],
            'statuses' => ['active', 'cancelled'],
            'paymentTypes' => $this->getStaticData()['paymentTypes'],
        ];
    }

    protected function getStaticData(): array
    {
        return array_merge(parent::getStaticData(), [
            'clients' => $this->getClientsForSelection(),
        ]);
    }
}
