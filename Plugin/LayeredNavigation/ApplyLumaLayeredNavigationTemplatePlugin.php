<?php

declare(strict_types=1);

namespace Venbhas\FilterMultiselect\Plugin\LayeredNavigation;

use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\View\Element\Template;
use Venbhas\FilterMultiselect\Model\Config\ModuleEnabledGuard;
use Venbhas\FilterMultiselect\Plugin\AbstractPlugin;

/**
 * Applies the Luma-style accordion shell only when Hyvä is not controlling the storefront.
 * Layout must NOT set this template globally — that breaks Hyvä's layered navigation.
 */
class ApplyLumaLayeredNavigationTemplatePlugin extends AbstractPlugin
{
    private const HYVA_THEME_MODULE = 'Hyva_Theme';

    private const LAYERED_NAV_TEMPLATE = 'Venbhas_FilterMultiselect::layer/navigation-view.phtml';

    /**
     * Magento module enablement helper.
     *
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * @param ModuleEnabledGuard $moduleEnabledGuard Module enabled guard
     * @param ModuleManager $moduleManager Magento module manager
     */
    public function __construct(
        ModuleEnabledGuard $moduleEnabledGuard,
        ModuleManager $moduleManager
    ) {
        parent::__construct($moduleEnabledGuard);
        $this->moduleManager = $moduleManager;
    }

    /**
     * Set layered navigation template on Luma when Hyvä Theme is not enabled.
     *
     * @param Template $subject Layered navigation template block
     *
     * @return void
     */
    public function beforeToHtml(Template $subject): void
    {
        if (!$this->isModuleEnabled()) {
            return;
        }

        $name = (string) $subject->getNameInLayout();
        if ($name !== 'catalog.leftnav' && $name !== 'catalogsearch.leftnav') {
            return;
        }
        if ($this->moduleManager->isEnabled(self::HYVA_THEME_MODULE)) {
            return;
        }
        $subject->setTemplate(self::LAYERED_NAV_TEMPLATE);
    }
}
