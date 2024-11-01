Vue.component('test-button', {
  template: '<div><button @click.stop.prevent="test">test</button></div>',
  methods: {
    test: function () {
      this.$http.post(ajaxurl, {
        action: 'twotap_test'
      }).then(function (response) {
        console.log(response.data);
      });
    }
  }
});

Vue.component('check-wc-webhooks', {
  template: '<div><div :class="{\'hidden\': isHidden}"><button class="button" @click.prevent="setupWebhooks()" :disabled="isHidden">Fix WooCommerce webhooks</button><br><br></div><span v-if="isLoading" class="spinner clear-float is-active"></span><span v-html="message"></span></div>',
  data: function () {
    return {
      isLoading: true,
      WCApiOk: false,
      webhooksInstalled: false,
      message: ''
    };
  },
  computed: {
    isHidden: function () {
      if (this.isLoading) {
        return true;
      }
      if (!this.WCApiOk) {
        return true;
      }
      return this.webhooksInstalled;
    }
  },
  methods: {
    check: function () {
      var vm = this;
      this.$http.post(ajaxurl, {
        action: 'twotap_check_wc_webhooks'
      }).
      then(function (response) {
        if (!_.isUndefined(response.data)) {
          Vue.set(vm, 'isLoading', false);
          Vue.set(vm, 'message', response.data.message);
          if (!_.isUndefined(response.data.wc_api_ok)) {
            Vue.set(vm, 'WCApiOk', response.data.wc_api_ok);
          }
          if (!_.isUndefined(response.data.webhooks_installed)) {
            Vue.set(vm, 'webhooksInstalled', response.data.webhooks_installed);
          }
        }
      });
    },
    setupWebhooks: function () {
      var vm = this;
      Vue.set(vm, 'isLoading', true);
      Vue.set(vm, 'message', '');
      this.$http.post(ajaxurl, {
        action: 'twotap_setup_webhooks'
      }).
      then(function (response) {
        if (!_.isUndefined(response.data) && !_.isUndefined(response.data.success)) {
          Vue.set(vm, 'isLoading', false);
          if (response.data.success) {
            location.reload();
          }
        }
      });
    }
  },
  mounted: function () {
    this.check();
  }
});

Vue.component('check-wc-api', {
  template: '<div><span :class="{\'hidden\': isLoading}" v-html="message"></span><span :class="{\'is-active\': isLoading}" class="spinner clear-float"></span></div>',
  data: function () {
    return {
      isLoading: true,
      message: ''
    };
  },
  computed: {
    isDisabled: function () {
      return this.locked;
    }
  },
  methods: {
    checkWCAPI: function () {
      var vm = this;
      this.$http.post(ajaxurl, {
        action: 'twotap_check_wc_api'
      }).then(function (response) {
        if (!_.isUndefined(response.data) && !_.isUndefined(response.data.success)) {
          Vue.set(vm, 'isLoading', false);
          Vue.set(vm, 'message', response.data.message);
        }
      });
    }
  },
  mounted: function () {
    this.checkWCAPI();
  }
});

Vue.component('scheduled-jobs', {
  data: function () {
    return {
      isLoading: false,
      jobs: {
        product_refresh_queue: {
          title: 'Product refresh queue',
          count: 0
        }
      }
    };
  },
  methods: {
    refreshQueuedJobs: function () {
      var vm = this;
      this.isLoading = true;
      this.$http.post(ajaxurl, {
        action: 'twotap_jobs_remaining'
      }).then(function (response) {
        var data = response.data;
        if (data.success) {
          if (!_.isUndefined(data.data.product_refresh_queue)) {
            vm.jobs.product_refresh_queue.count =  data.data.product_refresh_queue;
          }
        }
        vm.isLoading = false;
      });
    },
    resyncProducts: function () {
      var vm = this;
      var data = {};
      data.action = 'twotap_sync_products';

      this.$http.post(ajaxurl, data, function (response) {
        console.log(response);
        var responseData = response.data;
        if ( !_.isUndefined(responseData) ) {
          vm.refreshQueuedJobs();
        }
      });
    }
  },
  mounted: function () {
    var vm = this;
    vm.refreshQueuedJobs();
    setInterval(function () {
      vm.refreshQueuedJobs();
    }, 10000);
  }
});

