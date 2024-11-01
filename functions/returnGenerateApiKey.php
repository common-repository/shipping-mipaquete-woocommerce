<?php
//get ApiKey database
function mp_return_generate_api_key() {
    global $wpdb;
    $prefix=$wpdb->prefix;
    $nameTable = $prefix. WP_TABLE_MIPAQUETE;
    $query = "SELECT * FROM {$nameTable} WHERE apikey_config='" . get_option('mp_api_key') ."'
    AND development_environment =" . get_option('mp_enviroment');
    $resultDataGenerateApiKey = $wpdb->get_results($query);
    foreach ($resultDataGenerateApiKey as $value) {
        $readApiKey = $value->apikey_config;
        $readEmail = $value->email_user_registred;
    }
    return [$readApiKey, $readEmail];
}
