var entities = {
  product: {
    taxonomies: [
      'Two Tap Product'
    ],
    meta_fields: [
      'twotap_original_url',
      'twotap_site_id',
      'twotap_product_md5',
      'twotap_last_synced', // last time scraped from /product endpoint.
      'twotap_custom_title', // if set to 'yes' the product's field will not update on resync
      'twotap_custom_description', // if set to 'yes' the product's field will not update on resync
      'twotap_custom_markup', // if set to 'yes' the product's field will not update on resync
      'twotap_markup_type', // custom product markup type
      'twotap_markup_vale' // custom product markup value
    ],
    transients: [
      'twotap_product_refreshed_in_cart_POST_ID' // last time scraped from /cart
    ]
  },
  cart: {
    taxonomies: [],
    meta_fields: [
      'twotap_cart_id', // Two Tap cart_id hash
      'twotap_last_status', // last retrieved /cart/status
      'twotap_last_response', // last response from the callback
      'twotap_cart_products', // a hash with the selected attributes {site_id: {chosen_attributes: {quantity: 1, color: 'tt_attribute_value'}}}
      'twotap_request_params', // all the request params
      'twotap_discounts_applied', // if the discounts have been applied to the cart
      'twotap_last_synced', // last time scraped from /cart/status endpoint.
      'order_id' // if it's provided the WooCommerce order ID
    ],
    transients: []
  },
  purchase: {
    taxonomies: [],
    meta_fields: [
      'twotap_purchase_id',
      'twotap_cart_id',
      'twotap_request_params',
      'twotap_last_status', // last retrieved status from /purchase/status
      'twotap_insufficient_funds', // if the purchase tried to be sent but it  has been marked with insufficient funds
      'order_id' // WooCommerce order ID
    ],
    transients: []
  },
  order: {
    taxonomies: [
      'Two Tap Order'
    ],
    meta_fields: [
      'twotap_sent_state',
      'twotap_chosen_shipping_option', // string (cheapest|fastest)
      'twotap_estimate', // chosen estimate object
      'twotap_available_estimates', // estimates object for each shipping type
      'twotap_shipping_info', // shipping info sent in the fields input
      'twotap_meta_updated', // if the session cart meta have been copied onto the order in the 'woocommerce_thankyou' action
      'db_purchase_id' // ther db entry where the Two Tap purchase is stored
    ],
    transients: []
  },
  order_item: {
    meta_fields: [
      'twotap_status', // status of the product (still_processing|outofstock)
      'twotap_site_id',
      'twotap_product_md5'
    ]
  }
};
