<?php
/**
 * Venbhas FilterMultiselect - Attribute Filter
 *
 * Extends the core CatalogSearch Attribute filter to:
 *  - Accept single or array values for multi-select filtering.
 *  - Preserve filter items after apply() so options remain visible for
 *    additional selections (multi-select UX).
 *  - For multi-select attributes, build facet counts from the category (or search)
 *    base collection so all options stay visible with correct counts, not from the
 *    already-filtered collection.
 */

declare(strict_types=1);

namespace Venbhas\FilterMultiselect\Model\Layer\Filter;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Category as CategoryLayer;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder;
use Magento\Catalog\Model\Layer\Filter\ItemFactory;
use Magento\Catalog\Model\Layer\Search as SearchLayer;
use Magento\Catalog\Model\Layer\Category\CollectionFilter as CategoryCollectionFilter;
use Magento\Catalog\Model\Layer\Search\CollectionFilter as SearchCollectionFilter;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogSearch\Model\Layer\Filter\Attribute as CatalogSearchAttribute;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection as FulltextCollection;
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
     * Parent keeps the same instance as private; child needs its own reference for _getItemsData().
     *
     * @var StripTags
     */
    private StripTags $tagFilter;

    /**
     * DI maps this to Magento\CatalogSearch\Model\ResourceModel\Fulltext\CollectionFactory (virtual type).
     *
     * @var ProductCollectionFactory
     */
    private ProductCollectionFactory $fulltextCollectionFactory;

    /**
     * DI maps this to Magento\CatalogSearch\Model\ResourceModel\Fulltext\SearchCollectionFactory (virtual type).
     *
     * @var ProductCollectionFactory
     */
    private ProductCollectionFactory $searchCollectionFactory;

    /**
     * @var CategoryCollectionFilter
     */
    private CategoryCollectionFilter $categoryCollectionFilter;

    /**
     * @var SearchCollectionFilter
     */
    private SearchCollectionFilter $searchCollectionFilter;

    /**
     * @param ItemFactory $filterItemFactory
     * @param StoreManagerInterface $storeManager
     * @param Layer $layer
     * @param DataBuilder $itemDataBuilder
     * @param StripTags $tagFilter
     * @param Config $config
     * @param ProductCollectionFactory $fulltextCollectionFactory
     * @param ProductCollectionFactory $searchCollectionFactory
     * @param CategoryCollectionFilter $categoryCollectionFilter
     * @param SearchCollectionFilter $searchCollectionFilter
     * @param array $data
     */
    public function __construct(
        ItemFactory $filterItemFactory,
        StoreManagerInterface $storeManager,
        Layer $layer,
        DataBuilder $itemDataBuilder,
        StripTags $tagFilter,
        Config $config,
        ProductCollectionFactory $fulltextCollectionFactory,
        ProductCollectionFactory $searchCollectionFactory,
        CategoryCollectionFilter $categoryCollectionFilter,
        SearchCollectionFilter $searchCollectionFilter,
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
        $this->tagFilter = $tagFilter;
        $this->config = $config;
        $this->fulltextCollectionFactory = $fulltextCollectionFactory;
        $this->searchCollectionFactory = $searchCollectionFactory;
        $this->categoryCollectionFilter = $categoryCollectionFilter;
        $this->searchCollectionFilter = $searchCollectionFilter;
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
    public function apply(RequestInterface $request)
    {
        if (!$this->config->isLayeredMultiselectEnabledForAttribute($this->getAttributeModel())) {
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
     * @inheritdoc
     *
     * Uses facet data from the layer base collection (category or search only),
     * not the filtered listing collection, so all attribute options stay visible.
     */
    protected function _getItemsData()
    {
        /** @var EavAttribute $attribute */
        $attribute = $this->getAttributeModel();
        if (!$this->config->isLayeredMultiselectEnabledForAttribute($attribute)) {
            return parent::_getItemsData();
        }

        $optionsFacetedData = $this->getBaseLayerFacetedData($attribute);
        if ($optionsFacetedData === null) {
            return parent::_getItemsData();
        }

        $isAttributeFilterable =
            $this->getAttributeIsFilterable($attribute) === static::ATTRIBUTE_OPTIONS_ONLY_WITH_RESULTS;

        if (count($optionsFacetedData) === 0 && !$isAttributeFilterable) {
            return $this->itemDataBuilder->build();
        }

        $options = $attribute->getFrontend()->getSelectOptions();
        foreach ($options as $option) {
            $this->buildOptionDataRow($option, $isAttributeFilterable, $optionsFacetedData);
        }

        return $this->itemDataBuilder->build();
    }

    /**
     * Faceted counts for the current category/search scope without layered navigation filters applied.
     *
     * @return array<string, array<string, mixed>>|null Null = use parent (filtered collection)
     */
    private function getBaseLayerFacetedData(ProductAttributeInterface $attribute): ?array
    {
        $layer = $this->getLayer();
        $category = $layer->getCurrentCategory();
        if ($category === null || !$category->getId()) {
            return null;
        }

        $field = $attribute->getAttributeCode();

        try {
            if ($layer instanceof CategoryLayer) {
                /** @var FulltextCollection $collection */
                $collection = $this->fulltextCollectionFactory->create();
                $collection->addCategoryFilter($category);
                $this->categoryCollectionFilter->filter($collection, $category);

                return $collection->getFacetedData($field);
            }
            if ($layer instanceof SearchLayer) {
                /** @var FulltextCollection $collection */
                $collection = $this->searchCollectionFactory->create();
                $this->searchCollectionFilter->filter($collection, $category);

                return $collection->getFacetedData($field);
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    /**
     * Mirrors Magento\CatalogSearch\Model\Layer\Filter\Attribute::buildOptionData (private there).
     *
     * @param array<string, mixed> $option
     * @param array<string, array<string, mixed>> $optionsFacetedData
     */
    private function buildOptionDataRow(array $option, bool $isAttributeFilterable, array $optionsFacetedData): void
    {
        $value = $this->getOptionValueFromOption($option);
        if ($value === false) {
            return;
        }
        $count = $this->getOptionCountFromFacets($value, $optionsFacetedData);
        if ($isAttributeFilterable && $count === 0) {
            return;
        }

        $this->itemDataBuilder->addItemData(
            $this->tagFilter->filter($option['label']),
            $value,
            $count
        );
    }

    /**
     * @param array<string, mixed> $option
     * @return bool|string
     */
    private function getOptionValueFromOption(array $option)
    {
        if (empty($option['value']) && !is_numeric($option['value'])) {
            return false;
        }
        return $option['value'];
    }

    /**
     * @param int|string $value
     * @param array<string, array<string, mixed>> $optionsFacetedData
     */
    private function getOptionCountFromFacets($value, array $optionsFacetedData): int
    {
        foreach ([$value, (string) $value] as $key) {
            if (isset($optionsFacetedData[$key]['count'])) {
                return (int) $optionsFacetedData[$key]['count'];
            }
        }
        return 0;
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
