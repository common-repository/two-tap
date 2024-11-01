var vueApp;
var popup;
var $ = jQuery.noConflict();

var App = {

  init: function () {
    App.initVue();
    App.initPage();
  },
  initPage: function () {
    var $body = $('body');
    var page;
    var pageName;

    if ($body.hasClass('two-tap_page_twotap_settings_page')) {
      pageName = 'SettingsPage';
    }
    if ($body.hasClass('toplevel_page_twotap_products')) {
      pageName = 'ProductsPage';
    }
    if ($body.hasClass('post-type-tt_cart')) {
      pageName = 'CartsPage';
    }
    if ($body.hasClass('post-type-tt_purchase')) {
      pageName = 'PurchasesPage';
    }

    if (App[pageName]) {
      page = App[pageName];
      page.init();
    }
  },
  initVue: function () {
    if ($('body').hasClass('plugin-two-tap')) {
      Vue.http.options.emulateJSON = true;
      Vue.http.options.emulateHTTP = true;

      Vue.use(VueLazyload, {
        loading: '/wp-admin/images/spinner-2x.gif'
      });

      App.bus = new Vue();

      vueApp = new Vue({
        el: '.plugin-two-tap #wpcontent'
      });

      Vue.http.interceptors.push( function (request, next) {
        // continue to next interceptor
        next(function (response) {
          var data = response.data;
          if (!_.isUndefined(data.message)) {
            var type;
            if (!_.isUndefined(data.success) && data.success) {
              type = data.success ? 'success' : 'error';
            } else {
              type = 'info';
            }
            App.alert(data.message, type);
          }
        });
      });
    }
  },
  alert: function (message, type) {
    if (_.isUndefined(type)) {
      type = 'info';
    }
    var rand = getRandomInt(100000, 999999);
    var template = '<div class="notice notice-' + type + '" data-random="' + rand + '"><p>' + message + '</p></div>';
    var $noticesContainer = $('.js-notices-container');

    if ($noticesContainer.length === 0 ) {
      $('.wrap').prepend('<div class="js-notices-container">');
    }
    $noticesContainer = $('.js-notices-container');

    $noticesContainer.append(template);
    var $notice = $('.notice[data-random="' + rand + '"]');
    $notice.hide().slideDown(150);
    setTimeout(function () {
      $notice.slideUp(150, function () {
        $(this).remove();
      });
    }, 5000);
  }

};

$(document).ready(function () {
  App.init();
});
