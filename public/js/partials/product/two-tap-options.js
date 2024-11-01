jQuery(document).ready(function ($) {

  $('.js-twotap-refresh-product').click(function (e) {
    e.preventDefault();
    e.stopPropagation();
    var $this = $(this);
    $this.attr('disabled', 'disabled').text('Refreshing');
    var data = {
      action: 'twotap_refresh_product',
      post_id: tt_product_vars.post_id,
      product: {
        product_md5: tt_product_vars.twotap_product_md5,
        site_id: tt_product_vars.twotap_site_id
      }
    };

    $.post(ajaxurl, data, function (response) {
      $this.removeAttr('disabled').text('Refreshed');
      alert(response.message);
      if (response.success) {
        location.reload();
      }
    });
  });

  function changeCustomMarkup() {
    var $this = $(this);
    var checked = $this.is(':checked');
    if (checked) {
      $('.js-option-group-markup').removeClass('hidden');
    } else {
      $('.js-option-group-markup').addClass('hidden');
    }
  }

  function changeMarkupType() {
    var $this = $(this);
    var option = $this.val();
    switch (option) {
      case 'percent':
        $('.js-markup-info').text('%');
        break;
      case 'value':
        $('.js-markup-info').text(tt_product_vars.woocommerce_currency_symbol);
        break;
    }
  }

  $(document).on('change', '#twotap_custom_markup', changeCustomMarkup);
  $(document).on('change', '#twotap_markup_type', changeMarkupType);
});
