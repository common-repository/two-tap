jQuery(document).ready(function ($) {
  $('.js-send-purchase').click(function (e) {
    e.preventDefault();
    e.stopPropagation();
    var $this = $(this);

    $this.attr('disabled', 'disabled').text('Contacting Two Tap');
    var purchaseId = $this.attr('data-purchase-id');
    var data = {
      action: 'twotap_send_purchase',
      post_id: purchaseId
    };

    $.post(ajaxurl, data, function (response) {
      $this.removeAttr('disabled').text('Make purchase with Two Tap');
      if (response.message) {
        alert(response.message);
      }
      if (response.success) {
        location.reload();
      }
    });
  });

  $('.js-refresh-purchase').click(function (e) {
    e.preventDefault();
    e.stopPropagation();
    var $this = $(this);

    $this.attr('disabled', 'disabled').text('Refreshing purchase');
    var purchaseId = $this.attr('data-purchase-id');
    var data = {
      action: 'twotap_refresh_purchase_status',
      post_id: purchaseId
    };

    $.post(ajaxurl, data, function (response) {
      $this.removeAttr('disabled').text('Refresh purchase');
      if (response.message) {
        alert(response.message);
      }
      if (response.success) {
        location.reload();
      }
    });
  });
});
