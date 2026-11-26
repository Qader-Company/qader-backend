<?php

return [
    'gateway_not_configured' => 'Online payments are not configured yet.',
    'gateway_not_supported' => 'The selected payment gateway is not supported.',
    'invalid_webhook_signature' => 'The payment webhook signature is invalid.',
    'invalid_webhook_payload' => 'The payment webhook payload is invalid.',
    'webhook_received' => 'Payment update received.',
    'webhook_amount_mismatch' => 'The gateway amount or currency does not match the payment.',
    'payment_attempt_not_found' => 'The payment attempt was not found.',
    'idempotency_conflict' => 'This idempotency key was already used for a different payment request.',
    'evaluation_not_purchasable' => 'This evaluation is not available for purchase.',
    'unsupported_currency' => 'Currency :currency is not supported by the payment system.',
    'invalid_amount' => 'The payment amount is invalid.',
];
