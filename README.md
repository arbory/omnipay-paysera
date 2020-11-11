# Omnipay: Paysera

**Paysera gateway driver for the Omnipay PHP payment processing library**

[![Build Status](https://travis-ci.com/arbory/omnipay-paysera.svg?branch=master)](https://travis-ci.com/arbory/omnipay-paysera)
[![Coverage Status](https://coveralls.io/repos/github/arbory/omnipay-paysera/badge.svg?branch=master)](https://coveralls.io/github/arbory/omnipay-paysera?branch=master)

## Installation

Omnipay is installed via [Composer](http://getcomposer.org/). To install, require `league/omnipay` and `arbory/omnipay-paysera` with Composer:

```bash
composer require league/omnipay arbory/omnipay-paysera
```

For general usage instructions, please see the main [Omnipay](https://github.com/thephpleague/omnipay) repository.

## Requirements

* PHP 7.0+
* Omnipay v3+
* ext-openssl

## Basic Usage

The following gateways are provided by this package:

* Paysera

For general usage instructions, please see the main [Omnipay](https://github.com/thephpleague/omnipay) repository.

## Code Example

```php
use Omnipay\Omnipay;

// Setup payment gateway
$gateway = Omnipay::create('Paysera');
$gateway->setProjectId('123456');
$gateway->setPassword('abcde12345');

// Optionally to determine which order has been paid
$orderId = 1;

// Example card (actually customer) data
$card = [
    'email' => 'john.doe@example.com',
    'billingFirstName' => 'John',
    'billingLastName' => 'Doe',
    'billingPhone' => '+372 12345678',
    'billingCompany' => 'Good Workers Ltd.',
    'billingAddress1' => 'Viru valjak 24',
    'billingCity' => 'Tallinn',
    'billingPostcode' => '123456',
    'billingCountry' => 'EE',
];

// Send purchase request
$response = $gateway->purchase(
    [
        'language' => 'ENG',
        'transactionId' => $orderId,
        'paymentMethod' => 'hanzaee',
        'amount' => '10.00',
        'currency' => 'EUR',
        'returnUrl' => "https://example.com/paysera/return/{$orderId}",
        'cancelUrl' => "https://example.com/paysera/cancel/{$orderId}",
        'notifyUrl' => "https://example.com/paysera/notify/{$orderId}",
        'card' => $card,
    ]
)->send();

if ($response->isRedirect()) {
    return $response->redirect();
}
```

You should also implement method for `notifyUrl`. After successful charging, Paysera sends a request to this URL. 

```php
use Omnipay\Omnipay;

// Setup payment gateway
$gateway = Omnipay::create('Paysera');
$gateway->setProjectId('123456');
$gateway->setPassword('abcde12345');

// Accept the notification
$response = $gateway->completePurchase()
    ->send();
    
if ($response->isSuccessful()) {
    // Mark the order as paid

    return true;
}
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Povilas Susinskas](https://github.com/povilas)
- [Semyon Chetvertnyh](https://github.com/semyonchetvertnyh)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
