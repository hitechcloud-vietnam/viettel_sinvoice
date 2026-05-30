# Changelog

## [1.0.0] — 2026-01-20

### Added

- Initial WHMCS Viettel SInvoice addon module.
- Addon activation, upgrade, deactivation, and admin interface.
- `InvoicePaid` auto-issue hook and `DailyCronJob` async polling hook.
- Local invoice state table with unique `transactionUuid`.
- API-key, Basic Auth, and token-cookie authentication modes.
- Manual issue, retry, poll, and poll-pending actions.
- Commercial legal, support, security, installation, and configuration documentation.

### Security

- Added recursive redaction for credentials, auth headers, tokens, and buyer PII.
- Added safe `logModuleCall()` response handling for `/auth/login` and all API requests.
- Stores metadata for invalid/non-array provider responses instead of raw body content.
- Enforces strict TLS verification and bounded cURL timeouts.
