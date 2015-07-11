<?php

namespace Omnipay\Rabobank\Message;

use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Common\Exception\InvalidRequestException;

/**
 * Rabobank Purchase Request
 */
class PurchaseRequest extends AbstractRequest
{
    public $testEndpoint = 'https://payment-webinit.simu.omnikassa.rabobank.nl/paymentServlet';
    public $liveEndpoint = 'https://payment-webinit.omnikassa.rabobank.nl/paymentServlet';

    public function getMerchantId()
    {
        return $this->getParameter('merchantId');
    }

    public function setMerchantId($value)
    {
        return $this->setParameter('merchantId', $value);
    }

    public function getKeyVersion()
    {
        return $this->getParameter('keyVersion');
    }

    public function setKeyVersion($value)
    {
        return $this->setParameter('keyVersion', $value);
    }

    public function getSecretKey()
    {
        return $this->getParameter('secretKey');
    }

    public function setSecretKey($value)
    {
        return $this->setParameter('secretKey', $value);
    }

    public function getCustomerLanguage()
    {
        return $this->getParameter('customerLanguage');
    }

    public function setCustomerLanguage($value)
    {
        return $this->setParameter('customerLanguage', $value);
    }

    public function getData()
    {
        $this->validate('merchantId', 'keyVersion', 'secretKey', 'amount', 'returnUrl', 'currency');
        
        // Generate a unique hash for this transaction
        $transactionReference = md5($this->getTransactionId() . uniqid());

        $data = array();
        $data['Data'] = implode(
            '|',
            array(
                'amount='.$this->getAmountInteger(),
                'currencyCode='.$this->getCurrencyNumeric(),
                'merchantId='.$this->getMerchantId(),
                'normalReturnUrl='.$this->getReturnUrl(),
                'automaticResponseUrl='.($this->getNotifyUrl() ?: $this->getReturnUrl()),
                'transactionReference='.$transactionReference,
                'keyVersion='.$this->getKeyVersion(),
                'paymentMeanBrandList='.$this->getPaymentMethod(),
                'customerLanguage='.$this->getCustomerLanguage(),
                'orderId='.$this->getTransactionId(),
            )
        );
        $data['InterfaceVersion'] = 'HP_1.0';

        return $data;
    }

    public function generateSignature($data)
    {
        if (empty($data['Data'])) {
            throw new InvalidRequestException('Missing Data parameter');
        }

        return hash('sha256', $data['Data'].$this->getSecretKey());
    }

    public function sendData($data)
    {
        $data['Seal'] = $this->generateSignature($data);

        return $this->response = new PurchaseResponse($this, $data);
    }

    public function getEndpoint()
    {
        return $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint;
    }
}
