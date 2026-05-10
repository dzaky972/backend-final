<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey    = config('midtrans.server_key');
        Config::$clientKey    = config('midtrans.client_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized  = config('midtrans.is_sanitized');
        Config::$is3ds        = config('midtrans.is_3ds');
    }

    /**
     * Create a Snap transaction and return token + redirect URL.
     */
    public function createSnapTransaction(array $payload): array
    {
        $params = [
            'transaction_details' => [
                'order_id'     => $payload['order_id'],
                'gross_amount' => $payload['gross_amount'],
            ],
            'customer_details' => [
                'first_name' => $payload['customer']['first_name'],
                'email'      => $payload['customer']['email'],
                'phone'      => $payload['customer']['phone'],
            ],
            'item_details' => $payload['items'],
            'callbacks'    => [
                'finish' => $payload['finish_url'],
            ],
        ];

        $snapToken   = Snap::getSnapToken($params);
        $redirectUrl = Config::$isProduction
            ? "https://app.midtrans.com/snap/v2/vtweb/{$snapToken}"
            : "https://app.sandbox.midtrans.com/snap/v2/vtweb/{$snapToken}";

        return [
            'snap_token'   => $snapToken,
            'redirect_url' => $redirectUrl,
            'client_key'   => Config::$clientKey,
        ];
    }

    /**
     * Verify the SHA-512 signature from Midtrans webhook.
     */
    public function verifySignature(array $notification): bool
    {
        $orderId           = $notification['order_id'] ?? '';
        $statusCode        = $notification['status_code'] ?? '';
        $grossAmount       = $notification['gross_amount'] ?? '';
        $serverKey         = config('midtrans.server_key');

        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        return $expected === ($notification['signature_key'] ?? '');
    }
}