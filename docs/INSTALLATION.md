# Installation — Viettel SInvoice for WHMCS

## Requirements

- WHMCS 8.x/9.x compatible environment.
- PHP cURL and JSON extensions.
- Database user with permission to create/alter addon tables.
- Viettel SInvoice account, credentials, seller tax code, template code, and invoice series.

## Steps

1. Back up the WHMCS filesystem and database.
2. Copy `module_dev_whmcs/modules/addons/viettel_sinvoice/` to `modules/addons/viettel_sinvoice/` in the WHMCS installation.
3. In WHMCS admin, open **System Settings → Addon Modules**.
4. Activate **Viettel SInvoice**.
5. Assign administrator access permissions for trusted roles only.
6. Configure all required settings.
7. Save settings and open **Addons → Viettel SInvoice**.
8. Test manual issuance against Viettel sandbox/staging where available.
9. Confirm `InvoicePaid` auto-issue behavior only after UAT passes.

## Database

Activation creates or upgrades `mod_viettel_sinvoice_invoices` for local invoice state, `transactionUuid`, provider identifiers, sanitized request/response metadata, and async polling status.

## Verification

- PHP lint passes for all module PHP files.
- WHMCS addon page loads without errors.
- Admin actions require valid WHMCS token.
- WHMCS module logs do not include raw credentials or buyer PII.

## Common issues

- Missing template/series: verify values in Viettel portal.
- Non-VND invoice: this module intentionally blocks non-VND issuance.
- Timeout/ambiguous response: use **Poll** or daily cron reconciliation before force retry.
