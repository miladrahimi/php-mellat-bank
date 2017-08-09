<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 8/8/2017
 * Time: 12:16 AM
 */

namespace MiladRahimi\PhpMellatBank;

use MiladRahimi\PhpMellatBank\Exceptions\GatewayException;
use MiladRahimi\PhpMellatBank\Exceptions\UnsuccessfulPaymentException;
use MiladRahimi\PhpMellatBank\Values\BankResult;
use nusoap_client;

class Gateway
{
    const WSDL = 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';

    const SOAP_NAMESPACE = 'http://interfaces.core.sw.bps.com/';

    const GATEWAY_URL = 'https://bpm.shaparak.ir/pgwchannel/startpay.mellat';

    /**
     * Payment options
     *
     * @var array
     */
    private $options = [];

    /**
     * Gateway constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = [
            'terminalId' => $options['terminalId'],
            'userName' => $options['userName'],
            'userPassword' => $options['userPassword'],
            'callBackUrl' => $options['callBackUrl'],
            'payerId' => 0,
        ];
    }

    /**
     * Request a new payment
     *
     * @param int $amount
     * @param string $additionalData
     * @return string ReferenceId
     * @throws GatewayException
     */
    public function requestPayment($amount, $additionalData = '')
    {
        $client = $this->createSoapClient();

        $parameters = $this->options;

        $parameters['orderId'] = time() . mt_rand(100000, 999999);
        $parameters['amount'] = $amount;
        $parameters['localDate'] = date('Ymd');
        $parameters['localTime'] = date('His');
        $parameters['additionalData'] = $additionalData;

        $result = $client->call('bpPayRequest', $parameters, self::SOAP_NAMESPACE);

        $resultArray = explode(',', $result);
        $response = $resultArray[0];

        if ($client->fault) {
            throw new GatewayException('Fault');
        }

        if ($e = $client->getError()) {
            throw new GatewayException('Error: ' . $e);
        }

        if ($response != 0) {
            throw new GatewayException('Response: ' . $response);
        }

        return $resultArray[1];
    }

    /**
     * Get action url for payment html form
     *
     * @return string url
     */
    public function formActionUrl()
    {
        return self::GATEWAY_URL;
    }

    /**
     * Get reference id if the payment is successful or false if not
     *
     * @return string|false
     */
    public function checkPayment()
    {
        if (isset($_POST['ResCode']) && $_POST['ResCode'] == 0) {
            return $_POST['RefId'];
        }

        return false;
    }

    /**
     * Verify the payment and get bank response
     *
     * @return BankResult
     * @throws UnsuccessfulPaymentException
     */
    public function verifyPayment()
    {
        if ($this->checkPayment() == false) {
            throw new UnsuccessfulPaymentException();
        }

        $client = $this->createSoapClient();

        $parameters = array(
            'terminalId' => $this->options['terminalId'],
            'userName' => $this->options['userName'],
            'userPassword' => $this->options['userPassword'],
            'orderId' => $_POST['SaleOrderId'],
            'saleOrderId' => $_POST['SaleOrderId'],
            'saleReferenceId' => $_POST['SaleReferenceId']
        );

        $inquiryResult = $client->call('bpInquiryRequest', $parameters, self::SOAP_NAMESPACE);
        if ($inquiryResult != 0) {
            throw new UnsuccessfulPaymentException();
        }

        $client->call('bpVerifyRequest', $parameters, self::SOAP_NAMESPACE);

        $client->call('bpSettleRequest', $parameters, self::SOAP_NAMESPACE);

        $bankResult = new BankResult();
        $bankResult->refId = $_POST['RefId'];
        $bankResult->resCode = $_POST['ResCode'];
        $bankResult->saleOrderId = $_POST['SaleOrderId'];
        $bankResult->saleReferenceId = $_POST['SaleReferenceId'];

        return $bankResult;
    }

    /**
     * @return nusoap_client
     * @throws GatewayException
     */
    private function createSoapClient()
    {
        /** @var object $client */
        $client = new nusoap_client(self::WSDL);

        if (empty($client)) {
            throw new GatewayException('Gateway is not available.');
        }

        if ($e = $client->getError()) {
            throw new GatewayException('Error: ' . $e);
        }

        return $client;
    }
}