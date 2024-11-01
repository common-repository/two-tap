<div class="options_group">
	<p class="form-field display-block">
		<input type="checkbox" class="checkbox" style="" name="twotap_custom_markup" id="twotap_custom_markup" value="1" <?=($has_custom_markup?'checked="checked"':'')?>>
		<label for="twotap_custom_markup">Custom markup</label>
	</p>
	<p class="description">The product should ignore the global defined markup rules.</p>
</div>

<div class="options_group js-option-group-markup <?=($has_custom_markup?'':'hidden')?>">
	<p class="form-field display-block">
		<label for="twotap_markup_type">Markup type</label>
		<br>
		<select name="twotap_markup_type" id="twotap_markup_type">
			<?php if(!$custom_markup_type): ?>
				<option>Please select</option>
			<?php endif;?>
			<option value="percent" <?=($custom_markup_type == 'percent' ? 'selected' : '')?>>percent</option>
			<option value="value" <?=($custom_markup_type == 'value' ? 'selected' : '')?>>value (<?=get_woocommerce_currency_symbol()?>)</option>
		</select>
	</p>
	<p class="description">Choose the desired markup type, percent or value</p>
</div>

<div class="options_group js-option-group-markup  js-option-group-markup-value <?=($has_custom_markup?'':'hidden')?>">
	<p class="form-field display-block">
		<label for="twotap_markup_value">Markup value</label>
		<input type="text" name="twotap_markup_value" id="twotap_markup_value" value="<?=$post->twotap_markup_value?>" style="width: 93%;"> <span class="js-markup-info"><?=($custom_markup_type == 'value' ? get_woocommerce_currency_symbol() : '%')?></span>
	</p>
	<p class="description"></p>
</div>

<div class="options_group">
	<p class="form-field display-block">
		<input type="checkbox" class="checkbox" style="" name="twotap_custom_price" id="twotap_custom_price" value="1" <?=($post->twotap_custom_price?'checked="checked"':'')?>>
		<label for="twotap_custom_price">Keep product price</label>
	</p>
	<p class="description">The product's price won't change when Two Tap will refresh</p>
</div>

<div class="options_group">
	<p class="form-field display-block">
		<input type="checkbox" class="checkbox" style="" name="twotap_custom_title" id="twotap_custom_title" value="1" <?=($post->twotap_custom_title?'checked="checked"':'')?>>
		<label for="twotap_custom_title">Keep product title</label>
	</p>
	<p class="description">The product's title won't change when Two Tap will refresh</p>
</div>

<div class="options_group">
	<p class="form-field display-block">
		<input type="checkbox" class="checkbox" style="" name="twotap_custom_description" id="twotap_custom_description" value="1" <?=($post->twotap_custom_description?'checked="checked"':'')?>>
		<label for="twotap_custom_description">Keep product description</label>
	</p>
	<p class="description">The product's description won't change when Two Tap will refresh</p>
</div>

<br>
<button class="button js-twotap-refresh-product">Refresh product info from Two Tap</button>
