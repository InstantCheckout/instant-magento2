<?php

/**
 * Instant Checkout
 *
 * @package   Instant_Checkout
 * @author    Instant <hello@instant.one>
 * @copyright 2022 Copyright Instant. https://www.instantcheckout.com.au/
 * @license   https://opensource.org/licenses/OSL-3.0 OSL-3.0
 * @link      https://www.instantcheckout.com.au/
 */

declare(strict_types=1);

namespace Instant\Checkout\Api;

use Instant\Checkout\Api\Data\RequestLogInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface RequestLogRepositoryInterface
 */
interface RequestLogRepositoryInterface
{

    /**
     * Save RequestLog
     * @param RequestLogInterface $requestLog
     * @return RequestLogInterface
     * @throws LocalizedException
     */
    public function save(
        RequestLogInterface $requestLog
    );

    /**
     * Retrieve RequestLog
     * @param string $requestlogId
     * @return RequestLogInterface
     * @throws LocalizedException
     */
    public function get($requestlogId);

    /**
     * Retrieve RequestLog matching the specified criteria.
     * @param SearchCriteriaInterface $searchCriteria
     * @return RequestLogSearchResultsInterface
     * @throws LocalizedException
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete RequestLog
     * @param RequestLogInterface $RequestLog
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(
        RequestLogInterface $requestLog
    );

    /**
     * Delete RequestLog by ID
     * @param string $requestlogId
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($requestlogId);
}
