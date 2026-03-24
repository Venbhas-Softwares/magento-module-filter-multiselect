<?php

declare(strict_types=1);

namespace Venbhas\FilterMultiselect\Plugin\Request;

use Magento\Framework\App\Request\Http;

/**
 * Admin attribute form omits unchecked checkbox keys; default venbhas_layered_multiselect to 0 on save.
 */
class HttpGetPostValuePlugin
{
    private const ACTION_NAME = 'catalog_product_attribute_save';

    /**
     * @param Http $subject
     * @param array|null|false $result
     * @return array|null|false
     */
    public function afterGetPostValue(Http $subject, $result)
    {
        if (!is_array($result)) {
            return $result;
        }
        if ($subject->getFullActionName() !== self::ACTION_NAME) {
            return $result;
        }
        if (!array_key_exists('venbhas_layered_multiselect', $result)) {
            $result['venbhas_layered_multiselect'] = 0;
        }

        return $result;
    }
}
