<?php

namespace Instant\Checkout\Controller\Cart;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\MaskedQuoteIdToQuoteId;

class Merge extends Action implements HttpPostActionInterface
{
    protected $productRepository;
    protected $jsonResultFactory;
    private $request;
    protected $quoteFactory;
    protected $maskedQuoteIdToQuoteId;

    /**
     * Constructor.
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        JsonFactory $jsonResultFactory,
        ProductRepositoryInterface $productRepository,
        QuoteFactory $quoteFactory,
        MaskedQuoteIdToQuoteId $maskedQuoteIdToQuoteId
    ) {
        $this->productRepository = $productRepository;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->request = $request;
        $this->quoteFactory = $quoteFactory;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;

        return parent::__construct($context);
    }

    public function execute()
    {
        $params = $this->request->getPost();
        $fromMaskedCartId = $params['fromCartId'];
        $toMaskedCartId = $params['toCartId'];

        $fromCartId = $this->maskedQuoteIdToQuoteId->execute($fromMaskedCartId);
        $toCartId = $this->maskedQuoteIdToQuoteId->execute($toMaskedCartId);

        $fromQuote = $this->quoteFactory->create()->load($fromCartId, 'entity_id');
        $toQuote = $this->quoteFactory->create()->load($toCartId, 'entity_id');

        $toQuote->merge($fromQuote);
        $toQuote->save();
    }
}
