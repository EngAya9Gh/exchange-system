<?php
use App\Services\FaturaApiService;

$service = app(FaturaApiService::class);
$result = $service->getDeposit();
echo "Deposit Result:\n";
print_r($result);

// Let's also print the raw sendRequest output
$reflection = new ReflectionClass(FaturaApiService::class);
$method = $reflection->getMethod('sendRequest');
$method->setAccessible(true);
$rawResult = $method->invoke($service, 'GetDeposit');
echo "Raw Request Result:\n";
print_r($rawResult);
