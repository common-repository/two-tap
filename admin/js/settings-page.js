(function (App) {
  App.SettingsPage = {
    init: function () {
      console.log('SettingsPage.init');

      if ($('.js-twotap-logistics-options-form').length > 0) {
        App.SettingsPage.changeLogisticsType();
      }
      if ($('.js-current-deposit').length > 0) {
        App.SettingsPage.checkDeposits();
      }
      if ($('.js-twotap-current-plan-container').length > 0) {
        App.SettingsPage.checkCurrentPlan();
      }
      if ($('#twotap_markup_type').length > 0) {
        App.SettingsPage.changeMarkupType();
      }
      if ($('.js-international-logistics-enabled').length > 0) {
        App.SettingsPage.changeInternationalToggle();
      }
    },

    checkDeposits: function () {
      var url = $('.js-current-deposit').attr('data-url');
      var token = $('.js-current-deposit').attr('data-token');
      if (!_.isUndefined(url) && url !== '') {
        $.ajax({
          type: 'POST',
          url: url,
          data: {
            private_token: token
          },
          timeout: 5000,
          success: function (response) {
            if (!_.isUndefined(response.deposit)) {
              $('.js-current-deposit').text('$' + (response.deposit / 100));
            }
          }
        });
      }
    },

    checkCurrentPlan: function () {
      var $this = $('.js-twotap-current-plan-container');
      var url = $this.attr('data-url');
      var token = $this.attr('data-token');

      if (!_.isUndefined(url) && url !== '') {
        $.ajax({
          type: 'POST',
          url: url,
          data: {
            private_token: token
          },
          timeout: 5000,
          success: function (response) {
            var plans;
            var currentPlan = null;
            var planTemplate = '<div class="col-sm-4"><div class="plan" data-plan-id="PLAN_ID"><div class="plan-name">PLAN_NAME</div></div></div>';
            if (!_.isUndefined(response.plans)) {
              plans = response.plans;
              if (!_.isUndefined(response.current_plan)) {
                currentPlan = response.current_plan;
              }

              var r = '';
              _.each(plans, function (plan) {
                var planBody = planTemplate.replace(/PLAN_ID/, plan.id);
                plan.name = plan.name.replace(/\//g, '<br />');
                plan.name = plan.name.replace(/free/, '<strong>free</strong>');
                plan.name = plan.name.replace(/unlimited/g, '<strong>unlimited</strong>');
                planBody = planBody.replace(/PLAN_NAME/, plan.name);
                planBody = planBody.replace(/PLAN_PRICE/, plan.price_per_month);
                r += planBody;
              });
              $('.js-current-plan .js-twotap-plans').html(r);
              $('.js-current-plan .spinner').hide();
              if ( !_.isNull(currentPlan) ) {
                $('.js-current-plan .plan[data-plan-id=' + currentPlan.id + ']').first().addClass('active');
              }
            }
          }
        });
      }
    },

    changeMarkupType: function () {
      var option = $('#twotap_markup_type').val();

      switch (option) {
        case 'percent':
          $('.js-markup-info').text('%');
          $('#twotap_markup_value').removeAttr('disabled');
          break;
        case 'value':
          $('.js-markup-info').text(tt_settings_vars.woocommerce_currency_symbol);
          $('#twotap_markup_value').removeAttr('disabled');
          break;
        default:
        case 'none':
          $('#twotap_markup_value').attr('disabled', 'disabled');
          $('.js-markup-info').text('');
      }
    },

    changeLogisticsType: function () {
      var option = $('#twotap_logistics_type').val();
      $('.logistics-option').css({display: 'none'});
      $('#twotap-' + option + '-notice').css({display: 'table-row'});

      switch (option) {
        case 'own_logistics':
        case 'twotap_logistics_ship_to_office':
          App.SettingsPage.showInfo();
          break;
        default:
          App.SettingsPage.hideInfo();
      }
    },

    changeInternationalToggle: function () {
      var checked = $('.js-international-logistics-enabled').is(':checked');

      if (checked) {
        App.SettingsPage.showShippingLogisticsInfo();
        App.SettingsPage.showInfo();
      } else {
        App.SettingsPage.hideShippingLogisticsInfo();
        App.SettingsPage.hideInfo();
      }
    },

    hideInfo: function () {
      $('#twotap-shipping-info').closest('tr').css({display: 'none'});
    },
    showInfo: function () {
      $('#twotap-shipping-info').closest('tr').css({display: 'table-row'});
    },

    hideShippingLogisticsInfo: function () {
      $('#twotap-shipping-logistics-info').closest('tr').css({display: 'none'});
    },
    showShippingLogisticsInfo: function () {
      $('#twotap-shipping-logistics-info').closest('tr').css({display: 'table-row'});
    },

    useSameBillingDetails: function (e) {
      e.preventDefault();
      e.stopPropagation();
      var fields = ['first_name', 'last_name', 'address', 'city', 'state', 'zip', 'country', 'telephone' ];
      _.each(fields, function (field) {
        var value = $('#twotap_logistics_settings_shipping_' + field).val();
        $('#twotap_logistics_settings_billing_' + field).val(value);
      });
    },

    openDepositWindow: function (e) {
      e.preventDefault();
      e.stopPropagation();

      var $this = $(this);
      var href = $this.attr('data-href');

      // create popup window
      window.open(href, 'depositWindow', 'toolbar=no, location=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=550, height=800');

      window.addEventListener('message', function (event) {
        if (!_.isUndefined(event.data) && !_.isUndefined(event.data.action) && event.data.action === 'deposit_finished') {
          App.SettingsPage.checkDeposits();
        }
      }, false);
    },

    toggle: function () {
      var $this = $(this);
      var $target = $($this.attr('data-toggle'));
      var $toggleIcon = $this.find('span.dashicons');
      if ($target.is(':hidden')) {
        $target.slideDown();
        $toggleIcon.removeClass('dashicons-arrow-right');
        $toggleIcon.addClass('dashicons-arrow-down');
      } else {
        $target.slideUp();
        $toggleIcon.removeClass('dashicons-arrow-down');
        $toggleIcon.addClass('dashicons-arrow-right');
      }
    }
  };

  $(document).on('change', '#twotap_markup_type', App.SettingsPage.changeMarkupType);
  $(document).on('change', '#twotap_logistics_type', App.SettingsPage.changeLogisticsType);
  $(document).on('click', '.js-international-logistics-enabled', App.SettingsPage.changeInternationalToggle);
  $(document).on('click', '.js-use-shipping-details', App.SettingsPage.useSameBillingDetails);
  $(document).on('click', '.js-toggle', App.SettingsPage.toggle);
  $(document).on('click', '.js-open-deposit-window', App.SettingsPage.openDepositWindow);
})(App);
