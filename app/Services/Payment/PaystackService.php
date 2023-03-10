<?php

namespace App\Services\Payment;

use App\Actions\ValidatePaymentInitAction;
use App\Models\UserPayment;
use App\Traits\APIResponsesTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PaystackService
{
    use APIResponsesTrait;

    private ValidatePaymentInitAction $validatePaymentInit;

    public function __construct(ValidatePaymentInitAction $validatePaymentInit)
    {
        $this->validatePaymentInit = $validatePaymentInit;
    }

    public function initializeTransaction($payload)
    {
        if ($payload->type == 'PAYSTACK') {
            return $this->initPaystackPayment($payload);
        }

        //paystack charge authorization
        if ($payload->type == 'PAYSTACK_CHARGE_CARD') {
            return $this->paystackChargeAuthorization($payload);
        }

        if ($payload->type == 'VERIFY_PAYSTACK') {
            return $this->verifyPaystackPayment($payload->ref, $payload->user_id);
        }

        return [];
    }

    private function initPaystackPayment($payload)
    {
        // $this->validatePaymentInit->execute($payload);

        try {
            DB::beginTransaction();

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                "Cache-Control" => "no-cache",
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET')
            ])->post(env('PAYSTACK_URL') . 'transaction/initialize', [
                "email" =>  $payload->email,
                "amount" => $payload->amount,
                "channels" => ['card', 'bank'],
            ]);

            if ($response->json()['status']) {
                // add to db
                $payment = new UserPayment;
                $payment->user_id = $payload->user()->id;
                $payment->authorization_url = $response->json()['data']['authorization_url'];
                $payment->access_code = $response->json()['data']['access_code'];
                $payment->reference = $response->json()['data']['reference'];
                $payment->amount = $payload->amount; // multiply by 100
                $payment->save();

                DB::commit();
            }

            return $response->json();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseError([], $e->getMessage());
        }
    }

    private function verifyPaystackPayment($reference, $user_id)
    {
        try {
            DB::beginTransaction();

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                "Cache-Control" => "no-cache",
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET')
            ])->get(env('PAYSTACK_URL') . 'transaction/verify/' . $reference);

            if ($response->json()['data']['status'] == 'success') {
                // update to db
                $user_payment = UserPayment::where('reference', $reference)->first();
                if ($user_payment) {
                    $user_payment->update([
                        'status' => 'success',
                    ]);
                } else {
                    $payment = new UserPayment;
                    $payment->user_id = $user_id;
                    $payment->authorization_url = $response->json()['data']['authorization']['authorization_code'];
                    $payment->access_code = $response->json()['data']['authorization']['authorization_code'];
                    $payment->reference = $response->json()['data']['reference'];
                    $payment->amount = $response->json()['data']['amount'];
                    $payment->status = 'success';
                    $payment->save();
                }

                if ($response->json()['data']['authorization']['reusable'] && $response->json()['data']['authorization']['channel'] == 'card') {
                    // check if it exsist else create it
                    $auth = $response->json()['data']['authorization'];
                    DB::table('user_paystack_charge_authorizations')
                        ->updateOrInsert(
                            [
                                'user_id' => $user_id,
                                'bin' => $auth['bin'],
                                'last4' => $auth['last4'],
                                'exp_month' => $auth['exp_month'],
                                'exp_year' => $auth['exp_year'],
                                'channel' => $auth['channel'],
                                'card_type' => $auth['card_type'],
                                'bank' => $auth['bank'],
                            ],
                            [
                                'authorization_code' => $auth['authorization_code'],
                                'reusable' => $auth['reusable'],
                                'signature' => $auth['signature'],
                                'account_name' => $auth['account_name'],
                                'brand' => $auth['brand'],
                                'country_code' => $auth['country_code'],
                            ]
                        );
                }

                DB::commit();
            }

            return $response->json();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseError([], $e->getMessage());
        }
    }

    private function paystackChargeAuthorization($payload)
    {
        try {
            DB::beginTransaction();

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                "Cache-Control" => "no-cache",
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET')
            ])->post(env('PAYSTACK_URL') . 'transaction/charge_authorization', [
                "email" =>  $payload->email,
                "amount" => $payload->amount,
                "authorization_code" => $payload->authorization_code,
            ]);

            if ($response->json()['status']) {
                // add to db
                $payment = new UserPayment;
                $payment->user_id = $payload->user()->id;
                $payment->authorization_url = $response->json()['data']['authorization']['authorization_code'];
                $payment->access_code = $response->json()['data']['authorization']['authorization_code'];
                $payment->reference = $response->json()['data']['reference'];
                $payment->amount = $response->json()['data']['amount'];;
                $payment->save();

                DB::commit();
            }

            return $response->json();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseError([], $e->getMessage());
        }
    }

    public function getSavedCards($request)
    {
        $charge_authorization = DB::table('user_paystack_charge_authorizations')->where('user_id', $request->user()->id)->get();

        return $this->responseOK($charge_authorization, 'All saved cards');
    }
}
