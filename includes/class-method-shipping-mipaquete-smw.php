<?php


class WC_Shipping_Method_Shipping_Mipaquete_SMW extends WC_Shipping_Method
{
    public $email;

    protected $_password;

    public function __construct($instance_id = 0)
    {
        parent::__construct($instance_id);

        $this->id = 'shipping_mipaquete_wc';
        $this->instance_id = absint( $instance_id );
        $this->method_title = __( 'Mipaquete' );
        $this->method_description = __( 'Facilitador de entregas de productos' );
        $this->title = __( 'Mipaquete' );

        $this->supports = array(
            'settings',
            'shipping-zones'
        );

        $this->init();

        $this->debug = $this->get_option( 'debug' );
        $this->isTest = (bool)$this->get_option( 'environment' );

        if ($this->isTest){
            $this->email = $this->get_option( 'sandbox_email' );
            $this->_password = $this->get_option( 'sandbox_password' );
            
        }else{
            $this->email = $this->get_option( 'email' );
            $this->_password = $this->get_option( 'password' );
        }

        $this->name_beneficiary = $this->get_option( 'name_beneficiary' );
        $this->number_beneficiary = $this->get_option( 'number_beneficiary' );
        $this->bank = $this->get_option( 'bank' );
        $this->type_account = $this->get_option( 'type_account' );
        $this->number_account = $this->get_option( 'number_account' );
        $this->nit = $this->get_option( 'nit' );
        $this->phone_sender = $this->get_option( 'phone_sender' );
        $this->city_sender = $this->get_option( 'city_sender' );
        $this->address_sender = $this->get_option( 'address_sender' );
        $this->value_select = $this->get_option( 'value_select' );
        $this->transportadora_select = $this->get_option( 'transportadora_select' );
        $this->name_store = $this->get_option( 'name_store' );
        $this->collection = $this->get_option( 'collection' );
        //$this->free_shipping = (bool)$this->get_option( 'free_shipping' );
        $free_shipping = 1;
        //API URL
        $url = 'https://users.mpr.mipaquete.com/users/createUserAC';

        //create a new cURL resource
        $ch = curl_init($url);

        //setup request to send json via POST
        $data = array(
            'name' => $this->name_store, //email
            'surname' => $this->name_store, //email
            'email' => $this->email, //email
            'phone' => $this->phone_sender, //phone
            'listId' => '114', //(campo opcional) Fecha de envio, si se envia vacio se envia inmediatamente (Ejemplo: 2017-12-31 23:59:59
        );
        $payload = json_encode($data);
        //echo $payload;

        //attach encoded JSON string to the POST fields
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $headers_token = array(
           'content-Type: application/json',
           'session-tracker: b0c6d2aa-4d53-11eb-ae93-0242ac130002',
           'customer-key: b0c6d2aa-4d53-11eb-ae93-0242ac130002'
        );
        //set the content type to application/json
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_token);

        //return response instead of outputting
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //execute the POST request
        $result = curl_exec($ch);
        //echo $result;
        $jsonData = json_decode($result,true);


