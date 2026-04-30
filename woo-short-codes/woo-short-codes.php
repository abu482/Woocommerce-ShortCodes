<?php
/*
 * Plugin Name: Woocommerce ShortCodes
 * Plugin URI:  https://97display.com/
 * Description: Handle woocommerce shortcodes.
 * Version:     1.10.3
 * Author:      97Display
 */

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
// MAIN FEE CALCULATION - Priority 20
// Handles: Collection fees, multi-test discount, travel fees.
// Skips collection fee for product 2013 when leadfree is applied.
// ------------------------------------------------------------------
add_action('woocommerce_cart_calculate_fees', 'wcsc_add_custom_fees', 20, 1);

function wcsc_add_custom_fees($cart) {

    if (is_admin() && !defined('DOING_AJAX')) return;
    if (!$cart || $cart->is_empty()) return;

    // Check once if leadfree coupon is active — used in both (A) and (B)
    $applied_coupons  = WC()->cart->get_applied_coupons();
    $leadfree_applied = in_array('leadfree', array_map('strtolower', $applied_coupons));

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

        // Skip Collection Fee for product 2013 when leadfree coupon is applied.
        // Also excluded from $fee_units so multi-test discount isn't affected.
        if ($leadfree_applied && $product_id === 2013) {
            continue;
        }

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
            $total_discount += min(50.0, $fee_units[$i]);
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
// LEADFREE PRODUCT PRICE DISCOUNT - Priority 30
// Runs after the main fee calculation above.
// Adds a negative fee equal to Lead (#2013) product price,
// making it completely free when leadfree coupon is applied.
// The WooCommerce coupon itself is set to $0 — this code handles
// the actual price deduction visibly in the cart totals.
// ------------------------------------------------------------------
// add_action('woocommerce_cart_calculate_fees', 'wcsc_leadfree_product_discount', 30);

// function wcsc_leadfree_product_discount($cart) {

//     if (is_admin() && !defined('DOING_AJAX')) return;
//     if (!$cart || $cart->is_empty()) return;

//     // Only run if leadfree coupon is active
//     $applied_coupons  = WC()->cart->get_applied_coupons();
//     $leadfree_applied = in_array('leadfree', array_map('strtolower', $applied_coupons));

//     if (!$leadfree_applied) return;

//     // Find product 2013 and deduct its full price as a visible negative fee
//     foreach ($cart->get_cart() as $cart_item) {
//         if ((int) $cart_item['product_id'] === 2013) {

//             $product_price = (float) $cart_item['data']->get_price();
//             $qty           = (int) $cart_item['quantity'];
//             $total_price   = $product_price * $qty;

//             if ($total_price > 0) {
//                 $cart->add_fee(
//                     esc_html__('Lead Test – Free (leadfree)', 'woocommerce'),
//                     -$total_price,
//                     false // not taxable
//                 );
//             }
//             break;
//         }
//     }
// }

// ------------------------------------------------------------------
// LEADFREE COUPON VALIDATION (at moment of applying coupon)
// Blocks the coupon if product 2013 is the ONLY item in cart.
// Cart must have at least one other product.
// ------------------------------------------------------------------
// add_filter('woocommerce_coupon_is_valid', 'wcsc_validate_leadfree_coupon', 10, 2);

// function wcsc_validate_leadfree_coupon($valid, $coupon) {

//     if (strtolower($coupon->get_code()) !== 'leadfree') return $valid;

//     // Safety check: cart must be available
//     if (!WC()->cart) return $valid;

//     $has_other_product = false;

//     foreach (WC()->cart->get_cart() as $cart_item) {
//         if ((int) $cart_item['product_id'] !== 2013) {
//             $has_other_product = true;
//             break;
//         }
//     }

//     if (!$has_other_product) {
//         throw new \Exception(
//             esc_html__('The "leadfree" coupon can only be applied when your cart contains at least one other product alongside the Lead test.', 'woocommerce')
//         );
//     }

//     return $valid;
// }

// ------------------------------------------------------------------
// LEADFREE SAFETY CHECK (on cart & checkout page load)
// Edge case: user applies coupon correctly, then removes other items.
// Blocks checkout with a clear error message.
// ------------------------------------------------------------------
// add_action('woocommerce_check_cart_items', 'wcsc_block_checkout_if_only_2013_with_leadfree');

// function wcsc_block_checkout_if_only_2013_with_leadfree() {

//     $applied_coupons  = WC()->cart->get_applied_coupons();
//     $leadfree_applied = in_array('leadfree', array_map('strtolower', $applied_coupons));

//     if (!$leadfree_applied) return;

//     $has_other_product = false;

//     foreach (WC()->cart->get_cart() as $cart_item) {
//         if ((int) $cart_item['product_id'] !== 2013) {
//             $has_other_product = true;
//             break;
//         }
//     }

//     if (!$has_other_product) {
//         wc_add_notice(
//             esc_html__('The "leadfree" coupon requires at least one other product in your cart. Please add another test or remove the coupon to proceed.', 'woocommerce'),
//             'error'
//         );
//     }
// }