Vue.component('make-purchase', {
  props: {
    purchaseId: {}
  },
  methods: {
    makePurchase: function () {
      App.alert('Checking with Two Tap server.');
      this.$http.post(ajaxurl, {
        action: 'twotap_send_purchase',
        post_id: this.purchaseId
      }).then(function (response) {
        if (!_.isUndefined(response.success)) {
          if (response.success) {
            setTimeout(function () {
              location.reload();
            }, 1000);
          }
        }
        if (!_.isUndefined(response.message)) {
          App.alert(response.message);
        }
      });
    }
  }
});

Vue.component('refresh-purchase-status', {
  props: {
    purchaseId: {}
  },
  methods: {
    refresh: function () {
      this.$http.post(ajaxurl, {
        action: 'twotap_refresh_purchase_status',
        post_id: this.purchaseId
      }).then(function () {
        location.reload();
      });
    }
  }
});

Vue.component('import-products', {
  data: function () {
    return {
      products: [],
      filters: [],
      filtersSectionOpen: {
        'categories': true,
        'promotions': false,
        'genders': false,
        'sizes': false,
        'brands': false,
        'site_ids': false
      },
      hideQueryFilters: true,

      queryFilters: {
        keywords: '',
        categories: [],
        promotions: [],
        genders: [],
        sizes: [],
        brands: [],
        site_ids: []
      },
      page: 1,
      perPage: 12,
      perPageOptions: [12, 24, 48],
      sort: null,
      totalPages: 12,
      totalProducts: 0,
      searching: false,
      openCategories: [],

      // modal
      importButtonDisabled: false,
      importProgressVisible: false,
      importProgressPercent: 0,
      importProgressLabel: '',

      // product scroll
      totalProductsInCurrentFilter: 0,
      productsScrolled: 0,
      shouldImport: false
    };
  },
  computed: {
    hasQueryFilters: function () {
      var has = false;

      if (this.queryFilters.keywords !== '') {
        has = true;
      }
      var fields = [ 'categories', 'promotions', 'genders', 'sizes', 'brands', 'site_ids' ];

      var vm = this;
      _.each(fields, function (field) {
        if (!_.isEmpty(vm.queryFilters[field])) {
          has = true;
        }
      });

      return has;
    },
    hasProducts: function () {
      return this.products.length > 0;
    },
    importProgressbarStyle: function () {
      var style = {};

      style.width = this.importProgressPercent + '%';

      return style;
    }
  },
  watch: {
    // whenever question changes, this function will run
    'queryFilters.categories': function (newFilters) {
      // console.log('queryFilters changed', newFilters);
      this.performSearch();
    }
  },
  methods: {
    filtersSectionIcon: function (filter) {
      var icon = '';
      if (this.filterSectionOpen(filter)) {
        icon += 'dashicons-arrow-down';
      } else {
        icon += 'dashicons-arrow-right';
      }
      return icon;
    },
    filterSectionOpen: function (filter) {
      return this.filtersSectionOpen[filter];
    },
    toggleFilterSection: function (filter) {
      this.filtersSectionOpen[filter] = !this.filtersSectionOpen[filter];
    },
    filtersCount: function (filter) {
      if (!this.filters[filter]) {
        return 0;
      }
      return this.filters[filter].length;
    },
    showQueryFilter: function (filter) {
      if (filter === 'categories') {
        return this.filtersCount(filter) > 0;
      }

      return this.filtersCount(filter) > 0 && !this.hideQueryFilters;
    },
    prettyCategoryName: function (category) {
      categoryNames = category.name.split('~~');

      if (!_.isArray(categoryNames) || categoryNames.length <= 0) {
        return '';
      }

      var label = '';
      if (categoryNames.length > 1) {
        _.each(categoryNames, function () {
          label += '-';
        });
      }

      label += categoryNames[categoryNames.length - 1];
      label += ' (' + category.count + ')';

      return label;
    },
    categoryDropdown: function (category) {
      categoryNames = category.name.split('~~');

      if (!_.isArray(categoryNames) || categoryNames.length <= 0) {
        return [];
      }

      return categoryNames;
    },
    prettyPromotionName: function (promotion) {
      var promotionMap = {
        'p-sale': 'On Sale'
      };

      if ( !promotionMap.hasOwnProperty(promotion.name) ) {
        return '';
      }
      return promotionMap[promotion.name] + ' (' + promotion.count + ')';
    },
    prettyBrandName: function (brand) {
      return brand.name + ' (' + brand.count + ')';
    },
    prettyGenderName: function (gender) {
      var genderMap = {
        'g-men': 'Men',
        'g-women': 'Women',
        'g-boys': 'Boys',
        'g-girls': 'Girls'
      };

      if ( !genderMap.hasOwnProperty(gender.name) ) {
        return '';
      }
      return genderMap[gender.name] + ' (' + gender.count + ')';
    },
    prettySizeName: function (size) {
      return size.name + ' (' + size.count + ')';
    },
    prettySiteName: function (site) {
      if ( !ttSupportedSites.hasOwnProperty(site.name) ) {
        return '';
      }
      return ttSupportedSites[site.name].name + ' (' + site.count + ')';
    },
    addProductButtonLabel: function (product) {
      return product.product_added ? 'Product added' : 'Add to import list';
    },

    openProductsImportModal: function () {
      $('.js-import-modal').modal();
    },
    importFilteredProducts: function () {
      // show progressbar
      Vue.set(this, 'shouldImport', true);
      Vue.set(this, 'importButtonDisabled', true);
      Vue.set(this, 'importProgressVisible', true);
      Vue.set(this, 'importProgressPercent', 100);
      Vue.set(this, 'totalProductsInCurrentFilter', this.totalProducts);
      var vm = this;
      // start scroll
      this.$http.post(ajaxurl, {
        action: 'twotap_product_scroll',
        filter: this.queryFilters
      }).
      then(function (response) {
        if (!_.isUndefined(response.data) && response.data !== '0') {
          var data = response.data;
          if (data.success) {
            if (data.rescroll) {
              var scrollData = data.scroll_data;
              var productsScrolled = vm.productsScrolled;
              productsScrolled += parseInt(scrollData.count, 10);
              Vue.set(this, 'productsScrolled', productsScrolled);
              vm.reScroll(scrollData.filter, scrollData.size, scrollData.scroll_id, scrollData.count);
            }
          }
        }
      });
    },
    reScroll: function (filter, size, scrollId, count) {
      if (!this.shouldImport) {
        return;
      }
      var progress = Math.floor(this.productsScrolled * 100 / this.totalProductsInCurrentFilter);
      Vue.set(this, 'importProgressPercent', progress);
      var productsLeft = this.totalProductsInCurrentFilter - this.productsScrolled;
      productsLeft = productsLeft >= 0 ? productsLeft : '0';
      var label = progress + '% | ' + productsLeft + ' products left';
      Vue.set(this, 'importProgressLabel', label);
      var vm = this;

      this.$http.post(ajaxurl, {
        action: 'twotap_product_scroll',
        filter: filter,
        size: size,
        scroll_id: scrollId,
        count: count
      }).
      then(function (response) {
        if (!_.isUndefined(response.data) && response.data !== '0') {
          var data = response.data;
          if (data.success) {
            if (data.rescroll) {
              var scrollData = data.scroll_data;
              var productsScrolled = vm.productsScrolled;
              productsScrolled += parseInt(scrollData.count, 10);
              Vue.set(this, 'productsScrolled', productsScrolled);

              vm.reScroll(scrollData.filter, scrollData.size, scrollData.scroll_id, scrollData.count);
            } else {
              // scroll ended
              Vue.set(vm, 'importProgressPercent', 100);
              label = 'The products have been succesfully added to the queue system. Wordpress will now proceed to refresh the products info.';
              Vue.set(vm, 'importProgressLabel', label);
            }
          }
        }
      });
    },
    resetImportModal: function () {
      Vue.set(this, 'productsScrolled', 0);
      Vue.set(this, 'importProgressPercent', 0);
      Vue.set(this, 'importProgressVisible', false);
      Vue.set(this, 'importButtonDisabled', false);
      Vue.set(this, 'importProgressLabel', 'Starting product import...');
      this.stopProductImport();
    },
    stopProductImport: function () {
      Vue.set(this, 'shouldImport', false);
    },
    resetQueryFilters: function () {
      // reset the page to 1
      Vue.set(this, 'page', 1);
      // reset the query filters
      var defaultQueryFilters = {
        keywords: '',
        categories: [],
        promotions: [],
        genders: [],
        sizes: [],
        brands: [],
        site_ids: []
      };
      Vue.set(this, 'queryFilters', Vue.util.extend({}, defaultQueryFilters));
    },

    keywordSearch: function () {
      this.performSearch();
    },
    resetFiltersAction: function () {
      this.resetQueryFilters();
      this.performSearch();
    },
    changePerPage: function (perPage) {
      Vue.set(this, 'perPage', perPage);
      Vue.set(this, 'page', 1);
      this.performSearch();
    },
    changeSort: function (sort) {
      if (sort === this.sort) {
        Vue.set(this, 'sort', null);
      } else {
        Vue.set(this, 'sort', sort);
      }
      this.performSearch();
    },
    performSearch: function (page) {
      var vm = this;
      Vue.set(this, 'searching', true);

      if ( !_.isUndefined(page) ) {
        Vue.set(this, 'page', page);
      } else {
        Vue.set(this, 'page', 1);
      }

      this.$http.post(ajaxurl, {
        action: 'twotap_products_perform_search',
        query_filters: this.queryFilters,
        page: this.page,
        per_page: this.perPage,
        sort: this.sort
      }).
      then(function (response) {
        if (!_.isUndefined(response.data) && response.data !== '0') {
          var data = response.data;

          if (!_.isUndefined(data.products)) {
            Vue.set(vm, 'products', data.products);
          }
          if (!_.isUndefined(data.filters)) {
            Vue.set(vm, 'filters', []);
            Vue.set(vm, 'filters', data.filters);
          }
          if (!_.isUndefined(data.total_pages)) {
            Vue.set(vm, 'totalPages', data.total_pages);
          }
          if (!_.isUndefined(data.total_products)) {
            Vue.set(vm, 'totalProducts', data.total_products);
          }
          vm.initPagination(data.page, data.total_pages);

          Vue.set(vm, 'hideQueryFilters', !vm.hasQueryFilters);
        }
        Vue.set(vm, 'searching', false);
      });
    },
    initPagination: function (page, totalPages) {
      var defaultOptions = {
        initiateStartPageClick: false,
        totalPages: 10,
        visiblePages: 8
      };
      var vm = this;
      var paginationElement = $('.js-product-pagination');

      paginationElement.twbsPagination('destroy');

      if (totalPages <= 0) {
        return;
      }

      paginationElement.twbsPagination($.extend({}, defaultOptions, {
        startPage: page,
        totalPages: totalPages,
        onPageClick: function (event, newPage) {
          event.preventDefault();
          event.stopPropagation();
          paginationElement.twbsPagination('disable');
          vm.performSearch(newPage);
        }
      }));
    },
    addProductToShop: function (product, event) {
      if (_.isUndefined(product)) {
        App.alert('Bad product data.', 'error');
        return;
      }

      // getting the attributes
      var md5 = product.md5;
      var siteId = product.site_id;
      var target = event.target;

      var data = {
        action: 'twotap_add_product_to_shop',
        product: {
          product_md5: md5,
          site_id: siteId
        }
      };

      this.$http.post(ajaxurl, data).
        then(function () {
          Vue.set(product, 'product_added', true);
        });
    },
    updateCategoryQueryFilter: function (categoryName) {
      return this.filterChange('categories', categoryName);
    },
    filterChange: function (type, value) {
      if (_.isUndefined(this.queryFilters[type])) {
        App.alert('Bad filter type (' + type + ').', 'error');
        return;
      }

      if (this.hasFilter(type, value)) {
        var key = this.queryFilters[type].indexOf(value);
        Vue.delete(this.queryFilters[type], key);
      } else {
        this.queryFilters[type].push(value);
      }

      if ( type !== 'categories' ) {
        this.performSearch();
      }
    },
    hasFilter: function (type, value) {
      if (_.isUndefined(this.queryFilters[type])) {
        App.alert('Bad filter type (' + type + ').', 'error');
        return false;
      }

      if (this.queryFilters[type].indexOf(value) > -1) {
        return true;
      }

      return false;
    },

    getPopularProducts: function () {
      Vue.set(this, 'sort', 'on_sale_desc');
      this.performSearch();
      Vue.set(this, 'sort', null);
    }
  },
  mounted: function () {
    this.resetQueryFilters();
    this.getPopularProducts();
    var vm = this;
    $('.js-import-modal').on('hidden.bs.modal', function () {
      vm.resetImportModal();
    });
  }
});

