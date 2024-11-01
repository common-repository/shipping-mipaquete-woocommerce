<?php
//calculate dimensions for items in carts
function mp_calculate_imensions($items) { // $items are elements of products
    $height = 0;
    $length = 0;
    $weight = 0;
    $width = 0;
    $weight_ceil = 1;
    $total_valorization = 0;

    foreach ( $items as $item => $values ) {
        if ($values['variation_id'] != 0 && $values['product_id'] != 0) {
            $product_id = $values['variation_id'];
        } elseif ($values['variation_id'] == 0 && $values['product_id'] != 0) {
            $product_id = $values['product_id'];
        } elseif ($values['variation_id'] != 0 && $values['product_id'] == 0) {
            $product_id = $values['variation_id'];
        } else {
            $product_id = 0;
        }
        $_product = wc_get_product( $product_id );
        $quantity = $values['quantity'];
        $declared_value = 0;

        if ($_product->is_type( 'variable' )) {
            $variations = $_product->get_available_variations();
            $variations_total_data = (int)count($variations);
            for ($i=0; $i < $variations_total_data; $i++) {
                $height += ceil($variations[$i]['dimensions']['height'] * $quantity);
                $length = ceil($variations[$i]['dimensions']['length'] > $length ?
                $variations[$i]['dimensions']['length'] : $length);
                $weight += $variations[$i]['weight'] * $quantity;
                $width =  ceil($variations[$i]['dimensions']['width'] > $width ?
                $variations[$i]['dimensions']['width'] : $width);
                $declared_value += $variations[$i]['display_price'];
                $total_valorization = $declared_value > 10000 ? $declared_value : 10000;
                
            }

            if ($weight > 1) {
                $weight_ceil = ceil($weight);
                
            }
        }
        
        if (!$_product->get_weight() || !$_product->get_length()
            || !$_product->get_width() || !$_product->get_height()
            || $height > 200 || $width > 200 || $length > 200 || $weight_ceil > 150 ) {
                break;
            }
            
        
        $custom_price_product = get_post_meta($product_id, '_custom_declared_value', true);
        $total_valorization += $custom_price_product ? $custom_price_product : $_product->get_price();

        $total_valorization = $total_valorization * $quantity;

        if ($total_valorization < 10000) {
            $total_valorization = 10000;
        }
        $height += ceil($_product->get_height() * $quantity);
        $length = ceil($_product->get_length() > $length ? $_product->get_length() : $length);
        $weight += $_product->get_weight() * $quantity ;
        $width =  ceil($_product->get_width() > $width ? $_product->get_width() : $width);
        

        if ($weight > 1) {
            $weight_ceil = ceil($weight);
        }

    }
    return array(
        'height' => $height,
        'length' => $length,
        'weight' => $weight_ceil,
        'width' =>  $width,
        'total_valorization' => $total_valorization
    );
}
