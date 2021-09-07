# Copy below code to add Instant button in custom product sliders, widget, static blocks etc.

``````
$buyNowHtml = $this->getLayout()
    ->createBlock('Instant\Checkout\Block\Product\ListProduct')
    ->setProduct($_item)
    ->setTemplate('Instant_Checkout::buynow-list.phtml')
    ->toHtml();
echo $buyNowHtml;
``````
