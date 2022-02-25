<?php

namespace Instant\Checkout\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class DoRequest sends an API request to Instant
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class DoRequest
{
    const AGENT = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)';

    /**
     * @var Curl
     */
    private $curl;

    /**
     * DoRequest constructor.
     * @param Curl $curl
     * @param StoreManagerInterface $storeManager
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        Curl $curl,
        StoreManagerInterface $storeManager
    ) {
        $this->curl = $curl;
        $this->storeMananger = $storeManager;
    }

    /**
     * @param string $requestUri
     * @param array $body
     * @param string $requestMethod
     * @param int $doRequestLogId
     * @return DoRequestLogInterface
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function execute(
        string $endpoint,
        array $body = [],
        string $requestMethod = 'POST'
    ) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $instantHelper = $objectManager->create(\Instant\Checkout\Model\Config\InstantConfig::class);
        $requestBody = json_encode($body);

        $headers = [
            "Content-Type" => "application/json",
            "User-Agent" => static::AGENT,
            "X-Instant-App-Id" => $instantHelper->getInstantAppId(),
            "X-Instant-App-Auth" => $instantHelper->getInstantApiAccessToken(),
            'Expect:' => ''
        ];
        $this->curl->setHeaders($headers);

        $baseApiUrl = $instantHelper->getInstantApiUrl();

        switch ($requestMethod) {
            case 'POST':
                $this->curl->post($baseApiUrl . $endpoint, $requestBody);
                break;
            case 'GET':
                $this->curl->get($baseApiUrl . $endpoint);
                break;
            default:
                throw new LocalizedException(__('This %1 request method is not implemented yet.', $requestMethod));
        }

        $result = $this->curl->getBody();
        $status = $this->curl->getStatus();

        return [
            'result' => $result,
            'status' => $status,
        ];
    }
}
