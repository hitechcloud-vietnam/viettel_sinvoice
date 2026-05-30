# SECURITY_AUDIT: Viettel SInvoice for WHMCS

## Scope

- Module path: `module_dev_whmcs/modules/addons/viettel_sinvoice/`
- Platform: WHMCS
- Version: 1.0.0

## Findings

| ID | Severity | Status | Notes |
| --- | ---: | --- | --- |
| WHMCS-VSI-001 | High | Fixed | Token login no longer logs raw token-bearing provider responses. |
| WHMCS-VSI-002 | High | Fixed | General API requests no longer pass raw provider body to `logModuleCall()`. |
| WHMCS-VSI-003 | Medium | Fixed | Invalid/non-array provider responses are summarized by metadata instead of stored raw. |
| WHMCS-VSI-004 | Medium | Fixed | Configured `http://` API base URLs are coerced to `https://`. |
| WHMCS-VSI-005 | Medium | Fixed | Connect timeout is capped to 10 seconds; transfer timeout is capped to 90 seconds for Viettel async behavior. |
| WHMCS-VSI-006 | Medium | Fixed | Secret and buyer PII redaction expanded for nested payloads. |

## Checklist

| Block | Result | Evidence |
| --- | --- | --- |
| Secrets and credentials | PASS | No hardcoded credentials; WHMCS addon settings are used; logs are redacted. |
| TLS / SSL verification | PASS | cURL sets `CURLOPT_SSL_VERIFYPEER => true` and `CURLOPT_SSL_VERIFYHOST => 2`. |
| HTTP timeouts and errors | PASS | Bounded timeouts and async candidate handling exist for transport/ambiguous responses. |
| Input validation | PASS | Invoice ID casting, required config, VND currency, status, line items, and buyer data are validated. |
| Authorization and ownership | PASS | Admin actions require WHMCS token and trusted addon access; hooks use internal invoice IDs. |
| SQL safety | PASS | WHMCS Capsule query builder is used; no raw SQL concatenation. |
| XSS / output escaping | PASS | Admin output uses `viettel_sinvoice_e()` for dynamic values. |
| File and path safety | PASS | No upload/download/file path operations are present. |
| Logging and error disclosure | PASS | `logModuleCall()` uses sanitized request/response arrays; exception context stores class/code only. |
| Dangerous calls/debug code | PASS | No eval, shell execution, unserialize, var_dump, or print_r found. |

## Release decision

PASS for controlled staging validation. Production release requires real Viettel sandbox/UAT verification with sanitized WHMCS module logs.