        //close cURL resource
        curl_close($ch);
    }

    public function is_available($package)
    {
        return parent::is_available($package) &&
            !empty($this->email) &&
            !empty($this->_password) &&
            !empty($this->nit);
    }

     public function is_available1($package_cod)
    {
        return parent::is_available($package_cod) &&
            !empty($this->email) &&
            !empty($this->_password) &&
            !empty($this->nit);
    }

    public function init()
    {
        // Load the settings API.
        $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings.
        $this->init_settings(); // This is part of the settings API. Loads settings you previously init.
        // Save settings in admin if you have any defined.
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    public function init_form_fields()
    {
        $this->form_fields = include( dirname( __FILE__ ) . '/admin/settings.php' );
    }

    public function admin_options()
    {
        ?>
        <h3><?php echo $this->title; ?></h3>
            <p><?php echo $this->method_description; ?></p>
        <table class="form-table">
            <?php if (!empty($this->email) && !empty($this->_password)) ?>
                <?php Shipping_Shipping_Mipaquete_SMW::test_connection(); ?>
            <?php $this->generate_settings_html(); ?>
        </table>
        <?php
    }
    
    public function calculate_shipping($package = array())
    {
        global $woocommerce;
        $country = $package['destination']['country'];
        $state_destination = $package['destination']['state'];
        $city_destination  = $package['destination']['city'];
        $items = $woocommerce->cart->get_cart();

        if($country !== 'CO' || empty($state_destination))
            return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', false, $package, $this );

        $name_state_destination = Shipping_Shipping_Mipaquete_SMW::name_destination($country, $state_destination);

        if (empty($name_state_destination))
            return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', false, $package, $this );

        $address_destine = "$city_destination - $name_state_destination";

        if ($this->debug === 'yes')
            shipping_mipaquete_smw_smp()->log("origin: $this->city_sender address_destine: $address_destine");

        $cities = include dirname(__FILE__) . '/cities.php';

        $destine = array_search($address_destine, $cities);

        if(!$destine)
            $destine = array_search($address_destine, Shipping_Shipping_Mipaquete_SMW::clean_cities($cities));

        if ($this->debug === 'yes' && !$destine)
            shipping_mipaquete_smw_smp()->log("$address_destine  not found in cities Mipaquete");

        if(!$destine)
            return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', false, $package, $this );

        //Mensajería de 1 a 5kg cuyas dimensiones del producto sea maximo de 70 x 70 x 70 . Es decir que cabe en una bolsa de mensajeria
        //Paquetería. Mas de 5 kg indiferente de las dimensiones

        shipping_mipaquete_smw_smp()->log("$destine  seleccionado");

        $quantityItems = count($items);
        $initial_weight = 5;
        $count = 0;
        $quantity_packages = 0;
        $packing_weight_max = 150;
        $packing_length_max = 200;
        $packing_width_max = 200;
        $packing_height_max = 200;

        $selected_cod = WC()->session->get('chosen_payment_method'); 
        shipping_mipaquete_smw_smp()->log( "Payment gateway '" . $selected_cod . "'<br>");
        if ($selected_cod == 'cod') {
            $exist_code = 'code';
            shipping_mipaquete_smw_smp()->log("es este el cod " . $exist_code);
        }

        shipping_mipaquete_smw_smp()->log("fuera del if " . $exist_code);


        $calculate_dimensions_weight = Shipping_Shipping_Mipaquete_SMW::calculate_dimensions_weight($items);
        $length = $calculate_dimensions_weight['length'];
        $width = $calculate_dimensions_weight['width'];
        $height = $calculate_dimensions_weight['height'];
        $weight = $calculate_dimensions_weight['weight'];
        $total_valorization = $calculate_dimensions_weight['total_valorization'];
        $total_width = $width/100;
        $total_height = $height/100; 
        $total_lenght = $length/100;

        if ($length > $packing_length_max || $width >  $packing_width_max || $height > $packing_height_max || $weight > $packing_weight_max){
            shipping_mipaquete_smw_smp()->log("Dimensions $length $width $height $weight exceeded, maxims: 200 x 200 x 200 and 150kg");
            return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', false, $package, $this );
        }

        $weight = ceil($weight);

        if ($weight == " ") {
            shipping_mipaquete_smw_smp()->log("El peso no puede ser igual a 0");
            return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', false, $package, $this );
        }

        $type_shipping = 1;

        $calculate_weight = $total_width * $total_height * $total_lenght;
        $total_weight = $calculate_weight * 400;

        //if($weight <= 5 && $length <= 25 && $width <= 14 && $height <= 35)
        if($total_weight <= 5 && $weight <= 5 && $length <= 70 && $width <= 70 && $height <= 70)
            $type_shipping = 2;
        $shipping_attributes = array(
            'weight' => (int)$weight,
            'width' => (int)$width,
            'height' => (int)$height,
            'large' => (int)$length
        ); 
        
        if ($type_shipping === 2)
            $shipping_attributes = array(
                'weight' => (int)$weight,
            );

        
        if ($this->collection == 0)
            $collection = array(
                'payment_type' => 1
            );
        else
            $collection = array(
                'payment_type' => 5
            );
        /*
        $collection = array(
            'special_service'=> $this->collection,
            'value_collection' => WC()->cart->get_subtotal(),
            'payment_type' => 5
        );
        */
        if ($this->collection == 2)
            $collection = array(
                'special_service'=> $this->collection,
                'value_collection' => WC()->cart->get_subtotal(),
                'payment_type' => 5
            );
        if ($this->collection == 3 && $exist_code == 'code')
            $collection = array(
                'special_service'=> 2,
                'value_collection' => WC()->cart->get_subtotal(),
                'payment_type' => 5
            );
        if($this->collection == 3 && $exist_code != 'code')
            $collection = array(
                'special_service'=> 0,
                'payment_type' => 1
            );
        if ($transportadora_select == "0") {
                $others_params = array(
                'type' => (int)$type_shipping,
                'origin' => $this->city_sender,
                'destiny' => $destine,
                'declared_value' => $total_valorization,
                'quantity' => 1,
                'value_select' => (int)$this->value_select
            );
        }
        else {
                $others_params = array(
                'type' => (int)$type_shipping,
                'origin' => $this->city_sender,
                'destiny' => $destine,
                'declared_value' => $total_valorization,
                'quantity' => 1,
                'value_select' => (int)$this->value_select,
                'delivery' => $this->transportadora_select
            );
        }
        
        $params_calculate_shipping = array_merge($shipping_attributes, $collection, $others_params);
        shipping_mipaquete_smw_smp()->log($params_calculate_shipping);
            
        $response_calculate_shipping = Shipping_Shipping_Mipaquete_SMW::calculate_shipping_mipaquete($params_calculate_shipping);
        shipping_mipaquete_smw_smp()->log($response_calculate_shipping);

        if ( empty( $response_calculate_shipping ) )
            return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', false, $package, $this );

        
        if ($free_shipping == 1) 
            $rate = array(                
                'id'      => $this->id,
                'label'   => $this->title,
                'cost'    => 'Gratis',
                'package' => $package,
            );
        else
            $rate = array(
                'id'      => $this->id,
                'label'   => $this->title,
                'cost'    => $response_calculate_shipping->company->price,
                'package' => $package,
            );
        add_filter( 'woocommerce_cart_shipping_method_full_label', function($label) use($response_calculate_shipping) {
            $label .= "<br /><small>";
            /*$label .= "Envío normal a través de mipaquete.com. <br>Entregado por <b> ";
            $label .= $response_calculate_shipping->company->name;*/
            $label .= '</b></small>';
            return $label;
        }, 1);

        return $this->add_rate( $rate );

        

    }
    
}