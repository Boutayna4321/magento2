<?php
declare(strict_types=1);

namespace AlpineCommerce\StorePickup\Model\Carrier;

use AlpineCommerce\StorePickup\Api\StoreAvailabilityInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Shipping\Model\Rate\Result;
use Psr\Log\LoggerInterface;

class StorePickup extends AbstractCarrier implements CarrierInterface
{
    protected $_code = 'storepickup';

    protected $_isFixed = true;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        private readonly ResultFactory $rateResultFactory,
        private readonly MethodFactory $rateMethodFactory,
        private readonly StoreAvailabilityInterface $storeAvailability,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    public function getAllowedMethods(): array
    {
        return ['storepickup' => (string)__($this->getConfigData('name'))];
    }

    public function collectRates(RateRequest $request): Result|false
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        // Only offer pickup when at least one store can serve the whole cart; the
        // checkout lists the same stores (StorePickupConfigProvider).
        if (!$this->storeAvailability->getAvailableStores($this->cartQuantities($request))) {
            return false;
        }

        $result = $this->rateResultFactory->create();
        $method = $this->rateMethodFactory->create();

        $method->setCarrier($this->_code);
        $method->setCarrierTitle((string)__($this->getConfigData('title')));
        $method->setMethod($this->_code);
        $method->setMethodTitle((string)__($this->getConfigData('name')));
        $method->setPrice((float)$this->getConfigData('price'));
        $method->setCost(0.0);

        $result->append($method);

        return $result;
    }

    /**
     * SKU => quantity of the cart, counting a configurable product once (its child
     * items carry the same SKU on the parent line).
     *
     * @return array<string, float>
     */
    private function cartQuantities(RateRequest $request): array
    {
        $quantities = [];
        foreach ((array) $request->getAllItems() as $item) {
            if ($item->getParentItem()) {
                continue;
            }
            $sku = (string) $item->getSku();
            $quantities[$sku] = ($quantities[$sku] ?? 0.0) + (float) $item->getQty();
        }

        return $quantities;
    }
}
