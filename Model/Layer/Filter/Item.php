<?php
/**
 * Venbhas FilterMultiselect - Filter Item Model
 *
 * Extends core Item to support multi-select filtering with array URL parameters.
 * Also provides single-select URL helpers used by the price filter template.
 */

declare(strict_types=1);

namespace Venbhas\FilterMultiselect\Model\Layer\Filter;

use Magento\Catalog\Model\Layer\Filter\Item as CoreItem;
use Magento\Framework\App\Request\Http;
use Magento\Framework\UrlInterface;
use Magento\Theme\Block\Html\Pager;
use Venbhas\FilterMultiselect\Model\Config;

class Item extends CoreItem
{
    /**
     * @var Http
     */
    protected $request;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @param UrlInterface $url
     * @param Pager $htmlPagerBlock
     * @param Http $request
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        UrlInterface $url,
        Pager $htmlPagerBlock,
        Http $request,
        Config $config,
        array $data = []
    ) {
        parent::__construct($url, $htmlPagerBlock, $data);
        $this->request = $request;
        $this->config = $config;
    }

    /**
     * Multi-select URLs and selection logic only when global + per-attribute flags allow it.
     */
    private function isMultiselectModeForThisItem(): bool
    {
        if (!$this->config->isExtensionEnabled()) {
            return false;
        }

        try {
            $attribute = $this->getFilter()->getAttributeModel();
        } catch (\Throwable $e) {
            return false;
        }

        return $this->config->isLayeredMultiselectEnabledForAttribute($attribute);
    }

    /**
     * Get URL to add this value to the active multi-select array.
     *
     * Reads current selected values and appends this value to the array.
     * Handles both scalar and array item values (state items may store arrays).
     *
     * @return string
     */
    public function getUrl()
    {
        if (!$this->isMultiselectModeForThisItem()) {
            return parent::getUrl();
        }

        $filterCode = $this->getFilter()->getRequestVar();
        $currentValues = $this->_getCurrentFilterValues($filterCode);
        $value = $this->getValue();
        $valuesToAdd = is_array($value) ? $value : [$value];

        foreach ($valuesToAdd as $v) {
            if (!in_array($v, $currentValues)) {
                $currentValues[] = $v;
            }
        }

        return $this->_buildUrl($filterCode, $currentValues);
    }

    /**
     * Get URL to remove this filter value from the active state.
     *
     * - Layer state chip (Attribute::apply): one state item per attribute with value = full selection
     *   as an array with 2+ option ids → remove the entire attribute from the URL.
     * - Sidebar checkbox row: value is a single option → remove only that id from the current
     *   multi-select array so other checked options stay applied.
     *
     * @return string
     */
    public function getRemoveUrl()
    {
        if (!$this->isMultiselectModeForThisItem()) {
            return parent::getRemoveUrl();
        }

        $filterCode = $this->getFilter()->getRequestVar();
        $itemValue = $this->getValue();

        // Aggregated "Active filtering" row: value holds every selected option for this attribute.
        if (is_array($itemValue) && count($itemValue) > 1) {
            return $this->_buildUrl($filterCode, []);
        }

        $currentValues = $this->_getCurrentFilterValues($filterCode);
        $valuesToRemove = is_array($itemValue) ? array_values($itemValue) : [$itemValue];

        $remove = [];
        foreach ($valuesToRemove as $v) {
            $remove[(string) $v] = true;
        }

        $remaining = [];
        foreach ($currentValues as $cv) {
            if (!isset($remove[(string) $cv])) {
                $remaining[] = $cv;
            }
        }

        return $this->_buildUrl($filterCode, $remaining);
    }

    /**
     * Mirror Luma clear-link behavior using the multi-select-safe remove URL.
     *
     * Luma uses getClearLinkUrl() for the remove link when the filter defines clear link text.
     * Delegates to getRemoveUrl() so active chips clear the full filter param consistently.
     *
     * @return false|string
     */
    public function getClearLinkUrl()
    {
        if (!$this->isMultiselectModeForThisItem()) {
            return parent::getClearLinkUrl();
        }

        $parentUrl = parent::getClearLinkUrl();
        if ($parentUrl === false) {
            return false;
        }

        return $this->getRemoveUrl();
    }

    /**
     * Whether this filter value is active when multi-select mode is enabled.
     *
     * Handles both scalar and array item values (state items may store arrays).
     *
     * @return bool
     */
    public function isSelected()
    {
        if (!$this->isMultiselectModeForThisItem()) {
            return parent::isSelected();
        }

        $filterCode = $this->getFilter()->getRequestVar();
        $currentValues = $this->_getCurrentFilterValues($filterCode);
        $value = $this->getValue();
        $valuesToCheck = is_array($value) ? $value : [$value];

        foreach ($valuesToCheck as $v) {
            if (in_array($v, $currentValues)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get a single-select URL that replaces the current filter value.
     *
     * Used by price and other single-select filters rendered as links.
     *
     * @return string
     */
    public function getSingleSelectUrl(): string
    {
        $filterCode = $this->getFilter()->getRequestVar();
        $params = $this->_getBaseParams($filterCode);
        $params[$filterCode] = $this->getValue();
        $params['p'] = 1;

        return $this->_url->getUrl('*/*/*', [
            '_current'     => true,
            '_use_rewrite' => true,
            '_query'       => $params,
        ]);
    }

    /**
     * Get a URL that removes this single-select filter entirely.
     *
     * Used by price and other single-select filters rendered as links.
     *
     * @return string
     */
    public function getSingleSelectRemoveUrl(): string
    {
        $filterCode = $this->getFilter()->getRequestVar();
        $params = $this->_getBaseParams($filterCode);
        $params['p'] = 1;

        return $this->_url->getUrl('*/*/*', [
            '_current'     => true,
            '_use_rewrite' => true,
            '_query'       => $params,
        ]);
    }

    /**
     * Check if this item is the currently selected single-select value.
     *
     * Returns false if the request parameter is an array (multi-select URL).
     *
     * @return bool
     */
    public function isSingleSelectSelected(): bool
    {
        $filterCode = $this->getFilter()->getRequestVar();
        $value = $this->request->getParam($filterCode);

        if ($value === null || is_array($value)) {
            return false;
        }

        return (string)$value === (string)$this->getValue();
    }

    /**
     * Get current filter values from request as an array (multi-select).
     *
     * @param string $filterCode
     * @return array
     */
    protected function _getCurrentFilterValues(string $filterCode): array
    {
        $value = $this->request->getParam($filterCode);

        if ($value === null) {
            return [];
        }

        if (is_array($value)) {
            return array_values(array_filter($value));
        }

        return [$value];
    }

    /**
     * Build URL with array-based multi-select filter parameters.
     *
     * @param string $filterCode
     * @param array $values
     * @return string
     */
    protected function _buildUrl(string $filterCode, array $values): string
    {
        $params = $this->_getBaseParams($filterCode);

        // With _current => true, Magento merges the full request query first; only keys in _query
        // are updated. Omitting the filter leaves the old param — must pass null to clear it.
        if (!empty($values)) {
            $params[$filterCode] = $values;
        } else {
            $params[$filterCode] = null;
        }

        $params['p'] = 1;

        return $this->_url->getUrl('*/*/*', [
            '_current'     => true,
            '_use_rewrite' => true,
            '_query'       => $params,
        ]);
    }

    /**
     * Build the base query parameter array for layered navigation URLs.
     *
     * Omits the active filter code plus pagination, sort, and store keys so only relevant params carry over.
     *
     * @param string $filterCode Request parameter name for this filter.
     *
     * @return array
     */
    protected function _getBaseParams(string $filterCode): array
    {
        $skip = ['___from_store', '___store', 'q', 'p', 'limit', 'dir', 'order', $filterCode];
        $params = [];

        foreach ($this->request->getParams() as $key => $value) {
            if (!in_array($key, $skip, true)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}
