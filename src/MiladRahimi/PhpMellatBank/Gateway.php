<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 8/8/2017
 * Time: 12:16 AM
 */

namespace MiladRahimi\PhpMellatBank;

use MiladRahimi\PhpMellatBank\Exceptions\GatewayException;
use nusoap_client;

class Gateway
{
    const WSDL = 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';

    const SOAP_NAMESPACE = 'http://interfaces.core.sw.bps.com/';

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
    public function requestPayment($amount, $additionalData = null)
    {
        /** @var object $client */
        $client = new nusoap_client(self::WSDL);

        if (empty($client)) {
            throw new GatewayException('Gateway is not available.');
        }

        if ($e = $client->getError()) {
            throw new GatewayException('Error: ' . $e);
        }

        $this->options['orderId'] = time() . mt_rand(100000, 999999);
        $this->options['amount'] = $amount;
        $this->options['localDate'] = date('Ymd');
        $this->options['localTime'] = date('His');
        $this->options['additionalData'] = $additionalData;

        $result = $client->call('bpPayRequest', $this->options, self::SOAP_NAMESPACE);

        $resultArray = explode(',', $result);
        $response = $resultArray[0];

        if ($client->fault) {
            throw new GatewayException('Fault: ' . $client->fault);
        }

        if ($e = $client->getError()) {
            throw new GatewayException('Error: ' . $e);
        }

        if ($response != 0) {
            throw new GatewayException('Response: ' . $response);
        }

        return $resultArray[1];
    }
}