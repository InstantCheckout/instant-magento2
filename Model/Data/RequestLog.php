<?php

/**
 * Instant_Checkout
 *
 * @package   Instant_Checkout
 * @author    Instant <hello@instant.one>
 * @copyright 2022 Copyright Instant. https://www.instantcheckout.com.au/
 * @license   https://opensource.org/licenses/OSL-3.0 OSL-3.0
 * @link      https://www.instantcheckout.com.au/
 */

declare(strict_types=1);

namespace Instant\Checkout\Model\Data;

use Instant\Checkout\Api\Data\RequestLogExtensionInterface;
use Instant\Checkout\Api\Data\RequestLogInterface;
use Magento\Framework\Api\AbstractExtensibleObject;

/**
 * Class RequestLog
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class RequestLog extends AbstractExtensibleObject implements RequestLogInterface //NOSONAR
{
    /**
     * Get idempotency_key
     * @return string
     */
    public function getIdempotencyKey()
    {
        return $this->_get(static::IDEMPOTENCY_KEY);
    }

    /**
     * Set idempotency_key
     * @param string idempotency_key
     * @return RequestLogInterface
     */
    public function setIdempotencyKey($idempotencyKey)
    {
        return $this->setData(static::IDEMPOTENCY_KEY, $idempotencyKey);
    }

    /**
     * Get dorequestlog_id
     * @return int
     */
    public function getRequestlogId()
    {
        return $this->_get(static::REQUESTLOG_ID);
    }

    /**
     * Set requestlog_id
     * @param int $requestlogId
     * @return RequestLogInterface
     */
    public function setRequestlogId($requestlogId)
    {
        return $this->setData(static::REQUESTLOG_ID, $requestlogId);
    }

    /**
     * Get request_id
     * @return string|null
     */
    public function getRequestId()
    {
        return $this->_get(static::REQUEST_ID);
    }

    /**
     * Set request_id
     * @param string $requestId
     * @return RequestLogInterface
     */
    public function setRequestId($requestId)
    {
        return $this->setData(static::REQUEST_ID, $requestId);
    }

    /**
     * Get body
     * @return string|null
     */
    public function getBody()
    {
        return $this->_get(static::BODY);
    }

    /**
     * Set body
     * @param string $body
     * @return RequestLogInterface
     */
    public function setBody($body)
    {
        return $this->setData(static::BODY, $body);
    }

    /**
     * Get attempts
     * @return int
     */
    public function getAttempts()
    {
        return $this->_get(static::ATTEMPTS);
    }

    /**
     * Set attempts
     * @param int $attempts
     * @return RequestLogInterface
     */
    public function setAttempts($attempts)
    {
        return $this->setData(static::ATTEMPTS, $attempts);
    }

    /**
     * Get status
     * @return int
     */
    public function getStatus()
    {
        return $this->_get(static::STATUS);
    }

    /**
     * Set status
     * @param int $status
     * @return RequestLogInterface
     */
    public function setStatus($status)
    {
        return $this->setData(static::STATUS, $status);
    }

    /**
     * Get response_content
     * @return string|null
     */
    public function getResponseContent()
    {
        return $this->_get(static::RESPONSE_CONTENT);
    }

    /**
     * Set response_content
     * @param string $responseContent
     * @return RequestLogInterface
     */
    public function setResponseContent($responseContent)
    {
        return $this->setData(static::RESPONSE_CONTENT, $responseContent);
    }

    /**
     * Get priority
     * @return int|null
     */
    public function getPriority()
    {
        return $this->_get(static::PRIORITY);
    }

    /**
     * Set priority
     * @param int $priority
     * @return RequestLogInterface
     */
    public function setPriority($priority)
    {
        return $this->setData(static::PRIORITY, $priority);
    }

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->_get(static::CREATED_AT);
    }

    /**
     * Set created_at
     * @param string $createdAt
     * @return RequestLogInterface
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(static::CREATED_AT, $createdAt);
    }

    /**
     * Get updated_at
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->_get(static::UPDATED_AT);
    }

    /**
     * Set updated_at
     * @param string $updatedAt
     * @return RequestLogInterface
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(static::UPDATED_AT, $updatedAt);
    }

    /**
     * Get retry_required
     * @return int|null
     */
    public function getRetryRequired()
    {
        return $this->_get(static::RETRY_REQUIRED);
    }

    /**
     * Set retry_required
     * @param int $retryRequired
     * @return RequestLogInterface
     */
    public function setRetryRequired($retryRequired)
    {
        return $this->setData(static::RETRY_REQUIRED, $retryRequired);
    }

    /**
     * Get uri_endpoint
     * @return string|null
     */
    public function getUriEndpoint()
    {
        return $this->_get(static::URI_ENDPOINT);
    }

    /**
     * Set uri_endpoint
     * @param string $uriEndpoint
     * @return RequestLogInterface
     */
    public function setUriEndpoint($uriEndpoint)
    {
        return $this->setData(static::URI_ENDPOINT, $uriEndpoint);
    }

    /**
     * Get request_method
     * @return string|null
     */
    public function getRequestMethod()
    {
        return $this->_get(static::REQUEST_METHOD);
    }

    /**
     * Set request_method
     * @param string $requestMethod
     * @return RequestLogInterface
     */
    public function setRequestMethod($requestMethod)
    {
        return $this->setData(static::REQUEST_METHOD, $requestMethod);
    }


    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return RequestLogExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param RequestLogExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        RequestLogExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
