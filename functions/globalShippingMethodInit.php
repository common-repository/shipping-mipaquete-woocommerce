<?php
//show method shipping mipaquete in checkout
function mp_shipping_method_init() {
    include("filters/shipping-normal.php");
}

add_action( 'woocommerce_shipping_init', 'mp_shipping_method_init' );

function mp_shipping_method( $methods ) {
    $id = "clicoh_shipping_normal";
    if(WP_PLATFORM_MIPAQUETE == 'MIPAQUETE'){
       $id = "mi_paquete_shipping_normal";
    }
    $methods[$id] = 'mp_shipping_method';
    return $methods;
}

add_filter( 'woocommerce_shipping_methods', 'mp_shipping_method' );