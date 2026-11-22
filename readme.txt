=== JESB InfinitePay Gateway ===
Contributors: erysouzace
Tags: woocommerce, pix, payment-gateway, checkout, infinitepay
Requires at least: 6.4
Requires PHP: 7.4
Requires Plugins: woocommerce
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hosted InfinitePay checkout for WooCommerce, with Pix/card payments, verified confirmations and an operational overview.

== Description ==

JESB InfinitePay Gateway is an **independent, unofficial** payment integration between WooCommerce and InfinitePay. It redirects buyers to an InfinitePay-hosted checkout for Pix and card payments. On webhook or supported return, the plugin verifies payment with InfinitePay's `payment_check` endpoint before marking a WooCommerce order paid.

* Classic Checkout and WooCommerce Checkout Blocks integration.
* High-Performance Order Storage (HPOS) compatibility declaration.
* Buyer contact details and order lines are sent to the processor for prefill and identification.
* Digital-only downloadable orders can be completed on payment; physical and mixed orders remain processing.
* Read-only WooCommerce > JESB InfinitePay financial overview based on the latest 100 store orders; not a bank statement.
* Optional sanitized diagnostic logs.

**Requirements and limitations:** Only BRL, HTTPS and an enabled InfinitePay Checkout account are supported. The payment provider may require customer confirmation. An API key is strongly recommended for links with expiration and single-use options. Without the webhook or return transaction identifiers, this integration cannot independently recover every payment. There is no publicly documented fee/balance reconciliation endpoint in this checkout API. The financial overview does not claim to show settlement, exact fees or available account balance.

**External service:** When a customer chooses InfinitePay, the plugin sends order line descriptions and amounts, order reference, buyer contact details (where provided), and shipping address (for physical orders) directly to InfinitePay's API at `https://api.checkout.infinitepay.io/links`. Payment verification requests are sent to `https://api.checkout.infinitepay.io/payment_check`. The provider hosts the checkout, receives payment details directly, and handles payment processing. See [InfinitePay documentation](https://www.infinitepay.io/checkout-documentacao) and its privacy terms. Site owners are responsible for informing customers of payment processor data transfers in their privacy policy. No unrelated telemetry or developer analytics are sent.

The project is distributed under GPLv2 or later. InfinitePay and WooCommerce are trademarks of their respective owners; this plugin is not officially endorsed by either provider.

== Installation ==

1. Install and activate WooCommerce.
2. Enable Integrated Checkout in your InfinitePay account.
3. Install and activate this plugin.
4. Go to WooCommerce > Settings > Payments > JESB InfinitePay Gateway.
5. Enter your InfiniteTag and enable the gateway. Ensure BRL and HTTPS are configured.
6. Recommended: define `JESB_INFINITEPAY_API_KEY` securely server-side in wp-config.php/environment.
7. Perform controlled real-payment verification of webhooks, returns and order statuses before production use.

== Frequently Asked Questions ==

= Can I accept other currencies? =
Not with this version. Checkout is restricted to BRL.

= Is the checkout inside my website? =
No. Buyers pay on InfinitePay's hosted checkout and return to the store.

= Does the financial dashboard show real bank settlements or processor fees? =
No. It displays order-derived values in a bounded recent sample, not a bank statement or verified settlement report.

= Is there a test environment? =
InfinitePay's integrated checkout documentation does not provide a traditional sandbox for all flows. Use caution with real-money tests.

= Will a missed webhook always be recovered? =
No. The return page can verify only when the required transaction identifiers are available. Otherwise the store must reconcile with the provider.

= Is this plugin officially maintained by InfinitePay? =
No. It is independently maintained by JESB DIGITAL.

== Changelog ==

= 1.1.0 =
* Added read-only operational finance overview inside WooCommerce.
* Added public-directory documentation and release structure.
* Preserved previously implemented hosted checkout, Blocks and HPOS integration.

= 1.0.2 =
* Improved order line identification and buyer contact prefill.

= 1.0.1 =
* Improved Blocks initialization and configuration diagnosis.

= 1.0.0 =
* Initial hosted gateway, webhook and payment_check integration.

== Upgrade Notice ==

= 1.1.0 =
Introduces an administrative transaction overview; no payment settings migration is required.
