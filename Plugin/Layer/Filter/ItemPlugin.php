<?php
/**
 * Venbhas FilterMultiselect - Plugin to fix remove URL for active filter chips
 *
 * Ensures the trash link href omits the filter param even when the State block
 * uses the core Item (e.g. preference not applied or different layer).
 */

declare(strict_types=1);

namespace Venbhas\FilterMultiselect\Plugin\Layer\Filter;

use Magento\Catalog\Model\Layer\Filter\Item as FilterItem;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\UrlInterface;

class ItemPlugin
{
    private const SKIP_PARAMS = ['___from_store', '___store', 'q', 'p', 'limit', 'dir', 'order'];

    /** @var HttpRequest */
    private $request;
    /** @var UrlInterface */
    private $url;

    public function __construct(
        HttpRequest $request,
        UrlInterface $url
    ) {
        $this->request = $request;
        $this->url = $url;
    }

    /**
     * Replace remove URL with one that omits this filter's param (fixes trash icon in both themes).
     * Always applied so the remove link works regardless of module config or Item implementation.
     *
     * @param FilterItem $subject
     * @param string $result Original getRemoveUrl() result
     * @return string
     */
    public function afterGetRemoveUrl(FilterItem $subject, string $result): string
    {
        try {
            $filter = $subject->getFilter();
            $filterCode = $filter->getRequestVar();
        } catch (\Throwable $e) {
            return $result;
        }

        $params = [];
        foreach ($this->request->getParams() as $key => $value) {
            if (!in_array($key, self::SKIP_PARAMS, true) && $key !== $filterCode) {
                $params[$key] = $value;
            }
        }
        $params['p'] = 1;
        // _current merges full query first; addQueryParams only overwrites keys we pass — null clears.
        $params[$filterCode] = null;

        return $this->url->getUrl('*/*/*', [
            '_current' => true,
            '_use_rewrite' => true,
            '_query' => $params,
        ]);
    }
}
