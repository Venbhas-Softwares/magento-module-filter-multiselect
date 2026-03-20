<?php
/**
 * Venbhas FilterMultiselect - Attribute Filter
 *
 * Extends the core CatalogSearch Attribute filter to:
 *  - Accept single or array values for multi-select filtering.
 *  - Preserve filter items after apply() so options remain visible for
 *    additional selections (multi-select UX).
 */

declare(strict_types=1);

namespace Venbhas\FilterMultiselect\Model\Layer\Filter;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder;
use Magento\Catalog\Model\Layer\Filter\ItemFactory;
use Magento\CatalogSearch\Model\Layer\Filter\Attribute as CatalogSearchAttribute;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Filter\StripTags;
use Magento\Store\Model\StoreManagerInterface;
use Venbhas\FilterMultiselect\Model\Config;

class Attribute extends CatalogSearchAttribute
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @param ItemFactory $filterItemFactory
     * @param StoreManagerInterface $storeManager
     * @param Layer $layer
     * @param DataBuilder $itemDataBuilder
     * @param StripTags $tagFilter
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        ItemFactory $filterItemFactory,
        StoreManagerInterface $storeManager,
        Layer $layer,
        DataBuilder $itemDataBuilder,
        StripTags $tagFilter,
        Config $config,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $tagFilter,
            $data
        );
        $this->config = $config;
    }

    /**
     * Apply attribute filter to the product collection.
     *
     * Accepts both scalar and array values to support multi-select filtering.
     * Does NOT call setItems([]) after apply so filter options remain visible,
     * allowing shoppers to add or change their selection without losing context.
     *
     * @param RequestInterface $request
     * @return $this
     */
    public function apply(RequestInterface $request): static
    {
        if (!$this->config->isEnabled()) {
            return parent::apply($request);
        }

        $attributeValue = $request->getParam($this->_requestVar);
        if (empty($attributeValue) && !is_numeric($attributeValue)) {
            return $this;
        }

        $attribute = $this->getAttributeModel();
        /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
        $productCollection = $this->getLayer()->getProductCollection();

        $productCollection->addFieldToFilter(
            $attribute->getAttributeCode(),
            $this->convertAttributeValue($attribute, $attributeValue)
        );

        $labels = [];
        foreach ((array)$attributeValue as $value) {
            $label = $this->getOptionText($value);
            $labels[] = is_array($label) ? $label : [$label];
        }
        $label = implode(', ', array_unique(array_merge([], ...$labels)));
        $this->getLayer()->getState()->addFilter($this->_createItem($label, $attributeValue));

        // Intentionally NOT calling $this->setItems([]) here.
        // The parent clears items after apply; skipping that keeps filter options
        // visible so shoppers can continue selecting or changing values.

        return $this;
    }

    /**
     * Convert attribute value for OpenSearch compatibility.
     *
     * Duplicated from the parent's private method (inaccessible from subclass).
     * Handles both scalar and array inputs so integer-backed attributes such as
     * color and size are cast element-wise rather than array-to-int (which gives 0).
     *
     * @param ProductAttributeInterface $attribute
     * @param mixed $value Scalar or array of option IDs.
     * @return mixed
     */
    private function convertAttributeValue(ProductAttributeInterface $attribute, mixed $value): mixed
    {
        if ($attribute->getBackendType() === 'int') {
            if (is_array($value)) {
                // Use string option IDs for multi-select. Elasticsearch terms queries accept these for
                // integer-mapped fields, and this avoids Framework Search Binder applying trim() to raw
                // integers when binding placeholders (which would error on PHP 8+).
                return array_values(
                    array_map(
                        static fn ($v) => (string)(int)$v,
                        $value
                    )
                );
            }

            return (int)$value;
        }

        if (is_array($value)) {
            return array_values(array_map(static fn ($v) => is_scalar($v) ? (string)$v : $v, $value));
        }

        return $value;
    }
}
