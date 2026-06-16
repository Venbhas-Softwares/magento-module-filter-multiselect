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
use Venbhas\FilterMultiselect\Model\Config\ModuleEnabledGuard;
use Venbhas\FilterMultiselect\Plugin\AbstractPlugin;

class ItemPlugin extends AbstractPlugin
{
    private const SKIP_PARAMS = ['___from_store', '___store', 'q', 'p', 'limit', 'dir', 'order'];

    /** @var HttpRequest */
    private $request;

    /** @var UrlInterface */
    private $url;

    /**
     * @param ModuleEnabledGuard $moduleEnabledGuard Module enabled guard
     * @param HttpRequest $request Current HTTP request (query parameters)
     * @param UrlInterface $url Front-controller URL builder
     */
    public function __construct(
        ModuleEnabledGuard $moduleEnabledGuard,
        HttpRequest $request,
        UrlInterface $url
    ) {
        parent::__construct($moduleEnabledGuard);
        $this->request = $request;
        $this->url = $url;
    }

    /**
     * Replace remove URL for core Item only (clear whole filter param).
     * Venbhas Item::getRemoveUrl() already builds correct URLs: full clear for multi-value state
     * chips, or partial clear when unchecking one checkbox — do not overwrite that result.
     *
     * @param FilterItem $subject
     * @param string $result Original getRemoveUrl() result
     * @return string
     */
    public function afterGetRemoveUrl(FilterItem $subject, string $result): string
    {
        if (!$this->isModuleEnabled()) {
            return $result;
        }

        if ($subject instanceof \Venbhas\FilterMultiselect\Model\Layer\Filter\Item) {
            return $result;
        }

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
