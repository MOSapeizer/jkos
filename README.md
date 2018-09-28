<p align="center"><img src="https://d1.awsstatic.com/logos/customers/China/JKOS-logo.46e22ea31cbc26d9020ce6948e52004acbe7dd95.png"></p>

[![Latest Stable Version](https://poser.pugx.org/noclaf/jkos/v/stable)](https://packagist.org/packages/noclaf/jkos)
[![Total Downloads](https://poser.pugx.org/noclaf/jkos/downloads)](https://packagist.org/packages/noclaf/jkos)
[![License](https://poser.pugx.org/noclaf/jkos/license)](https://packagist.org/packages/noclaf/jkos)

## 街口支付 php plugin v1.0.0

請先申請加入廠商即可提供 API 金鑰匙使用
https://www.jkos.com/client.html

*需要使用 php 套件管理 composer 請預先安裝到開發環境

立即安裝的語法，請先切到您的開發目錄後執行
<pre>
composer require noclaf/jkos // 使用 composer 安裝
</pre>

PHP 使用語法
<pre>
require __DIR__ . '/vendor/autoload.php'; // 引入 composer

use Noclaf\Payment\Jkos; 

$api_key = 'input_your_api_key';   // 由街口提供
$secret = 'input_your_secret';     // 由街口提供
$store_id = 'input_your_store_id'; // 由街口提供

$platform_order_id = 'input_your_order_id';  // 您的自訂訂單編號
$total_price = 100; // 訂單的金額
$final_price = 100; // 訂單實際需支付的金額

$jkos = new Jkos( $api_key, $secret, $store_id );
$jkos->enableTestEnv(); // 如果是測試環境的話，加上這行

echo $jkos->getPaymentUrl($platform_order_id, $total_price, $final_price); // 取得付款連結
echo "\n";
echo $jkos->queryOrders($platform_order_id); // 可查詢訂單，可把訂單編號用, 串成字串，或是直接給字串陣列
echo "\n";
echo $jkos->refundOrder($platform_order_id, 100); // 退款需要單一訂單編號跟自訂退款金額
echo $jkos->getResponseCode(); // 這邊會看到訂單不存在，因為上面的其實還沒完成支付動作
echo "\n";
</pre>
