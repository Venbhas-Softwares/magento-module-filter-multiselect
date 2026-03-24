<?php

declare(strict_types=1);

namespace Venbhas\FilterMultiselect\Observer\Adminhtml;

use Magento\Config\Model\Config\Source\Yesno;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Adds "Allow Multi-Select in Layered Navigation" on the legacy Storefront Properties tab
 * (catalog → product attribute edit). Layered Navigation uses the same event.
 */
class ProductAttributeFormBuildFrontTabObserver implements ObserverInterface
{
    /**
     * @var Yesno
     */
    private $yesNo;

    public function __construct(Yesno $yesNo)
    {
        $this->yesNo = $yesNo;
    }

    public function execute(Observer $observer): void
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $observer->getForm();
        if (!$form) {
            return;
        }

        $fieldset = $form->getElement('front_fieldset');
        if (!$fieldset) {
            return;
        }

        $after = $fieldset->getElement('position') ? 'position' : false;

        $fieldset->addField(
            'venbhas_layered_multiselect',
            'select',
            [
                'name' => 'venbhas_layered_multiselect',
                'label' => __('Allow Multi-Select in Layered Navigation'),
                'title' => __('Allow Multi-Select in Layered Navigation'),
                'note' => __(
                    'When Venbhas Filter Multiselect is enabled and "Allow Multi-Select in Layered Navigation (Global)" is Yes (Stores > Configuration > Venbhas), shoppers can select multiple values for this attribute only if you set this to Yes. If this is No, this attribute stays single-select on the storefront.'
                ),
                'values' => $this->yesNo->toOptionArray(),
            ],
            $after
        );
    }
}
