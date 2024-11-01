var vueApp;
var $ = jQuery.noConflict();
var App = {

  init: function () {
    App.initPage();
  },
  initPage: function () {
    var $body = $('body');
    var page;
    var pageName;

    if ($body.hasClass('woocommerce-cart')) {
      pageName = 'CartPage';
    }

    if ($body.hasClass('woocommerce-checkout')) {
      pageName = 'CheckoutPage';
    }

    if (pageName === 'CartPage' || pageName === 'CheckoutPage') {
      if (!_.isUndefined(window.Two_Tap_Estimates)) {
        Two_Tap_Estimates.init();
      }
    }

    if (App[pageName]) {
      page = App[pageName];
      page.init();
    }
  }
};

$(document).ready(function () {
  App.init();
});
