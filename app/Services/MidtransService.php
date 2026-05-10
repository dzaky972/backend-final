<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey    = config('midtrans.server_key');
        Config::$isProduction = (bool) config('midtrans.is_production', false);
        Config::$isSanitized  = (bool) config('midtrans.is_sanitized', true);
        Config::$is3ds        = (bool) config('midtrans.is_3ds', true);
    }

    /**
     * Create Snap transaction. Cukup 1× call ke Midtrans → dapat token + redirect_url sekaligus.
     */
    public function createSnapTransaction(array $params): array
    {
        $payload = [
            'transaction_details' => [
                'order_id'     => $params['order_id'],
                'gross_amount' => (int) $params['gross_amount'],
            ],
            'customer_details' => [
                'first_name' => $params['customer']['first_name'] ?? 'Customer',
                'email'      => $params['customer']['email']      ?? 'noreply@example.com',
                'phone'      => $params['customer']['phone']      ?? '',
            ],
            'item_details' => $params['items'] ?? [],
            'callbacks' => [
                'finish' => $params['finish_url'] ?? config('app.frontend_url', 'http://localhost:5173'),
            ],
        ];

        // createTransaction mengembalikan object {token, redirect_url}
        $tx = Snap::createTransaction($payload);

        return [
            'snap_token'   => $tx->token       ?? null,
            'redirect_url' => $tx->redirect_url ?? null,
            'client_key'   => config('midtrans.client_key'),
        ];
    }

    /**
     * Verify signature notifikasi Midtrans (sha512).
     */
    public function verifySignature(array $notification): bool
    {
        $orderId      = $notification['order_id']      ?? '';
        $statusCode   = $notification['status_code']   ?? '';
        $grossAmount  = $notification['gross_amount']  ?? '';
        $signatureKey = $notification['signature_key'] ?? '';
        $serverKey    = config('midtrans.server_key', '');

        if (empty($signatureKey) || empty($serverKey)) {
            return false;
        }

        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        return hash_equals($expected, $signatureKey);
    }
}
