# AI Agent Guide — Viettel SInvoice for WHMCS

## Module facts

- Platform: WHMCS
- Type: Addon module
- Main file: `viettel_sinvoice.php`
- Shared library: `lib/bootstrap.php`
- Hooks: `hooks.php`
- Language file: `lang/english.php`
- Local table: `mod_viettel_sinvoice_invoices`

## Workflow rules

- Follow `.agents/skills/hddt-einvoice-builder/SKILL.md`, `.agents/skills/whmcs-dev-skills/SKILL.md`, and `.agents/skills/module-security-audit/SKILL.md`.
- Do not log raw Viettel responses or unredacted WHMCS client PII.
- State-changing admin actions must validate WHMCS admin CSRF token.
- Treat transport failures and ambiguous 2xx responses as async and reconcile by `transactionUuid`.

## Key files and functions

- `viettel_sinvoice_config()` defines addon settings.
- `viettel_sinvoice_output()` renders admin UI and handles manual actions.
- `viettel_sinvoice_issue_invoice()` performs qualification, payload build, issue, and state transitions.
- `viettel_sinvoice_poll_invoice()` reconciles UUID state.
- `viettel_sinvoice_api_request()` wraps cURL with strict TLS, timeout caps, redacted `logModuleCall()`, and safe JSON handling.
- `viettel_sinvoice_redact()` masks secrets and buyer PII recursively.

## Constraints

- VND only.
- Requires seller MST, template code, invoice series, and seller legal details.
- WHMCS stores only a taxed flag per invoice item; `defaultVatRate` is used for taxed lines.
- Invalid/non-array provider responses must be stored as metadata only, never raw body content.

## Validation

- Run PHP lint on `viettel_sinvoice.php`, `hooks.php`, `lib/bootstrap.php`, and `lang/english.php`.
- Search for disabled TLS, hardcoded secrets, unsafe raw logging, dangerous calls, and missing escaping before release.
