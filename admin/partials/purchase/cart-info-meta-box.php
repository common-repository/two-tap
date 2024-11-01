<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>

<h3>Cart products</h3>
<table class="tt-products-table widefat">
    <thead>
        <tr>
            <th class="row-title" width="100">Image</th>
            <th class="row-title">Title</th>
            <th class="row-title">Chosen attributes</th>
            <th class="row-title">Price</th>
        </tr>
    </thead>
    <tbody>
        <?php
            foreach ($cart_status['sites'] as $site_id => $site_response) {

                if(isset($site_response['add_to_cart']) && count($site_response['add_to_cart']) > 0){
                    if($message == 'bad_required_fields'){
                        // $has_issue_with_site = isset();
                    }
                foreach ($site_response['add_to_cart'] as $product_md5 => $product) {
                    $possible_product_md5 = md5($product['original_url']);
                    $cart_product = isset($cart_products[$possible_product_md5]) ? $cart_products[$possible_product_md5] : null;
                    $wc_product = wc_get_product($cart_product['product_id']);
                    $parent_id = null;
                    // d($wc_product, $cart_product['product_id']);
                    if(isset($wc_product) && $wc_product){
                        if($wc_product->is_type('variation')){
                            $parent_id = $wc_product->get_parent_id();
                        } else {
                            $parent_id = $wc_product->get_id();
                        }
                    }
            ?>
            <tr>
                <td>
                <img src="<?=$product['image']?>" style="max-height: 60px;">
                <br>
                <br>
                <img src="<?=$site_response['info']['logo']?>" style="max-height: 25px; max-width: 100px;">
                </td>
                <td>
                    <?php if($cart_product):?>
                        <a href="<?=get_edit_post_link($parent_id)?>" target="_blank"><?=$product['title']?></a>
                    <?php else:?>
                        <?=$product['title']?>
                    <?php endif;?>
                    <small><a href="<?=$product['url']?>" target="_blank">visit</a></small>
                    <br>
                    <small>
                        site_id: <?=$site_id?>
                        <br />
                        product_md5: <?=$product_md5?>
                        <br />
                        site: <a href="http://<?=$site_response['info']['url']?>" target="_blank"><?=$site_response['info']['name']?></a>
                    </small>
                </td>
                <td>
                    <?php
                    if($cart_product){
                        $chosen_attributes = $cart_product['chosen_attributes'];
                        foreach ($chosen_attributes as $att_key => $att_value) {
                            if (strpos($att_value, 'http') !== false): ?>
                                <small>
                                    <?=$att_key?>: <img src="<?=$att_value?>" alt="<?=$att_key?>" style="max-height: 30px; max-width: 30px;">
                                </small>
                            <?php else: ?>
                                <small><?=$att_key?>: <?=$att_value?></small><br>
                            <?php endif;
                        }
                    }
                    ?>
                </td>
                <td>
                    <?php if(isset($product['discounted_price'])): ?>
                        <strike><?=$product['original_price']?></strike><br>
                        <?=$product['discounted_price']?>
                    <?php else: ?>
                        <?=$product['price']?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php
                }
            }
            if(isset($site_response['failed_to_add_to_cart']) && count($site_response['failed_to_add_to_cart']) > 0){

                foreach ($site_response['failed_to_add_to_cart'] as $product_md5 => $product) {
                    // $db_product = get_product_by_site_id_and_product_md5($site_id, $product_md5);
                    // dump($db_product);
            ?>
            <tr>
                <td><img src="<?=$product['image']?>" style="max-height: 50px;"></td>
                <td>
                    <?=$product['title']?>
                    <br>
                    site_id: <?=$site_id?>
                    product_md5: <?=$product_md5?>
                    <br>
                    reason: <?=$product['status_reason']?>
                </td>
                <td><?=$product['price']?></td>
            </tr>
            <?php
                }
            }
        } ?>
    </tbody>
</table>

<?php 

// d($purchase_status);
// d($cart_status);
// d($request_params);
?>