<?php
//reurn info user for createSending
function mp_return_get_user() {
    [$apikeyConfig, $emailUser] = mp_return_generate_api_key();
    $platform = strtolower(WP_PLATFORM_MIPAQUETE);
    $url = mp_get_url_api_crupier() . 'monitor/v2/onboardings/get_user/' . $platform;

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'apikey:' . $apikeyConfig,
        'email:' . $emailUser,
    ));
    $resultGetUser = curl_exec($curl);
    curl_close($curl);
    $resultGetUserJson = json_decode($resultGetUser, true);
    $status = $resultGetUserJson['statusCode'];
    $response = $resultGetUserJson['response'];
    $name = "";
    $address = "";
    $locationCode = "";
    $email = "";
    $cellPhone = "";
    $documentNumber = "";
    $locationName = "";
    $businessName = "";

    if($status && $status == 200){
        $name = $response['first_name'] . ' ' . $response['last_name'];
        $address = $response['address'];
        $locationCode = $response['code'];
        $email = $response['email'];
        $cellPhone = $response['phone'];
        $documentNumber = $response['dni'];
        $locationName = $response['city'];
        $businessName = $response['business_name'];
    }
    $dataUser = array($name, $address, $locationCode, $email, $cellPhone, $documentNumber, $locationName, $businessName);
    return apply_filters( 'mp_shipping_data_user', $dataUser);
}

//reurn info user for createSending
function mp_validate_store() {
    $platform = strtolower(WP_PLATFORM_MIPAQUETE);
    $url = mp_get_url_api_crupier() . 'monitor/v2/onboardings/get_store/' . $platform;
    $urlSite = get_bloginfo('url');
    $urlSite = parse_url($urlSite);
    $shop_client_id = str_replace("www.","", $urlSite['host']);

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'store-client-id: ' . $shop_client_id,
        'integration-name: ' . 'WOOCOMMERCE',
    ));
    $resultGetStore = curl_exec($curl);
    curl_close($curl);
    $resultGetUserJson = json_decode($resultGetStore, true);
    $status = $resultGetUserJson['statusCode'];
    $response = false;
    if($status && $status == 200){
        $response = $resultGetUserJson['response'];
    }
    $dataStore = $response;
    return apply_filters( 'mp_shipping_data_store', $dataStore);
}