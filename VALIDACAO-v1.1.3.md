# Validation status — 1.1.3 candidate

## Completed locally
- PHP syntax checks against PHP 8.4.
- tests/check-metadata.php
- tests/plugin-check-regressions.php
- tests/payment-flow.php (mocked responses only)

## Must be completed before WordPress.org submission
- Test named MySQL/MariaDB payment locks in the real HostGator staging environment.
- Test full Classic Checkout and Checkout Blocks payment flow after replacing locking.
- Confirm an identical repeat callback does not repeat order transition or issue a new payment link.
- Run official Plugin Check against installed 1.1.3 package.
- Run CI matrix on GitHub with PHP 8.2, 8.3 and 8.4.
- Check callback delivery and scheduled reconciliation without returning from provider.
- Reconcile any real provider transaction with the WooCommerce order.

Do not treat mocks as a substitute for live gateway or database concurrency testing.
