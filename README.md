# PhpMellatBank
Iranian Mellat Bank Gateway for PHP Projects

## Installation

```
composer require miladrahimi/phpmellatbank
```

### Lifecycle

The process of an online payment with Mellat bank gateway consists of following steps:

* Request for a new payment

* Redirect user to the bank gateway page

* Verify payment on callback page

## Getting Started

To use the package for your payments you must instantiate the `Gateway` class 
and provide its configuration.

```
use MiladRahimi\PhpMellatBank\Gateway;

$config = [
    'terminalId' => '...',
    'userName' => '...',
    'userPassword' => '...',
    'callBackUrl' => 'https://example.com/payment/callback',
];

$gateway = new Gateway($config);
```

## Request Payment

As mentioned above, a the first step you must request for a new payment.
It can be done this way:

```
$refId = $gateway->requestPayment($amountInRial, 'Some optional description...');
```

This method would throws `GatewayException` if it could not call the bank server api,
`MellatException`if the bank response is something other than 0 (success).

`MellatException` holds the bank response in its message.

If there was no error it would returns the `RefId`.

## Redirect to Gateway

Now that you have gotten the `RefId` you can lead user to the bank gateway.

To do this you should display an html form like this:

```
<form action="$bankGatewayUrl" method="post">
    <input type="hidden" name="RefId" value="$refId">
    <input type="submit" value="Pay">
</form>
```

The `$bankGatewayUrl` is an url that bank provides for you
but you can get it from a `Gateway` instance this way:

```
$gateway->url()
```

## Verify Payment

User completes the payment in bank gateway
then bank redirects him to the callback you have provided in the configuration,
it also sends some parameters to the callback with `POST` method.

Your callback url must verify the payment.

```
use MiladRahimi\PhpMellatBank\Gateway;

$gateway = new Gateway($config);

$refId = $gateway->checkPayment();

if($refId != false) {
    $bankResult = $gateway->verifyPayment();
}
```

`checkPayment()` method would return `$refId` or `false` if there were any error.

`verifyPayment()` method would return the bank response as an instance of `BankResult` class,
it throws `InvalidResponseException` if the request is not valid (might not from the bank),
`GatewayException` if it cannot call the bank server api, 
`MellatException` if the bank response is something other than 0 (success).

## Exceptions

* `GatewayException`: It'd be thrown when there were a problem on calling bank server APIs.
* `MellatException`: It'd be thrown when bank `ResCode` is not `0` (success).
It holds the real response in its message.
* `InvalidResponseException`: It'd be thrown in verification level (callback url) when the parameters are not valid.
This problem usually occurs when someone/something other than bank calls your callback url.

## License
PhpMellatBank is created by [Milad Rahimi](http://miladrahimi.com)
and released under the [MIT License](http://opensource.org/licenses/mit-license.php).