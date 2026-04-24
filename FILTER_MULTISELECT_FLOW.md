# Venbhas Filter Multiselect 





## 1\) What this module does

Magento core layered navigation attribute filters are usually **single-select** (clicking an option replaces the previous one).

This module enables **multi-select** for attribute filters by:

* Adding a per-attribute flag: `catalog\_eav\_attribute.venbhas\_layered\_multiselect`
* Adding store config switches (master enable + global multi-select enable)
* Rendering attribute filter options as **checkboxes**
* Using **array query parameters** (e.g. `?color\[]=12\&color\[]=13`)
* Modifying filter apply and item URL logic so:

  * applying multiple values works
  * active state remove URLs work correctly
  * facet counts still show correctly while filters are active

Price filter remains **single-select links** (always).



## 2\) Configuration

System config UI:

* `etc/adminhtml/system.xml` (`Stores → Configuration → Venbhas → Filter Multiselect`)

Defaults:

* `etc/config.xml`

Keys:

* `venbhas\_filtermultiselect/general/enabled`

  * Master on/off switch for this module’s storefront behavior.
* `venbhas\_filtermultiselect/general/multiselect\_globally`

  * Global switch: multi-select can only happen when this is enabled **and** the attribute flag is enabled.

Config reader:

* `Model/Config.php`

ViewModel exposed to templates:

* `ViewModel/FilterConfig.php`

  * `isMultiselectForFilter($filter)` → checks per-attribute flag via `Model/Config`
  * `shouldDisplayProductCount()` → reads core Magento setting `catalog/layered\_navigation/display\_product\_count`



## 3\) Database schema changes 

## Declared in:

* `etc/db\_schema.xml`

Adds a column to `catalog\_eav\_attribute`:

* `venbhas\_layered\_multiselect` (smallint, default 0)

Meaning:

* If `0`, attribute filter behaves like standard Magento (single-select)
* If `1`, attribute filter can be multi-select **only if** store config is enabled



## 4\) Admin UI: 

## Magento admin event:

* `product\_attribute\_form\_build\_front\_tab`

Observer:

* `Observer/Adminhtml/ProductAttributeFormBuildFrontTabObserver.php`

  * Adds form field `venbhas\_layered\_multiselect` to the attribute edit UI in “Storefront Properties”

Admin request plugin (save defaulting behavior):

* `Plugin/Request/HttpGetPostValuePlugin.php`

  * On `catalog\_product\_attribute\_save`, if admin form omits the field, defaults it to `0`
  * Special-case: swatch inputs (`swatch\_visual`, `swatch\_text`) can omit field without forcing 0 (prevents wiping existing values)



## 5\) Core rewiring: 

## DI preferences in:

* `etc/di.xml`

### A) Filter Item URL logic

Preference:

* `Magento\\Catalog\\Model\\Layer\\Filter\\Item`
→ `Venbhas\\FilterMultiselect\\Model\\Layer\\Filter\\Item`

Key behaviors (`Model/Layer/Filter/Item.php`):

* In multi-select mode:

  * `getUrl()` appends this item’s value into an array query param
  * `getRemoveUrl()` removes only this value from the array, OR clears the full attribute param (when state item contains full array)
  * `isSelected()` checks if any of this item’s values are in the request array
* For single-select link rendering (price, etc.):

  * `getSingleSelectUrl()`, `getSingleSelectRemoveUrl()`, `isSingleSelectSelected()`

### B) Filter apply logic (attribute filter)

Preference:

* `Magento\\CatalogSearch\\Model\\Layer\\Filter\\Attribute`
→ `Venbhas\\FilterMultiselect\\Model\\Layer\\Filter\\Attribute`

Key behaviors (`Model/Layer/Filter/Attribute.php`):

* If attribute is not configured for multi-select → uses parent behavior.
* If multi-select enabled:

  * `apply()` accepts scalar **or array** request param values
  * adds a filter to the product collection with array values
  * adds a layer state item representing the selection
  * does **not** clear items after apply (keeps options visible)
* `\_getItemsData()` uses faceted counts from the **base layer collection**
(category or search scope) so options stay visible with correct counts,
instead of reading facets from the already-filtered listing collection.

### C) Swatches compatibility

DI preference:

* `Magento\\Swatches\\Model\\Plugin\\FilterRenderer`
→ `Plugin/Swatches/SwatchLayeredFilterRenderer`

And plugin:

* `Plugin/Swatches/Helper/SwatchRequestAttributesPlugin.php`

Purpose:

* Swatches can override layered navigation rendering with single-select behavior.
This module normalizes request attributes and renderer behavior so multi-select remains consistent.

### D) Active state remove URL compatibility

Plugin:

* `Plugin/Layer/Filter/ItemPlugin.php`

Purpose:

* Fix remove URL generation when Magento state “chips” or other code paths call remove URL
through interfaces/types that may not be the concrete multiselect item instance.



## 6\) Frontend rendering: 

## This module swaps the layered navigation filter template to a checkbox-capable template.

Category layered navigation layout:

* `view/frontend/layout/catalog\_category\_view\_type\_layered.xml`

  * changes `catalog.navigation.renderer` template to:

    * `view/frontend/templates/layer/filter.phtml`
  * injects ViewModel `Venbhas\\FilterMultiselect\\ViewModel\\FilterConfig` as `venbhas\_filter\_config`

Search layered navigation layout:

* `view/frontend/layout/catalogsearch\_result\_index.xml`

  * changes `catalogsearch.navigation.renderer` template to the same `layer/filter.phtml`

Always-load JS (Hyvä + Luma safe):

* `view/frontend/layout/default.xml` adds `layer/filter-js.phtml` in `before.body.end`

  * only when `venbhas\_filtermultiselect/general/enabled`
* `view/frontend/templates/layer/filter-js.phtml` loads:

  * `view/frontend/web/js/venbhas-filter-multiselect.js`

Template rendering logic:

* `view/frontend/templates/layer/filter.phtml`

  * Detects filter object and whether it’s price (`requestVar === 'price'`)
  * Price filter: always links (single-select)
  * Attribute filters: checkboxes only if `FilterConfig::isMultiselectForFilter($filter)` is true
  * Each checkbox has a `data-url`:

    * if unchecked → `Item::getUrl()` (add value)
    * if checked → `Item::getRemoveUrl()` (remove value)

Request format used:

* `?{attribute\_code}\[]=<option\_id>\&{attribute\_code}\[]=<option\_id>`



## 7\) Setup patches 

## Data patches:

* `Setup/Patch/Data/EnableLayeredMultiselectForFilterableAttributes.php`
* `Setup/Patch/Data/RestoreLayeredMultiselectForSwatchAttributes.php`

Purpose (high level):

* Bulk-enable the per-attribute flag for filterable attributes (and handle swatch types),
so you don’t have to open each attribute manually.



