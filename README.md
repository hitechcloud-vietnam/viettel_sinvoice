# Viettel SInvoice for WHMCS

> WHMCS addon module for issuing Vietnamese HĐĐT through Viettel SInvoice.

## Overview

This addon integrates WHMCS with Viettel SInvoice (`vinvoice.viettel.vn`) for Vietnamese electronic invoice issuance. It can issue invoices automatically when WHMCS fires `InvoicePaid`, supports manual admin issuance/retry/poll actions, and reconciles ambiguous Viettel responses by persisted `transactionUuid`.

## Requirements

- WHMCS 8.x/9.x compatible addon environment.
- PHP with cURL and JSON extensions.
- Viettel SInvoice API credentials and active invoice template/series.
- VND invoices.

## Features

- WHMCS addon lifecycle: activate, deactivate, upgrade, and admin output.
- `InvoicePaid` auto-issue hook.
- `DailyCronJob` pending-record polling.
- Manual admin actions: issue, retry, poll, poll pending.
- Local table `mod_viettel_sinvoice_invoices` for UUID/state tracking.
- API-key, Basic Auth, and token-cookie authentication modes.
- Strict TLS verification, bounded timeouts, and redacted WHMCS module logs.

## Installation

Copy this directory to `modules/addons/viettel_sinvoice/`, activate the addon in WHMCS, and configure the settings. See `docs/INSTALLATION.md`.

## Configuration

Configure Viettel credentials, seller information, invoice template/series, VAT defaults, and buyer tax-code rules. See `docs/CONFIGURATION.md`.

## Support

Commercial support: `support@photuesoftware.com`.

Security reports: `report@photuesoftware.com`.

## License

Commercial proprietary license — copyright © 2026 Pho Tue SoftWare And Technology Solutions Joint Stock Company.
