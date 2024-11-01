<?php
// create and insert info apikey in mysql


function mp_create_table(){
    global $wpdb;
    $prefix = $wpdb->prefix;
    $name_table = $prefix . WP_TABLE_MIPAQUETE;
    $sql = "CREATE TABLE IF NOT EXISTS $name_table(
            id bigint(20) NOT NULL AUTO_INCREMENT,
            date_creation datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            apikey_config text,
            email_user_registred varchar(150),
            name_store varchar(150),
            url_store text,
            development_environment int(1),
            PRIMARY KEY  (id)
        );";
        $wpdb->query($sql);
}