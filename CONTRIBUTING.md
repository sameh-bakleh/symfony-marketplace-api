# Contributing

Thank you for your interest in this project. This repository is primarily a **portfolio sample**, but issues and focused pull requests are welcome.

## Before you start

1. Read [README.md](README.md) for scope and setup.
2. Check open issues to avoid duplicate work.
3. For security concerns, follow [SECURITY.md](SECURITY.md) — do not file public issues with exploit details.

## Local development

```bash
cp .env.example .env
composer install
./vendor/bin/phpunit -c phpunit.dist.xml
```

Docker setup is documented in the README.

## Pull request guidelines

- Keep changes **focused** — one concern per PR.
- Match existing code style (`declare(strict_types=1);`, readonly services, DTO validation patterns).
- Add or update **functional tests** for behavior changes under `tests/Functional/`.
- Do not commit `.env`, JWT `.pem` files, `vendor/`, or `var/`.
- Update README or `docs/` when you change public API behavior or setup steps.

## Running tests

```bash
composer install
rm -rf var/cache/test
./vendor/bin/phpunit -c phpunit.dist.xml
```

CI runs the same suite on PHP 8.3 via GitHub Actions.

## Commit messages

Use clear, imperative subjects:

- `fix: reject checkout when cart is empty`
- `test: cover wishlist removal`
- `docs: clarify logout requires JWT`

## License

By contributing, you agree that your contributions will be licensed under the [MIT License](LICENSE).
