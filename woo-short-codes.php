<?php
/*
 * Plugin Name: Woocommerce ShortCodes
 * Plugin URI:  https://97display.com/
 * Description: Handle woocommerce shortcodes.
 * Version:     1.10.3
 * Author:      97Display
 */

// ------------------------------------------------------------------
// CHECKOUT TRAVEL FEE SCRIPT
// Runs on checkout page only — handles travel fee dropdown updates
// ------------------------------------------------------------------
add_action('wp_footer', 'wcsc_travel_fee_script');

function wcsc_travel_fee_script() {
    if (!is_checkout()) return;
    ?>
    <script>
    jQuery(function($){
        $('form.checkout').on('change','select[name="additional_travel_fee"]',function(){
            $('body').trigger('update_checkout');
        });
    });
    </script>
    <?php
}

// ------------------------------------------------------------------
// CART PAGE CHECKOUT REDIRECT SCRIPT
// Runs on cart page only — fixes WooCommerce block cart button
// pointing to cart URL instead of checkout URL
// ------------------------------------------------------------------
add_action('wp_footer', 'wcsc_checkout_redirect_script');

function wcsc_checkout_redirect_script() {
    if (!is_cart()) return; // Only runs on cart page
    ?>
    <script>
    jQuery(function($) {

        var checkoutUrl = '<?php echo esc_url(wc_get_checkout_url()); ?>';

        // Fix the button href immediately on page load
        function fixCheckoutButton() {
            var $btn = $('.wc-block-cart__submit-button');
            if ($btn.length && $btn.attr('href') !== checkoutUrl) {
                $btn.attr('href', checkoutUrl);
            }
        }

        // Run on page load
        fixCheckoutButton();

        // Run again after every cart block re-render
        // (WooCommerce blocks re-render button after cart updates)
        var observer = new MutationObserver(function() {
            fixCheckoutButton();
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        // Intercept click as final safety net
        $(document).on('click', '.wc-block-cart__submit-button', function(e) {

            var hasErrors = $(
                '.woocommerce-error, ' +
                '.wc-block-components-notice-banner--error, ' +
                '.wc-block-components-validation-error'
            ).length > 0;

            if (!hasErrors) {
                e.preventDefault();
                e.stopImmediatePropagation();
                window.location.href = checkoutUrl;
            }
        });

    });
    </script>
    <?php
}

// ------------------------------------------------------------------
// MAIN FEE CALCULATION - Priority 20
// Handles: Collection fees, multi-test discount, travel fees.
// ------------------------------------------------------------------
add_action('woocommerce_cart_calculate_fees', 'wcsc_add_custom_fees', 20, 1);

function wcsc_add_custom_fees($cart) {

    if (is_admin() && !defined('DOING_AJAX')) return;
    if (!$cart || $cart->is_empty()) return;

    /**
     * (A) COLLECTION FEES + MULTI-TEST DISCOUNT
     */
    $fee_units = [];

    foreach ($cart->get_cart() as $cart_item) {

        $product_id = (int) $cart_item['product_id'];
        $qty        = max(1, (int) $cart_item['quantity']);

        $raw_fee = get_post_meta($product_id, 'service_fee', true);
        if ($raw_fee === '' || $raw_fee === null) continue;

        $fee = (float) preg_replace('/[^0-9\.\-]/', '', (string) $raw_fee);
        if ($fee <= 0) continue;

        $line_fee = $fee * $qty;
        $label    = sprintf('Collection Fee – %s', get_the_title($product_id));
        $cart->add_fee($label, $line_fee, false);

        for ($i = 0; $i < $qty; $i++) {
            $fee_units[] = $fee;
        }
    }

    // Multi-test discount: each unit after the first gets up to $50 off
    $num_units = count($fee_units);

    if ($num_units > 1) {
        rsort($fee_units, SORT_NUMERIC);

        $total_discount = 0.0;
        for ($i = 1; $i < $num_units; $i++) {
            $total_discount += min(75.0, $fee_units[$i]);
        }

        if ($total_discount > 0) {
            $cart->add_fee('Multi-Test Collection Discount', -$total_discount, false);
        }
    }

    /**
     * (B) ADDITIONAL TRAVEL FEES
     */
    $selected = WC()->session->get('additional_travel_fee');

    if (isset($_POST['post_data'])) {
        parse_str($_POST['post_data'], $post_data);
        if (isset($post_data['additional_travel_fee'])) {
            $selected = wc_clean($post_data['additional_travel_fee']);
            WC()->session->set('additional_travel_fee', $selected);
        }
    }

    $amount = is_numeric($selected) ? (float) $selected : 0.0;

    if ($amount > 0) {
        $cart->add_fee('Additional Travel Fees', $amount, false);
    }
}

// ------------------------------------------------------------------
// COUPON MINIMUM SPEND — INCLUDE SERVICE FEES
// WooCommerce checks minimum spend against product subtotal only,
// ignoring fees. This includes service fees in that check so coupons
// with a minimum spend aren't incorrectly blocked.
// ------------------------------------------------------------------
add_filter('woocommerce_coupon_validate_minimum_amount', 'wcsc_validate_minimum_with_fees', 10, 2);

function wcsc_validate_minimum_with_fees($is_invalid, $coupon) {
    if (!$is_invalid) return $is_invalid;

    $minimum = (float) $coupon->get_minimum_amount();
    if ($minimum <= 0) return $is_invalid;

    $cart = WC()->cart;
    if (!$cart) return $is_invalid;

    $cart_total = (float) $cart->get_subtotal();

    foreach ($cart->get_cart() as $cart_item) {
        $product_id = (int) $cart_item['product_id'];
        $qty        = max(1, (int) $cart_item['quantity']);
        $raw_fee    = get_post_meta($product_id, 'service_fee', true);
        if ($raw_fee === '' || $raw_fee === false) continue;
        $fee = (float) preg_replace('/[^0-9\.\-]/', '', (string) $raw_fee);
        if ($fee <= 0) continue;
        $cart_total += $fee * $qty;
    }

    return $cart_total < $minimum;
}

// ------------------------------------------------------------------
// COUPON EXTENSION TO SERVICE FEES - Priority 999
// WooCommerce coupons only apply to product subtotals, not fees.
// If a fixed-cart coupon exceeds the product subtotal, the leftover
// is applied here as a negative fee against collection fees.
// ------------------------------------------------------------------
add_action('woocommerce_cart_calculate_fees', 'wcsc_extend_coupons_to_service_fees', 999, 1);

function wcsc_extend_coupons_to_service_fees($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (!$cart || $cart->is_empty()) return;

    $applied_coupons = $cart->get_applied_coupons();
    if (empty($applied_coupons)) return;

    // Sum all service fees in the cart
    $total_service_fee = 0.0;
    foreach ($cart->get_cart() as $cart_item) {
        $product_id = (int) $cart_item['product_id'];
        $qty        = max(1, (int) $cart_item['quantity']);
        $raw_fee    = get_post_meta($product_id, 'service_fee', true);
        if ($raw_fee === '' || $raw_fee === false) continue;
        $fee = (float) preg_replace('/[^0-9\.\-]/', '', (string) $raw_fee);
        if ($fee <= 0) continue;
        $total_service_fee += $fee * $qty;
    }

    if ($total_service_fee <= 0) return;

    $cart_subtotal = (float) $cart->get_subtotal();

    foreach ($applied_coupons as $code) {
        $coupon = new WC_Coupon($code);
        if ($coupon->get_discount_type() !== 'fixed_cart') continue;

        $coupon_amount       = (float) $coupon->get_amount();
        $applied_to_products = min($coupon_amount, $cart_subtotal);
        $remaining           = $coupon_amount - $applied_to_products;

        if ($remaining <= 0) continue;

        $fee_discount       = min($remaining, $total_service_fee);
        $total_service_fee -= $fee_discount; // prevent over-discounting with stacked coupons

        $cart->add_fee(
            sprintf('Collection Fee Discount – %s', strtoupper($code)),
            -$fee_discount,
            false
        );
    }
}