Vue.component('category-item', {
  template: '<div :class="itemClasses"><div class="dashicons" :class="{\'dashicons-arrow-down\': childrenDropdownOpen, \'dashicons-arrow-right\': !childrenDropdownOpen, }" v-show="hasChildren" @click="toggleChildren"></div><label><input type="checkbox" :checked="categoryChecked" @change="categoryItemUpdated">{{ category.label }}</label> <ul class="child-list" :class="{\'hidden\': !childrenDropdownOpen}" v-show="hasChildren"><li v-for="child in category.children":key="child.full_name"><category-item :name="child.full_name" :category="child" :query-filters.sync="queryFilters" @category-is-open="openChildrenAndEmit"></category-item></li></ul></div>',
  data: function () {
    return {
      childrenDropdownOpen: false
    };
  },
  computed: {
    label: function () {
      return this.category.label;
    },
    categoryChecked: function () {
      var type = 'categories';
      var value = this.category.full_name;

      if (_.isUndefined(this.queryFilters[type])) {
        App.alert('Bad filter type (' + type + ').', 'error');
        return false;
      }

      var categoriesPresent = _.filter(this.queryFilters[type], function (entry) {
        return entry === value;
      });

      if (categoriesPresent.length > 0) {
        return true;
      }

      return false;
    },
    itemClasses: function () {
      var classes = '';
      if (this.categoryChecked) {
        classes += ' is-selected';
      }
      if (this.hasChildren) {
        classes += ' has-children';
      } else {
        classes += ' no-children';
      }
      return classes;
    },
    hasChildren: function () {
      return !_.isUndefined(this.category.children) && this.category.children.length > 0;
    }
  },
  props: {
    category: {},
    queryFilters: {}
  },
  methods: {
    toggleChildren: function () {
      this.childrenDropdownOpen = !this.childrenDropdownOpen;
    },
    hasFilter: function (type, value) {
      if (_.isUndefined(this.queryFilters[type])) {
        App.alert('Bad filter type (' + type + ').', 'error');
        return false;
      }

      var categoriesPresent = _.filter(this.queryFilters[type], function (entry) {
        return entry === value;
      });

      if (categoriesPresent.length > 0) {
        return true;
      }

      return false;
    },
    categoryItemUpdated: function () {
      var categoryName = this.category.full_name;
      return this.filterChange('categories', categoryName);
    },
    filterChange: function (type, value) {
      if (_.isUndefined(this.queryFilters[type])) {
        App.alert('Bad filter type (' + type + ').', 'error');
        return;
      }

      if (this.hasFilter(type, value)) {
        var key = this.queryFilters[type].indexOf(value);
        Vue.delete(this.queryFilters[type], key);
      } else {
        this.queryFilters[type].push(value);
      }
    },
    openChildrenAndEmit: function () {
      this.openChildren();
      this.$emit('category-is-open');
      if (this.$parent) {
        this.$parent.openChildrenAndEmit();
      }
    },
    openChildren: function () {
      Vue.set(this, 'childrenDropdownOpen', true);
    }
  },
  mounted: function () {
  }
});
