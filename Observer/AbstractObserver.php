<?php

declare(strict_types=1);

namespace Venbhas\FilterMultiselect\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Venbhas\FilterMultiselect\Model\Config\ModuleEnabledGuard;

/**
 * Base observer: no-op when the module is disabled in store configuration.
 */
abstract class AbstractObserver implements ObserverInterface
{
    /**
     * @var ModuleEnabledGuard
     */
    private ModuleEnabledGuard $moduleEnabledGuard;

    /**
     * @param ModuleEnabledGuard $moduleEnabledGuard Module enabled guard
     */
    public function __construct(ModuleEnabledGuard $moduleEnabledGuard)
    {
        $this->moduleEnabledGuard = $moduleEnabledGuard;
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        if (!$this->moduleEnabledGuard->isEnabled($this->resolveStoreId($observer))) {
            return;
        }

        $this->executeWhenEnabled($observer);
    }

    /**
     * Run observer logic when the module is enabled for the resolved store.
     *
     * @param Observer $observer Observer
     *
     * @return void
     */
    abstract protected function executeWhenEnabled(Observer $observer): void;

    /**
     * Resolve store ID from common observer event payloads.
     *
     * @param Observer $observer Observer
     *
     * @return int|null
     */
    protected function resolveStoreId(Observer $observer): ?int
    {
        $event = $observer->getEvent();

        $dataObject = $event->getDataObject();
        if ($dataObject instanceof DataObject && method_exists($dataObject, 'getStoreId')) {
            $storeId = (int) $dataObject->getStoreId();
            if ($storeId > 0) {
                return $storeId;
            }
        }

        $attribute = $observer->getData('attribute') ?: $event->getData('attribute');
        if ($attribute instanceof DataObject && method_exists($attribute, 'getStoreId')) {
            $storeId = (int) $attribute->getStoreId();
            if ($storeId > 0) {
                return $storeId;
            }
        }

        return null;
    }
}
