<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
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
<br>
<?php include('_total-prices.php'); ?>
<?php if(!$purchase_status): ?>
<br>

<make-purchase
    :purchase-id="<?=$post->ID?>"
    inline-template
>
    <div>
        <button class="button button-primary button-large pull-right" @click.prevent="makePurchase()">Send purchase to Two Tap</button>
        <div class="clear"></div>
    </div>
</make-purchase>

<?php else: ?>
<br>

<refresh-purchase-status
    :purchase-id="<?=$post->ID?>"
    inline-template
>
    <div>
        <button class="button button-primary button-large pull-right" @click.prevent="refresh()">Refresh purchase status</button>
        <div class="clear"></div>
    </div>
</refresh-purchase-status>
<?php endif; ?>