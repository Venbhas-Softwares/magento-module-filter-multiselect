<?php
/**
 * Venbhas FilterMultiselect - Configuration Model
 *
 * Reads module configuration values from Stores > Configuration.
 */

declare(strict_types=1);

namespace Venbhas\FilterMultiselect\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_ENABLED = 'venbhas_filtermultiselect/general/enabled';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {}

    /**
     * Check whether multi-select attribute filters are enabled.
     *
     * @param string|int|null $scopeCode Store code or ID; null uses the current store.
     * @return bool
     */
    public function isEnabled(string|int|null $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }
}
