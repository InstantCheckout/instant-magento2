To add the Instant PDP button anywhere, copy and paste this block into a .phtml

                <?php
                    $instantCheckoutHtml = $this->getLayout()
                    ->createBlock('Instant\Checkout\Block\PdpBlock')
                    ->setProduct($product)
                    ->setTemplate('Instant_Checkout::ic-pdp-btn.phtml')
                    ->toHtml();
                    echo $instantCheckoutHtml;
                ?>
