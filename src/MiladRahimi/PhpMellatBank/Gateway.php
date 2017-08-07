<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 8/8/2017
 * Time: 12:16 AM
 */

namespace MiladRahimi\PhpMellatBank;

use MiladRahimi\PhpMellatBank\Exceptions\GatewayException;
use SoapClient;

class Gateway
{
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
     * @return string Bank Response
     * @throws GatewayException
     */
    public function requestPayment($amount, $additionalData = null)
    {
        /** @var object $client */
        $client = new SoapClient('https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl');
        $namespace = 'http://interfaces.core.sw.bps.com/';

        if (empty($client) || $client->getError()) {
            throw new GatewayException('Gateway is not available.');
        }

        $this->options['orderId'] = time() . mt_rand(100000, 999999);
        $this->options['amount'] = $amount;
        $this->options['localDate'] = date('Ymd');
        $this->options['localTime'] = date('His');
        $this->options['additionalData'] = $additionalData;

        $result = $client->call('bpPayRequest', $this->options, $namespace);

        $resultArray = explode(',', $result);
        $response = $resultArray[0];

        if ($client->fault) {
            throw new GatewayException('Fault: ' . $client->fault);
        }

        if ($response != 0) {
            throw new GatewayException('Response: ' . $response);
        }

        return $result;
    }
}