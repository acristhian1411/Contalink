<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Web\WebController;
use App\Http\Controllers\Products\ProductsController;
use App\Http\Controllers\PurchasesDetails\PurchasesDetailsController;
use App\Http\Controllers\TillDetails\TillDetailsController;
use App\Http\Controllers\TillDetailProofPayments\TillDetailProofPaymentsController;
use App\Http\Controllers\Tills\TillsController;
use App\Http\Requests\PurchaseStoreRequest;
use App\Models\Purchases;
use App\Models\PurchasesDetails;
use App\Models\TillDetails;
use App\Models\TillDetailProofPayments;
use App\Models\Products;
use App\Validators\QuantityValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PDF;

class PurchasesController extends WebController
{
    public function index()
    {
        if (request()->wantsJson()) {
            $query = $this->filterData(Purchases::query(), Purchases::query()->first());
            $datos = $query->with(['person', 'purchaseDetails.product.measurementUnit'])->get();
            $this->decorateUnits($datos);
            return $this->showAll($datos, 'api', '', 200);
        }

        return Inertia::render('Purchases/index', [
            'userContext' => $this->getUserContext(),
            'filters' => $this->getPurchasesFilters(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Purchases/form', [
            'staticData' => $this->getStaticData(),
            'userContext' => $this->getUserContext(),
            'formConfig' => $this->getPurchasesFormConfiguration(),
            'mode' => 'create',
        ]);
    }

    public function store(PurchaseStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            foreach ($request->purchase_details as $detail) {
                $product = Products::with('measurementUnit')->find($detail['product_id']);

                if (!$product) {
                    return response()->json([
                        'error' => "Producto con ID {$detail['product_id']} no encontrado",
                        'message' => 'Producto no encontrado',
                    ], 400);
                }

                if ($product->measurementUnit && !QuantityValidator::validate($detail['pd_qty'], $product->measurementUnit)) {
                    return response()->json([
                        'error' => "Error en producto '{$product->product_name}': " . QuantityValidator::getErrorMessage($detail['pd_qty'], $product->measurementUnit),
                        'message' => 'Cantidad inválida para la unidad de medida del producto',
                    ], 400);
                }
            }

            $tillAmount = (new TillsController())->showTillAmount(new Request(['fromController' => true]), $request->till_id);
            $purchaseAmount = collect($request->purchase_details)->map(fn ($d) => $d['pd_amount'] * $d['pd_qty'])->sum();

            if ($tillAmount < $purchaseAmount) {
                return response()->json([
                    'error' => 'No hay suficiente efectivo en la caja para realizar la compra',
                    'message' => 'No hay suficiente efectivo en la caja para realizar la compra',
                ], 400);
            }

            (new ProductsController())->updatePriceAndQty(new Request([
                'fromController' => true,
                'controller' => 'purchase',
                'details' => collect($request->purchase_details)->map(fn ($item) => [
                    'id' => $item['product_id'],
                    'product_cost_price' => $item['pd_amount'],
                    'product_quantity' => $item['pd_qty'],
                ])->toArray(),
            ]));

            $purchase = Purchases::create([
                'person_id' => is_object($request->person_id) ? $request->person_id->value : $request->person_id,
                'purchase_date' => $request->purchase_date,
                'purchase_number' => $request->purchase_number,
            ]);

            (new PurchasesDetailsController())->storeMany(new Request([
                'details' => collect($request->purchase_details)->map(fn ($item) => array_merge($item, [
                    'purchase_id' => $purchase->id,
                ]))->toArray(),
            ]));

            foreach ($request->proofPayments as $payment) {
                $tillDetail = (new TillDetailsController())->store(new Request([
                    'till_id' => $request->till_id,
                    'account_p_id' => 1,
                    'ref_id' => $purchase->id,
                    'person_id' => $request->user_id,
                    'td_desc' => "Compra {$request->purchase_number}",
                    'td_date' => $request->purchase_date,
                    'td_type' => false,
                    'td_amount' => $payment['amount'],
                ]));

                (new TillDetailProofPaymentsController())->store(new Request([
                    'till_detail_id' => $tillDetail->original['data']['id'],
                    'proof_payment_id' => empty($request->proofPayments) ? 1 : $payment['value'],
                    'td_pr_desc' => empty($request->proofPayments) ? 1 : ($payment['value'] == 1 ? 'Efectivo' : $payment['td_pr_desc']),
                ]));
            }

            DB::commit();

            return $this->showAfterAction([], 'create', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Los datos no son correctos',
                'details' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage(), 'message' => 'Ocurrió un error mientras se creaba la compra'], 500);
        }
    }

    public function show($id)
    {
        try {
            $purchase = Purchases::with(['person', 'purchaseDetails.product.measurementUnit'])->find($id);

            if (!$purchase) {
                return response()->json(['error' => 'Compra no encontrada', 'message' => 'La compra especificada no existe'], 404);
            }

            $this->decorateUnits(collect([$purchase]));

            if (request()->wantsJson()) {
                return $this->showOne($purchase, $purchase->audits, 200);
            }

            return Inertia::render('Purchases/show', [
                'purchase' => $purchase,
                'userContext' => $this->getUserContext(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'Ocurrió un error mientras se obtenía el registro'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'person_id' => 'required',
                'purchase_desc' => 'required',
                'purchase_date' => 'required',
                'purchase_number' => 'required',
                'purchase_status' => 'required',
                'purchase_type' => 'required',
            ]);

            $purchase = Purchases::find($id);
            $purchase->update($request->only(['person_id', 'purchase_desc', 'purchase_date', 'purchase_number', 'purchase_status', 'purchase_type']));

            return $this->showAfterAction($purchase, 'update', 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'Ocurrió un error mientras se actualizaba el registro'], 500);
        }
    }

    public function destroy(Request $request, $purchaseId)
    {
        try {
            DB::beginTransaction();

            $purchase = Purchases::findOrFail($purchaseId);

            $details = PurchasesDetails::where('purchase_id', $purchaseId)->with('product')->get();

            (new ProductsController())->updatePriceAndQty(new Request([
                'fromController' => true,
                'controller' => 'purchases_reversal',
                'details' => $details->map(fn ($d) => [
                    'id' => $d->product_id,
                    'product_cost_price' => $d->pd_amount,
                    'product_quantity' => $d->pd_qty,
                ])->toArray(),
            ]));

            $tillDetails = TillDetails::where('ref_id', $purchaseId)
                ->where('td_desc', 'ilike', '%compra%')
                ->get();

            if ($tillDetails->isNotEmpty()) {
                TillDetailProofPayments::whereIn('till_detail_id', $tillDetails->pluck('id'))->delete();
                TillDetails::whereIn('id', $tillDetails->pluck('id'))->delete();
            }

            PurchasesDetails::where('purchase_id', $purchaseId)->delete();
            $purchase->delete();

            DB::commit();

            return response()->json([
                'message' => 'Compra eliminada con éxito',
                'data' => ['purchase_id' => $purchaseId],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo eliminar la compra'], 500);
        }
    }

    public function getPurchasesReport(Request $request)
    {
        try {
            $subquery = DB::table('purchases_details as pd')
                ->select('pd.purchase_id', DB::raw('SUM(pd.pd_amount) as total'))
                ->groupBy('pd.purchase_id');

            $purchases = Purchases::query()
                ->join('persons', 'purchases.person_id', '=', 'persons.id')
                ->joinSub($subquery, 'd', fn ($join) => $join->on('d.purchase_id', '=', 'purchases.id'))
                ->where('purchase_date', '>=', $request->start_date)
                ->where('purchase_date', '<=', $request->end_date)
                ->select('purchases.purchase_date as date', 'purchases.purchase_number as number', 'persons.person_fname', 'persons.person_lastname', 'd.total');

            if ($request->has('entity') and $request->entity != null) {
                $purchases = $purchases->where('person_id', $request->entity);
            }

            $purchases = $purchases->get();

            if ($request->from_view != 'false') {
                return $this->showAll($purchases, 'api', '', 200);
            }

            $data = [
                'title' => 'Reporte de compras',
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'date' => date('Y-m-d'),
                'purchases' => $purchases,
            ];

            $pdf = PDF::loadView('Purchases.PurchasesReport', $data);

            return response()->stream(function () use ($pdf) {
                echo $pdf->stream();
            }, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="purchases_report.pdf"',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'No se pudo obtener los datos'], 500);
        }
    }

    private function getPurchasesFormConfiguration(): array
    {
        return array_merge($this->getFormConfiguration(), [
            'purchaseNumber' => $this->generateTransactionNumber('CMP-'),
            'validationRules' => $this->getPurchasesValidationRules(),
            'businessRules' => $this->getPurchasesBusinessRules(),
            'tillAmounts' => $this->getTillAmounts(),
        ]);
    }

    private function getPurchasesValidationRules(): array
    {
        return [
            'person_id' => 'required',
            'purchase_date' => 'required|date',
            'purchase_number' => 'required|unique:purchases,purchase_number',
            'till_id' => 'required',
            'purchase_details' => 'required|array|min:1',
            'proofPayments' => 'required|array|min:1',
        ];
    }

    private function getPurchasesBusinessRules(): array
    {
        return [
            'maxItemsPerPurchase' => 50,
            'maxPurchaseAmount' => 1000000,
            'requiresApprovalAbove' => 100000,
            'allowFutureDates' => false,
            'requiresTillValidation' => true,
        ];
    }

    private function getPurchasesFilters(): array
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

    private function getTillAmounts(): array
    {
        $tillAmounts = [];
        foreach ($this->getUserTills(Auth::user()) as $till) {
            $tillAmounts[$till['id']] = (float) (new TillsController())->showTillAmount(new Request(['fromController' => true]), $till['id']);
        }
        return $tillAmounts;
    }

    protected function getStaticData(): array
    {
        return array_merge(parent::getStaticData(), [
            'providers' => $this->getProvidersForSelection(),
        ]);
    }

    private function decorateUnits($purchases): void
    {
        foreach ($purchases as $purchase) {
            if (!$purchase->purchaseDetails) {
                continue;
            }
            foreach ($purchase->purchaseDetails as $detail) {
                if ($detail->product && $detail->product->measurementUnit) {
                    $detail->unit_name = $detail->product->measurementUnit->unit_name;
                    $detail->unit_abbreviation = $detail->product->measurementUnit->unit_abbreviation;
                    $detail->allows_decimals = $detail->product->measurementUnit->allows_decimals;
                } else {
                    $detail->unit_name = 'Unidad';
                    $detail->unit_abbreviation = 'u';
                    $detail->allows_decimals = false;
                }
            }
        }
    }
}
