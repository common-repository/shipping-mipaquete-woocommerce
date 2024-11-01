<?php

function mp_get_url_api()
{
    return get_option('mp_enviroment') == 0 ?
    'https://api-v2.mpr.mipaquete.com/' :
    'https://api-v2.dev.mpr.mipaquete.com/';
}

function mp_get_url_api_crupier($platform = 'crupier')
{
    return get_option('mp_enviroment') == 0 ?
    'https://ecommerce.clicoh.com/ecommerce/'.$platform.'/' :
    'https://ecommerce.dev.clicoh.com/ecommerce/'.$platform.'/';
}

