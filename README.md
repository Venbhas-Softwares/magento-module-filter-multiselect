# Venbhas FilterMultiselect — Magento 2 Module

> Multi-select checkbox filtering for layered navigation attribute filters.

[![Magento 2.4.8+](https://img.shields.io/badge/Magento-2.4.8%2B-orange?logo=magento)](https://devdocs.magento.com/)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%20%7C%208.3%20%7C%208.4-blue?logo=php)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Packagist](https://img.shields.io/packagist/v/venbhas/module-filter-multiselect)](https://packagist.org/packages/venbhas/module-filter-multiselect)

---

## Overview

By default, Magento's layered navigation replaces the active filter each time a shopper clicks an attribute option. This module changes attribute filters (size, color, brand, etc.) to **checkboxes**, so shoppers can select multiple values simultaneously — without losing their previous selections.

Price filters are intentionally left as single-select links, preserving the standard Magento price range behavior.

---

## Features

- Renders attribute filter options as **checkboxes** instead of links
- Supports **multiple simultaneous selections** per attribute (e.g. Red + Blue + Medium)
- Uses **array-based URL parameters** (`?color[]=5&color[]=6`) — fully bookmarkable and shareable
- **Price filters** always remain single-select links (unaffected by this module)
- Toggle on/off via **Magento Admin** without touching code
- Configurable per **Default / Website / Store** scope
- Compatible with **OpenSearch** and **Elasticsearch** backends
- Storefront script (`venbhas-filter-multiselect.js`) expands the layered-nav section that contains the active filter after load (markers: `data-venbhas-has-active` on the option list, Luma accordion, Alpine `x-data`, swatch URL params)

---

## Requirements

| Dependency       | Version        |
|-----------------|----------------|
| PHP             | 8.2 / 8.3 / 8.4 |
| Magento CE/EE   | 2.4.8+         |
| magento/framework | 103.0.*      |
| Magento_Catalog | 104.0.*        |
| Magento_LayeredNavigation | 100.4.* |
| Magento_CatalogSearch | 800.0.* |

---

## Installation

### Via Composer (recommended)

```bash
composer require venbhas/module-filter-multiselect
bin/magento module:enable Venbhas_FilterMultiselect
bin/magento setup:upgrade
bin/magento cache:clean
```

### Manual Installation

1. Download or clone this repository.
2. Copy the contents into `app/code/Venbhas/FilterMultiselect/`.
3. Run the following commands from your Magento root:

```bash
bin/magento module:enable Venbhas_FilterMultiselect
bin/magento setup:upgrade
bin/magento cache:clean
```

---

## Configuration

Navigate to **Admin → Stores → Configuration → Venbhas → Filter Multiselect → General Settings**.

| Setting | Description | Default |
|---------|-------------|---------|
| Enable Multi-Select Attribute Filters | Toggles checkbox rendering for attribute filters. When disabled, reverts to standard Magento single-select links. | Yes |

The setting is configurable at Default, Website, and Store View scope.

---

## How It Works

### Attribute Filters (checkboxes)

When enabled, attribute filter options are rendered as checkboxes. Selecting or deselecting a checkbox navigates to a URL that adds or removes that value from the active filter array.

**URL format:**
```
/women/tops.html?color[]=15&color[]=16&size[]=167
```

Filter options remain visible after selection so shoppers can keep refining.

### Price Filters (single-select links)

Price filters always render as standard links regardless of the module setting. Only one price range can be active at a time.

**URL format:**
```
/women/tops.html?price=50-100
```

---

## Module Structure

```
Venbhas/FilterMultiselect/
├── Model/
│   ├── Config.php                          # Reads admin configuration
│   └── Layer/Filter/
│       ├── Attribute.php                   # Core multi-select filter logic
│       └── Item.php                        # URL generation (add/remove values)
├── ViewModel/
│   └── FilterConfig.php                    # Provides config to templates
├── etc/
│   ├── module.xml                          # Module declaration
│   ├── di.xml                              # Dependency injection config
│   ├── config.xml                          # Default configuration values
│   ├── acl.xml                             # Admin ACL resource
│   └── adminhtml/system.xml               # Admin configuration UI
├── view/frontend/
│   ├── layout/
│   │   ├── default.xml                     # Loads filter-js.phtml globally
│   │   ├── catalog_category_view_type_layered.xml
│   │   └── catalogsearch_result_index.xml
│   ├── templates/layer/
│   │   ├── filter.phtml                    # Filter rendering template
│   │   └── filter-js.phtml                 # References storefront JS asset
│   └── web/js/venbhas-filter-multiselect.js  # Checkbox → URL navigation
├── composer.json
├── registration.php
└── LICENSE
```

---

## Compatibility

| Magento Version | Supported |
|----------------|-----------|
| 2.4.8          | ✅        |
| 2.4.7 and below | Not tested |

> **Note:** This module overrides `Magento\Catalog\Model\Layer\Filter\Item` and the `layer/filter.phtml` template via DI and layout updates. If another extension overrides the same class or template, a compatibility adjustment may be required.

---

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you'd like to change.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/my-feature`)
3. Commit your changes (`git commit -m 'Add my feature'`)
4. Push to the branch (`git push origin feature/my-feature`)
5. Open a pull request

---

## License

This module is open-source software licensed under the [MIT License](LICENSE).

---

## Support

Maintained by [Venbhas Softwares](https://github.com/Venbhas-Softwares).
For bugs or feature requests, please [open an issue](https://github.com/Venbhas-Softwares/magento-module-filter-multiselect/issues).
