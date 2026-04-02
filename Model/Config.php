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
    private const XML_PATH_EXTENSION_ENABLED = 'venbhas_filtermultiselect/general/enabled';

    private const XML_PATH_MULTISELECT_GLOBALLY = 'venbhas_filtermultiselect/general/multiselect_globally';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Master switch: module / extension active (Stores > Configuration > Venbhas).
     *
     * @param string|int|null $scopeCode Store code or ID; null uses the current store.
     */
    public function isExtensionEnabled(string|int|null $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_EXTENSION_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * Legacy alias for whether the extension is enabled.
     *
     * @param string|int|null $scopeCode Store code or ID; null uses the current store.
     *
     * @deprecated 1.0.0 Vendor code should call isExtensionEnabled() for clearer intent.
     * @see \Venbhas\FilterMultiselect\Model\Config::isExtensionEnabled()
     */
    public function isEnabled(string|int|null $scopeCode = null): bool
    {
        return $this->isExtensionEnabled($scopeCode);
    }

    /**
     * Global storefront permission for layered navigation multi-select (still requires per-attribute Yes).
     *
     * @param string|int|null $scopeCode Store code or ID; null uses the current store.
     */
    public function isMultiselectGloballyEnabled(string|int|null $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_MULTISELECT_GLOBALLY,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * Whether layered navigation may use multi-select UX for this catalog attribute.
     *
     * Requires extension enabled, global multi-select enabled, and per-attribute flag.
     *
     * @param \Magento\Framework\DataObject|null $attribute EAV/catalog attribute with getData().
     *
     * @return bool
     */
    public function isLayeredMultiselectEnabledForAttribute($attribute): bool
    {
        if (!$this->isExtensionEnabled()) {
            return false;
        }
        if (!$this->isMultiselectGloballyEnabled()) {
            return false;
        }
        if ($attribute === null || !is_object($attribute) || !method_exists($attribute, 'getData')) {
            return false;
        }

        return (bool) (int) $attribute->getData('venbhas_layered_multiselect');
    }
}
