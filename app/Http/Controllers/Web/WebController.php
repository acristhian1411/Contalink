<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\ApiController;
use App\Models\PaymentTypes;
use App\Models\IvaType;
use App\Models\MeasurementUnit;
use App\Models\Categories;
use App\Models\Brand;
use App\Models\Tills;
use App\Models\Persons;
use Illuminate\Support\Facades\Auth;

abstract class WebController extends ApiController
{
    /**
     * Reference data preloaded for forms.
     */
    protected function getStaticData(): array
    {
        return [
            'paymentTypes' => PaymentTypes::with('proofPayments')->get(),
            'ivaTypes' => IvaType::all(),
            'measurementUnits' => MeasurementUnit::active()->get(),
            'categories' => Categories::all(),
            'brands' => Brand::all(),
        ];
    }

    /**
     * Current user context (user, permissions, roles, tills).
     */
    protected function getUserContext(): array
    {
        $user = Auth::user();

        if (!$user) {
            throw new \Exception('Usuario no autenticado');
        }

        return [
            'user' => $user->load('person'),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'roles' => $user->getRoleNames()->pluck('name'),
            'userTills' => $this->getUserTills($user),
        ];
    }

    /**
     * Tills assigned to the current user (or all active if admin).
     */
    protected function getUserTills($user): array
    {
        if ($user->person_id) {
            return Tills::where('person_id', $user->person_id)
                ->with('type')
                ->get()
                ->toArray();
        }

        if ($user->can('tills.manage_all')) {
            return Tills::where('till_status', true)
                ->with('type')
                ->get()
                ->toArray();
        }

        return [];
    }

    /**
     * Base form configuration (default dates, timezone).
     */
    protected function getFormConfiguration(): array
    {
        return [
            'defaultDate' => now()->format('Y-m-d'),
            'currentDateTime' => now()->format('Y-m-d H:i:s'),
            'timezone' => config('app.timezone'),
        ];
    }

    /**
     * Persons for client selection.
     */
    protected function getClientsForSelection(): array
    {
        return Persons::select('id', 'person_fname', 'person_lastname', 'person_corpname', 'person_idnumber')
            ->get()
            ->map(fn($person) => [
                'id' => $person->id,
                'name' => $person->person_corpname ?: ($person->person_fname . ' ' . $person->person_lastname),
                'document' => $person->person_idnumber,
            ])
            ->toArray();
    }

    /**
     * Persons for provider selection.
     */
    protected function getProvidersForSelection(): array
    {
        return Persons::select('id', 'person_fname', 'person_lastname', 'person_corpname', 'person_idnumber')
            ->whereHas('type', fn($query) => $query->where('p_type_desc', 'ilike', '%proveedor%'))
            ->get()
            ->map(fn($person) => [
                'id' => $person->id,
                'name' => $person->person_corpname ?: ($person->person_fname . ' ' . $person->person_lastname),
                'document' => $person->person_idnumber,
            ])
            ->toArray();
    }

    /**
     * Generate a unique transaction number.
     */
    protected function generateTransactionNumber(string $prefix = ''): string
    {
        $timestamp = now()->format('YmdHis');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return $prefix . $timestamp . $random;
    }
}
