<?php

namespace Noclaf\Payment;

use InvalidArgumentException;
use JsonSerializable;

class Jkos implements JsonSerializable {

    const test_entry_url = 'https://test-onlinepay.jkopay.com/platform/entry';
    const test_refund_url = 'https://test-onlinepay.jkopay.com/platform/refund';
    const test_inquiry_url = 'https://test-onlinepay.jkopay.com/platform/inquiry';

    const live_entry_url = 'https://onlinepay.jkopay.com/platform/entry';
    const live_refund_url = 'https://onlinepay.jkopay.com/platform/refund';
    const live_inquiry_url = 'https://onlinepay.jkopay.com/platform/inquiry';

    const response_code = [
        '000' => '成功',
        '100' => '訂單不存在',
        '101' => '此訂單號已付款',
        '102' => '超過 180 天無法退款',
        '103' => '退款金額錯誤',
        '200' => '失敗;參數錯誤',
        '201' => '失敗;驗證錯誤',
        '999' => '其他',
    ];

    static private $is_test = false;

    private $api_key = '';
    private $digest = '';
    private $secret = '';
    private $store_id = '';

    private $platform_order_id = '';
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
    private $result_json = '';
    private $response_code = '';
    private $message = '';
    private $payment_url = '';
    private $qr_img = '';
    private $qr_timeout = '';

    /**
     * Jkos constructor.
     * @param $api_key
     * @param $secret
     * @param $store_id
     * @throws \Exception
     */
    public function __construct( $api_key, $secret, $store_id )
    {
        if ( empty( $api_key ) || empty( $secret ) || empty( $store_id ) )
        {
            throw new InvalidArgumentException( 'InvalidArgument' );
        }

        $this->api_key = $api_key;
        $this->secret = $secret;
        $this->store_id = $store_id;
    }

    public function enableTestEnv()
    {
        self::$is_test = true;
    }

    /**
     * 電商平台呼叫此 API 取得街口付款 payment_url，
     * 電商平台交易序號 為唯一值不可重複;
     * 當訂單付款未完成前，重複呼叫此 API 會回覆 同一街口付款網址。
     * @param $platform_order_id
     * @param $total_price
     * @param $final_price
     * @return string
     */
    public function getPaymentUrl( $platform_order_id, $total_price, $final_price )
    {
        $this->getPaymentResult( $platform_order_id, $total_price, $final_price );

        return $this->payment_url;
    }

    public function getQrCodeImageUrl( $platform_order_id, $total_price, $final_price )
    {
        $this->getPaymentResult( $platform_order_id, $total_price, $final_price );

        return $this->qr_img;
    }

    public function refundOrder( $platform_order_id, $refund_amount )
    {
        $this->checkRefundArgument( $platform_order_id, $refund_amount );
        $this->makeRefundPayload( $platform_order_id, $refund_amount );
        $this->makeDigest( $this->payload, $this->secret );
        $this->postCurl( ( self::$is_test ) ? self::test_refund_url : self::live_refund_url );
        $this->saveRefundResult();

        return $this->response_code;
    }

    public function queryOrders( $platform_order_ids )
    {
        $this->makeQueryPayload( $platform_order_ids );
        $this->makeDigest( $this->payload, $this->secret );
        $this->getCurl( ( ( self::$is_test ) ? self::test_inquiry_url : self::live_inquiry_url ) . '?' . $this->payload );
        $this->saveQueryResult();

        return $this->result_json;
    }

