# Configuration — Viettel SInvoice for WHMCS

## Required settings

- `Auth Mode`: `api_key`, `basic`, or `token`.
- `API Base URL`: Viettel SInvoice base URL. HTTP and schemeless values are coerced to HTTPS.
- `Supplier Tax Code`: seller MST used in Viettel endpoints.
- `Template Code`: active invoice template code.
- `Invoice Series`: active invoice series.
- `Seller Legal Name`: seller legal name.
- `Seller Address`: seller registered address.

## Authentication

### API Key headers

Set `APP-KID`, `X-KID`, and `X-API-KEY`.

### Basic Auth

Set `Username` and `Password`.

### Token Cookie

Set `Username` and `Password`. The addon calls `/auth/login` and uses `Cookie: access_token=...`.

## Seller fields

Optional seller fields include phone, email, bank name, bank account, and city name.

## Invoice fields

- `Invoice Type`: default `1`.
- `Payment Method Name`: default `CK`.
- `Payment Method Code`: default `2`.
- `Default Unit`: default `Lần`.
- `Default VAT Rate`: default `10`. WHMCS invoice items only store a taxed flag, so this rate is applied to taxed lines.

## Buyer tax-code settings

- `Require Buyer Tax Code`: blocks issuance when buyer MST is absent.
- `Buyer Tax Code Field`: WHMCS client property or custom field name for buyer MST.
- `B2C Buyer Not Get Invoice`: sets Viettel B2C flag for buyers without MST.

## Automation

- `Auto Issue On Paid`: when enabled, the `InvoicePaid` hook issues invoices automatically.
- `DailyCronJob`: polls up to 20 async records each day.
- Admin page: supports manual issue, poll, force retry, and poll pending.

## Network hardening

- `Connect Timeout`: default `10`; capped between 1 and 10 seconds.
- `API Timeout`: default `90`; capped between 10 and 90 seconds.
- TLS peer and host verification are always enabled.

## Logging and storage

WHMCS module logs and local `last_request`/`last_response` values are redacted. Invalid/non-array provider responses are stored as metadata with body length only.
