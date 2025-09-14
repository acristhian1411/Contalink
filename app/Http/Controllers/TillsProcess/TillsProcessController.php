<?php

namespace App\Http\Controllers\TillsProcess;

use App\Models\Tills;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\TillDetails\TillDetailsController;
use App\Http\Controllers\TillDetailProofPayments\TillDetailProofPaymentsController;
use App\Http\Controllers\TillsTransfers\TillsTransfersController;
use App\Http\Controllers\Tills\TillsController;
use Illuminate\Support\Facades\DB;

class TillsProcessController extends ApiController{
    /**
     * processes a cash opening 
     * @param int $till_id
     * @param int $amount
     * @return \Illuminate\Http\Response
     */
    public function cashOpening(Request $request){
        $rules = [
            'till_id' => 'required|integer', 
            'person_id' => 'required|integer', 
            'td_amount' => 'required|numeric'
        ];
        $request->validate($rules);
            try{
                DB::beginTransaction();
                $till = Tills::findOrFail($request->till_id);
                $till->open();
                $now = date('Y-m-d');
                
                $detail = new TillDetailsController;
                $detail_data = new Request([
                    'till_id' => $request->till_id,
                    'person_id' => $request->person_id,
                    'account_p_id' => 0,
                    'td_desc' => 'Apertura de Caja',
                    'td_date' => $now,
                    'td_type' => true,
                    'ref_id'=> 0,
                    'td_amount' => intval($request->td_amount),
                    'wantsJson'=>true
                ]);
                $detailMessage = $detail->store($detail_data);
                $details_id = data_get($detailMessage, 'original.data.id');
                $detProofPayment = new TillDetailProofPaymentsController;
                $proofs = new Request([
                    'till_detail_id'=>$details_id,
                    'proof_payment_id'=>1,
                    'td_pr_desc'=>'Ninguno'
                ]);
                $detProofPayment->store($proofs);
                DB::commit();
                return response()->json(['message'=>'Caja abierta con exito']);
            }catch(\Exception $e){
                DB::rollback();
                return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo abrir la caja'],500);
            }catch(\Illuminate\Validation\ValidationException $e){
                DB::rollback();
                return response()->json(['error'=>$e->getMessage(),'message'=>'Los datos enviados no son correctos'],420);
            }
    }

    /**
     * processes a cash closing
     * @param int $till_id
     * @param int $amount
     * @return \Illuminate\Http\Response
     */
    public function close(Request $request){
        $rules = [
            'till_id' => 'required|integer', 
            'person_id' => 'required|integer', 
            'td_amount' => 'required|numeric'
        ];
        $request->validate($rules);
        try{
            DB::beginTransaction();
                $till = Tills::findOrFail($request->till_id);
                $till->close();
                $now = date('Y-m-d');
                
                $detail = new TillDetailsController;
                $detail_data = new Request([
                    'till_id' => $request->till_id,
                    'person_id' => $request->person_id,
                    'account_p_id' => 0,
                    'td_desc' => 'Cierre de Caja',
                    'td_date' => $now,
                    'ref_id'=>0,
                    'td_type' => false,
                    'td_amount' => $request->td_amount
                ]);
                $detailMessage = $detail->store($detail_data);
                
                $details_id = $detailMessage->original['data']['id'];
                
                $detProofPayment = new TillDetailProofPaymentsController;
                $proofs = new Request([
                    'till_detail_id'=>$details_id,
                    'proof_payment_id'=>1,
                    'td_pr_desc'=>'Ninguno'
                ]);
                $detProofPayment->store($proofs);
                
                DB::commit();
                return response()->json(['message'=>'Caja cerrada con exito']);
            
        }catch(\Exception $e){
            DB::rollback();
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo cerrar la caja'],500);
        }
    }

