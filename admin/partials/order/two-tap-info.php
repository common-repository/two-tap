<?php
    // d($db_purchase);
    // d($request_params);
    // d($purchase_status);
?>

<div>
<?php if($final_message): ?>
<strong>Final message: </strong><?=$final_message?>
<br>
<?php endif; ?>
<?php if($message): ?>
<strong>Message: </strong><?=$message?>
<br>
<?php endif; ?>
<?php if($description): ?>
<strong>Description: </strong>
<br>
<?=nl2br($description)?>
<br>
<?php endif; ?>
<?php if($status_messages): ?>
<strong>Status messages: </strong><?=$status_messages?>
<br>
<?php endif; ?>

<?php include('_total-prices.php'); ?>

</div>

<?php if(!$purchase_status ) : ?>

    <p>
        ❗️Purchase was not yet sent to Two Tap. Please send it quick to ensure the products are still in stock.
    </p>

    <button class="button button-primary button-large pull-right js-send-purchase" data-purchase-id="<?=$db_purchase->ID?>">Send purchase with Two Tap</button>
    <div class="clear"></div>

<?php endif; ?>

<div class="clear"></div>
