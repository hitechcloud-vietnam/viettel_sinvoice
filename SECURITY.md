# Security Policy

## Scope

This policy covers Viettel SInvoice for WHMCS, including addon settings, admin actions, hooks, local invoice record storage, API authentication, and WHMCS module logging.

## Supported versions

Security fixes are provided for the latest commercial release and the immediately previous maintained release.

## Reporting a vulnerability

Report vulnerabilities privately to `report@photuesoftware.com`. Do not open public issues or disclose details until a fix is available.

Please include:

- Module version.
- WHMCS/PHP versions.
- Reproduction steps.
- Impact assessment.
- Sanitized logs only.

Expected initial response: within 48 hours.

## Security expectations

- Keep Viettel credentials in WHMCS addon settings only.
- Use HTTPS endpoints with valid certificates.
- Do not disable `CURLOPT_SSL_VERIFYPEER` or `CURLOPT_SSL_VERIFYHOST`.
- Do not log raw provider responses containing credentials or PII.
- Restrict addon access to trusted WHMCS administrators.
- Keep WHMCS admin CSRF token validation on all state-changing actions.
