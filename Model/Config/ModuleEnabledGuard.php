<?php

declare(strict_types=1);

namespace Venbhas\FilterMultiselect\Model\Config;

use Venbhas\FilterMultiselect\Model\Config;

/**
 * Central guard for store-config module enable flag (venbhas_filtermultiselect/general/enabled).
 */
class ModuleEnabledGuard
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @param Config $config Module config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Whether filter multiselect features should run for the given store.
     *
     * @param string|int|null $storeId Store code or ID
     *
     * @return bool
     */
    public function isEnabled(string|int|null $storeId = null): bool
    {
        return $this->config->isExtensionEnabled($storeId);
    }
}
