<?php
use Mipaquete\Client;

class Shipping_Shipping_Mipaquete_SMW extends WC_Shipping_Method_Shipping_Mipaquete_SMW
{
    public function __construct($instance_id = 0)
    {
        parent::__construct($instance_id);

        $this->mipaquete = new Client($this->email, $this->_password);
        $this->mipaquete->sandboxMode($this->isTest);
    }

    public static function test_connection()
    {
        $instance = new self();

        try{
            $instance->mipaquete->getToken();
        }catch (\Exception $exception){
            shipping_mipaquete_smw_smp_notices($exception->getMessage());
        }
    }

    public static function calculate_shipping_mipaquete(array $params){

        $instance = new self();

        $data = array();

        try{
            $data = $instance->mipaquete->calculateSending($params);
        }catch (\Exception $exception){
            shipping_mipaquete_smw_smp()->log($exception->getMessage());
        }

        return $data;
    }
    
    public static function sendings_type($order_id, $old_status, $new_status, $order)
    {
        $instance = new self();

        if( !$order->has_shipping_method($instance->id))
            return;

        $sending_type_id = get_post_meta($order_id, 'sending_mipaquete_status', true);

        if (($order->has_shipping_method($instance->id) ||
            $order->get_shipping_total() == 0 &&
            $instance->free_shipping) &&
            empty($sending_type_id) && $new_status === 'mi-paquete'){
            $sending = $instance->sending($order);
            //$pdf = $instance->post_pdf($order);

            if ($sending == new stdClass())
                return;

            $sending_id = $sending->result->sending->_id;
            $sending_guide = $sending->result->sending->code;
            $sending_guide_number = $sending->result->sending->guide_number;
            $sending_company_name = $sending->result->sending->company_name;
            //$pdf_url_id = $pdf->result->pdf;
            $sending_status = array(
                'status' => true,
                'sending_id' => $sending_id,
                'code' => $sending_guide,
                'guide_number' => $sending_guide_number,
                'sending_transportadora' => $sending_company_name
                //'pdf' => $sending_guide
            );
            
            $concatenate_data = "<br>El código del envío es: " . $sending_guide . "<br>La transportadora seleccionada fue: " . $sending_company_name;


            /////// get token for get guide pdf ////////
            $url_token = "https://ecommerce.mipaquete.com/api/auth/";

            $curl_token = curl_init($url_token);
            curl_setopt($curl_token, CURLOPT_URL, $url_token);
            curl_setopt($curl_token, CURLOPT_POST, true);
            curl_setopt($curl_token, CURLOPT_RETURNTRANSFER, true);

            $headers_token = array(
               "Content-Type: application/json"
            );
            curl_setopt($curl_token, CURLOPT_HTTPHEADER, $headers_token);

            ///ignorar porfavor este machetazo, la calse get token no me genera el token aca no se porque
            $data_token = '{
                "email": "test@gmail.com",
                "password": "87654321"
            }';
            curl_setopt($curl_token, CURLOPT_POSTFIELDS, $data_token);

            $resp_token = curl_exec($curl_token);
            curl_close($curl_token);
            $jsonData_token = json_decode($resp_token,true);
            $jsonData_token['token'];
            //////////////////// end get token for get guide pdf ///////


            /////// get guide ////////
            $url = "https://ecommerce.mipaquete.com/api/sendings/generate-pdf/";

            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $headers = array(
               "Content-Type: application/json",
               "Authorization: " . $jsonData_token['token'],
            );
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            $data = '{"sending":{"code":';
            $data .= $sending_guide;
            $data .= '}}';
            if ($sending_company_name == "TCC" || $sending_company_name == "ENVIA" || $sending_company_name == "SERVIENTREGA") {
                sleep(25);
            }
            else{
                sleep(10);
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

            $resp = curl_exec($curl);
            curl_close($curl);
            $jsonData = json_decode($resp,true);
            $jsonData['result']['pdf'][0];
            //////////////////// end get guide ///////

            //shipping_mipaquete_smw_smp()->log("param code " . $resp);
            shipping_mipaquete_smw_smp()->log("token code " . $jsonData_token['token']);
            shipping_mipaquete_smw_smp()->log("url code 0 " . $jsonData['result']['pdf'][0]);
            shipping_mipaquete_smw_smp()->log("url code 1 " . $jsonData['result']['pdf'][1]);
            update_post_meta($order_id, 'sending_mipaquete_status', $sending_status  );
            $order->add_order_note(sprintf('Envío Mipaquete.com generado con éxito %s ', $concatenate_data));
            if (isset($jsonData['result']['pdf'][1])) {
                $order->add_order_note(sprintf('La relación de despacho de tu envío ha sido generado con éxito, imprímela y hazla firmar del domiciliario. <a href="%s">Descarga la relación de despacho</a> ', $jsonData['result']['pdf'][1]));
            }
            if (empty($jsonData['result']['pdf'][0])) {
                $order->add_order_note(sprintf('No pudimos generar tu guía de manera automática, ingresa a o <a href="https://app.mipaquete.com">app.mipaquete.com</a> Descarga la relación de despacho</a> ', $jsonData['result']['pdf'][1]));
            }
            else{
                $order->add_order_note(sprintf('La guía de tu envío procesado a través de mipaquete.com ha sido generada con éxito. Imprímela y pégala a tu paquete. <a href="%s">Descarga la guía</a> ', $jsonData['result']['pdf'][0]));
            }


        }  
    }
    public function sending(WC_Order $order)
    {
        $instance = new self();


        $packing_weight_max = 150;
        $packing_length_max = 200;
        $packing_width_max = 200;
        $packing_height_max = 200;

        $calculate_dimensions_weight = Shipping_Shipping_Mipaquete_SMW::calculate_dimensions_weight($order->get_items());

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
        $total_weight = ceil($total_weight);

        //if($weight <= 5 && $length <= 25 && $width <= 14 && $height <= 35)
        if($total_weight <= 5 && $weight <= 5 && $length <= 60 && $width <= 25 && $height <= 100)
            $type_shipping = 2;

        $state = $order->get_shipping_state() ? $order->get_shipping_state() : $order->get_billing_state();
        $city = $order->get_shipping_city() ? $order->get_shipping_city() : $order->get_billing_city();
        $receiver_name = $order->get_shipping_first_name() ? $order->get_shipping_first_name() .
            " " . $order->get_shipping_last_name() : $order->get_billing_first_name() .
            " " . $order->get_billing_last_name();
        $receiver_phone = $order->get_billing_phone();
        $receiver_email = $order->get_billing_email();
        $receiver_address = $order->get_shipping_address_1() ? $order->get_shipping_address_1() .
            " " . $order->get_shipping_address_2() : $order->get_billing_address_1() .
            " " . $order->get_billing_address_2();
        //$sender_address = get_option( 'woocommerce_store_address' ) .
            " " .  get_option( 'woocommerce_store_address_2' ) .
            " " . get_option( 'woocommerce_store_city' );
        $country = 'CO';

        $name_state_destination = Shipping_Shipping_Mipaquete_SMW::name_destination($country, $state);
        $address_destine = "$city - $name_state_destination";

        $cities = include dirname(__FILE__) . '/cities.php';
        $destine = array_search($address_destine, $cities);
        if (empty($this->name_store)) 
            $name_store = substr(get_bloginfo('name'),0,60);
        else
            $name_store = substr($this->name_store,0,60);
        

        if(!$destine)
            $destine = array_search($address_destine, Shipping_Shipping_Mipaquete_SMW::clean_cities($cities));

        $shipping_attributes = array(
            'weight' => (int)$weight,
            'width' => (int)$width,
            'height' => (int)$height,
            'large' => (int)$length
        );
        $value_collection == '';
        if ($type_shipping === 2)
            $shipping_attributes = array(
                'weight' => (int)$weight,
            );

        
        if ($this->collection == 0)
            $collection = array(
                'special_service'=> (int)$this->collection,
                'payment_type' => 1 // corregir una vez se haya habilitado paqueteria por 1
                
            );
        
        if ($this->collection == 2)
            $collection = array(
                'special_service'=> (int)$this->collection,
                'value_collection' => $this->collection == 2 ? (int)$order->get_subtotal() - (int)$order->get_total_discount() : 0,
                'payment_type' => 5
            );
        
        if ($this->collection == 3 && 'cod' == $order->get_payment_method())
            $collection = array(
                'special_service'=> 2,
                'value_collection' => (int)$order->get_subtotal() - (int)$order->get_total_discount(),
                'payment_type' => 5
            );
        if ($this->collection == 3 && 'cod' != $order->get_payment_method())
            $collection = array(
                'special_service'=> 0,
                'payment_type' => 1
            );
        $sender = array(
            'sender' => array(
                'name' => $name_store,
                'surname' => $name_store,
                'phone' => $this->phone_sender,
                'cell_phone' => $this->phone_sender,
                'email' => $this->email,
                'collection_address' => $this->address_sender,
                'nit' => $this->nit,
            )
        );

        $receiver = array(
            'receiver' => array(
                'name' => $receiver_name,
                'surname' => $receiver_name,
                'phone' => $receiver_phone,
                'cell_phone' => $receiver_phone,
                'email' => $receiver_email,
                'destination_address' => $receiver_address,
            )
        );
        $collection_information = array(
            'collection_information' => array(
                'bank' => $this->bank,
                'type_account' => $this->type_account,
                'number_account' => $this->number_account,
                'name_beneficiary' => $this->name_beneficiary,
                'number_beneficiary' => $this->number_beneficiary  
            )
        );
        /*
        $collection_information = array(
            'collection_information' => array(
                'bank' => get_post_meta($order->get_id(),'_billing_bank_name', true),
                'type_account' => get_post_meta($order->get_id(),'_billing_bank_account_type', true),
                'number_account' => (int)get_post_meta($order->get_id(),'_billing_bank_account_number', true),
                'name_beneficiary' => get_post_meta($order->get_id(),'_billing_bank_beneficiary_name', true),
                'number_beneficiary' => (int)get_post_meta($order->get_id(),'_billing_bank_account_number', true)
            )
        );
        */
        if ($transportadora_select == "0") {
            $others_params = array(
                'type' => (int)$type_shipping,
                'origin' => $this->city_sender,
                'destiny' => $destine,
                'declared_value' => (int)$total_valorization,
                'quantity' => 1,
                'alternative' => (int)$this->value_select,
                'comments' => $order->get_customer_order_notes() ? $order->get_customer_order_notes() : $order->get_customer_note(),
            );
        }
        else {
            $others_params = array(
                'type' => (int)$type_shipping,
                'origin' => $this->city_sender,
                'destiny' => $destine,
                'declared_value' => (int)$total_valorization,
                'quantity' => 1,
                'comments' => $order->get_customer_order_notes() ? $order->get_customer_order_notes() : $order->get_customer_note(),
                "delivery" =>  $this->transportadora_select,
                "alternative" => 1,
            );
        }

        $params = array_merge($shipping_attributes, $collection, $sender, $receiver, $others_params);
        
        if ($type_shipping == 2)
            $params = array_merge($params, $collection_information);
        $data = new stdClass;

        try{
            $data = $instance->mipaquete->sendingType($params);
        }catch (\Exception $exception){
            $sending_status = array(
                'status' => false,
                'message' => $exception->getMessage()
            );
            update_post_meta($order->get_id(), 'sending_mipaquete_status', $sending_status  );
            shipping_mipaquete_smw_smp()->log($exception->getMessage());
            shipping_mipaquete_smw_smp()->log( 'holaaa '. $order->get_payment_method());
            shipping_mipaquete_smw_smp()->log( 'array sending'. json_encode($params));
        }

        return $data;
    }
    /*public static function get_pdf($order_id, $old_status, $new_status, $order)
    {
        $instance = new self();

        if( !$order->has_shipping_method($instance->id))
            return;

        $get_pdf_id = get_post_meta($order_id, 'sending_mipaquete_status', true);

        if (($order->has_shipping_method($instance->id) ||
            $order->get_shipping_total() == 0 &&
            $instance->free_shipping) &&
            empty($sending_type_id) && $new_status === 'completed'){
            $pdf = $instance->post_pdf($order);

            if ($pdf == new stdClass())
                return;

            $pdf_url_id = $pdf->result->pdf;

            $sending_status = array(
                'status' => true,
                'pdf' => $code,

            );
        update_post_meta($order_id, 'sending_mipaquete_status', $sending_status  );
        $order->add_order_note(sprintf('guia Mipaquete.com %s generada con éxito', $pdf_url_id[0]));
        }
    }
    */
    public function post_pdf(WC_Order $order)
    {
        $instance = new self();


       shipping_mipaquete_smw_smp()->log("code generado en post pdf" . $sending_guide);
        $send_code_pdf = array(
            'sending' => array(
                'code' => $sending_guide,
            )
        );

        shipping_mipaquete_smw_smp()->log($send_code_pdf);
        $pdfarray = array_merge($send_code_pdf);
        
        $data = new stdClass;

        try{
            $data = $instance->mipaquete->getTopdf($pdfarray);
        }catch (\Exception $exception){
            $sending_status = array(
                'status' => false,
                'message' => $exception->getMessage()
            );
            update_post_meta($order->get_id(), 'sending_mipaquete_status', $sending_status  );
            shipping_mipaquete_smw_smp()->log($exception->getMessage());
            shipping_mipaquete_smw_smp()->log(json_encode($pdfarray));
        }

        return $data;
    }
    public static function calculate_dimensions_weight($items)
    {
        $height = 0;
        $length = 0;
        $weight = 0;
        $width = 0;
        $packing_weight_max = 150;
        $packing_length_max = 200;
        $packing_width_max = 200;
        $packing_height_max = 200;
        $total_valorization = 0;

        foreach ( $items as $item => $values ) {
            $_product_id = $values['product_id'] ?? $values->get_product_id();
            $_product = wc_get_product( $_product_id );


            if ( !$_product->get_weight() || !$_product->get_length()
                || !$_product->get_width() || !$_product->get_height() )
                break;
            if ( ceil($_product->get_weight()) > $packing_weight_max || $_product->get_length() > $packing_length_max
                || $_product->get_width() > $packing_width_max || $_product->get_height() > $packing_height_max ){
                shipping_mipaquete_smw_smp()->log($_product->get_name() . " dimensions or weight for exceeded, maxims: 200 x 200 x 200 and 150kg");
                break;
            }

            $custom_price_product = get_post_meta($_product_id, '_shipping_custom_price_product_smp', true);
            $total_valorization += $custom_price_product ? $custom_price_product : $_product->get_price();

            $quantity = $values['quantity'];

            $total_valorization = $total_valorization * $quantity;

            $height += $_product->get_height() * $quantity;
            $length = $_product->get_length() > $length ? $_product->get_length() : $length;
            $weight =+ $weight + ($_product->get_weight() * $quantity);
            $width =  $_product->get_width() > $width ? $_product->get_width() : $width;
        }

        return array(
            'height' => $height,
            'length' => $length,
            'weight' => $weight,
            'width' =>  $width,
            'total_valorization' => $total_valorization
        );
    }

