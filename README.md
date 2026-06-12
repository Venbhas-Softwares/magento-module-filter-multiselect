# Venbhas FilterMultiselect — Magento 2 Module

> Multi-select checkbox filtering for layered navigation attribute filters.


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
- Luma accordion expansion is included; optional Hyvä package adds Alpine.js panel expansion

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

**Luma / Blank theme:**
```bash
composer require venbhas/module-filter-multiselect
bin/magento module:enable Venbhas_FilterMultiselect
bin/magento setup:upgrade
bin/magento cache:clean
```

**Hyvä theme** (requires the base module plus the Hyvä integration package):
```bash
composer require venbhas/module-filter-multiselect venbhas/module-filter-multiselect-hyva
bin/magento module:enable Venbhas_FilterMultiselect Venbhas_FilterMultiselectHyva
bin/magento setup:upgrade
bin/magento cache:clean
```

> On Hyvä stores, enable `Venbhas_FilterMultiselectHyva` so Hyvä templates override the Luma storefront files.

### Manual Installation

1. Copy `Venbhas/FilterMultiselect/` into `app/code/`.
2. For Hyvä, also copy `Venbhas/FilterMultiselectHyva/` into `app/code/`.
3. Run the following commands from your Magento root:

```bash
bin/magento module:enable Venbhas_FilterMultiselect
# Hyvä only:
# bin/magento module:enable Venbhas_FilterMultiselectHyva
bin/magento setup:upgrade
bin/magento cache:clean
```

---

## Configuration

Navigate to **Admin → Stores → Configuration → Venbhas → Filter Multiselect → General Settings**.

| Setting | Description | Default |
|---------|-------------|---------|
| Enable Extension | Master switch for the module. When disabled, layered navigation behaves like core Magento. | Yes |
| Enable Multi-Select Attribute Filters | Global permission for checkbox rendering. Each attribute must also opt in via **Use in Layered Navigation Multi-Select**. | Yes |

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

The extension is split into two packages:

| Package | Module | Purpose |
|---------|--------|---------|
| `venbhas/module-filter-multiselect` | `Venbhas_FilterMultiselect` | Core logic, Luma/Blank templates, accordion JS |
| `venbhas/module-filter-multiselect-hyva` | `Venbhas_FilterMultiselectHyva` | Hyvä templates (Tailwind), Alpine.js panel expansion |

```
Venbhas/FilterMultiselect/          # Required (includes Luma storefront)
├── Model/, Plugin/, Observer/, Setup/, ViewModel/
├── etc/
└── view/frontend/

Venbhas/FilterMultiselectHyva/     # Hyvä storefront (optional)
└── view/frontend/
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
