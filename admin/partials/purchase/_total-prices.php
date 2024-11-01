<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
if(!isset($purchase_status['total_prices'])){
  return;
}
$total_prices = $purchase_status['total_prices'];
$fields = ["duties", "discount_value", "final_price", "sales_tax", "subtotal", "shipping_price"];
?>
<h3>Total prices:</h3>
<?php foreach ($fields as $field):?>
    <?php if(isset($total_prices[$field])): ?>
    <strong><?=twotap_pretty_status($field)?></strong> <?=$total_prices[$field]?><br>
    <?php endif; ?>
<?php endforeach; ?>