    public static  function name_destination($country, $state_destination)
    {
        $countries_obj = new WC_Countries();
        $country_states_array = $countries_obj->get_states();

        $name_state_destination = '';

        if(!isset($country_states_array[$country][$state_destination]))
            return $name_state_destination;

        $name_state_destination = $country_states_array[$country][$state_destination];
        $name_state_destination = self::clean_string($name_state_destination);
        return self::short_name_location($name_state_destination);
    }

    public static function short_name_location($name_location)
    {
        if ( '' === $name_location )
            $name_location =  '';
        return $name_location;
    }

    public static function clean_string($string)
    {
        $not_permitted = array ("á","é","í","ó","ú","Á","É","Í",
            "Ó","Ú","ñ","À","Ã","Ì","Ò","Ù","Ã™","Ã ","Ã¨","Ã¬",
            "Ã²","Ã¹","ç","Ç","Ã¢","ê","Ã®","Ã´","Ã»","Ã‚","ÃŠ",
            "ÃŽ","Ã”","Ã›","ü","Ã¶","Ã–","Ã¯","Ã¤","«","Ò","Ã",
            "Ã„","Ã‹");
        $permitted = array ("a","e","i","o","u","A","E","I","O",
            "U","n","N","A","E","I","O","U","a","e","i","o","u",
            "c","C","a","e","i","o","u","A","E","I","O","U","u",
            "o","O","i","a","e","U","I","A","E");
        $text = str_replace($not_permitted, $permitted, $string);
        return $text;
    }

    public static function clean_cities($cities)
    {
        foreach ($cities as $key => $value){
            $cities[$key] = self::clean_string($value);
        }

        return $cities;
    }
}