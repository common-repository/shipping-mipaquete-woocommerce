<?php
// Remove "shipping" section from cart page only
add_filter('woocommerce_cart_needs_shipping', 'mp_filter_cart_needs_shipping');
function mp_filter_cart_needs_shipping($needsShipping){
    return is_cart() ? false : $needsShipping;
}
function mp_cart_custom_shipping_message_row(){
    if (!WC()->cart->needs_shipping()) :
        $mp_shipping_message = __("Calcula tu envío al finalizar la compra.", "woocommerce");
        ?>
        <tr class="shipping">
            <th id="th-shipping">
                <?php _e('Shipping', 'woocommerce'); ?>
            </th>
            <td class="message" data-title="<?php esc_attr_e('Shipping', 'woocommerce'); ?>">
                <em>
                    <?php echo $mp_shipping_message; ?>
                </em>
            </td>
        </tr>
    <?php endif;
}
function mp_display_order_meta_location($order) //$order all info order woocommerce
{
    // get data location
    $mp_location_code = mp_return_get_locations($order->get_billing_city());
    $mp_city = $mp_location_code[0];
    $mp_state = $mp_location_code[1];
    // compatibility with WC +3
    //$orderId = method_exists($order, 'get_id') ? $order->get_id() : $order->id;
    echo '<p class="form-field"><strong>'.__('Ciudad', 'woocommerce').':</strong> ' . $mp_city . '</p>';
    echo '<p class="form-field"><strong>'.__('Departamento', 'woocommerce').':</strong> ' . $mp_state . '</p>';
}

add_action('woocommerce_admin_order_data_after_billing_address', 'mp_display_order_meta_location', 10, 1);
// Add a custom shipping message row
add_action('woocommerce_cart_totals_before_order_total', 'mp_cart_custom_shipping_message_row');
    
?>