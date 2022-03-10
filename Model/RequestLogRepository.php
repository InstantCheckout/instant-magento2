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

namespace Instant\Checkout\Model;

use Exception;
use Instant\Checkout\Api\Data\RequestLogInterface;
use Instant\Checkout\Api\Data\RequestLogInterfaceFactory;
use Instant\Checkout\Api\Data\RequestLogSearchResultsInterfaceFactory;
use Instant\Checkout\Api\RequestLogRepositoryInterface;
use Instant\Checkout\Model\ResourceModel\RequestLog as ResourceRequestLog;
use Instant\Checkout\Model\ResourceModel\RequestLog\CollectionFactory as RequestLogCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;

/**
 * Class RequestLogRepository
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RequestLogRepository implements RequestLogRepositoryInterface
{

    /**
     * @var RequestLogSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var RequestLogFactory
     */
    protected $requestLogFactory;

    /**
     * @var RequestLogInterfaceFactory
     */
    protected $dataRequestLogFactory;

    /**
     * @var RequestLogCollectionFactory
     */
    protected $requestLogCollectionFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var ResourceRequestLog
     */
    protected $resource;

    /**
     * @var JoinProcessorInterface
     */
    protected $extensionAttributesJoinProcessor;

    /**
     * @var ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @param ResourceRequestLog $resource
     * @param RequestLogFactory $requestLogFactory
     * @param RequestLogInterfaceFactory $dataRequestLogFactory
     * @param RequestLogCollectionFactory $requestLogCollectionFactory
     * @param RequestLogSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ResourceRequestLog $resource,
        RequestLogFactory $requestLogFactory,
        RequestLogInterfaceFactory $dataRequestLogFactory,
        RequestLogCollectionFactory $requestLogCollectionFactory,
        RequestLogSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->requestLogFactory = $requestLogFactory;
        $this->requestLogCollectionFactory = $requestLogCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataRequestLogFactory = $dataRequestLogFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        RequestLogInterface $requestLog
    ) {

        $requestLogData = $this->extensibleDataObjectConverter->toNestedArray(
            $requestLog,
            [],
            RequestLogInterface::class
        );

        $requestLogModel = $this->requestLogFactory->create()->setData($requestLogData);

        try {
            $this->resource->save($requestLogModel);
        } catch (Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the requestLog: %1',
                $exception->getMessage()
            ));
        }
        return $requestLogModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        SearchCriteriaInterface $criteria
    ) {
        $collection = $this->requestLogCollectionFactory->create();

        $this->extensionAttributesJoinProcessor->process(
            $collection,
            RequestLogInterface::class
        );

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($requestLogId)
    {
        return $this->delete($this->get($requestLogId));
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        RequestLogInterface $requestLog
    ) {
        try {
            $requestLogModel = $this->requestLogFactory->create();
            $this->resource->load($requestLogModel, $requestLog->getRequestLogId());
            $this->resource->delete($requestLogModel);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the requestLog: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get($requestLogId)
    {
        $requestLog = $this->requestLogFactory->create();
        $this->resource->load($requestLog, $requestLogId);
        if (!$requestLog->getId()) {
            throw new NoSuchEntityException(__('requestLog with id "%1" does not exist.', $requestLogId));
        }
        return $requestLog->getDataModel();
    }
}
