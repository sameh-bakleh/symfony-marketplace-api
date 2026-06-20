# Security Policy

## Supported versions

| Version | Supported |
|---------|-----------|
| `main` branch | Yes |

This is a **portfolio / demonstration** API. Do not deploy it to production without a full security review.

## Reporting a vulnerability

If you discover a security issue in this repository:

1. **Do not** open a public GitHub issue with exploit details.
2. Email the maintainer via the contact address on their GitHub profile, or use GitHub **Private vulnerability reporting** if enabled on the repository.
3. Include steps to reproduce, affected endpoints, and impact assessment.

You can expect an initial response within **7 days**.

## Security practices in this project

- JWT access tokens (Lexik) and refresh tokens (Gesdinet) for stateless API auth.
- Symfony Security firewalls, `access_control`, and resource voters (`ProductVoter`, `OrderVoter`).
- Passwords hashed with Symfony `password_hashers` (Argon2/bcrypt via `auto`).
- Request validation via Symfony Validator on DTOs (`MapRequestPayload`).
- Secrets and keys belong in `.env` and `config/jwt/*.pem` — both are **gitignored** (only `config/jwt/.gitkeep` is committed).
- Copy `.env.example` to `.env` and generate fresh JWT keys before running locally.
- Docker Compose database passwords are **development defaults** — not for production.
- Demo fixture accounts are documented in the README for **local use only**.

Detailed auth design: [docs/AUTH.md](docs/AUTH.md).

## What this project does not include

- Rate limiting on login endpoints (not configured).
- Production-grade file upload scanning (basic local storage only).
- Inventory / stock locking on checkout.

These are intentional scope limits for a portfolio sample, not claims of production hardening.
