<?php

namespace Creationent\Payware\Model;

class Payware extends \Magento\Payment\Model\Method\Cc
{
    const CODE                      = 'creationent_payware';
    const RESPONSE_CODE_APPROVED    = 'Approval';
    const ACTION_AUTHORIZE          = 'authorize';
    const ACTION_AUTHORIZE_CAPTURE  = 'authorize_capture';

    protected $_code                = self::CODE;
    protected $_canAuthorize        = true;
    protected $_canCapture          = true;

    /**
     * Capture Payment.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {

        try {
      $nonce = uniqid();
    $timestamp = (string)time();
    
    $requestData = [
        "Ecommerce" => [
            "OrderNumber" => $payment->getOrder()->getIncrementId(),
            "Amounts" => [
                "Total" => $amount
            ],
            "CardData" => [
                "Number" => $payment->getCcNumber(),
                "Expiration" => sprintf('%02d', $payment->getCcExpMonth()) . substr($payment->getCcExpYear(), -2, 2)
            ]
        ]
    ];


     $merchantCredentials = [
        
        "ID" => $this->getConfigData('merchant_id'),
        "KEY" => $this->getConfigData('merchant_key')
        
    ];

   // print_r($merchantCredentials);

    // your application's credentials
    $developerCredentials = [
        "ID" => $this->getConfigData('client_id'),
        "KEY" => $this->getConfigData('client_secret_key')
    ];
    //print_r($developerCredentials);

    // convert to json for transport
    $payload = json_encode($requestData);
    
    // this time our "type" parameter is "Authorization"
    // an Authorization charge can be adjusted once -- via a Capture -- before 
    // being settled. this is useful for adding tips, shipping, etc.
    $verb = "POST";
    $url = "https://api-cert.sagepayments.com/bankcard/v1/charges?type=Authorization";

    // the request is authorized via an HMAC header that we generate by
    // concatenating certain info, and then hashing it using our client key
    $toBeHashed = $verb . $url . $payload . $merchantCredentials["ID"] . $nonce . $timestamp;
    $hmac = $this->getHmac($toBeHashed, $developerCredentials["KEY"]);


    // ok, let's make the request! cURL is always an option, of course,
    // but i find that file_get_contents is a bit more intuitive.
    $config = [
        "http" => [
            "header" => [
                "clientId: " . $developerCredentials["ID"],
                "merchantId: " . $merchantCredentials["ID"],
                "merchantKey: " . $merchantCredentials["KEY"],
                "nonce: " . $nonce,
                "timestamp: " . $timestamp,
                "authorization: " . $hmac,
                "content-type: application/json",
            ],
            "method" => $verb,
            "content" => $payload,
            "ignore_errors" => true // exposes response body on 4XX errors
        ]
    ];
    $context = stream_context_create($config);
    $result = file_get_contents($url, false, $context);
    $response = json_decode($result);
    
    echo '<pre>';
    print_r($response);
    echo '</pre>';
    
    // ---------------------------------------------------------------
    
    // so, now we should have an approved $1.00 authorization charge.
    // what if we want to add a $0.15 tip?
    
    // we'll need a new nonce and timestamp:
    $nonce = uniqid();
    $timestamp = (string)time();
    
    // the request object is pretty straightforward:
    $requestData = [
        "Amounts" => [
            "Total" => $amount,
            "Tip" => "0"
        ]
    ];
    print_r($requestData);
    
    $payload = json_encode($requestData);

    // if you're familiar wtih RESTful APIs, you might have guessed this part:
    // we're going to make a PUT request to update the previous transaction.
    $verb = "PUT";
    $url = "https://api-cert.sagepayments.com/bankcard/v1/charges/" . $response->reference;

    // and then hmac...
    $toBeHashed = $verb . $url . $payload . $merchantCredentials["ID"] . $nonce . $timestamp;
    $hmac = $this->getHmac($toBeHashed, $developerCredentials["KEY"]);
    
    // ... and submit!
    
    $config = [
        "http" => [
            "header" => [
                "clientId: " . $developerCredentials["ID"],
                "merchantId: " . $merchantCredentials["ID"],
                "merchantKey: " . $merchantCredentials["KEY"],
                "nonce: " . $nonce,
                "timestamp: " . $timestamp,
                "authorization: " . $hmac,
                "content-type: application/json",
            ],
            "method" => $verb,
            "content" => $payload,
            "ignore_errors" => true // exposes response body on 4XX errors
        ]
    ];
            $context = stream_context_create($config);
            $result = file_get_contents($url, false, $context);
            $response = json_decode($result);


            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/testpay.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info(var_dump($response,true));
            $logger->info(var_export($response,true));
            var_dump($response);
            $payment->setIsTransactionClosed(1);

        } catch (\Exception $e) {
            $this->debug($payment->getData(), $e->getMessage());
        }

        return $this;
    }


    public function getHmac($toBeHashed, $privateKey){
       
        $hmac = hash_hmac(
            "sha512", // use the SHA-512 algorithm...
            $toBeHashed, // ... to hash the combined string...
            $privateKey, // .. using your private dev key to sign it.
            true // (php returns hexits by default; override this)
        );
        // convert to base-64 for transport
        $hmac_b64 = base64_encode($hmac);
        return $hmac_b64;
    }


   
}