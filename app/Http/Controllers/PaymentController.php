<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Handle webhook callback notification from Midtrans.
     */
    public function handleNotification(Request $request)
    {
        $result = $this->paymentService->processWebhookNotification($request->all());

        return response()->json([
            'message' => $result[1]
        ], $result[2]);
    }
}
