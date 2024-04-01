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

namespace Instant\Checkout\Controller\Data;

use Instant\Checkout\Helper\InstantHelper;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Checkout\Model\CompositeConfigProvider;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class GetConfig extends Action
{
    /**
     * @var JsonFactory
     */
    protected $jsonResultFactory;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var CompositeConfigProvider
     */
    protected $configProvider;
    /**
     * @var InstantHelper
     */
    private $instantHelper;

    /**
     * Constructor.
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        InstantHelper $instantHelper
    ) {
        $this->jsonResultFactory = $jsonResultFactory;
        $this->instantHelper = $instantHelper;

        return parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonResultFactory->create();
        $data = $this->instantHelper->getInstantConfig();
        $result->setData($data);

        return $result;
    }
}
