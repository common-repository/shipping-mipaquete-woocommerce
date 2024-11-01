<?php

function mp_get_countries_option(){
    global $woocommerce;
    $countries_obj   = new WC_Countries();
    $countries   = $countries_obj->__get('countries');
    $countriesFinal = array();
    foreach ( $countries as $code => $country ) {
        if($code == 'CO'){
        $countriesFinal[$code] = $country;
        }
    }
    return $countriesFinal;
}

function mp_change_city_customer($fields)
{
    $countryArgs = wp_parse_args( array(
        'label' => 'Escoge el país',
        'type' => 'select',
        'required'  => true,
        'clear'     => false,
        'options' => mp_get_countries_option(),
        'input_class' => array(
            'wc-enhanced-select',
        )
        ) );
    $cityArgs = wp_parse_args( array(
        'label' => 'Escoge la ciudad',
        'type' => 'select',
        'required'  => true,
        'clear'     => false,
        'options' => mp_get_cities_option(),
        'input_class' => array(
            'wc-enhanced-select',
        )
        ) );
        
    wc_enqueue_js( "
        
    jQuery( ':input.wc-enhanced-select' ).filter( ':not(.enhanced)' ).each( function() {
        var select2_args = { minimumResultsForSearch: 5 };
        jQuery( this ).select2( select2_args ).addClass( 'enhanced' );
    });" );
    
    $fields['shipping']['shipping_country'] = $countryArgs;
    $fields['billing']['billing_country'] = $countryArgs; // Also change for billing field 
    
    $fields['shipping']['shipping_city'] = $cityArgs;
    $fields['billing']['billing_city'] = $cityArgs; // Also change for billing field
   //unset($fields['billing']['billing_state']);
   //unset($fields['shipping']['shipping_state']);
    
    return $fields;
}

add_filter('woocommerce_checkout_fields', 'mp_change_city_customer');

// Admin editable single orders billing and shipping city field
add_filter('woocommerce_admin_billing_fields', 'mp_admin_order_pages_city_fields');
add_filter('woocommerce_admin_shipping_fields', 'mp_admin_order_pages_city_fields');

function mp_admin_order_pages_city_fields( $fields ) {
    $fields = mp_admin_order_fields($fields);
    $fields['country']['type']    = 'select';
    $fields['country']['options'] = mp_get_countries_option();
    $fields['country']['class']   = 'short'; // Or 'js_field-country select short' to enable selectWoo (select2).
    
    $fields['city']['type']    = 'select';
    $fields['city']['options'] = mp_get_cities_option();
    $fields['city']['class']   = 'short'; // Or 'js_field-country select short' to enable selectWoo (select2).
    
    unset($fields['state']);
    return $fields;
}

function mp_admin_order_fields($fields){
     $i = 0;

    $order = array(
        "first_name",
        "last_name",
        "company",
        "address_1",
        "address_2",
        "country",
        "city",
        "postcode",
        "phone",
        "email",
        "state"
    );

    foreach ( $order as $field ) {
        if( $fields[ $field ]){
            $ordered_fields[ $field ] = $fields[ $field ];
            $ordered_fields[ $field ][ "priority" ] = ++$i;
        }
    }
    $fields = $ordered_fields;
    return $fields;
}

// Admin editable User billing and shipping city
add_filter( 'woocommerce_customer_meta_fields', 'mp_custom_override_user_city_fields' );
function mp_custom_override_user_city_fields( $fields ) {
    $fields['billing']['fields']['billing_country']['type']    =
    $fields['shipping']['fields']['shipping_country']['type']  = 'select';
    $fields['billing']['fields']['billing_country']['options'] =
    $fields['shipping']['fields']['shipping_country']['options'] = mp_get_countries_option();

   $fields['billing']['fields']['billing_city']['type']    =
    $fields['shipping']['fields']['shipping_city']['type']  = 'select';
    $fields['billing']['fields']['billing_city']['options'] =
    $fields['shipping']['fields']['shipping_city']['options'] = mp_get_cities_option();
    return $fields;
}

add_action( 'wp_footer','custom_checkout_jqscript');
function custom_checkout_jqscript(){
    if(is_checkout() && ! is_wc_endpoint_url()):
    ?>
    <script type="text/javascript">
    jQuery( function($){
        $('form.checkout').on('change', 'input[name="payment_method"]', function(){
            $(document.body).trigger('update_checkout');
        });
    });
    </script>
    <?php
    endif;
}
