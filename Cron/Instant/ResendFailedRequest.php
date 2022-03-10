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

namespace Instant\Checkout\Cron\Instant;

use Instant\Checkout\Api\RequestLogRepositoryInterface;
use Instant\Checkout\Helper\InstantHelper;
use Instant\Checkout\Service\DoRequest;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class ResendFailedRequest
{
    /**
     * @var RequestLogRepositoryInterface
     */
    private $requestLogRepository;
    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;
    /**
     * @var DoRequest
     */
    private $doRequest;
    /**
     * @var InstantHelper
     */
    private $instantHelper;

    public function __construct(
        RequestLogRepositoryInterface $requestLogRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        DoRequest $doRequest,
        InstantHelper $instantHelper
    ) {
        $this->requestLogRepository = $requestLogRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->doRequest = $doRequest;
        $this->instantHelper = $instantHelper;
    }

    /**
     * Execute the cron
     *
     * @return void
     * @throws LocalizedException
     */
    public function execute()
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder->addFilter('retry_required', 1)
            ->addFilter('attempts', $this->instantHelper->getRetryFailuresCount(), 'lt')->create();
        $items = $this->requestLogRepository->getList($searchCriteria)->getItems();
        foreach ($items as $item) {
            $body = (array)json_decode($item->getBody());
            $this->doRequest->execute(
                $item->getUriEndpoint(),
                json_decode(json_encode($body['body']), true),
                $item->getRequestMethod(),
                $item->getIdempotencyKey(),
                (int)$item->getRequestlogId()
            );
        }
    }
}
