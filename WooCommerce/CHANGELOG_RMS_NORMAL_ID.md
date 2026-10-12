# Change log for WooCommerce E2Pay Normal Plugin

## v3.1.0 - Oct 6, 2026

- Release version 3.1.0
- First entry in this change log. Earlier versions were not listed.

**Updates:**

- Validate the callback signature, order, amount and currency before updating an order. Invalid requests are rejected with HTTP 400.
- Rebrand to E2Pay.
- Add BRI CC channel.
- Use the sandbox URL for the test environment.

**Bug Fix:**

- Reply `CBTOKEN:MPSTATOK` to callbacks that arrive after the order is already paid.
- Accept `RM` as `MYR` in the callback amount/currency check.
- Reject notifications and callbacks when no WooCommerce order is found, instead of confirming them.
- Fix malformed order-number lookup query.
- Fix `account_type` default value.
