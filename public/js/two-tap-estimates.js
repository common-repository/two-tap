var Two_Tap_Estimates = {
  context: null,
  status: null,
  shipping_selector: null,
  submit_selector: null,
  blockSettings: {
    message: null,
    overlayCSS: {
      background: '#fff',
      opacity: 0.6
    }
  },

  init: function () {
    console.log('Two_Tap_Estimates init');

    if ( typeof Two_Tap_Estimates_Vars === 'undefined') {
      return;
    }

    Two_Tap_Estimates.context = Two_Tap_Estimates_Vars.context;
    Two_Tap_Estimates.status = Two_Tap_Estimates_Vars.status;

    switch (Two_Tap_Estimates.context) {
      case 'cart':
      // Two_Tap_Estimates.shipping_selector = '.cart_totals';
      Two_Tap_Estimates.submit_selector = '.cart_totals';
      break;
      case 'checkout':
      // Two_Tap_Estimates.shipping_selector = '#shipping_method';
      Two_Tap_Estimates.submit_selector = '#order_review';
      break;
    }

    if (Two_Tap_Estimates.status !== 'done') {
      Two_Tap_Estimates.checkEstimates();
    }
    Two_Tap_Estimates.setStatus(Two_Tap_Estimates.status);

    // set listeners
    if (Two_Tap_Estimates.context === 'cart') {
      $( document ).on( 'updated_cart_totals', function () {
        Two_Tap_Estimates.checkEstimates(true);
        // Two_Tap_Estimates.setStatus(Two_Tap_Estimates.status);
      });
    }
  },

  setStatus: function (status) {
    if (status === 'still_processing') {
      Two_Tap_Estimates.disableCheckout();
    }
    if (status === 'done') {
      Two_Tap_Estimates.enableCheckout();
      console.log('Two Tap estimates finished.');
    }
  },

  checkEstimates: function (afterUpdate) {
    if (typeof afterUpdate === 'undefined') {
      afterUpdate = false;
    }
    console.log('Checking estimates.');

    var data = {
      action: 'twotap_check_estimates'
    };

    $.post(Two_Tap_Estimates_Vars.ajaxurl, data, function (response) {
      if (response.message === 'still_processing') {
        if (Two_Tap_Estimates.status !== response.message) {
          Two_Tap_Estimates.setStatus(response.message);
        }
        Two_Tap_Estimates.status = 'still_processing';
        setTimeout(function () {
          Two_Tap_Estimates.checkEstimates();
        }, 1500);
      }
      if (response.message === 'done') {
        Two_Tap_Estimates.status = 'done';
        // Two_Tap_Estimates.enableCheckout();
        if (Two_Tap_Estimates.context === 'cart') {
          if (!afterUpdate) {
            setTimeout(function () {
              $( 'body' ).trigger( 'updated_shipping_method' );
              $( 'body' ).trigger( 'wc_update_cart' );
            }, 1);
          }
        }
        if (Two_Tap_Estimates.context === 'checkout') {
          setTimeout(function () {
            Two_Tap_Estimates.enableCheckout();
            $( 'body' ).trigger( 'update_checkout' );
          }, 1);
        }
      }
    });
  },

  enableCheckout: function () {
    setTimeout(function () {
      $(Two_Tap_Estimates.submit_selector).unblock();
    }, 1);
  },
  disableCheckout: function () {
    setTimeout(function () {
      $(Two_Tap_Estimates.submit_selector).block(Two_Tap_Estimates.blockSettings);
    }, 1);
  }
};

// $( document ).on( 'init_checkout', function () {
//   console.log('init_checkout');
// });
// $( document ).on( 'updated_checkout', function () {
//   console.log('updated_checkout');
// });
// $( document ).on( 'wc_update_cart', function () {
//   console.log('wc_update_cart');
// });
// $( document ).on( 'updated_cart_totals', function () {
//   console.log('updated_cart_totals');
// });
