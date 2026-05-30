# Contributing

This is a commercial module. Contributions are accepted only through authorized customer, partner, or internal development channels.

## Bug reports

Send reproducible reports to `support@photuesoftware.com` with:

- WHMCS version and PHP version.
- Module version.
- Sanitized WHMCS module log excerpts.
- Steps to reproduce.

Do not include production credentials, access tokens, full buyer PII, or raw Viettel API responses.

## Pull requests

- Follow WHMCS addon conventions and existing code style.
- Preserve direct-access guards.
- Preserve admin token validation for state-changing actions.
- Keep all logs and stored request/response data redacted.
- Run PHP lint and security grep checks before submission.
- Update `docs/CHANGELOG.md` for customer-visible changes.

## Contact

General contact: `info@photuesoftware.com`.
