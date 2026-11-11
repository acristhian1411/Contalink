<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Tills\TillsController;
use App\Models\Tills;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'period' => 'sometimes|in:today,week,month',
        ]);
        $period = $filters['period'] ?? 'month';
        [$from, $to] = self::periodBounds($period, now());

        $user = $request->user();

        return Inertia::render('Home', [
            'name' => $user->name ?? 'Usuario',
            'period' => $period,
            'metrics' => [
                'sales' => $user->can('sales.index') ? $this->salesMetrics($from, $to) : null,
                'purchases' => $user->can('purchases.index') ? $this->purchaseMetrics($from, $to) : null,
                'openTills' => $user->can('tills.index') || $user->can('tills.show')
                    ? $this->openTillMetrics()
                    : null,
            ],
            'links' => [
                'salesCreate' => $user->can('sales.create'),
                'purchasesCreate' => $user->can('purchases.create'),
                'tillsShow' => $user->can('tills.show'),
            ],
        ]);
    }

    public static function periodBounds(string $period, Carbon $today): array
    {
        return match ($period) {
            'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
            'week' => [$today->copy()->startOfWeek(Carbon::MONDAY), $today->copy()->endOfDay()],
            default => [$today->copy()->startOfMonth(), $today->copy()->endOfDay()],
        };
    }

    private function salesMetrics(Carbon $from, Carbon $to): array
    {
        $sales = DB::table('sales')
            ->whereBetween('sale_date', [$from->toDateString(), $to->toDateString()])
            ->whereNull('deleted_at');
        $amountExpression = 'CAST(sales_details.sd_qty AS DECIMAL(20, 4)) * CAST(sales_details.sd_amount AS DECIMAL(20, 2))';

        $details = DB::table('sales_details')
            ->join('sales', 'sales.id', '=', 'sales_details.sale_id')
            ->whereBetween('sales.sale_date', [$from->toDateString(), $to->toDateString()])
            ->whereNull('sales.deleted_at')
            ->whereNull('sales_details.deleted_at');

        return [
            'count' => $sales->count(),
            'amount' => (float) (clone $details)->sum(DB::raw($amountExpression)),
            'topProducts' => DB::table('sales_details')
                ->join('sales', 'sales.id', '=', 'sales_details.sale_id')
                ->join('products', 'products.id', '=', 'sales_details.product_id')
                ->whereBetween('sales.sale_date', [$from->toDateString(), $to->toDateString()])
                ->whereNull('sales.deleted_at')
                ->whereNull('sales_details.deleted_at')
                ->whereNull('products.deleted_at')
                ->select('products.id', 'products.product_name')
                ->selectRaw("SUM(CAST(sales_details.sd_qty AS DECIMAL(20, 4))) AS quantity")
                ->selectRaw("SUM($amountExpression) AS amount")
                ->groupBy('products.id', 'products.product_name')
                ->orderByDesc('quantity')
                ->limit(5)
                ->get(),
        ];
    }

    private function purchaseMetrics(Carbon $from, Carbon $to): array
    {
        $purchases = DB::table('purchases')
            ->whereBetween('purchase_date', [$from->toDateString(), $to->toDateString()])
            ->whereNull('deleted_at');

        return [
            'count' => $purchases->count(),
            'amount' => (float) DB::table('purchases_details')
                ->join('purchases', 'purchases.id', '=', 'purchases_details.purchase_id')
                ->whereBetween('purchases.purchase_date', [$from->toDateString(), $to->toDateString()])
                ->whereNull('purchases.deleted_at')
                ->whereNull('purchases_details.deleted_at')
                ->sum(DB::raw('CAST(purchases_details.pd_qty AS DECIMAL(20, 4)) * CAST(purchases_details.pd_amount AS DECIMAL(20, 2))')),
        ];
    }

    private function openTillMetrics(): array
    {
        $tillController = new TillsController();
        $tills = Tills::where('till_status', true)->orderBy('till_name')->get();
        $openTills = $tills->map(function (Tills $till) use ($tillController) {
            $amount = $tillController->showTillAmount(new Request(['fromController' => true]), $till->id);
            if (!is_numeric($amount)) {
                throw new \RuntimeException("No se pudo calcular el saldo de la caja {$till->id}");
            }

            return [
                'id' => $till->id,
                'name' => $till->till_name,
                'amount' => (float) $amount,
            ];
        });

        return [
            'items' => $openTills,
            'amount' => $openTills->sum('amount'),
        ];
    }
}
