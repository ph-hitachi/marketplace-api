<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = Illuminate\Http\Request::create('/api/auth/register', 'POST', [
    'name' => 'John Doe',
    'email' => 'john.doe@example.com',
    'password' => 'Password123!',
    'password_confirmation' => 'Password123!',
    'role' => 'customer',
    'shop_name' => 'Johns Shop',
    'shop_description' => 'aliquam'
]);
$request->headers->set('Accept', 'application/json');

$response = $app->handle($request);
echo "STATUS: " . $response->getStatusCode() . "\n";
echo "HEADERS:\n" . $response->headers . "\n";
echo "BODY:\n" . $response->getContent() . "\n";
