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
     * Get URL to add this value to the active multi-select array.
     *
     * Reads current selected values and appends this value to the array.
     *
     * @return string
     */
    public function getUrl()
    {
        if (!$this->config->isEnabled()) {
            return parent::getUrl();
        }

        $filterCode = $this->getFilter()->getRequestVar();
        $currentValues = $this->_getCurrentFilterValues($filterCode);

        if (!in_array($this->getValue(), $currentValues)) {
            $currentValues[] = $this->getValue();
        }

        return $this->_buildUrl($filterCode, $currentValues);
    }

    /**
     * Get URL to remove this value from the active multi-select array.
     *
     * @return string
     */
    public function getRemoveUrl()
    {
        if (!$this->config->isEnabled()) {
            return parent::getRemoveUrl();
        }

        $filterCode = $this->getFilter()->getRequestVar();
        $currentValues = $this->_getCurrentFilterValues($filterCode);

        $key = array_search($this->getValue(), $currentValues);
        if ($key !== false) {
            unset($currentValues[$key]);
            $currentValues = array_values($currentValues);
        }

        return $this->_buildUrl($filterCode, $currentValues);
    }

    /**
     * Check if this filter value is currently active (multi-select).
     *
     * @return bool
     */
    public function isSelected()
    {
        $filterCode = $this->getFilter()->getRequestVar();
        return in_array($this->getValue(), $this->_getCurrentFilterValues($filterCode));
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

        if (!empty($values)) {
            $params[$filterCode] = $values;
        }

        $params['p'] = 1;

        return $this->_url->getUrl('*/*/*', [
            '_current'     => true,
            '_use_rewrite' => true,
            '_query'       => $params,
        ]);
    }

    /**
     * Build the base query parameter array, excluding the given filter code
     * and standard navigation parameters that should reset on filter change.
     *
     * @param string $filterCode
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
