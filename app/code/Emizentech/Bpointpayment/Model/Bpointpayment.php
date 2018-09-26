<?php

namespace Emizentech\Bpointpayment\Model;

class Bpointpayment extends \Magento\Payment\Model\Method\Cc
{
    const ACTION_PURCHASE              = 'purchase';
    const ACTION_PREAUTH_CAPTURE       = 'preauth_capture';
    
    const CODE                         = 'emizentech_bpointpayment';
    const RESPONSE_CODE_APPROVED       = '0';
    const ACTION_AUTHORIZE             = 'authorize';
    const ACTION_AUTHORIZE_CAPTURE     = 'authorize_capture';
    const ACTION_REFUND                = 'refund';
    const ORDER_STATUS_PROCESSING      = 'processing';
    const ORDER_STATUS_PENDING_CAPTURE = 'pending_capture';
    const API_URL                      = 'https://www.bpoint.com.au/webapi/';
    const PROXY_HOST                   = '';
    const PROXY_PORT                   = '';

    protected $_code                   = self::CODE;
    protected $_canAuthorize           = true;
    protected $_canCapture             = true;


     /**
     * Key for storing transaction id in additional information of payment model
     * @var string
     */
    protected $_realTransactionIdKey = 'real_transaction_id';

    /**
     * Do not validate payment form using server methods
     *
     * @return  bool
     */
    public function validate() {
        return true;
    }

    /**
     * Capture Payment.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/test.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info('Your text message');

        if ($amount <= 0) {
            throw new \Exception('Invalid amount for capture.');
        }
        $order      = $payment->getOrder();
        $txnNumber  = $order->getIncrementId();
        $currency   = 'AUS';
        $lowestDenominationAmount = $this->getLowestDenominationAmount($amount, $currency);

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/test.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        //$logger->info(var_export($order->debug(),true));
        $logger->info('Currency'.$currency);
        $logger->info('lowestDenominationAmount'.$lowestDenominationAmount);


        $txn = $this->getBpointAPIObject();
        $txn->setAction('capture');
        $txn->setAmount($lowestDenominationAmount);
        $txn->setCurrency($currency);
        $txn->setCrn1($order->getIncrementId());
        $txn->setCrn2($order->getCustomerId());
        $txn->setOriginalTxnNumber($txnNumber);
        $result = $txn->processTransaction();
        
        if (isset($result->TxnResp->ResponseCode)) {
            if ($result->TxnResp->ResponseCode == self::RESPONSE_CODE_APPROVED) {
                $payment
                        ->setTransactionId($result->TxnResp->ReceiptNumber)
                        ->setIsTransactionClosed(0)
                        ->setTransactionAdditionalInfo($this->_realTransactionIdKey, $result->TxnResp->TxnNumber);
                return $this;
            } else {
                throw new \Exception(__('Payment capturing error. ') . 'Error reason: ' . $result->TxnResp->ResponseText);
            }
        }
        throw new \Exception(__('Payment capturing error. '));
    } 
    

    private function getBpointAPIObject() {
        $proxyHost      = self::PROXY_HOST;
        $proxyPort      = self::PROXY_PORT;
        $apiUsername    = $this->getConfigData('api_username');
        $apiPassword    = $this->getConfigData('api_password');
        $mebershipId    = $this->getConfigData('merchant_number');
        $apiUrl         = $this->getApiUrl() . 'v2';
        $txn            = new \Emizentech\Bpointpayment\Model\Source\BpointApi($apiUsername, $apiPassword, $mebershipId, $apiUrl, $proxyHost, $proxyPort);
        $txn->setCrn3("");
        $txn->setBillerCode(null);
        $txn->setMerchantReference("");
        $txn->setSubType("single");
        $txn->setType("internet");
        if ($this->getConfigData('test')) {
            $testMode = true;
        } else {
            $testMode = false;
        }
        $txn->setTestMode($testMode);
        return $txn;
    }

       /**
     * Get API url
     *
     * @return string
     */
    public function getApiUrl() {
        $uri = $this->getConfigData('gateway_url');
        if ($uri) {
            if (substr($uri, -1) != '/') {
                return $uri . '/';
            } else {
                return $uri;
            }
        } else {
            return self::API_URL;
        }
    }


       /**
     * Return additional information`s transaction_id value of parent transaction model
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return string
     */
    protected function _getRealParentTransactionId($payment) {
        $transaction = $payment->getTransaction($payment->getParentTransactionId());
        if ($transaction) {
            return $transaction->getAdditionalInformation($this->_realTransactionIdKey);
        }
        throw new \Exception(__('Payment error. Transaction was not found.'));
    }

     /**
     * get lowest denomination amount
     * return number: 5056 for AUD 50.56  |  51 for JPY 51
     */
    public function getLowestDenominationAmount($amount, $currency) {
        $numberOfDigit = $this->getNumberOfDigitsAfterDecimal($currency);
        if ($numberOfDigit === null) {
            return null;
        }
        return round($amount * pow(10, $numberOfDigit));
    }

    
     /**
     * Return response.
     *
     * @return Bpoint_Payment_Model_Browsermethod_Response
     */
    public function getResponse() {
        return Mage::getSingleton('bpoint/browsermethod_response');
    }
    
