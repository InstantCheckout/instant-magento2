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

use Instant\Checkout\Helper\InstantHelper;
use Instant\Checkout\Service\InstantIntegrationService;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Integration\Model\IntegrationFactory;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\OauthService;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

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

    private $scopeConfig;
    private $storeManager;
    private $integrationFactory;
    private $oAuthService;
    private $oAuthToken;
    private $integrationService;
    private $instantHelper;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        IntegrationFactory $integrationFactory,
        Token $oAuthToken,
        OauthService $oAuthService,
        InstantIntegrationService $integrationService,
        InstantHelper $instantHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->integrationFactory = $integrationFactory;
        $this->oAuthToken = $oAuthToken;
        $this->oAuthService = $oAuthService;
        $this->integrationService = $integrationService;
        $this->instantHelper = $instantHelper;

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
     * Instant backend API endpoint to activate the Magento extension.
     * 
     * @return string
     */
    public function getActivateExtensionEndpointUrl(): string
    {
        return $this->instantHelper->getInstantApiUrl() . 'admin/extension/activate';
    }

    /**
     * Gets the params we need to send to the Instant backend to create a Merchant and its Stores.
     * 
     * @return string
     */
    public function getPostParams(): string
    {
        $this->checkIntegrationExists();

        $instantIntegration = $this->integrationFactory->create()->load('Instant Checkout', 'name')->getData();
        $consumer = $this->oAuthService->loadConsumer($instantIntegration['consumer_id']);
        $token = $this->oAuthToken->loadByConsumerIdAndUserType($consumer->getId(), 1);
        $merchantId = $this->instantHelper->getUncachedCoreConfigValue(InstantHelper::INSTANT_APP_ID_PATH);
        $accessToken = $this->instantHelper->getUncachedCoreConfigValue(InstantHelper::ACCESS_TOKEN_PATH);
        $storeEmail = $this->scopeConfig->getValue('trans_email/ident_support/email', ScopeInterface::SCOPE_STORE);

        $postData = [
            'merchantName' => $this->getStoreName(),
            'email' => $storeEmail,
            'merchantId' => $merchantId,
            'accessToken' => $accessToken,
            'platformData' => [
                'baseUrl' => $this->storeManager->getStore()->getBaseUrl(),
                'platform' => 'MAGENTO',
                'consumerKey' => $consumer->getKey(),
                'consumerSecret' => $consumer->getSecret(),
                'accessToken' => $token->getToken(),
                'accessTokenSecret' => $token->getSecret(),
            ],
        ];

        return json_encode($postData);
    }

    private function checkIntegrationExists()
    {
        $instantIntegration = $this->integrationFactory->create()->load('Instant Checkout', 'name')->getData();

        if (empty($instantIntegration)) {
            $this->integrationService->createInstantIntegration();
        }

        $consumer = $this->oAuthService->loadConsumer($instantIntegration['consumer_id']);
        $token = $this->oAuthToken->loadByConsumerIdAndUserType($consumer->getId(), 1);

        if (empty($consumer->getKey()) || empty($consumer->getSecret()) || empty($token->getToken()) || empty($token->getSecret())) {
            $this->integrationService->createInstantIntegration();
        }
    }

    private function getStoreName(): string
    {
        $storeName = $this->scopeConfig->getValue('general/store_information/name', ScopeInterface::SCOPE_STORE);

        if (empty($storeName)) {
            $storeName = $this->storeManager->getStore()->getName();
        }

        return $storeName;
    }
}
