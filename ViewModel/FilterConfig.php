<?php
/**
 * Venbhas FilterMultiselect - Filter Configuration ViewModel
 *
 * Exposes module configuration and display settings to frontend templates.
 */

declare(strict_types=1);

namespace Venbhas\FilterMultiselect\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;
use Venbhas\FilterMultiselect\Model\Config;

class FilterConfig implements ArgumentInterface
{
    private const XML_PATH_DISPLAY_COUNT = 'catalog/layered_navigation/display_product_count';

    /**
     * @param Config $config
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly Config $config,
        private readonly ScopeConfigInterface $scopeConfig
    ) {}

    /**
     * Whether multi-select checkbox mode is enabled for attribute filters.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    /**
     * Whether product counts should be displayed next to each filter option.
     *
     * Reads from the standard Magento catalog layered navigation configuration.
     *
     * @return bool
     */
    public function shouldDisplayProductCount(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_DISPLAY_COUNT,
            ScopeInterface::SCOPE_STORE
        );
    }
}
