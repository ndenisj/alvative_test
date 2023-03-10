<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\InitPaymentRequest;
use App\Services\Payment\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    private PaystackService $paystackService;

    public function __construct(PaystackService $paystackService)
    {
        $this->paystackService = $paystackService;
    }

    public function initializeTransaction(InitPaymentRequest $request)
    {
        return $this->paystackService->initializeTransaction($request);
    }

    public function getSavedCards(Request $request)
    {
        return $this->paystackService->getSavedCards($request);
    }
}
