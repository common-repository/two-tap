<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
$billing_fields = ["billing_first_name", "billing_last_name", "billing_address", "billing_city", "billing_country", "billing_state", "billing_zip", "billing_telephone"];
$shipping_fields = ["shipping_first_name", "shipping_last_name", "shipping_address", "shipping_city", "shipping_country", "shipping_state", "shipping_zip", "shipping_telephone"];
?>

<div id="order_data" class="panel">
    <h1><?=$post->post_title?></h1>
    <div class="section group">
        <div class="col span_1_of_3">
            <h3>Purchase info</h3>
            <strong>E-mail:</strong> <?=$fields_input[$first_site_id]['noauthCheckout']['email']?><br>
            <strong>Shipping option:</strong> <?=$fields_input[$first_site_id]['shipping_option']?><br>
            <strong>Destination country:</strong> <?=$cart_status['destination_country']?><br>
            <strong>Confirm method:</strong> <?=$request_params['confirm']['method']?><br>
            <strong>Two Tap Cart ID:</strong> <?=$request_params['twotap_cart_id']?>
            <?php if($order_id): ?>
            <strong>WooCommerce Order ID: </strong><a href="<?=get_edit_post_link($order_id)?>" target="_blank"><?=$order_id?></a>
            <br>
            <?php endif; ?>
        </div>
        <div class="col span_1_of_3">
            <h3>Billing Info</h3>
            <?php foreach($billing_fields as $field): if(isset($fields_input[$first_site_id]['noauthCheckout'][$field])):?>
                <strong><?=ucfirst(str_replace('_', ' ', str_replace('billing_', '', $field)))?>:</strong> <?=$fields_input[$first_site_id]['noauthCheckout'][$field]?><br>
            <?php endif; endforeach;?>
        </div>
        <div class="col span_1_of_3">
            <h3>Shipping Info</h3>
            <?php foreach($shipping_fields as $field): if(isset($fields_input[$first_site_id]['noauthCheckout'][$field])):?>
                <strong><?=ucfirst(str_replace('_', ' ', str_replace('shipping_', '', $field)))?>:</strong> <?=$fields_input[$first_site_id]['noauthCheckout'][$field]?><br>
            <?php endif; endforeach;?>
        </div>
        <div class="clear"></div>
    </div>
</div>
