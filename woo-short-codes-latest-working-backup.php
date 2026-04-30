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
add_action('woocommerce_cart_calculate_fees', 'wcsc_add_custom_fees', 20, 1);

function wcsc_add_custom_fees($cart) {

    if (is_admin() && !defined('DOING_AJAX')) return;
    if (!$cart || $cart->is_empty()) return;

    /**
     * (A) COLLECTION FEES + DISCOUNT
     */
    $fee_units = [];

    foreach ($cart->get_cart() as $cart_item) {

        $product_id = (int) $cart_item['product_id'];
        $qty = max(1, (int) $cart_item['quantity']);

        $raw_fee = get_post_meta($product_id, 'service_fee', true);
        if ($raw_fee === '' || $raw_fee === null) continue;

        $fee = (float) preg_replace('/[^0-9\.\-]/', '', (string) $raw_fee);
        if ($fee <= 0) continue;

        $line_fee = $fee * $qty;
        $label = sprintf('Collection Fee – %s', get_the_title($product_id));

        $cart->add_fee($label, $line_fee, false);

        for ($i = 0; $i < $qty; $i++) {
            $fee_units[] = $fee;
        }
    }

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


?>

 