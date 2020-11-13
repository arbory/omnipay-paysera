<?php

namespace Omnipay\Paysera\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\Common\Message\NotificationInterface;

class AcceptNotificationResponse extends AbstractResponse implements NotificationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getTransactionReference()
    {
        return $this->getDataValueOrNull('orderid');
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionStatus()
    {
        switch ($this->getCode()) {
            case '0':
                return NotificationInterface::STATUS_FAILED;
            case '1':
                return NotificationInterface::STATUS_COMPLETED;
        }

        return NotificationInterface::STATUS_PENDING;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return $this->getDataValueOrNull('paytext');
    }

    /**
     * Determine test mode is on.
     *
     * @return bool
     */
    public function isTestMode()
    {
        return $this->getDataValueOrNull('test') !== '0';
    }

    /**
     * {@inheritdoc}
     */
    public function isSuccessful()
    {
        return $this->getCode() === '1';
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return $this->getDataValueOrNull('status');
    }

    /**
     * Return the value from data or null.
     *
     * @param  string  $name
     * @return string
     */
    protected function getDataValueOrNull($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    /**
     * @return bool
     */
    public function isServerToServerRequest(): bool
    {
        return $this->request->isServerToServerRequest();
    }
}
