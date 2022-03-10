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

use Instant\Checkout\Api\Data\RequestLogInterface;
use Instant\Checkout\Api\Data\RequestLogInterfaceFactory;
use Instant\Checkout\Model\ResourceModel\RequestLog\Collection;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;

/**
 * Class RequestLog
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class RequestLog extends AbstractModel
{

    /**
     * @var string
     */
    protected $_eventPrefix = 'instant_checkout_requestlog';

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var RequestLogInterfaceFactory
     */
    protected $requestlogDataFactory;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param RequestLogInterfaceFactory $requestlogDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param ResourceModel\RequestLog $resource
     * @param Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        RequestLogInterfaceFactory $requestlogDataFactory,
        DataObjectHelper $dataObjectHelper,
        ResourceModel\RequestLog $resource,
        Collection $resourceCollection,
        array $data = []
    ) {
        $this->requestlogDataFactory = $requestlogDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve requestlog model with requestlog data
     * @return \Instant\Checkout\Api\Data\RequestLogInterface
     */
    public function getDataModel(): RequestLogInterface
    {
        $requestlogData = $this->getData();

        $requestlogDataObject = $this->requestlogDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $requestlogDataObject,
            $requestlogData,
            RequestLogInterface::class
        );

        return $requestlogDataObject;
    }
}
