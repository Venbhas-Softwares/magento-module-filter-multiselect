<?php

declare(strict_types=1);

namespace Venbhas\FilterMultiselect\Plugin\Swatches;

use Magento\Catalog\Model\Layer\Filter\FilterInterface;
use Magento\LayeredNavigation\Block\Navigation\FilterRenderer as FilterRendererBlock;
use Magento\Swatches\Helper\Data as SwatchHelper;
use Magento\Swatches\Model\Plugin\FilterRenderer as SwatchFilterRendererPlugin;
use Venbhas\FilterMultiselect\Model\Config;
use Venbhas\FilterMultiselect\Model\Config\ModuleEnabledGuard;

/**
 * Swatch layered navigation normally replaces the filter renderer and builds single-select URLs.
 * When Venbhas multi-select is allowed for that attribute, defer to the standard renderer so
 * the Venbhas template (checkboxes, array query params) is used — same as select/multiselect.
 */
class SwatchLayeredFilterRenderer extends SwatchFilterRendererPlugin
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var ModuleEnabledGuard
     */
    private ModuleEnabledGuard $moduleEnabledGuard;

    /**
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @param SwatchHelper $swatchHelper
     * @param Config $config
     * @param ModuleEnabledGuard $moduleEnabledGuard
     */
    public function __construct(
        \Magento\Framework\View\LayoutInterface $layout,
        SwatchHelper $swatchHelper,
        Config $config,
        ModuleEnabledGuard $moduleEnabledGuard
    ) {
        parent::__construct($layout, $swatchHelper);
        $this->config = $config;
        $this->moduleEnabledGuard = $moduleEnabledGuard;
    }

    /**
     * Use standard filter rendering when multi-select is enabled for a swatch attribute.
     *
     * @param FilterRendererBlock $subject Filter renderer block.
     * @param \Closure $proceed Original render closure.
     * @param FilterInterface $filter Layer filter instance.
     *
     * @return mixed
     */
    public function aroundRender(
        FilterRendererBlock $subject,
        \Closure $proceed,
        FilterInterface $filter
    ) {
        if (!$this->moduleEnabledGuard->isEnabled()) {
            return parent::aroundRender($subject, $proceed, $filter);
        }

        if ($filter->hasAttributeModel()) {
            $attribute = $filter->getAttributeModel();
            if ($this->swatchHelper->isSwatchAttribute($attribute)
                && $this->config->isLayeredMultiselectEnabledForAttribute($attribute)
            ) {
                return $proceed($filter);
            }
        }

        return parent::aroundRender($subject, $proceed, $filter);
    }
}