    private function getCurl( $url )
    {
        $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'API-KEY:' . $this->api_key,
            'DIGEST:' . $this->digest,
        ] );

        $this->result_json = curl_exec( $ch );
    }

    private function postCurl( $url )
    {
        $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $this->payload );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'API-KEY:' . $this->api_key,
            'DIGEST:' . $this->digest,
        ] );

        $this->result_json = curl_exec( $ch );
    }

    public function getResultJson()
    {
        return $this->result_json;
    }

    private function makeDigest( $payload, $secret )
    {
        $this->digest = hash_hmac( 'sha256', utf8_encode( $payload ), utf8_encode( $secret ) );
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
        $payload = [
            'store_id'          => $this->store_id,
            'platform_order_id' => $this->platform_order_id,
            'currency'          => $this->currency,
            'total_price'       => $this->total_price,
            'final_price'       => $this->final_price,
            'escrow'            => $this->escrow,
            'payment_type'      => $this->payment_type,
        ];

        if ( ! empty( $this->valid_time ) ) $payload[ 'valid_time' ] = $this->valid_time;
        if ( ! empty( $this->confirm_url ) ) $payload[ 'confirm_url' ] = $this->confirm_url;
        if ( ! empty( $this->result_url ) ) $payload[ 'result_url' ] = $this->result_url;
        if ( ! empty( $this->result_display_url ) ) $payload[ 'result_display_url' ] = $this->result_display_url;
        if ( ! empty( $this->products ) ) $payload[ 'products' ] = $this->products;

        return $payload;
    }

    /**
     * @param mixed $valid_time
     */
    public function setValidTime( $valid_time )
    {
        $this->valid_time = $valid_time;
    }

    /**
     * @param mixed $confirm_url
     */
    public function setConfirmUrl( $confirm_url )
    {
        $this->confirm_url = $confirm_url;
    }

    /**
     * @param mixed $result_url
     */
    public function setResultUrl( $result_url )
    {
        $this->result_url = $result_url;
    }

    /**
     * @param mixed $result_display_url
     */
    public function setResultDisplayUrl( $result_display_url )
    {
        $this->result_display_url = $result_display_url;
    }

    /**
     * @param array $products
     */
    public function setProducts( $products )
    {
        $this->products = $products;
    }

    /**
     * 000 成功
     * 100 訂單不存在
     * 101 此訂單號已付款
     * 102 超過 180 天無法退款
     * 103 退款金額錯誤
     * 200 失敗;參數錯誤
     * 201 失敗;驗證錯誤
     * 999 其他
     */
    public function getResponseCode()
    {
        return ( ! empty( self::response_code[ $this->response_code ] ) ) ? self::response_code[ $this->response_code ] : '其他';
    }

    /**
     * @param $platform_order_id
     * @param $total_price
     * @param $final_price
     */
    private function makePaymentPayload( $platform_order_id, $total_price, $final_price )
    {
        $this->platform_order_id = $platform_order_id;
        $this->total_price = $total_price;
        $this->final_price = $final_price;

        $this->payload = json_encode( $this );
    }

    /**
     * @param $platform_order_id
     * @param $total_price
     * @param $final_price
     */
    private function checkPaymentArgument( $platform_order_id, $total_price, $final_price )
    {
        if ( empty( $platform_order_id ) || empty( $total_price ) || empty( $final_price ) )
        {
            throw new InvalidArgumentException( 'InvalidArgument' );
        }
    }

    private function savePaymentResult()
    {
        $result = json_decode( $this->result_json, true );

        ( ! empty( $result[ 'result' ] ) ) ? $this->response_code = $result[ 'result' ] : $this->response_code = '';
        ( ! empty( $result[ 'message' ] ) ) ? $this->message = $result[ 'message' ] : $this->message = '';
        ( ! empty( $result[ 'result_object' ][ 'payment_url' ] ) ) ? $this->payment_url = $result[ 'result_object' ][ 'payment_url' ] : $this->payment_url = '';
        ( ! empty( $result[ 'result_object' ][ 'qr_img' ] ) ) ? $this->qr_img = $result[ 'result_object' ][ 'qr_img' ] : $this->qr_img = '';
        ( ! empty( $result[ 'result_object' ][ 'qr_timeout' ] ) ) ? $this->qr_timeout = $result[ 'result_object' ][ 'qr_timeout' ] : $this->qr_timeout = '';
    }

    private function saveRefundResult()
    {
        $result = json_decode( $this->result_json, true );

        ( ! empty( $result[ 'result' ] ) ) ? $this->response_code = $result[ 'result' ] : $this->response_code = '';
        ( ! empty( $result[ 'message' ] ) ) ? $this->message = $result[ 'message' ] : $this->message = '';
    }

    private function saveQueryResult()
    {
        $result = json_decode( $this->result_json, true );

        ( ! empty( $result[ 'result' ] ) ) ? $this->response_code = $result[ 'result' ] : $this->response_code = '';
        ( ! empty( $result[ 'message' ] ) ) ? $this->message = $result[ 'message' ] : $this->message = '';
    }

    /**
     * @param $platform_order_id
     * @param $refund_amount
     */
    private function checkRefundArgument( $platform_order_id, $refund_amount )
    {
        if ( empty( $platform_order_id ) || empty( $refund_amount ) )
        {
            throw new InvalidArgumentException( 'InvalidArgument' );
        }
    }

    private function makeRefundPayload( $platform_order_id, $refund_amount )
    {
        $this->payload = json_encode( [
            'platform_order_id' => $platform_order_id,
            'refund_amount'     => $refund_amount,
        ] );
    }

    /**
     * @param $platform_order_ids
     */
    private function makeQueryPayload( $platform_order_ids )
    {
        if ( is_array( $platform_order_ids ) )
        {
            $this->payload = 'platform_order_ids=' . implode( ',', $platform_order_ids );
        } else
        {
            $this->payload = 'platform_order_ids=' . $platform_order_ids;
        }
    }

    /**
     * @param $platform_order_id
     * @param $total_price
     * @param $final_price
     */
    private function getPaymentResult( $platform_order_id, $total_price, $final_price )
    {
        $this->checkPaymentArgument( $platform_order_id, $total_price, $final_price );
        $this->makePaymentPayload( $platform_order_id, $total_price, $final_price );
        $this->makeDigest( $this->payload, $this->secret );
        $this->postCurl( ( self::$is_test ) ? self::test_entry_url : self::live_entry_url );
        $this->savePaymentResult();
    }
}