    /**
     * processes a cash deposit
     * @param int $till_id
     * @param int $amount
     * @return \Illuminate\Http\Response
     */
    public function deposit(Request $request){
        $rules = [
            'till_id' => 'required|integer', 
            'person_id' => 'required|integer', 
            'td_amount' => 'required|numeric'
        ];
        $request->validate($rules);
        try{
            DB::beginTransaction();
            $now = date('Y-m-d');
            
            $detail = new TillDetailsController;
            $detail_data = new Request([
                'till_id' => $request->till_id,
                'person_id' => $request->person_id,
                'account_p_id' => 0,
                'td_desc' => 'Ingreso de Efectivo',
                'td_date' => $now,
                'ref_id'=>0,
                'td_type' => true,
                'td_amount' => $request->td_amount
            ]);
            $detailMessage = $detail->store($detail_data);
            
            $details_id = $detailMessage->original['data']['id'];
            
            $detProofPayment = new TillDetailProofPaymentsController;
            $proofs = new Request([
                'till_detail_id'=>$details_id,
                'proof_payment_id'=>1,
                'td_pr_desc'=>'Ninguno'
            ]);
            $detProofPayment->store($proofs);
            
            DB::commit();
            return response()->json(['message'=>'Deposito realizado con exito']);
            
        }catch(\Exception $e){
            DB::rollback();
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo realizar el deposito'],500);
        }
    }

    /**
    * processes a transfer between tills
    * @param int $id the origin till
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\JsonResponse
    */
    public function transfer(Request $req,$id){
        try{
            $rules = [
                'destiny_id' => 'required|integer',
                't_transfer_amount' => 'required|numeric',
                't_transfer_obs' => 'required|string|max:255'
            ];
            $req->validate($rules);
            DB::beginTransaction();
            $tillsTransfers = new TillsTransfersController;
            $tills = new TillsController;
            $till_data = new Request([
                'fromController'=> true
            ]);
            $till_amount = $tills->showTillAmount($till_data,$id);
            if($till_amount < $req->t_transfer_amount){
                return response()->json(['error'=>'No hay suficiente efectivo en la caja para realizar la transferencia','message'=>'No hay suficiente efectivo en la caja para realizar la transferencia'],400);
            }

            $now = date('Y-m-d');
            $transfer_req = new Request([
                'origin_id' => $id,
                'destiny_id' => $req->destiny_id,
                't_transfer_amount' => $req->t_transfer_amount,
                't_transfer_date' => $now,
                't_transfer_obs' => $req->t_transfer_obs
            ]);
            $transferStored = $tillsTransfers->store($transfer_req);
            $ref_id = $transferStored->original['data']['id'];
            $destinyDetail = new TillDetailsController;
            $destiny_req = new Request([
                'till_id' => $req->destiny_id,
                'person_id' => $req->person_id,
                'account_p_id' => 0,
                'td_desc' => 'Transferencia de caja',
                'td_date' => $now,
                'ref_id'=>$ref_id,
                'td_type' => true,
                'td_amount' => $req->t_transfer_amount
            ]);
            $destinyStored = $destinyDetail->store($destiny_req);
            $destinyID = $destinyStored->original['data']['id'];
            $detProofPayment = new TillDetailProofPaymentsController;
            $proofs = new Request([
                'till_detail_id'=>$destinyID,
                'proof_payment_id'=>1,
                'td_pr_desc'=>'Ninguno'
            ]);
            $detProofPayment->store($proofs);

            $origin_req = new Request([
                'till_id' => $id,
                'person_id' => $req->person_id,
                'account_p_id' => 0,
                'td_desc' => 'Transferencia de caja',
                'td_date' => $now,
                'ref_id'=>$ref_id,
                'td_type' => false,
                'td_amount' => $req->t_transfer_amount
            ]);
            $originDetail = new TillDetailsController;
            $originStored = $originDetail->store($origin_req);
            $originID = $originStored->original['data']['id'];
            $detProofPayment = new TillDetailProofPaymentsController;
            $proofs = new Request([
                'till_detail_id'=>$originID,
                'proof_payment_id'=>1,
                'td_pr_desc'=>'Ninguno'
            ]);
            $detProofPayment->store($proofs);
            DB::commit();
            return response()->json(['message'=>'Transferencia realizada con exito']);
        }catch(\Exception $e){
            DB::rollback();
            return response()->json(['error'=>$e->getMessage(),'message'=>'No se pudo realizar la transferencia'],500);
        }
    }
}