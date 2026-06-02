<?php

declare(strict_types=1);

namespace Venbhas\FilterMultiselect\Plugin\Swatches\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Swatches\Helper\Data as SwatchDataHelper;
use Venbhas\FilterMultiselect\Plugin\AbstractPlugin;

/**
 * Layered navigation multi-select uses array query params; Swatches helpers compare attribute values
 * with array_diff_assoc / eq filters expecting scalars. Normalize to one option id for image resolution.
 */
class SwatchRequestAttributesPlugin extends AbstractPlugin
{
    /**
     * Flatten multi-select attribute values before loading a variation by fallback.
     *
     * @param SwatchDataHelper $subject Swatch data helper
     * @param ProductInterface $parentProduct Parent configurable product
     * @param array $attributes Selected attribute codes and values
     *
     * @return array
     */
    public function beforeLoadVariationByFallback(
        SwatchDataHelper $subject,
        ProductInterface $parentProduct,
        array $attributes
    ): array {
        if (!$this->isModuleEnabled()) {
            return [$parentProduct, $attributes];
        }

        return [$parentProduct, $this->normalizeConfigurableAttributeMap($attributes)];
    }

    /**
     * Flatten multi-select attribute map before loading first variation with an image.
     *
     * @param SwatchDataHelper $subject Swatch data helper
     * @param ProductInterface $configurableProduct Configurable product
     * @param array $requiredAttributes Required attribute codes and values
     *
     * @return array
     */
    public function beforeLoadFirstVariationWithImage(
        SwatchDataHelper $subject,
        ProductInterface $configurableProduct,
        array $requiredAttributes
    ): array {
        if (!$this->isModuleEnabled()) {
            return [$configurableProduct, $requiredAttributes];
        }

        return [$configurableProduct, $this->normalizeConfigurableAttributeMap($requiredAttributes)];
    }

    /**
     * Flatten multi-select attribute map before loading first variation with swatch image.
     *
     * @param SwatchDataHelper $subject Swatch data helper
     * @param ProductInterface $configurableProduct Configurable product
     * @param array $requiredAttributes Required attribute codes and values
     *
     * @return array
     */
    public function beforeLoadFirstVariationWithSwatchImage(
        SwatchDataHelper $subject,
        ProductInterface $configurableProduct,
        array $requiredAttributes
    ): array {
        if (!$this->isModuleEnabled()) {
            return [$configurableProduct, $requiredAttributes];
        }

        return [$configurableProduct, $this->normalizeConfigurableAttributeMap($requiredAttributes)];
    }

    /**
     * Normalize to scalar option ids per attribute; drop empty values.
     *
     * @param array $attributes Attribute code to value map (values may be arrays)
     *
     * @return array
     */
    private function normalizeConfigurableAttributeMap(array $attributes): array
    {
        $out = [];
        foreach ($attributes as $code => $value) {
            if (is_array($value)) {
                $first = reset($value);
                $value = $first !== false ? $first : null;
            }
            if ($value === null || $value === '') {
                continue;
            }
            $out[$code] = $value;
        }
        return $out;
    }
}
