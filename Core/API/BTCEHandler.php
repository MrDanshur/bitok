<?php

namespace Core\API;

class BTCEHandler implements StocksHandlerInterface
{
    private $key;
    private $secret;
    private $method;
    private $sign;

    public function __construct()
    {
        // API settings
        $this->key = '';
        $this->secret = '';

    }

    private function sign()
    {
        $req['method'] = $this->method;
        $mt = explode(' ', microtime());
        $req['nonce'] = $mt[1];

        // generate the POST data string
        $post_data = http_build_query($req, '', '&');

        $this->sign = hash_hmac("sha512", $post_data, $this->secret);

        return $post_data;


    }

    private function generateHeaders($data = null, $url = "https://btc-e.com/api/2/btc_usd/fee")
    {
        $headers = ['Sign: '.$this->sign, 'Key: '.$this->key];
        // our curl handle (initialize if required)
        static $ch = null;
        if (is_null($ch)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; BTCE PHP client; '.php_uname('s').'; PHP/'.phpversion().')');
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        return $ch;
    }

    public function sendRequest($method, array $req = []) {
        $this->method = $method;
        $data = $this->sign();

        // generate the extra headers
        $ch = $this->generateHeaders($data, "https://btc-e.com/tapi");

        // run the query
        $res = curl_exec($ch);
        if ($res === false) throw new \Exception('Could not get reply: '.curl_error($ch));
        $dec = json_decode($res, true);
        if (!$dec) throw new \Exception('Invalid data received, please make sure connection is working and requested API exists');
        return $dec;
    }

}