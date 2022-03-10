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

namespace Instant\Checkout\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface RequestLogInterface
 */
interface RequestLogInterface extends ExtensibleDataInterface
{

    const REQUESTLOG_ID = 'requestlog_id';
    const RETRY_REQUIRED = 'retry_required';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const PRIORITY = 'priority';
    const REQUEST_ID = 'request_id';
    const BODY = 'body';
    const RESPONSE_CONTENT = 'response_content';
    const STATUS = 'status';
    const ATTEMPTS = 'attempts';
    const URI_ENDPOINT = 'uri_endpoint';
    const REQUEST_METHOD = 'request_method';
    const IDEMPOTENCY_KEY = 'idempotency_key';

    /**
     * Get requestlog_id
     * @return int
     */
    public function getRequestlogId();

    /**
     * Set requestlog_id
     * @param string $requestlogId
     * @return \Instant\Checkout\Api\Data\RequestLogInterface
     */
    public function setRequestlogId($requestlogId);

    /**
     * Get request_id
     * @return string|null
     */
    public function getRequestId();

    /**
     * Set request_id
     * @param string $requestId
     * @return \Instant\Checkout\Api\Data\RequestLogInterface
     */
    public function setRequestId($requestId);

    /**
     * Get body
     * @return string|null
     */
    public function getBody();

    /**
     * Set body
     * @param string $body
     * @return \Instant\Checkout\Api\Data\RequestLogInterface
     */
    public function setBody($body);

    /**
     * Get attempts
     * @return int
     */
    public function getAttempts();

    /**
     * Set attempts
     * @param int $attempts
     * @return \Instant\Checkout\Api\Data\RequestLogInterface
     */
    public function setAttempts($attempts);

    /**
     * Get status
     * @return int
     */
    public function getStatus();

    /**
     * Set status
     * @param string $status
     * @return \Instant\Checkout\Api\Data\RequestLogInterface
     */
    public function setStatus($status);

    /**
     * Get response_content
     * @return string|null
     */
    public function getResponseContent();

    /**
     * Set response_content
     * @param string $responseContent
     * @return \Instant\Checkout\Api\Data\RequestLogInterface
     */
    public function setResponseContent($responseContent);

    /**
     * Get priority
     * @return int|null
     */
    public function getPriority();

    /**
     * Set priority
     * @param int $priority
     * @return \Instant\Checkout\Api\Data\RequestLogInterface
     */
    public function setPriority($priority);

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $createdAt
     * @return \Instant\Checkout\Api\Data\RequestLogInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated_at
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated_at
     * @param string $updatedAt
     * @return \Instant\Checkout\Api\Data\RequestLogInterface
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Get retry_required
     * @return int|null
     */
    public function getRetryRequired();

    /**
     * Set retry_required
     * @param int $retryRequired
     * @return \Instant\Checkout\Api\Data\RequestLogInterface
     */
    public function setRetryRequired($retryRequired);

    /**
     * Get uri_endpoint
     * @return string|null
     */
    public function getUriEndpoint();

    /**
     * Set uri_endpoint
     * @param string $uriEndpoint
     * @return \Instant\Checkout\Api\Data\RequestLogInterface
     */
    public function setUriEndpoint($uriEndpoint);

    /**
     * Get request_method
     * @return string|null
     */
    public function getRequestMethod();

    /**
     * Set request_method
     * @param string $requestMethod
     * @return \Instant\Checkout\Api\Data\RequestLogInterface
     */
    public function setRequestMethod($requestMethod);

    /**
     * @return \Instant\Checkout\Api\Data\RequestLogExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * @param \Instant\Checkout\Api\Data\RequestLogExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(RequestLogExtensionInterface $extensionAttributes);

    /**
     * @return string|null
     */
    public function getIdempotencyKey();

    /**
     * @param string $idempotencyKey
     * @return void
     */
    public function setIdempotencyKey($idempotencyKey);
}
