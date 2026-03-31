<?php

declare(strict_types=1);

namespace Venbhas\FilterMultiselect\Plugin\Swatches\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Swatches\Helper\Data as SwatchDataHelper;

/**
 * Layered navigation multi-select uses array query params; Swatches helpers compare attribute values
 * with array_diff_assoc / eq filters expecting scalars. Normalize to one option id for image resolution.
 */
class SwatchRequestAttributesPlugin
{
    /**
     * @param SwatchDataHelper $subject
     * @param ProductInterface $parentProduct
     * @param array<string, mixed> $attributes
     * @return array{0: ProductInterface, 1: array<string, mixed>}
     */
    public function beforeLoadVariationByFallback(
        SwatchDataHelper $subject,
        ProductInterface $parentProduct,
        array $attributes
    ): array {
        return [$parentProduct, $this->normalizeConfigurableAttributeMap($attributes)];
    }

    /**
     * @param SwatchDataHelper $subject
     * @param ProductInterface $configurableProduct
     * @param array<string, mixed> $requiredAttributes
     * @return array{0: ProductInterface, 1: array<string, mixed>}
     */
    public function beforeLoadFirstVariationWithImage(
        SwatchDataHelper $subject,
        ProductInterface $configurableProduct,
        array $requiredAttributes
    ): array {
        return [$configurableProduct, $this->normalizeConfigurableAttributeMap($requiredAttributes)];
    }

    /**
     * @param SwatchDataHelper $subject
     * @param ProductInterface $configurableProduct
     * @param array<string, mixed> $requiredAttributes
     * @return array{0: ProductInterface, 1: array<string, mixed>}
     */
    public function beforeLoadFirstVariationWithSwatchImage(
        SwatchDataHelper $subject,
        ProductInterface $configurableProduct,
        array $requiredAttributes
    ): array {
        return [$configurableProduct, $this->normalizeConfigurableAttributeMap($requiredAttributes)];
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
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
