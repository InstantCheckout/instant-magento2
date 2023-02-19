<?php

namespace Instant\Checkout\Model\Ui;

use Instant\Checkout\Helper\InstantHelper;
use Instant\Checkout\Helper\InstantPayHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Checkout\Model\ConfigProviderInterface;
use StripeIntegration\Payments\Gateway\Http\Client\ClientMock;
use Magento\Framework\Locale\Bundle\DataBundle;
use StripeIntegration\Payments\Helper\Logger;
use StripeIntegration\Payments\Model\PaymentMethod;
use StripeIntegration\Payments\Model\Config;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'instantpay';
    const YEARS_RANGE = 15;

    /**
     * @var InstantHelper
     */
    private $instantHelper;
    /**
     * @var InstantPayHelper
     */
    private $instantPayHelper;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \StripeIntegration\Payments\Model\Config $config,
        \Magento\Customer\Model\Session $session,
        \StripeIntegration\Payments\Helper\Generic $helper,
        \StripeIntegration\Payments\Helper\ExpressHelper $expressHelper,
        \StripeIntegration\Payments\Model\PaymentIntent $paymentIntent,
        \StripeIntegration\Payments\Model\Adminhtml\Source\CardIconsSpecific $cardIcons,
        \StripeIntegration\Payments\Helper\Subscriptions $subscriptionsHelper,
        \StripeIntegration\Payments\Helper\InitParams $initParams,
        \StripeIntegration\Payments\Helper\PaymentMethod $paymentMethodHelper,
        InstantHelper $instantHelper,
        InstantPayHelper $instantPayHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->localeResolver = $localeResolver;
        $this->_date = $date;
        $this->request = $request;
        $this->assetRepo = $assetRepo;
        $this->serializer = $serializer;
        $this->config = $config;
        $this->session = $session;
        $this->helper = $helper;
        $this->expressHelper = $expressHelper;
        $this->customer = $helper->getCustomerModel();
        $this->paymentIntent = $paymentIntent;
        $this->cardIcons = $cardIcons;
        $this->subscriptionsHelper = $subscriptionsHelper;
        $this->initParams = $initParams;
        $this->paymentMethodHelper = $paymentMethodHelper;
        $this->instantHelper = $instantHelper;
        $this->storeManager = $storeManager;
        $this->instantPayHelper = $instantPayHelper;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $data = [];

        $data = [
            'payment' => [
                self::CODE => [
                    'merchantId' => $this->instantHelper->getInstantAppId(),
                    'storeCode' => $this->storeManager->getStore()->getCode(),
                    'cartId' => $this->instantHelper->getSessionCartId(),
                    'verificationElement' => [
                        'emailFieldSelector' => $this->instantPayHelper->getVerificationElementEmailFieldSelector(),
                    ],
                    'bannerElement' => [
                        'targetElementSelector' => $this->instantPayHelper->getBannerElementTargetElementSelector(),
                        'shouldAppendToElement' => $this->instantPayHelper->getBannerElementShouldAppendToElement(),
                        'theme' => $this->instantPayHelper->getBannerElementTheme(),
                    ]
                ]
            ]
        ];

        return $data;
    }
}
