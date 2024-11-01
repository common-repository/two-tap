<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

global $tt_api;
$tt_api = null;
$tt_tokens = get_tt_tokens();

if ( $tt_tokens ){
    $config = [
        'public_token' => $tt_tokens['twotap_public_token'],
        'private_token' => $tt_tokens['twotap_private_token'],
        'response_format' => 'array'
    ];
    $tt_api = new TwoTap\Api($config);
}

global $wc_api;
$wc_api = null;
$wc_tokens = get_wc_tokens();

if ( $wc_tokens ) {
    $wc_api = new Automattic\WooCommerce\Client(
        $wc_tokens['wc_url'],
        $wc_tokens['wc_consumer_key'],
        $wc_tokens['wc_consumer_secret'],
        [
            'wp_api' => true,
            'timeout' => 300,
            'version' => 'wc/v2',
            'enable_html_description' => true,
            'enable_html_short_description' => true,
        ]
    );
}

global $apis_enabled;
$apis_enabled['tt_api'] = !is_null($tt_api);
$apis_enabled['wc_api'] = !is_null($wc_api);

/**
 * Fetches the set Two Tap tokens
 *
 * @return [type] [description]
 */
function get_tt_tokens(){
    if( !TWOTAP_PUBLIC_TOKEN || !TWOTAP_PRIVATE_TOKEN ){
        return false;
    }
    return [
        'twotap_public_token' => TWOTAP_PUBLIC_TOKEN,
        'twotap_private_token' => TWOTAP_PRIVATE_TOKEN,
    ];
}

/**
 * Fetches the set WooCommerce tokens
 *
 * @return [type] [description]
 */
function get_wc_tokens(){
    if( !WC_URL || !WC_CONSUMER_KEY || !WC_CONSUMER_SECRET ){
        return false;
    }
    return [
        'wc_url' => WC_URL,
        'wc_consumer_key' => WC_CONSUMER_KEY,
        'wc_consumer_secret' => WC_CONSUMER_SECRET,
    ];
}

/**
 * Checks wether the Two Tap tokens are set
 *
 * @return [type] [description]
 */
function tt_tokens_set(){
    return get_tt_tokens() !== false;
}

/**
 * Checks wether the WooCommerce tokens are set
 *
 * @return [type] [description]
 */
function wc_tokens_set(){
    return get_wc_tokens() !== false;
}

/**
 * Checks wether the Two Tap API is enabled
 *
 * @return [type] [description]
 */
function tt_api_enabled(){
    global $apis_enabled;
    return $apis_enabled['tt_api'];
}

/**
 * Checks wether the WooCommerce is enabled
 * We fetch the result from cache because it's a costly operation
 *
 * @return [type] [description]
 */
function wc_api_enabled()
{
    if(!wc_tokens_set()){
        return false;
    }
    return get_option(TT_OPTION_WC_API_ENABLED);
}