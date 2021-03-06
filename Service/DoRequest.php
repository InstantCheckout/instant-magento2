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

namespace Instant\Checkout\Service;

use Exception;
use Instant\Checkout\Api\Data\RequestLogInterface;
use Instant\Checkout\Helper\InstantHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Instant\Checkout\Api\Data\RequestLogInterfaceFactory;
use Instant\Checkout\Api\RequestLogRepositoryInterfaceFactory;

/**
 * Class DoRequest sends an API request to Instant
 */
class DoRequest
{
    const AGENT = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)';

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var InstantHelper
     */
    private $instantHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var RequestLogInterfaceFactory
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    private $requestLogInterfaceFactory;
    /**
     * @var RequestLogRepositoryInterfaceFactory
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    private $requestLogRepositoryInterfaceFactory;

    /**
     * DoRequest constructor.
     * @param Curl $curl
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger,
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        Curl $curl,
        StoreManagerInterface $storeManager,
        InstantHelper $instantHelper,
        LoggerInterface $logger,
        RequestLogInterfaceFactory $requestLogInterfaceFactory,
        RequestLogRepositoryInterfaceFactory $requestLogRepositoryInterfaceFactory
    ) {
        $this->curl = $curl;
        $this->storeMananger = $storeManager;
        $this->instantHelper = $instantHelper;
        $this->logger = $logger;
        $this->requestLogInterfaceFactory = $requestLogInterfaceFactory;
        $this->requestLogRepositoryInterfaceFactory = $requestLogRepositoryInterfaceFactory;
    }

    public function logInfo($msg)
    {
        $this->logger->info('[INSTANT]: ' . $msg);
    }

    public function logWarning($msg)
    {
        $this->logger->warning('[INSTANT]: ' . $msg);
    }

    public function logError($msg)
    {
        $this->logger->error('[INSTANT]: ' . $msg);
    }

    /**
     * @param string $requestUri
     * @param array $body
     * @param string $requestMethod
     * @param int $requestLogId
     * @return RequestLogInterface
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function execute(
        string $endpoint,
        array $body = [],
        $requestMethod = 'POST',
        $idempotencyKey = -1,
        $requestLogId = 0,
        $enableRetry = true,
        $enableIdempotency = true
    ) {
        try {
            $requestBody = json_encode($body);
            $requestId = $this->instantHelper->guid();

            if ($enableIdempotency && $idempotencyKey === -1) {
                $idempotencyKey = $this->instantHelper->guid();
            }

            $headers = [
                "Content-Type" => "application/json",
                "User-Agent" => static::AGENT,
                "X-Instant-App-Id" => $this->instantHelper->getInstantAppId(),
                "X-Instant-App-Auth" => $this->instantHelper->getInstantApiAccessToken(),
                'Expect:' => '',
            ];

            if ($enableIdempotency) {
                $headers["Idempotency-Key"] = $idempotencyKey;
            }
            $this->curl->setHeaders($headers);
            $this->curl->setTimeout(30);

            $baseApiUrl = $this->instantHelper->getInstantApiUrl();
            $requestUri = $baseApiUrl . $endpoint;

            $this->logInfo('Sending ' . $requestMethod . ' request to ' . $requestUri);
            $this->logInfo('Idempotency Key ' . $idempotencyKey);
            $this->logInfo('Request Log ID ' . $requestLogId);

            try {
                switch ($requestMethod) {
                    case 'POST':
                        $this->curl->post($requestUri, $requestBody);
                        break;
                    case 'GET':
                        $this->curl->get($requestUri);
                        break;
                    default:
                        throw new LocalizedException(__('This %1 request method is not implemented yet.', $requestMethod));
                }
            } catch (Exception $e) {
                $this->logError('Error sending request: ' . $e->getMessage());
            }

            $result = $this->curl->getBody();
            $status = $this->curl->getStatus();
            $this->logInfo('Request response received.');
            $this->logInfo('Result: ' . $result);
            $this->logInfo('Status Code ' . $status);

            $requestLogTableExists = $this->instantHelper->doesInstantRequestLogTableExist();
            $this->logInfo('Should create request log: ' . $enableRetry);
            $this->logInfo('Request log table exists: ' . ($requestLogTableExists ? 'true' : 'false'));

            try {
                if ($enableRetry && $requestLogTableExists) {
                    $this->logInfo('Proceeding to create request log row for this request.');

                    $repository = $this->requestLogRepositoryInterfaceFactory->create();
                    if ($requestLogId > 0) {
                        $model = $repository->get($requestLogId);
                        $model->setAttempts($model->getAttempts() + 1);
                        $model->setResponseContent($result);
                        $model->setIdempotencyKey($idempotencyKey);
                        $model->setRetryRequired($this->checkShouldRetry($status));
                        $model->setStatus($status);
                    } else {
                        $model = $this->requestLogInterfaceFactory->create();
                        $model->setAttempts(0);
                        $model->setRequestId($requestId);
                        $model->setBody(json_encode(['body' => $body]));
                        $model->setPriority(0);
                        $model->setRequestMethod($requestMethod);
                        $model->setIdempotencyKey($idempotencyKey);
                        $model->setResponseContent($result);
                        $model->setRetryRequired($this->checkShouldRetry($status));
                        $model->setStatus($status);
                        $model->setUriEndpoint($endpoint);
                    }

                    $repository->save($model);
                    $this->logInfo('New request log saved.');
                } else {
                    $this->logInfo('Either retry is not enabled for this request or request log table does not exist.');
                }
            } catch (Exception $e) {
                $this->logError('Could not create request log. ' . $e->getMessage());
            }

            return ['result' => $result, 'status' => $status];
        } catch (Exception $e) {
            $this->logError("Exception raised in Instant/Checkout/Service/DoRequest: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @param $status
     * @return int
     */
    protected function checkShouldRetry($status)
    {
        if ($status != 200) {
            return 1;
        }
        return 0;
    }
}
