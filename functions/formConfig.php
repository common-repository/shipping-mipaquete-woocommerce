<?php
require_once ( 'loginSubmit.php' );

// form config for conect api
if (!function_exists('mp_options_page')) {
    function mp_options_page(){
        $text_option = "ClicOh configuración";
        if (WP_PLATFORM_MIPAQUETE == 'MIPAQUETE') {
            $text_option = "Mipaquete configuración";
        }
        add_menu_page(
            $text_option,
            $text_option,
            'manage_options',
            'mp_options_shipping',
            'mp_options_shipping_page_display',
            plugin_dir_url( __FILE__ ) . '../assets/img/icon-mipaquete.png',
            15
        );
    }
    add_action('admin_menu', 'mp_options_page');
    add_action('admin_init', 'mp_admin_init');
}

if (!function_exists('mp_admin_init')) {
    function mp_admin_init(){
        register_setting( 'mp_options_group', 'mp_id' );
        register_setting( 'mp_options_group', 'mp_email' );
        register_setting( 'mp_options_group', 'mp_api_key' );
        register_setting( 'mp_options_group', 'mp_enviroment' );
        mp_create_table();
    }
}

if (!function_exists('mp_options_shipping_page_display')) {
    function mp_options_shipping_page_display()
    {
         if ( isset( $_GET['settings-updated'] ) ) {
              mp_save_configuration();
         }
         $info_user = mp_return_get_user();
         $valid_store = mp_validate_store();
         $valid_store = $valid_store == 'True';
?>
    <div class="wrap">
        <form action="options.php"  method="POST" >
            <?php settings_fields( 'mp_options_group');
                if (($info_user[0]) != '') {
                   add_action('admin_notices', mp_admin_user_notice_warn('Conexión correcta. Bienvenido '. $info_user[0], 'success'));
                }else{
                   add_action('admin_notices', mp_admin_user_notice_warn('No se encuentra un usuario con los datos ingresados', "danger"));
                }
                $pathStyle='../assets/css/style-clicoh.css';
                if(WP_PLATFORM_MIPAQUETE == 'MIPAQUETE'){
                    $pathStyle='../assets/css/style-mipaquete.css'; 
                }
                wp_enqueue_style( 'my-style-mpq', plugins_url($pathStyle, __FILE__), false, '1.0', 'all' );
            ?>
            
            <center>
                <?php
                $image = '<img  class="img" src="' . WP_BANNER_MIPAQUETE . '" style="width:100%;" alt="'. WP_PLATFORM_MIPAQUETE .'">';
                 echo($image);
                ?>
                <div id="contenedor">
                    <h3>Tus datos</h3>
                    <div id="contenidos">
                        <div id="columna1">
                            <span class="mpq" style="margin:auto 0px; position:absolute;" >Nombre: </span>
                        </div>
                        <div id="columna2" class="right mpq position">
                            <?php
                                if (($info_user[0]) != '') {
                                    echo $info_user[0];
                                } else {
                                    echo "No se encontraron datos, verifique el api key";
                                }
                            ?>
                            
                        </div>
                    </div>
                    <br>
                    <div id="contenidos">
                        <div id="columna1">
                            <span class="mpq" style="margin:auto 0px; position:absolute;">Dirección de recogida: </span>
                        </div>
                        <div id="columna2" class="righ mpq position"  >
                        <?php
                            if (!is_null($info_user[1])) {
                                echo $info_user[1];
                            } else {
                                echo "No se encontraron datos, verifique el api key";
                            }
                        ?>
                        </div>
                    </div>
                 </div>

                <div id="contenedor">
                    <div id="contenidos" <?php if (WP_PLATFORM_MIPAQUETE == 'MIPAQUETE') echo "style= 'display:none;'" ?>>
                        <div id="columna1">
                            <label name="mp_email" id="mp_email" class="mpq" >Email</label>
                        </div>
                        <div id="columna2">
                            <input type="email"
                            id="mp_email"
                            name="mp_email"
                            class="input"
                            placeholder="email"
                            value=<?php echo get_option('mp_email')?>>
                            <br>
                            Correo electronico
                        </div>
                    </div>
                    <div id="contenidos">
                        <div id="columna1">
                            <label name="mp_api_key" id="mp_api_key" class="mpq" >API Key</label>
                        </div>
                        <div id="columna2">
                            <input type="text"
                            id="mp_api_key"
                            name="mp_api_key"
                            class="input"
                            placeholder="Api key"
                            value=<?php echo get_option('mp_api_key')?>>
                            <br>
                            API Key
                        </div>
                        
                    </div>

                    <div id="contenidos">
                        <div id="columna1">
                            <label name="mp_enviroment"
                                id="mp_enviroment_label"
                                class="mpq" >¿Deseas habilitar el ambiente de pruebas?
                            </label>
                        </div>
                        <div id="columna2">
                            <select name="mp_enviroment" id="mp_enviroment" class="input" >
                            <?php
                                if (get_option('mp_enviroment') == 0) {
                                    $valueEnviroment = "NO";
                                } else {
                                    $valueEnviroment = "SI";
                                }
                            ?>
                                <option
                                    value ="<?php echo get_option('mp_enviroment') ?>" >Valor actual:
                                    <strong>
                                        <?php echo $valueEnviroment?>
                                    </strong>
                                </option>
                                <option value="0">NO</option>
                                <option value="1">SI</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="contenedor">
                    <div class="contenidos">
                <div id="columna1" <?php if (!$valid_store) echo "style= 'display:none;'" ?> >
                    <p class="submit">
                    <?php 
                    ;
                    $redirect = "window.open(document.getElementById('mp_enviroment').value == 1 ? '".WP_URL_MIPAQUETE."' : '".WP_URL_MIPAQUETE_PROD."' , '_blank')";
                        echo '<input type="button" value="Gestionar ordenes" class="button button-secondary" id="btnHome" 
                    onClick="'.$redirect.'" />';
                    ?>
                    
                    </p>
                    </div>
                    <div id="columna2">
                    <?php @submit_button($valid_store ? 'Reconectar' : 'Conectar'); ?>
                    </div>
                    </div>
                </div>
                <br>
                <h3>
                    ¿Necesitas ayuda para realizar tu integración? consulta el siguiente paso a paso 
                </h3>
                <h4>
                    <?php 
                        echo '<a href="' . WP_HELP_MIPAQUETE . '" target="_blank" rel="noopener">"' . WP_HELP_MIPAQUETE . '"</a>';
                    ?>
                </h4>
                    <?php 
                        echo '<h3> ¿Aún no tienes cuenta tu cuenta en ' . WP_TEXT_REGISTER_MIPAQUETE . '</h3>';
                    ?>
                <h3>
                </h3>
                <h4>
                    <?php 
                        echo '<a href="' . WP_REGISTER_MIPAQUETE . '" target="_blank" rel="noopener">"' . WP_REGISTER_MIPAQUETE . '"</a>';
                    ?>
                </h4>
                <br><br>
                <h3>
                    Si deseas realizar pruebas primero,
                    <?php 
                        echo WP_TEXT_TEST_MIPAQUETE;
                        echo '<a href="' . WP_TEST_MIPAQUETE . '" target="_blank" rel="noopener">"' . WP_TEST_MIPAQUETE . '"</a>';
                    ?>
                <br>
                    <?php
                        if (WP_PLATFORM_MIPAQUETE == 'MIPAQUETE') {
                            echo "Una vez lo hayas hecho escríbenos a soporte@mipaquete.com con el correo que te registraste y
                            procederemos a asignarte un saldo para que hagas pruebas.";
                        }
                    ?>
                </h3>
            </center>
        </form>
    </div>
    <?php
        
    }
}
