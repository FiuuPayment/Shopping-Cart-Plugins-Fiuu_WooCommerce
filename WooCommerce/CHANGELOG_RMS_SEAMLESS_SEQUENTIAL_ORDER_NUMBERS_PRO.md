# Change log for WooCommerce Fiuu Seamless Plugin for Sequential Order Numbers Pro

## v3.1.0 - Oct 6, 2026

- Release version 3.1.0
- Includes all changes since v3.0.1.

**Updates:**

- Validate the callback signature, order, amount and currency before updating an order. Invalid requests are rejected with HTTP 400.
- Compare the callback signature with `hash_equals()`.
- Rebrand Razer Merchant Services references to Fiuu and use Fiuu endpoints (pay.fiuu.com, api.fiuu.com).

**Bug Fix:**

- Do not change an order that is already paid when a late or repeated result arrives.
- Accept `RM` as `MYR` in the callback amount/currency check.
- Reject notifications and callbacks when no WooCommerce order is found, instead of confirming them.
- Fix `account_type` default value.

## v3.0.1 - Apr 21, 2020

- Release version 3.0.1

**Updates:**

- Minor improvement on payment UI.

## v3.0.0 - Mar 13, 2020

- Release version 3.0.0

**Updates:**

- Rebrand to Razer Merchant Services.
- Update FPX channel logos in accordance to FPX Compliance.
- Add support for e-wallet channels.
- Remove FPX-TPA and MOLWallet channels.

## v2.6.0 - Apr 2, 2019

- Release version 2.6.0

**Updates:**

- Update author & plugin link.
- Restructure response handling.
- When failed response handling, inquiry before update record.

## v2.5.2 - Oct 23, 2018

**Updates:**

- Plugin naming changes.

## v2.5.1 - Oct 22, 2018

**Bug Fix:**

- Order status updating in returnURL handling.
- Missing parameter when submitting order.

## v2.5.0 - Oct 19, 2018

- Release version 2.5.0

**Updates:**

- Addon Channel:
  - Cash-SAM
  - eNets
  - China Union Pay
  - Alipay
  - WeChatPay Cross Border
- Remove Channel:
  - credit3  
- Parameter naming:
  - change type to account_type
- Algorithm optimizing:
  - payment url definition.

## v2.4.3 - Jul 9, 2018

**Bug Fix:**

- NBCB parameter handling - check isset NBCB parameter.

## v2.4.2 - Jun 18, 2018

**Bug Fix:**

- NBCB parameter handling - check callbackURL or notificationURL.

## v2.4.1 - Jun 7, 2018

**Updates:**

- Remove error log feature.

## v2.4.0 - Jun 5, 2018

- Release MOLPay seamless plugin for sequential order numbers pro.
