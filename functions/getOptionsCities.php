<?php
function mp_get_cities_option(){
    [$apiKey, $email] = mp_return_generate_api_key();
    $url = mp_get_url_api() . 'getLocations';
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $headers = array(
        "session-tracker: a0c96ea6-b22d-4fb7-a278-850678d5429c",
        "apikey:" . $apiKey,
    );
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    $resultGetLocations = curl_exec($curl);
    curl_close($curl);
    $resultGetLocationsJson = json_decode($resultGetLocations, true);
    $options = array();
    if($resultGetLocationsJson && $resultGetLocationsJson['status'] != 400){
    foreach ( $resultGetLocationsJson as $result ) {
        $options[0] = "SELECCIONE LA CIUDAD";
        $options[$result['locationName'] . '|' . $result['locationCode']] = $result['locationName'] . "/" . $result['departmentOrStateName'];
    }
    }
    return $options;
}
