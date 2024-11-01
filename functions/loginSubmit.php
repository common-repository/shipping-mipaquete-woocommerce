<?php

function mp_save_configuration()
{
    global $wpdb;
    $prefix = $wpdb->prefix;
    $nameTable = $prefix . WP_TABLE_MIPAQUETE;
    $query = "SELECT * FROM {$nameTable} WHERE apikey_config='" . get_option('mp_api_key') . "'
    AND development_environment =" . get_option('mp_enviroment');
    $resultRead = $wpdb->get_results($query);
    if ($resultRead == null) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $urlSite = get_bloginfo('url');
        $nameSite = get_bloginfo('name');
        $dataInsertApiKey = array(
            "apikey_config" => get_option('mp_api_key'),
            "development_environment" => get_option('mp_enviroment'),
            "email_user_registred" => get_option('mp_email'),
            "url_store" => $urlSite,
            "name_store" => $nameSite,
        );
        if (count($resultRead) <= 0) {
            $resultInsert = $wpdb->insert($nameTable, $dataInsertApiKey);
            if (get_option('mp_api_key') != "") {
                mp_onbording_crupier($dataInsertApiKey);
            }
        }
    }else{
        $len = count($resultRead) > 0;
        if ($len) {
            mp_onbording_crupier($resultRead[0]);
        }
    }
}

function mp_onbording_crupier($infoData)
{
    $url = mp_get_url_api_crupier() . 'monitor/v2/onboardings/init_onboarding';
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $platform = strtolower(WP_PLATFORM_MIPAQUETE);
    $store_url = $infoData->url_store;
    $store_name = $infoData->name_store;
    $apikey = $infoData->apikey_config;
    $email = $infoData->email_user_registred;
    $data = <<<DATA
    {
        "platform_name": "$platform",
        "integration_name": "WOOCOMMERCE",
        "store_url": "{$store_url}",
        "store_name": "{$store_name}",
        "email": "{$email}",
        "apikey": "{$apikey}",
        "app_id": null,
        "app_secret": null,
        "distribution_link": null
    }
DATA;

    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    $resultInitOnboarding = curl_exec($curl);
    curl_close($curl);

    $resultGetOnboardingJson = json_decode($resultInitOnboarding, true);
    if($resultGetOnboardingJson['statusCode'] == 200){
        $urlRedirect = $resultGetOnboardingJson['response']['installation_link'];
        echo "<script>window.open('".$urlRedirect."', '_blank');</script> ";
    }else{
        echo '<script> alert("Ha ocurrido un error generando el link de acceso") </script>';
    }
}


function mp_admin_user_notice_warn($text, $type){
    echo '<div class="notice notice-'.$type.' is-dismissible">
    <p>'. $text . '</p>
    </div>';
}
