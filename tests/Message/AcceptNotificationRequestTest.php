<?php

namespace Omnipay\Paysera\Tests\Message;

use Omnipay\Tests\TestCase;
use GuzzleHttp\Psr7\Response;
use Omnipay\Paysera\Common\Encoder;
use Omnipay\Paysera\Common\Signature;
use Psr\Http\Message\ResponseInterface;
use function GuzzleHttp\Psr7\_parse_message;
use Omnipay\Common\Message\NotificationInterface;
use Omnipay\Paysera\Message\AcceptNotificationRequest;
use Omnipay\Paysera\Message\AcceptNotificationResponse;

class AcceptNotificationRequestTest extends TestCase
{
    /**
     * @var string
     */
    protected $projectId;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $httpRequest;

    /**
     * @var string
     */
    protected $password;

    public function setUp()
    {
        $this->projectId = uniqid('', true);
        $this->password = uniqid('', true);

        $this->httpRequest = $this->getHttpRequest();
    }

    public function testSendSuccess()
    {
        $this->httpRequest->attributes->replace($this->notifyData($this->getSuccessData()));
        $this->httpRequest->headers->set('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.1 Safari/605.1.15');

        /** @var \Omnipay\Paysera\Message\AcceptNotificationResponse $response */
        $response = $this->createRequest()->send();

        $this->assertTrue($response->isSuccessful());
        $successData = $this->getSuccessData();
        $this->assertSame($successData['orderid'], $response->getTransactionReference());
        $this->assertSame(NotificationInterface::STATUS_COMPLETED, $response->getTransactionStatus());
        $this->assertSame('1', $response->getCode());
        $this->assertTrue($response->isTestMode());
        $this->assertFalse($response->isServerToServerRequest());
        $this->assertSame($successData['paytext'], $response->getMessage());
    }

    public function testSendSuccessCallbackFromPayseraServer()
    {
        $this->httpRequest->attributes->replace($this->notifyData($this->getSuccessData()));
        $this->httpRequest->headers->set('User-Agent', 'Mozilla/5.0 (compatible; Evp)');

        /** @var \Omnipay\Paysera\Message\AcceptNotificationResponse $response */
        $response = $this->createRequest()->send();

        $this->assertTrue($response->isSuccessful());
        $successData = $this->getSuccessData();
        $this->assertSame($successData['orderid'], $response->getTransactionReference());
        $this->assertSame(NotificationInterface::STATUS_COMPLETED, $response->getTransactionStatus());
        $this->assertSame('1', $response->getCode());
        $this->assertTrue($response->isTestMode());
        $this->assertTrue($response->isServerToServerRequest());
        $this->assertSame($successData['paytext'], $response->getMessage());
    }

    public function testSendFailed()
    {
        $failedData = $this->getSuccessData();
        $failedData['status'] = '0';

        $this->httpRequest->attributes->replace($this->notifyData($failedData));

        /** @var AcceptNotificationResponse $response */
        $response = $this->createRequest()->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame(NotificationInterface::STATUS_FAILED, $response->getTransactionStatus());
        $this->assertSame('0', $response->getCode());
    }

    public function testSendPending()
    {
        $pendingData = $this->getSuccessData();
        $pendingData['status'] = '2';

        $this->httpRequest->attributes->replace($this->notifyData($pendingData));

        /** @var \Omnipay\Paysera\Message\AcceptNotificationResponse $response */
        $response = $this->createRequest()->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame(NotificationInterface::STATUS_PENDING, $response->getTransactionStatus());
        $this->assertSame('2', $response->getCode());
    }

    public function testSendFailure_SignatureIsInvalid_InvalidSS1()
    {
        $notifyData = $this->notifyData($this->getSuccessData());
        $notifyData['ss1'] = 'invalid_signature';

        $this->httpRequest->attributes->replace($notifyData);

        $this->expectException('\Omnipay\Common\Exception\InvalidRequestException');
        $this->createRequest()->send();
    }

    public function testSendFailure_SignatureIsInvalid_InvalidSS2()
    {
        $notifyData = $this->notifyData($this->getSuccessData());
        $notifyData['ss2'] = 'invalid_signature';

        $this->httpRequest->attributes->replace($notifyData);

        $this->expectException('\Omnipay\Common\Exception\InvalidRequestException');
        $this->createRequest()->send();
    }

    public function testSendFailure_SignatureIsInvalid_BadResponseForSS2()
    {
        $notifyData = $this->notifyData($this->getSuccessData());
        $notifyData['ss2'] = 'invalid_signature';

        $this->httpRequest->attributes->replace($notifyData);

        $this->expectException('\Omnipay\Common\Exception\InvalidRequestException');
        $this->createRequest()->send();
    }

    /**
     * @param  array  $data
     * @return array
     */
    protected function notifyData(array $data)
    {
        $encodedData = $this->getEncodedData($data);

        return [
            'data' => $encodedData,
            'ss1' => Signature::generate($encodedData, $this->password),
            'ss2' => $this->getSS2Signature($encodedData),
        ];
    }

    /**
     * @param  array  $data
     * @return string
     */
    protected function getEncodedData(array $data)
    {
        return Encoder::encode(http_build_query($data, '', '&'));
    }

    /**
     * @param  string  $data
     * @return string
     */
    protected function getSS2Signature($data)
    {
        $resource = openssl_pkey_new();
        openssl_pkey_export($resource, $privateKey);

        $privateKey = openssl_pkey_get_private($privateKey);
        openssl_sign($data, $ss2, $privateKey);

        $publicKey = openssl_pkey_get_details($resource);

        $response = $this->getMockHttpResponseWithBody('PubKeyResponse.txt', $publicKey['key']);
        $this->getMockClient();
        $this->setMockHttpResponse([$response]);

        return Encoder::encode($ss2);
    }

    /**
     * Get a mock response for a client by mock file name.
     *
     * @param string $path Relative path to the mock response file
     *
     * @return ResponseInterface
     */
    protected function getMockHttpResponseWithBody($path, $body)
    {
        if ($path instanceof ResponseInterface) {
            return $path;
        }

        $ref = new \ReflectionObject($this);
        $dir = dirname($ref->getFileName());

        $data = _parse_message(file_get_contents($dir.'/../Mock/'.$path));
        if (! preg_match('/^HTTP\/.* [0-9]{3} .*/', $data['start-line'])) {
            throw new \InvalidArgumentException('Invalid response string');
        }
        $parts = explode(' ', $data['start-line'], 3);

        return new Response(
            $parts[1],
            $data['headers'],
            $body,
            explode('/', $parts[0])[1],
            isset($parts[2]) ? $parts[2] : null
        );
    }

    /**
     * @return array
     */
    protected function getSuccessData()
    {
        return [
            'projectId' => $this->projectId,
            'orderid' => 'order_id',
            'version' => '1.6',
            'lang' => 'LIT',
            'type' => 'macro',
            'amount' => '1000',
            'currency' => 'EUR',
            'country' => 'LT',
            'paytext' => 'some information',
            'status' => '1',
            'test' => '1',
        ];
    }

    /**
     * @return \Omnipay\Paysera\Message\AcceptNotificationRequest
     */
    protected function createRequest()
    {
        $request = new AcceptNotificationRequest($this->getHttpClient(), $this->httpRequest);
        $request->setProjectId($this->projectId)->setPassword($this->password);

        return $request;
    }
}