        /**
     * get number of digits after decimal
     * return number: 2 for AUD, 0 for JPY
     */
    public function getNumberOfDigitsAfterDecimal($currency) {
        switch ($currency) {
            case 'BHD':
            case 'IQD':
            case 'JOD':
            case 'KWD':
            case 'LYD':
            case 'OMR':
            case 'TND':
                return 3;
            case 'AED':
            case 'AFN':
            case 'ALL':
            case 'AMD':
            case 'ANG':
            case 'AOA':
            case 'ARS':
            case 'AUD':
            case 'AWG':
            case 'AZN':
            case 'BAM':
            case 'BBD':
            case 'BDT':
            case 'BGN':
            case 'BMD':
            case 'BND':
            case 'BOB':
            case 'BRL':
            case 'BSD':
            case 'BTN':
            case 'BWP':
            case 'BZD':
            case 'CAD':
            case 'CDF':
            case 'CFA':
            case 'CFP':
            case 'CHF':
            case 'CNY':
            case 'COP':
            case 'CRC':
            case 'CUP':
            case 'CZK':
            case 'DKK':
            case 'DOP':
            case 'DZD':
            case 'ECS':
            case 'EGP':
            case 'ERN':
            case 'ETB':
            case 'EUR':
            case 'FJD':
            case 'FKP':
            case 'GBP':
            case 'GEL':
            case 'GGP':
            case 'GHS':
            case 'GIP':
            case 'GMD':
            case 'GWP':
            case 'GYD':
            case 'HKD':
            case 'HNL':
            case 'HRK':
            case 'HTG':
            case 'HUF':
            case 'IDR':
            case 'ILS':
            case 'INR':
            case 'IRR':
            case 'JMD':
            case 'KES':
            case 'KGS':
            case 'KHR':
            case 'KPW':
            case 'KYD':
            case 'KZT':
            case 'LAK':
            case 'LBP':
            case 'LKR':
            case 'LRD':
            case 'LSL':
            case 'LTL':
            case 'LVL':
            case 'MAD':
            case 'MDL':
            case 'MGF':
            case 'MKD':
            case 'MMK':
            case 'MNT':
            case 'MOP':
            case 'MRO':
            case 'MUR':
            case 'MVR':
            case 'MWK':
            case 'MXN':
            case 'MYR':
            case 'MZN':
            case 'NAD':
            case 'NGN':
            case 'NIO':
            case 'NOK':
            case 'NPR':
            case 'NZD':
            case 'PAB':
            case 'PEN':
            case 'PGK':
            case 'PHP':
            case 'PKR':
            case 'PLN':
            case 'QAR':
            case 'QTQ':
            case 'RON':
            case 'RSD':
            case 'RUB':
            case 'SAR':
            case 'SBD':
            case 'SCR':
            case 'SDG':
            case 'SEK':
            case 'SGD':
            case 'SHP':
            case 'SLL':
            case 'SOS':
            case 'SRD':
            case 'SSP':
            case 'STD':
            case 'SVC':
            case 'SYP':
            case 'SZL':
            case 'THB':
            case 'TJS':
            case 'TMT':
            case 'TOP':
            case 'TRY':
            case 'TTD':
            case 'TWD':
            case 'TZS':
            case 'UAH':
            case 'USD':
            case 'UYU':
            case 'UZS':
            case 'VEF':
            case 'WST':
            case 'XCD':
            case 'YER':
            case 'ZAR':
            case 'ZMW':
            case 'ZWD':
                return 2;
            case 'BIF':
            case 'BYR':
            case 'CLP':
            case 'CVE':
            case 'DJF':
            case 'GNF':
            case 'ISK':
            case 'JPY':
            case 'KMF':
            case 'KRW':
            case 'PYG':
            case 'RWF':
            case 'UGX':
            case 'VND':
            case 'VUV':
            case 'XAF':
            case 'XOF':
            case 'XPF':
                return 0;
            default:
                return null; //return null if currency code not found
        }
    }




    /**
     * refund the amount with transaction id
     *
     * @param string $payment Varien_Object object
     * @return Bpoint_Payment_Model_Browsermethod
     * @throws Mage_Core_Exception
     */
    protected function _refund(Varien_Object $payment, $amount) {
        if ($amount <= 0) {
           throw new \Exception(__('Invalid amount for refund.'));
        }

        if (!$payment->getParentTransactionId()) {
            throw new \Exception(__('Invalid transaction ID.'));
        }

        $order = $payment->getOrder();
        $txnNumber                  = $this->_getRealParentTransactionId($payment);
        $currency                   = $order->getBaseCurrencyCode();
        $lowestDenominationAmount   = $this->getLowestDenominationAmount($amount, $currency);

        $txn = $this->getBpointAPIObject();
        $txn->setAction('refund');
        $txn->setAmount($lowestDenominationAmount);
        $txn->setCurrency($currency);
        $txn->setCrn1($order->getIncrementId());
        $txn->setCrn2($order->getCustomerId());
        $txn->setOriginalTxnNumber($txnNumber);
        $result = $txn->processTransaction();

        if (isset($result->TxnResp->ResponseCode)) {
            if ($result->TxnResp->ResponseCode == self::RESPONSE_CODE_APPROVED) {
                $shouldCloseCaptureTransaction = $payment->getOrder()->canCreditmemo() ? 0 : 1;
                $payment
                        ->setTransactionId($result->TxnResp->ReceiptNumber)
                        ->setIsTransactionClosed(1)
                        ->setShouldCloseParentTransaction($shouldCloseCaptureTransaction)
                        ->setTransactionAdditionalInfo($this->_realTransactionIdKey, $result->TxnResp->TxnNumber);
                return $this;
            } else {
                throw new \Exception(__('Payment refunding error. ') . $result->TxnResp->ResponseText);
            }
        }
        throw new \Exception(__('Payment refunding error. '));
    }


}