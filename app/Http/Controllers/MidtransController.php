<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Notification;

class MidtransController extends Controller
{
    public function callback()
    {
        // Midtrans Configuration
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$clientKey = config('services.midtrans.clientKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        // Midtrans Notification Instance
        $notification = new Notification();

        // Assign to Variable
        $status = $notification->transaction_status;
        $type = $notification->payment_type;
        $fraud = $notification->fraud_status;
        $order_id = $notification->order_id;

        // Get Transaction ID
        $order = explode('-', $order_id);

        // Find Transaction By ID
        $transaction = Transaction::findOrFail($order[1]);

        // Handle Midtrans Notification Status
        if ($status == 'capture') {
            if ($type == 'credit_card') {
                if ($fraud == 'challenge') {
                    $transaction->status = 'PENDING';
                }
                else {
                    $transaction->status = 'SUCCESS';
                }
            }
        }
        else if ($status == 'settlement') {
            $transaction->status = 'SUCCESS';
        }
        else if ($status == 'pending') {
            $transaction->status = 'PENDING';
        }
        else if ($status == 'deny') {
            $transaction->status = 'PENDING';
        }
        else if ($status == 'expire') {
            $transaction->status = 'FAILED';
        }
        else if ($status == 'cancel') {
            $transaction->status = 'FAILED';
        }

        // Save Transaction
        $transaction->save();

        // Return Response
        return response()->json([
            'meta' => [
                'code' => 200,
                'message' => 'Midtrans Payment Success'
            ]
        ]);
    }
}