<?php

namespace App\Http\Controllers\AccountPlans;

use App\Models\AccountPlan;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;


class AccountPlanController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $t = AccountPlan::query()->first();
        $query = AccountPlan::query();
        $query = $this->filterData($query, $t);
        $datos = $query->get();
        return $this->showAll($datos, 200);
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $rules=[
            'account_desc' => 'required|string|max:255',
            'account_code' => 'required|string|max:255',
        ];
        $this->validate($request, $rules);
        $accountPlan = AccountPlan::create($request->validated());
        return $this->showOne($accountPlan, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\AccountPlan  $accountPlan
     * @return \Illuminate\Http\Response
     */
    public function show( $id)
    {
        //
        $accountPlan = AccountPlan::find($id);
        return $this->showOne($accountPlan,200);
    }

    

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AccountPlan  $accountPlan
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,  $id)
    {
        //add code to validate and update
        $rules = [
            'account_desc' => 'required|string|max:255',
            'account_code' => 'required|string|max:255',
        ];
        $this->validate($request, $rules);

        // Find the account plan by ID
        $accountPlan = AccountPlan::find($id);

        // Update the account plan with the new data
        $accountPlan->update($request->validated());

        // Return a success response
        return $this->showOne($accountPlan, 200);
        

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AccountPlan  $accountPlan
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        $accountPlan = AccountPlan::find($id);
        $accountPlan->delete();
        return response()->json('Eliminado con exito');

    }
}
