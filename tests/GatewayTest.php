<?php

namespace Omnipay\Paysera\Tests;

use Omnipay\Paysera\Gateway;
use Omnipay\Tests\GatewayTestCase;

class GatewayTest extends GatewayTestCase
{
    /**
     * @var string
     */
    protected $projectId;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var Gateway
     */
    protected $gateway;

    public function setUp()
    {
        parent::setUp();

        $this->projectId = uniqid('', true);
        $this->password = uniqid('', true);
        $this->gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());
        $this->gateway->setProjectId($this->projectId)->setPassword($this->password);
    }

    public function testDefaultParameters()
    {
        $this->assertSame($this->projectId, $this->gateway->getProjectId());
        $this->assertSame($this->password, $this->gateway->getPassword());
        $this->assertTrue($this->gateway->getTestMode());
    }

    public function testPurchase()
    {
        $this->assertTrue($this->gateway->supportsPurchase());

        $request = $this->gateway->purchase(['amount' => 10.5]);
        $this->assertInstanceOf('\Omnipay\Paysera\Message\PurchaseRequest', $request);
        $this->assertSame(1050, $request->getAmountInteger());
    }

    public function testCompletePurchase()
    {
        $this->assertTrue($this->gateway->supportsCompletePurchase());

        $request = $this->gateway->completePurchase();
        $this->assertInstanceOf('\Omnipay\Paysera\Message\AcceptNotificationRequest', $request);
    }
}
