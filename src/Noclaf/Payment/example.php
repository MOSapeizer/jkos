<?php
require __DIR__ . '/vendor/autoload.php';

use Noclaf\Payment\Jkos;

$api_key = 'input_your_api_key';
$secret = 'input_your_secret';
$store_id = 'input_your_store_id';
$platform_order_id = 'input_your_order_id';
$total_price = 100;
$final_price = 100;

$jkos = new Jkos( $api_key, $secret, $store_id );
$jkos->enableTestEnv(); // if in test env

echo $jkos->getPaymentUrl($platform_order_id, $total_price, $final_price);
echo "\n";
echo $jkos->queryOrders($platform_order_id);
echo "\n";
echo $jkos->refundOrder($platform_order_id, 100);
echo $jkos->getResponseCode();
echo "\n";