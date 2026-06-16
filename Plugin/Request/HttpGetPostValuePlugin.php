<?php

declare(strict_types=1);

namespace Venbhas\FilterMultiselect\Plugin\Request;

use Magento\Framework\App\Request\Http;
use Venbhas\FilterMultiselect\Plugin\AbstractPlugin;

/**
 * Admin attribute form omits unchecked checkbox keys; default venbhas_layered_multiselect to 0 on save.
 */
class HttpGetPostValuePlugin extends AbstractPlugin
{
    private const ACTION_NAME = 'catalog_product_attribute_save';

    /**
     * Default venbhas_layered_multiselect when the admin form omits the field on save.
     *
     * @param Http $subject Magento HTTP request
     * @param array|null|false $result Posted field values from the parent method
     *
     * @return array|null|false
     */
    public function afterGetPostValue(Http $subject, $result)
    {
        if (!$this->isModuleEnabled()) {
            return $result;
        }

        if (!is_array($result)) {
            return $result;
        }
        if ($subject->getFullActionName() !== self::ACTION_NAME) {
            return $result;
        }
        if (!array_key_exists('venbhas_layered_multiselect', $result)) {
            // UI form did not render our field (e.g. swatch types before they were in valuesForEnable).
            // Do not force 0 or every attribute save wipes catalog_eav_attribute.venbhas_layered_multiselect.
            $input = (string) ($result['frontend_input'] ?? '');
            if ($input === 'swatch_visual' || $input === 'swatch_text') {
                return $result;
            }
            $result['venbhas_layered_multiselect'] = 0;
        }

        return $result;
    }
}
