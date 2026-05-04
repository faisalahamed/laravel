<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/api/incomes', 'POST', [
    'incomes' => [
        [
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'shop_id' => '123e4567-e89b-12d3-a456-426614174000', // Assuming fake shop id will fail validation, let's see the exact error
            'category_id' => '123e4567-e89b-12d3-a456-426614174000',
            'amount' => 100,
        ]
    ],
    'cash_transactions' => [
        [
            'id' => '123e4567-e89b-12d3-a456-426614174001',
            'shop_id' => '123e4567-e89b-12d3-a456-426614174000',
            'type' => 'income',
            'direction' => 'in',
            'amount' => 100,
        ]
    ]
]);

$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Content: " . $response->getContent() . "\n";
