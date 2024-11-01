<?php
if ( ! class_exists( 'mp_shipping_method' ) ) {
    class mp_shipping_method extends WC_Shipping_Method {
        /**
         * Constructor for your shipping class
         *
         * @access public
         * @return void
         */
        public function __construct( $instanceId = 0 ) {
            $method_title = 'clicOh envío';
            $title = 'clicOh envío ';
            $id = "clicoh_shipping_normal";
            if(WP_PLATFORM_MIPAQUETE == 'MIPAQUETE'){
                $id = "mi_paquete_shipping_normal";
                $method_title = 'mipaquete envío';
                $title = 'mipaquete.com envío '; 
            }
            $this->id                 = $id;
            $this->method_title       = __( $method_title);
            $this->method_description = __( 'Envíos normales' );

            $this->enabled            = "yes";
            $this->title              = $title;

            $this->instance_id = absint( $instanceId );

            $this->supports  = array(
                'shipping-zones',
                'instance-settings',
                'instance-settings-modal',
                );

            $this->init();
        }
        /**
         * Init your settings
         *
         * @access public
         * @return void
         */
        public function init()
        {
            // Load the settings API
            // This is part of the settings API. Override the method to add your own settings
            //$this->initFormFields();
            $this->init_settings(); // This is part of the settings API. Loads settings you previously init.
            
            // Save settings in admin if you have any defined
            add_action('woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options'));
        }

        public function initFormFields()
        {
            $this->instance_form_fields = array(
                'price_personalized' => array(
                    'title' => __( 'Configurar tarifa de envío personalizada', 'mipaquete' ),
                    'type' => 'number',
                    'description' => __('Entendemos que para facilitar procesos de venta
                    algunas veces quieres configurar una única
                    tarifa de envío, si deseas tener una tarifa estándar
                    puedes hacerlo (Una vez generes el envío te cobraremos el valor real)',
                        'mipaquete'),
                    'desc_tip' => true,
                ),
                'free_shipping' => array(
                    'title' => __('¿Deseas que los envíos sean gratuitos para el cliente?'),
                    'type'        => 'select',
                    'class'       => 'wc-enhanced-select',
                    'description' => __('Habilitar el envío gratis para mis clientes'),
                    'desc_tip' => true,
                    'options'     => array(
                        '0' => __('Selecciona opción'),
                        '2' => __('SI'),
                        '3' => __('NO')
                    )
                ),
                'free_shipping_cost_total' => array(
                    'title' => __( 'Envío gratuito a partir de un valor de venta en especifico(Debes tener habilitada la opción si, en envío gratuito)', 'mipaquete' ),
                    'type' => 'number',
                    'description' => __( 'Debes tener habilitada la opción si en el envío gratuito, por defecto el valor será cero ', 'mipaquete' ),
                    'desc_tip' => true,
                    'default'  => 0
                ),
            );
        }
        public function is_available( $package ){
            return true;
        }
        /**
         * calculate_shipping function.
         *
         * @access public
         * @param mixed $packagect
         * @return void
         */
        public function calculate_shipping( $package = array() ) {  
            $urlSite = get_bloginfo('url');
            $urlSite = parse_url($urlSite);
            $shop_client_id = str_replace("www.","", $urlSite['host']);
            global $woocommerce, $post;
            $items = $woocommerce->cart->get_cart();
            $calculate = mp_calculate_imensions($items);
            $height = $calculate['height'];
            $width = $calculate['width'];
            $length = $calculate['length'];
            $weight = $calculate['weight'];
            $totalValorization = $calculate['total_valorization'];
            $cityDestination  = $package['destination']['city'];
            
            $quantityCart = $woocommerce->cart->cart_contents_count;
            foreach ($items as $item) {
                $_product =  wc_get_product( $item['data']->get_id());
                $productId = $_product->get_id();
            }
            
            //Loop through each item from the cart
            $customer = new WC_Customer(0, true);
            $location = $customer->get_shipping_state();
            $infoUserLocationCode = mp_return_get_user();
            $originCode = $infoUserLocationCode[2];
            
            $items = array("height" => $height, "width" => $width, "length" => $length, "weight" => $weight, "price" => $totalValorization);
            $rate = array("origin" => array("postal_code" => $originCode), "destination"=> array("postal_code" => $cityDestination), "items" => array($items));
            $data = array("rate" => $rate);
            $dataString = json_encode($data);
            [$apikeyConfig, $emailUser] = mp_return_generate_api_key();
            $url = mp_get_url_api_crupier('woocommerce') . 'rates';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $cod = 'cod' === WC()->session->get('chosen_payment_method') ? 1 : 0;
            $headers = array(
                'apikey:' . $apikeyConfig,
                'email: ' . $emailUser,
                'woocommerce-shop-domain: ' . $shop_client_id,
                'cod:'. $cod,
                'Content-Type:application/json'
            );
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            try {
                $result = curl_exec($ch);
                $resultData = json_decode($result, true);
                $rates = $resultData['rates'];
                $totalData = 0;
                if($rates) {
                    $totalData = (int)count($rates);
                }
                curl_close($ch);

                if ($totalData > 0 && $height <= 200
                    && $width <= 200
                    && $length <= 200
                    && $weight <= 150) {
                         
                    // Register the rate
                    $rate = array(
                        'id' => $this->id,
                        'label' => $this->title ,
                        'cost' => $rates[0]['total_price'],
                        'calc_tax' => 'per_item'
                    );
                    
                    $this->add_rate( $rate );
                }
            } catch (Exception $e) {
                echo '<script>console.log("Excepción capturada: ", "'.$e->getMessage().'"); </script>';
            }

        }
    }
}
?>