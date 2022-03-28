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

namespace Instant\Checkout\Plugin;

use Instant\Checkout\Helper\InstantHelper;
use Magento\Checkout\Block\Checkout\LayoutProcessor;

/**
 * Class CPageBtnDisable
 *
 * @package Magento\Checkout\Plugin
 */
class CPageBtnDisable
{
    /**
     * @var InstantHelper
     */
    private $instantHelper;

    /**
     * Constructor.
     */
    public function __construct(
        InstantHelper $instantHelper
    ) {
        $this->instantHelper = $instantHelper;
    }

    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $processor
     * @param array $jsLayout
     *
     * @return array
     */
    public function afterProcess(
        LayoutProcessor $processor,
        array $jsLayout
    ) {
        $enabled = $this->instantHelper->getInstantBtnCheckoutPageEnabled();

        if (!$enabled) {
            $jsLayout['components']['checkout']['children']['cpage-btn']['componentDisabled'] = true;
        }

        return $jsLayout;
    }
}
