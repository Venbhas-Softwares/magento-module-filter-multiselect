<?php

declare(strict_types=1);

namespace Venbhas\FilterMultiselect\Plugin;

use Venbhas\FilterMultiselect\Model\Config\ModuleEnabledGuard;

/**
 * Base plugin: no-op when the module is disabled in store configuration.
 */
abstract class AbstractPlugin
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
     * Whether the filter multiselect module is enabled for the given store.
     *
     * @param string|int|null $storeId Store code or ID
     *
     * @return bool
     */
    protected function isModuleEnabled(string|int|null $storeId = null): bool
    {
        return $this->moduleEnabledGuard->isEnabled($storeId);
    }
}
