# FenixTrace for WordPress

WordPress plugin that adds a **"FenixTrace Products"** custom post type and registers them on the **IOTA L1** blockchain via the FenixTrace Integration Kit. **No WooCommerce required** — works with plain WordPress.

> Built by [Fenix Software Labs](https://www.fenixsoftwarelabs.com)

## How It Works

```
WordPress Product (CPT) → JSON → Integration Kit → IPFS + IOTA L1 → FenixTrace Scanner
```

## vs WooCommerce Plugin

| Feature | This Plugin | WooCommerce Plugin |
|---|---|---|
| Requires WooCommerce | No | Yes |
| Product source | Custom Post Type | WC Products |
| Best for | Non-shop sites, catalogs, traceability portals | Online stores |

## Requirements

- WordPress 5.8+
- PHP 7.4+
- [FenixTrace Integration Kit](https://github.com/SantoBaldassarre/FenixTrace-IOTA-auto-add-product-Integration-Kit) running

## Installation

1. Copy to `wp-content/plugins/fenixtrace/`
2. Activate from **Plugins** in WP Admin
3. Go to **Settings → FenixTrace** to configure

## Configuration

| Setting | Description |
|---|---|
| Kit URL | Integration Kit address (default: `http://localhost:3005`) |
| Upload Directory | Optional path to Kit's `uploads/` folder |
| Company Name | Your company name for blockchain records |
| Default Template | Product category (agro, pharma, fashion, etc.) |
| Auto-sync | Sync automatically when product is published |

## Usage

### Create Products
Go to **FenixTrace → Add Product**. Fill in title, description, and product data (SKU, barcode, price, weight, origin, template).

### Sync to Blockchain
On the product edit page → sidebar **"FenixTrace Blockchain"** → click **"Send to FenixTrace"**

### REST API
```bash
# Sync a product
POST /wp-json/fenixtrace/v1/sync/{id}

# Get product blockchain status
GET /wp-json/fenixtrace/v1/sync/{id}
```

## Admin List Columns

The product list shows: **State** (badge), **TX Hash**, **Last Sync** for quick overview.

## Other Plugins

| Plugin | Platform | Repository |
|---|---|---|
| **FenixTrace for WooCommerce** | WordPress + WooCommerce | [GitHub](https://github.com/SantoBaldassarre/FenixTrace-IOTA-Plugin-WooCommerce) |
| **FenixTrace for Odoo** | Odoo 16/17 | [GitHub](https://github.com/SantoBaldassarre/FenixTrace-IOTA-Plugin-Odoo) |
| **FenixTrace for PrestaShop** | PrestaShop 1.7/8.x | [GitHub](https://github.com/SantoBaldassarre/FenixTrace-IOTA-Plugin-PrestaShop) |
| **FenixTrace for Salesforce** | Salesforce CRM | [GitHub](https://github.com/SantoBaldassarre/FenixTrace-Plugin-Salesforce) |

## Links

- [FenixTrace Platform](https://fenixtrace.com)
- [FenixTrace Integration Docs](https://fenixtrace.com/docs/integration-gateway)
- [Integration Kit](https://github.com/SantoBaldassarre/FenixTrace-IOTA-auto-add-product-Integration-Kit)
- [Fenix Software Labs](https://www.fenixsoftwarelabs.com)

## License

GPL-2.0-or-later
