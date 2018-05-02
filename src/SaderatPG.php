<?php

namespace SaderatPaymentGateway;

use SoapClient;

class SaderatPG
{
    private $terminalId = null;
    private $merchantId = null;
    private $publicKey = null;
    private $privateKey = null;
    private $callbackURL = null;
    private $requestUrl = 'https://mabna.shaparak.ir/TokenService?wsdl';
    private $verificationUrl = 'https://mabna.shaparak.ir/TransactionReference/TransactionReference?wsdl';
    private $token = null;

    public function __construct($tid, $mid, $public_key, $private_key, $callback_url = null)
    {
        $this->setTerminalId($tid);
        $this->setMerchantId($mid);
        $this->setPublicKey($public_key);
        $this->setPrivateKey($private_key);

        if ($callback_url)
            $this->setCallbackURL($callback_url);

    }

    private function setTerminalId($tid)
    {
        if(strlen($tid) != 8)
            throw new SaderatPGException(1, 'input');

        $this->terminalId = $tid;
    }

    private function setMerchantId($mid)
    {
        if(strlen($mid) != 15)
            throw new SaderatPGException(2, 'input');

        $this->merchantId = $mid;
    }

    private function setPublicKey($public_key)
    {
        $this->publicKey = @file_get_contents($public_key);

        if(!$this->publicKey)
            throw new SaderatPGException(3, 'input');

    }

    private function setPrivateKey($private_key)
    {
        $this->privateKey = @file_get_contents($private_key);

        if(!$this->privateKey)
            throw new SaderatPGException(4, 'input');
    }

    private function setCallbackURL($callback_url)
    {
        if(!filter_var($callback_url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED))
            throw new SaderatPGException(5, 'input');

        $this->callbackURL = $callback_url;
    }

    private function encrypt($val)
    {
        openssl_public_encrypt($val, $encrypted, $this->publicKey);
        return base64_encode($encrypted);
    }

    private function sign($val)
    {
        openssl_sign($val, $signature, $this->privateKey, OPENSSL_ALGO_SHA1);
        return base64_encode($signature);
    }


    public function getToken(int $amount, $crn, $callbackURL = null)
    {
        if($amount < 1000)
            throw new SaderatPGException(6, 'input');

        $crn_len = strlen($crn);
        if($crn_len < 3 or $crn_len > 20)
            throw new SaderatPGException(7, 'input');


        if($callbackURL)
            $this->setCallbackURL($callbackURL);
        else if (!$this->callbackURL)
            throw new SaderatPGException(8, 'input');

        $signature = $this->sign($amount.$crn.$this->merchantId.$this->callbackURL.$this->terminalId);
        try {
            $soap = new SoapClient($this->requestUrl);
            $response = $soap->reservation([
                'Token_param' => [
                    'AMOUNT' => $this->encrypt($amount),
                    'CRN' => $this->encrypt($crn),
                    'MID' => $this->encrypt($this->merchantId),
                    'REFERALADRESS' => $this->encrypt($this->callbackURL),
                    'TID' => $this->encrypt($this->terminalId),
                    'SIGNATURE' => $signature,
                ]
            ]);
        } catch (\Exception $e) {
            throw new SaderatPGException(0, 'soap', 'Code: '.$e->getCode().' Message: '.$e->getMessage());
        }

        if ($response->return->result != 0) {
            throw new SaderatPGException($response->return->result, 'token', $response->return->token);
        }

        $this->checkSign($response->return->token, $response->return->signature);

        $this->token = $response->return->token;

        return $this->token;
    }

    /**
     * verify paid transaction
     * before using this function please check
     * response code that must be equal to '00'
     *
     * @param $token
     * @param $crn
     * @param $trn
     * @param $sign
     * @return bool
     * @throws SaderatPGException
     */
    public function verifyTransaction($token, $crn, $trn, $sign)
    {
        $crn_len = strlen($crn);
        if($crn_len < 3 && $crn_len > 20)
            throw new SaderatPGException(7, 'input');

        $this->checkSign($token.$crn.$trn, $sign);


        $signature = $this->sign($this->merchantId.$trn.$crn);
        try {
            $soap = new SoapClient($this->verificationUrl);
            $response = $soap->sendConfirmation([
                'SaleConf_req' => [
                    'MID' => $this->encrypt($this->merchantId),
                    'CRN' => $this->encrypt($crn),
                    'TRN' => $this->encrypt($trn),
                    'SIGNATURE' => $signature,
                ]
            ]);
        } catch (\Exception $e) {
            throw new SaderatPGException(0, 'soap', 'Code: '.$e->getCode().' Message: '.$e->getMessage());
        }

        $response = $response->return;

        if($response->RESCODE == '0') {

            $this->checkSign($response->RESCODE.$response->REPETETIVE.$response->AMOUNT.$response->DATE.$response->TIME.$response->TRN.$response->STAN, $response->SIGNATURE);

            return true;
        } else
            throw new SaderatPGException($response->RESCODE, 'verify');

    }

    private function checkSign($data, $signature)
    {
        $check_sign = openssl_verify($data, base64_decode($signature), $this->publicKey);
        if ($check_sign == 0) {
            throw new SaderatPGException(1, 'soap');
        } elseif ($check_sign == -1) {
            throw new SaderatPGException(2, 'soap');
        }
    }

}