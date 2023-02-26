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

namespace Instant\Checkout\Helper;

use Exception;
use Magento\Framework\App\Helper\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\Session\SessionManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\QuoteGraphQl\Model\Cart\CreateEmptyCartForCustomer;
use Magento\QuoteGraphQl\Model\Cart\CreateEmptyCartForGuest;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\ScopeInterface;

class InstantPayHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;
    /**
     * @var InstantHelper
     */
    private $instantHelper;

    /**
     * Constructor.
     * @param Context $context
     * @param Session $customerSession
     * */
    public function __construct(
        Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        InstantHelper $instantHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->instantHelper = $instantHelper;

        return parent::__construct($context);
    }

    public function getConfig($section, $field)
    {
        $storeId = $this->instantHelper->getStoreId();
        $data = $this->scopeConfig->getValue("payment/instant/$section" . "_" . $field, ScopeInterface::SCOPE_STORE, $storeId);

        return $data;
    }

    public function getGeneralConfig($field)
    {
        return $this->getConfig('general', $field);
    }

    public function getInstantPayEnabled()
    {
        return $this->getGeneralConfig('enabled') === '1';
    }

    public function getVerificationElementConfig($field)
    {
        return $this->getConfig('verificationElement', $field);
    }

    public function getBannerElementConfig($field)
    {
        return $this->getConfig('bannerElement', $field);
    }

    public function getVerificationElementEmailFieldSelector()
    {
        return $this->getVerificationElementConfig('emailFieldSelector');
    }

    public function getBannerElementTargetElementSelector()
    {
        return $this->getBannerElementConfig('targetElementSelector');
    }

    public function getBannerElementShouldAppendToElement()
    {
        return $this->getBannerElementConfig('shouldAppendToElement') === 'append';
    }

    public function getBannerElementTheme()
    {
        return $this->getBannerElementConfig('theme');
    }

    public function getBannerElementEnabled()
    {
        return $this->getGeneralConfig('bannerElementEnabled') === '1';
    }

    public function getVerificationElementEnabled()
    {
        return $this->getGeneralConfig('verificationElementEnabled') === '1';
    }
}
