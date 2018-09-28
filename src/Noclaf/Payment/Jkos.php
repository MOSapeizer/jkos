<?php

namespace Noclaf\Payment;

use JsonSerializable;

class Jkos implements JsonSerializable {

    const test_entry_url = 'https://test-onlinepay.jkopay.com/platform/entry';
    const test_refund_url = 'https://test-onlinepay.jkopay.com/platform/refund';
    const test_inquiry_url = 'https://test-onlinepay.jkopay.com/platform/inquiry';

    const live_entry_url = 'https://onlinepay.jkopay.com/platform/entry';
    const live_refund_url = 'https://onlinepay.jkopay.com/platform/refund';
    const live_inquiry_url = 'https://onlinepay.jkopay.com/platform/inquiry';

    static private $is_test = true;

    private $api_key = 'lzMLNYCmYmnOCUNjkKRLxOyRfjefYUTvXKv4';
    private $digest;
    private $secret = 'rsUvuVe_wrpbq3Ulb5FX2hSMkcg9wVYYL4onV6zrYcGL5RasgJJ3xRHri-Dka2DXUf3czxCIXdUhFEXX5eRRrg';
    private $store_id = 'c1101e2b-b5a6-11e8-9a4e-0ab9182841ae';

    private $platform_order_id;
    private $currency = 'TWD';
    private $total_price = 0;
    private $final_price = 0;

    private $valid_time;
    private $confirm_url;
    private $result_url;
    private $result_display_url;
    private $payment_type = 'onetime';
    private $escrow = false;
    private $products = [];

    private $payload = '';

    /**
     * Jkos constructor.
     * @param $api_key
     * @param $secret
     * @param $store_id
     * @param $platform_order_id
     * @param $total_price
     * @param $final_price
     */
    public function __construct( $api_key, $secret, $store_id,
                                 $platform_order_id, $total_price, $final_price )
    {
        $this->api_key = $api_key;
        $this->secret = $secret;
        $this->store_id = $store_id;
        $this->platform_order_id = $platform_order_id;
        $this->total_price = $total_price;
        $this->final_price = $final_price;
        $this->payload = json_encode( $this );
        $this->digest = $this->makeDigest( $this->payload, $secret );
    }

    /**
     * 電商平台呼叫此 API 取得街口付款 payment_url，
     * 電商平台交易序號 為唯一值不可重複;
     * 當訂單付款未完成前，重複呼叫此 API 會回覆 同一街口付款網址。
     */
    public function getPaymentUrl()
    {
        $ch = curl_init( ( self::$is_test ) ? self::test_entry_url : self::live_entry_url );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $this->payload );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'API-KEY:' . $this->api_key,
            'DIGEST:' . $this->digest,
        ] );

        $result = curl_exec( $ch );

        print_r($result);

    }

    private function makeDigest( $payload, $secret )
    {
        $this->digest = hash_hmac( 'sha256', utf8_encode( $payload ), utf8_encode( $secret ) );

        return $this->digest;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'store_id'          => $this->store_id,
            'platform_order_id' => $this->platform_order_id,
            'currency'          => $this->currency,
            'total_price'       => $this->total_price,
            'final_price'       => $this->final_price,
            'escrow'            => $this->escrow,
            'payment_type'      => $this->payment_type,
        ];
    }
}