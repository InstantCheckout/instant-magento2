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

namespace Instant\Checkout\Block\Adminhtml\System\Config\Activation;

use Instant\Checkout\Service\InstantIntegrationService;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Integration\Model\IntegrationFactory;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\OauthService;

/**
 * Class Send
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Send extends Field
{
    /**
     * @var string
     */
    protected $_template = 'system/config/activation/send.phtml';

    private $logger;
    private $scopeConfig;
    private $storeManager;
    private $integrationFactory;
    private $oAuthService;
    private $oAuthToken;
    private $integrationService;

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        IntegrationFactory $integrationFactory,
        Token $oAuthToken,
        OauthService $oAuthService,
        InstantIntegrationService $integrationService
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->integrationFactory = $integrationFactory;
        $this->oAuthToken = $oAuthToken;
        $this->oAuthService = $oAuthService;
        $this->integrationService = $integrationService;
        parent::__construct($context);
    }

    /**
     * Unset scope
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element->unsScope();

        return parent::render($element);
    }

    /**
     * Get the button content
     *
     * @param AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $originalData = $element->getOriginalData();

        $this->addData([
            'button_label' => $originalData['button_label'],
            'button_url' => $this->getUrl($originalData['button_url'], ['_current' => true]),
            'html_id' => $element->getHtmlId(),
        ]);

        return $this->_toHtml();
    }

    /**
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('https://gqqe5b9w1m.execute-api.ap-southeast-2.amazonaws.com/pr725/admin/extension/activate');
    }

    /**
     * Gets the params we need to send to the Instant backend to create a Merchant and its Stores.
     * 
     * @return string
     */
    public function getPostParams(): string
    {
        $instantIntegration = $this->integrationFactory->create()->load('Instant Checkout', 'name')->getData();

        if (empty($instantIntegration)) {
            $this->integrationService->createInstantIntegration();
        }

        // Also check to see if Consumer Key, Secret, Access Token and Secret all exist.
        // If they don't create a new integration.

        $consumer = $this->oAuthService->loadConsumer($instantIntegration["consumer_id"]);
        $token = $this->oAuthToken->loadByConsumerIdAndUserType($consumer->getId(), 1);

        $postData = [
            'consumerKey'       => $consumer->getKey(),
            'consumerSecret'    => $consumer->getSecret(),
            'accessToken'       => $token->getToken(),
            'accessTokenSecret' => $token->getSecret(),
            'platform'          => 'MAGENTO',
            'baseUrl'           => $this->storeManager->getStore()->getBaseUrl(),
            'merchantName'      => $this->getStoreName(),
            'email'             => $this->getStoreEmail(),
            'isStaging'         => true,
        ];

        return json_encode($postData);
    }

    // TODO: Return an array for all stores?
    private function getStoreEmail(): string
    {
        return $this->scopeConfig->getValue('trans_email/ident_support/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    private function getStoreName(): string
    {
        $storeName = $this->scopeConfig->getValue('general/store_information/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if (empty($storeName)) {
            $storeName = $this->storeManager->getStore()->getName();
        }

        return $storeName;
    }
}
