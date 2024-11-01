<?php

use Automattic\WooCommerce\HttpClient\HttpClientException;

/**
 * @package TwoTap
 * @version 1.0
 */

/**
 * Main TwoTap Class.
 *
 * @class TwoTap
 * @version 1.0.0
 */
class Two_Tap_Product {

    /**
     * Two Tap API.
     *
     * @since    1.0.0
     * @access   private
     * @var      null|object $api Two Tap API.
     */
    private $api;

    /**
     * WooCommerce API
     *
     * @since    1.0.0
     * @access   private
     * @var      null|object $wc_api WooCommerce API.
     */
    private $wc_api;

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    protected $processed_variations = [];

    /**
     * The product taxonomies.
     *
     * @var array
     */
    public $taxonomies = [];

    /**
     * An array with the attribute names and id's as key.
     *
     * @var array
     */
    protected $attribute_names_by_id = [];

    /**
     * Default product variation
     *
     * @var array
     */
    protected $default_variation = [];

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        global $tt_api;
        $this->api = $tt_api;

        global $wc_api;
        $this->wc_api = $wc_api;

        do_action( 'two_tap_product_loaded' );
    }

    public function twotap_products_perform_search()
    {
        $data = $_POST;
        array_filter($data);
        unset($data['action']);

        $filters = isset($data['query_filters']) && $data['query_filters'] != '' && is_array($data['query_filters']) ? $data['query_filters'] : [];
        $sort = isset($data['sort']) && $data['sort'] != '' && $data['sort'] ? $data['sort'] : '';
        $page = isset($data['page']) && $data['page'] != '' ? $data['page'] : 1;
        $per_page = isset($data['per_page']) && $data['per_page'] != '' && $data['per_page'] <= 100 ? $data['per_page'] : 20;


        $response = [
            'per_page' => (int)$per_page,
            'page' => (int)$page,
            'sort' => $sort,
            'total_pages' => 0,
            'total_products' => 0,
            'products' => [],
            'filters' => [],
        ];

        $products_response = $this->api->product()->search($filters, $sort, $page, $per_page, null, TT_DESTINATION_COUNTRY);

        if($products_response['message'] == TT_STATUS_DONE){

            $products = array_map(function($product){
                $db_product = get_product_by_site_id_and_product_md5($product['site_id'], $product['md5']);
                $product['product_added'] = !is_null($db_product);
                if(isset($product['original_price'])){
                    $price = sanitize_price($product['price']);
                    $original_price = sanitize_price($product['original_price']);
                    $product['discount_percent'] = (integer)(100 - ($price * 100 / $original_price));
                }
                return $product;
            }, $products_response['products']);
            $products = array_chunk($products, 4);
            // temporary caching the response
            set_transient('twotap_temp_response_cache', $products, HOUR_IN_SECONDS);
            $response['products'] = $products;
            $response['total_products'] = $products_response['total'];
            $response['total_pages'] = ceil($products_response['total'] / $products_response['per_page']);
        } else {
            $response['message'] = 'Two Tap error: '.$products_response['description'];
            $response['products'] = [];
        }

        $filters_response = $this->api->product()->filters($filters);

        if($filters_response['message'] == TT_STATUS_DONE){
            unset($filters_response['message']);
            $twotap_categories = new Two_Tap_Categories();
            $categories = $twotap_categories->getMenu($filters_response['categories']);
            $filters_response['categories'] = $categories;
            $response['filters'] = $filters_response;
        }
        $twotap_admin = new Two_Tap_Admin( $this->plugin_name, $this->version );
        $twotap_admin->ensure_supported_sites_synced();

        wp_send_json($response);
        wp_die();
    }

    /**
     * Add a single product from a POST request
     */
    public function add_product(){
        $data = $_POST;

        $added = false;
        $response = [];
        if (isset($data['product'])){
            if( !tt_tokens_set() ){
                tt_send_json_error_message('Two Tap tokens not set.');
                wp_die();
            }
            $api_response = $this->api->product()->get($data['product']['site_id'], $data['product']['product_md5'], TT_DESTINATION_COUNTRY);

            if($api_response['message'] == TT_STATUS_DONE){
                $product = $api_response['product'];
                $serialized = $this->create_temporary_product($product);
                $post = array(
                    // 'post_author' => $user_id,
                    'post_content' => $serialized,
                    'post_status' => "tt-processing",
                    'post_title' => "Temporary product {$product['title']}",
                    'post_parent' => '',
                    'post_type' => "product",
                );

                //Create post
                $post_id = wp_insert_post( $post, false );

                $response['success'] = $post_id ? true : false;
                $response['message'] = 'Product sent to queue.';

                l("Finished adding product {$product['title']} to queue.");
            } else {
                $response['success'] = false;
                $response['message'] = 'Two Tap error: '.$api_response['description'];
            }
        }

        wp_send_json($response);

        wp_die();
    }

    /**
     * Add the product to the database
     *
     * @param array $product_info Two Tap returned product info
     */
    public function add_product_to_db($product_info = null)
    {
        l('add_product_to_db started', $product_info['title']);
        if(is_null($product_info)){
            return false;
        }

        $post = array(
            'post_content' => isset($product_info['description']) ? $product_info['description'] : '',
            'post_status' => "publish",
            'post_title' => $product_info['title'],
            'post_parent' => '',
            'post_type' => "product",
        );


        //Create post
        $post_id = wp_insert_post( $post, false );

        // twotap meta
        update_post_meta( $post_id, TT_META_TWOTAP_PRODUCT_MD5, $product_info['md5'] );
        update_post_meta( $post_id, TT_META_TWOTAP_SITE_ID, $product_info['site_id'] );
        update_post_meta( $post_id, TT_META_TWOTAP_ORIGINAL_URL, $product_info['url'] );

        $this->refresh_product_info([
            'product_md5' => $product_info['md5'],
            'site_id' => $product_info['site_id']
        ], $post_id, true);

        return true;
    }

    /**
     * Update a product and it's variations
     *
     * @param  [type] $post_id      [description]
     * @param  [type] $product_info [description]
     *
     * @return [type]               [description]
     */
    public function update_product_and_variations( $post_id, $product_info = null )
    {
        $this->add_to_twotap_category($post_id);

        $this->update_product_info( $post_id, $product_info );
        l("Finished updating product {$product_info['title']}!");

        $this->update_variations( $post_id, $product_info );
        $this->add_to_categories( $post_id, $product_info );
    }

    /**
     * Update product's info
     *
     * @param  [type] $post_id      [description]
     * @param  [type] $product_info [description]
     *
     * @return [type]               [description]
     */
    public function update_product_info( $post_id, $product_info = null )
    {

        if(!wc_tokens_set()){
            l()->error('The WooCommerce keys are not set.');
            return false;
        }

        if(is_null($product_info)){
            l()->error('Product info is null');
            return false;
        }

        l("Started updating product info for {$product_info['title']} with post_id {$post_id}.");
        $data = [];

        if(get_post_meta($post_id, 'twotap_custom_title', true) != 1){
            $data['name'] = $product_info['title'];
            $data['slug'] = $product_info['title'];
        }

        if(get_post_meta($post_id, 'twotap_custom_description', true) != 1){
            $data['description'] = isset($product_info['description']) ? $product_info['description'] : '';
        }

        $data["enable_html_description"] = true;
        $data["enable_html_short_description"] = true;

        if(isset($product_info['weight']) && $product_info['weight'] != ''){
            $finalUnit = 'kg'; // grams
            $productWeight = new Two_Tap_Convertor($product_info['weight'], "g");
            $data['weight'] = (string)$productWeight->to($finalUnit, 4, true);
        }
        $data['catalog_visibility'] = 'visible';
        $data['in_stock'] = TT_PRODUCT_STOCK_STATUS;
        $data['manage_stock'] = false;


        $custom_price = get_post_meta($post_id, 'twotap_custom_price', true) == 1;

        if(!$custom_price){

            if(isset($product_info['original_price'])){
                $data['regular_price'] = (string)$this->apply_markup(sanitize_price($product_info['original_price']), $post_id);
                $data['sale_price'] = (string)$this->apply_markup(sanitize_price($product_info['price']), $post_id);
                $data['price'] = (string)$this->apply_markup(sanitize_price($product_info['price']), $post_id);
            } else {
                $data['regular_price'] = (string)$this->apply_markup(sanitize_price($product_info['price']), $post_id);
                $data['price'] = (string)$this->apply_markup(sanitize_price($product_info['price']), $post_id);
            }

        }

        if( TT_OPTION_REFRESH_IMAGES ){

            $sync_featured_image = true;
            $main_image = $product_info['image'];
            $alt_images = $product_info['alt_images'];
            $images = get_attached_media('image', $post_id);


            if(count($images) > 0){
                // fetching the original urls of images
                $image_twotap_original_urls = array_map(function($image){
                    return get_post_meta($image->ID, TT_META_TWOTAP_ORIGINAL_URL, true);
                }, $images);

                // checking if the main image has changed
                $post_thumbnail_id = get_post_thumbnail_id($post_id);
                if($post_thumbnail_id){
                    $post_thumbnail_twotap_original_url = get_post_meta($post_thumbnail_id, TT_META_TWOTAP_ORIGINAL_URL, true);
                    if($post_thumbnail_twotap_original_url){
                        if($post_thumbnail_twotap_original_url == $main_image){
                            // we remove the post thumbnail id from the post images array
                            $images = array_filter($images, function($image) use ($post_thumbnail_id){
                                return $image->ID != $post_thumbnail_id;
                            });
                            $sync_featured_image = false;
                        } else {
                            /**
                             * @todo: make a front-end switch for this
                             */
                            // we're deleting the featured image
                            wp_delete_attachment($post_thumbnail_id, true);
                            $images = get_attached_media('image', $post_id);
                        }
                    }
                }
            }

            // checking to see if the main image is in the images array
            $main_image_present = array_filter($alt_images, function($image) use ($main_image){
                return $main_image == $image;
            });
            if($main_image_present){
                // removing it from the alt_images array
                $alt_images = array_filter($alt_images, function($image) use ($main_image){
                    return $main_image != $image;
                });
            }

            $db_images = array_values(array_map(function($image){
                return get_post_meta($image->ID, 'twotap_original_url', true);
            }, $images));
            $db_images_ids = array_values(array_map(function($image){
                return $image->ID;
            }, $images));
            sort($alt_images);
            sort($db_images);

            $images_to_import = array_diff($alt_images, $db_images);
            $images_to_delete = array_diff($db_images, $alt_images);

            /**
             * @todo: change this to a constant & setting TT_DELETE_CUSTOM_IMAGES or something
             */
            if(TT_OPTION_DELETE_CUSTOM_IMAGES){
                // dd($images_to_delete, $images_to_import);
                $deleted_images = array_map(function($image_id){
                    $image_id = twotap_get_meta_id(TT_META_TWOTAP_ORIGINAL_URL, $image_id);
                    return $image_id;
                    // return wp_delete_attachment($image_id, true);
                }, $images_to_delete);

                // dd($deleted_images);
                $deleted_images_count = count($deleted_images);
                if($deleted_images_count > 0){
                    l("Deleted {$deleted_images_count} images for post_id {$post_id}.");
                }
            }

            $all_images = [];

            // add featured image
            if($sync_featured_image && $main_image){
                $data['images'][] = [
                    'src' => $main_image,
                    'position' => 0
                ];
                $all_images[] = $main_image;
            }

            if(isset($alt_images) && count($alt_images) > 0 && count($images_to_import) > 0){

                // reattaching the old images as well. if we don't do this, woocommerce will detach them from the current product
                foreach ($db_images_ids as $id) {
                    $data['images'][] = [
                        'id' =>  $id,
                    ];
                    $all_images[] = null;
                }
                // cycling throught the images we need to import
                foreach ($images_to_import as $key => $image_url) {
                    //try and find another image with this url
                    $existing_image_id = twotap_get_meta_id( TT_META_TWOTAP_ORIGINAL_URL, $image_url );

                    if ( $existing_image_id ) {
                        if(!in_array($existing_image_id, $db_images_ids)){
                            $data['images'][] = [
                                'id' =>  $existing_image_id,
                            ];
                            $all_images[] = $image_url;
                        }
                    } else {
                        $data['images'][] = [
                            'src' =>  $image_url,
                        ];
                        $all_images[] = $image_url;
                    }

                }
            }

            try {
                $response = $this->wc_api->put("products/{$post_id}", $data);
            } catch (HttpClientException $e) {
                l()->error('Bad WC API response', $e->getMessage());
                l()->error('WC data.', [ $post_id, $data] );
                update_post_meta($post_id, TT_META_PRODUCT_INFO_NOT_UPDATED, true);
            }

            if(isset($response)){
                delete_post_meta($post_id, TT_META_PRODUCT_INFO_NOT_UPDATED);
            }

            if(isset($response) && isset($response['images']) && count($response['images']) > 0){
                // assigning twotap_original_url meta to image
                foreach ($response['images'] as $key => $image) {
                    if(!isset($all_images[$key]) || is_null($all_images[$key])){
                        continue;
                    } else {
                        update_post_meta($image['id'], TT_META_TWOTAP_ORIGINAL_URL, $all_images[$key]);
                    }
                }
            }
        }

    }

    /**
     * Sync all products info from the Two Tap API
     *
     * @return [type] [description]
     */
    public function sync_products()
    {
        $this->reset_sync_products_option();
        wp_send_json([
            'success' => true,
            'message' => "Products sent to queue for resync."
        ]);
        wp_die();
    }

    /**
     * Reset sync products
     *
     * @return [type] [description]
     */
    public function reset_sync_products_option()
    {
        $time = time();
        $human_readable = date('Y-m-d H:i:s', $time);
        l("Set the ".TT_OPTION_TWOTAP_PRODUCTS_LAST_SYNCED." option to {$time} ($human_readable)");
        do_action('two_tap_products_last_synced_reset');
        update_option(TT_OPTION_TWOTAP_PRODUCTS_LAST_SYNCED, $time);
    }

    /**
     * Resync Two Tap Products
     *
     * @return [type] [description]
     */
    public function resync_products(){

        $products = [];

        $args = [
            'post_type' => TT_POST_TYPE_PRODUCT,
            'post_status' => twotap_product_statuses(),
            'posts_per_page' => 1000,
            'meta_query' => array(
                array(
                    'key' => 'twotap_product_md5',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => 'twotap_site_id',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => 'twotap_last_synced',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => 'twotap_last_synced',
                    'compare' => '<=',
                    'value' => (int)get_option(TT_OPTION_TWOTAP_PRODUCTS_LAST_SYNCED),
                ),
            )
        ];
        // The Query
        $query = new WP_Query( $args );
        $products = $query->get_posts();

        if(count($products) === 0){
            return;
        }
        $product_count = count($products);
        l("Schedueling {$product_count} products.");

        $product_ids = array_map(function($product){
            return $product->ID;
        }, $products);
        global $wpdb;
        $time = time();
        $imploded_ids = implode(',', $product_ids);

        $sql = "UPDATE {$wpdb->prefix}postmeta set meta_value = %s where meta_key = '" . TT_META_TWOTAP_LAST_SYNCED . "' AND post_id IN ({$imploded_ids})";
        $sql = $wpdb->prepare($sql,$time);
        $result = $wpdb->query($sql);

        foreach ($products as $product) {
            $productArgs = [
                'product_md5' => $product->twotap_product_md5,
                'site_id' => $product->twotap_site_id
            ];
            wp_schedule_single_event( time(), 'refresh_product_info', [$productArgs, $product->ID] );
        }
    }

    /**
     * Import products filtered on the Two Tap -> Products admin page
     *
     * @return [type] [description]
     */
    public function twotap_import_filtered_products()
    {

        if( !isset($_POST['query_filters']) ){
            wp_send_json([
                'success' => false,
                'message' => 'Invalid query filters sent.'
            ]);
            wp_die();
        }

        if( isset($_POST['query_filters']) && is_array($_POST['query_filters'])){
            $filter = $_POST['query_filters'];
        } else {
            $filter = [];
        }

        $size = TT_PRODUCT_IMPORT_LIMIT;
        $scroll_id = null;

        $scroll = $this->product_scroll($filter, $size, $scroll_id);

        $response = [
            'success' => true,
            'message' => 'Started syncing the products',
            'rescroll' => $scroll['rescroll'],
        ];

        if($scroll['rescroll']){
            $response['data'] = [
                'filter' => $scroll['data']['filter'],
                'size' => $scroll['data']['size'],
                'scroll_id' => $scroll['data']['scroll_id']
            ];
        }
        wp_send_json($response);
        wp_die();
    }

    /**
     * Scroll products from the Two Tap -> Products admin page
     *
     * @return [type] [description]
     */
    public function ajax_product_scroll()
    {

        if( !isset($_POST['filter']) ){
            wp_send_json([
                'success' => false,
                'message' => 'Invalid query filters sent.'
            ]);
            wp_die();
        }

        $filter = $_POST['filter'];
        $size = TT_PRODUCT_IMPORT_LIMIT;
        $scroll_id = $_POST['scroll_id'];

        $scroll = $this->product_scroll($filter, $size, $scroll_id);

        $response = [
            'success' => true,
            'rescroll' => $scroll['rescroll'],
        ];

        if(is_null($scroll_id)){
            $response['message'] = 'Started syncing the products';
        }

        if($scroll['rescroll']){
            $response['scroll_data'] = [
                'filter' => $scroll['data']['filter'],
                'size' => $scroll['data']['size'],
                'scroll_id' => $scroll['data']['scroll_id'],
                'count' => $scroll['data']['count'],
            ];
        }

        wp_send_json($response);
        wp_die();
    }

    /**
     * Actually scroll the products
     *
     * @param  array  $filter    filter object
     * @param  integer  $size      size of the scroll
     * @param  string|null  $scroll_id if it's available, the scroll_id
     *
     * @return [type]             [description]
     */
    public function product_scroll($filter = null, $size = TT_PRODUCT_IMPORT_LIMIT, $scroll_id = null){
        l()->warning('Started scrolling', [$filter, $size, $scroll_id]);
        $api_response = $this->api->product()->scroll($filter, $size, $scroll_id, null, TT_DESTINATION_COUNTRY);
        if($api_response['message'] == TT_STATUS_FAILED){
            l()->error('Scroll failed.', [$filter, $size, $scroll_id, $api_response['description']]);
            return;
        }
        if(isset($api_response['products']) && count($api_response['products']) > 0){
            l('Scrolling count.', count($api_response['products']));
            $this->add_temporary_products($api_response['products']);
            $scroll_id = $api_response['scroll_id'];

            // l('New scroll started.', [$filter, $size, $scroll_id]);
            return [
                'success' => true,
                'rescroll' => true,
                'data' => [
                    'filter' => $filter,
                    'size' => (int)$size,
                    'scroll_id' => $scroll_id,
                    'count' => count($api_response['products']),
                ],
            ];
        } else {
            // l('Scrolling ended.', [$filter, $size]);
            return [
                'success' => true,
                'rescroll' => false,
            ];
        }
    }

    /**
     * Add temporary products
     *
     * @param [type] $products [description]
     */
    public function add_temporary_products($products)
    {
        l('Starting add_temporary_products with '.count($products).' products.');
        global $wpdb;
        $products_to_insert = [];
        $place_holders = [];

        foreach ($products as $product) {
            $serialized = $this->create_temporary_product($product);
            array_push($products_to_insert, $serialized, "Temporary product {$product['title']}", 'tt-processing', 'closed', 'closed', TT_POST_TYPE_PRODUCT);
            $place_holders[] = "('%s', '%s', '%s', '%s', '%s', '%s')";
        }

        $query = "INSERT INTO {$wpdb->prefix}posts (post_content, post_title, post_status, comment_status, ping_status, post_type) VALUES ";
        $query .= implode(', ', $place_holders);
        $prep = $wpdb->prepare("$query ", $products_to_insert);

        $result = $wpdb->query( $prep );
        l('Inserted add_temporary_products.');
    }

    /**
     * Create temporary product
     *
     * @param  [type] $product_info [description]
     *
     * @return [type]               [description]
     */
    private function create_temporary_product($product_info = null){
        if(is_null($product_info)){
            return serialize([]);
        }
        return serialize(['product_md5' => $product_info['md5'], 'site_id' => $product_info['site_id']]);
    }

    /**
     * Refresh temporary products
     *
     * @return [type] [description]
     */
    public function refresh_temporary_products()
    {
        $args = [
            'post_type' => TT_POST_TYPE_PRODUCT,
            'post_status' => [ 'tt-processing' ],
            'posts_per_page' => 1000
        ];

        $wpdb = new WP_Query( $args );
        $products = $wpdb->get_posts();
        $product_count = count($products);

        if( $product_count > 0 ){
            l('refresh_temporary_products product count', [$product_count]);
            $i = 1;
            foreach ($products as $product) {
                $post_id = $product->ID;
                l("Sending refresh_product_info to queue with product. {$i}", $product->post_title);
                $i++;
                $product_args = @unserialize($product->post_content);
                l()->warning('product_args', $product_args);
                wp_schedule_single_event( time(), 'refresh_product_info', [(array)$product_args, $product->ID] );
            }
        }
        return true;
    }

    /**
     * Refresh the product info along with variations
     * @param  array  $product_info
     * @param  integer  $post_id
     * @return boolean
     */
    public function refresh_product_info($product_info = null, $post_id = null, $product_response = null)
    {
        $product = null;
        $api_response = null;

        if(!$post_id){
            l()->error('No post_id provided.', $product_info);
            return false;
        }

        $product = wc_get_product($post_id);

        if(!wc_tokens_set() || !tt_tokens_set()){
            l()->error('Tokens not set.', ['wc_tokens_set' => wc_tokens_set(), 'tt_tokens_set' => tt_tokens_set()]);
            set_transient('twotap_product_not_updated', 'token_not_set', DAY_IN_SECONDS);
            update_post_meta( $post_id, TT_META_TWOTAP_LAST_SYNCED, time() );
            return false;
        }

        if(!wc_api_enabled()){
            l()->error('WC API failed at enabled test.');
            set_transient('twotap_product_not_updated', 'token_not_set', DAY_IN_SECONDS);
            update_post_meta( $post_id, TT_META_TWOTAP_LAST_SYNCED, time() );
            return false;
        }

        if(is_null($product_info) || empty($product_info)){
            // try and get the Two Tap info from the meta keys
            $product_md5 = get_post_meta($post_id, 'twotap_product_md5', true);
            $site_id = get_post_meta($post_id, 'twotap_site_id', true);
        } else {
            if(isset($product_info['product']['md5']) && isset($product_info['product']['site_id'])){
                // if we receive the api response
                $api_response = $product_info;
                $product_md5 = $product_info['product']['md5'];
                $site_id = $product_info['product']['site_id'];
            }
            if(isset($product_info['product_md5']) && isset($product_info['site_id'])){
                // we receive just an array of site_id and product_md5
                $product_md5 = $product_info['product_md5'];
                $site_id = $product_info['site_id'];
            }
        }


        if(isset($site_id) && isset($product_md5)){
            if(is_null($api_response)){
                $api_response = $this->api->product()->get($site_id, $product_md5, TT_DESTINATION_COUNTRY);
            }
            l("API response for site_id:{$site_id} product_md5:{$product_md5}.", $api_response);

            if($api_response['message'] == TT_STATUS_DONE){

                update_post_meta( $post_id, 'twotap_product_md5', $api_response['product']['md5'] );
                update_post_meta( $post_id, 'twotap_site_id', $api_response['product']['site_id'] );
                update_post_meta( $post_id, 'twotap_original_url', $api_response['product']['url'] );
                update_post_meta( $post_id, TT_META_TWOTAP_LAST_SYNCED, time() );

                $this->update_product_and_variations($post_id, $api_response['product']);
                try {
                    $this->wc_api->put("products/{$post_id}", ['status' => 'publish']);
                } catch ( Automattic\WooCommerce\HttpClient\HttpClientException $e ) {
                    l()->error( 'Couldn\'t update the product.', $e->getResponse() );
                }
                l('Published product.');
                return true;

            }elseif($api_response['message'] == TT_STATUS_FAILED){

                $decision = null;
                if(get_post_status($post_id) == 'tt-processing'){
                    $decision = 'delete';
                }

                $this->remove_product($post_id, $decision);
                return true;

            } else {

                l()->warning('Bad Two Tap response: ', $api_response);
                return false;

            }

        } else {
            l()->error("Bad site_id & product_md5 for post_id: {$post_id} in refresh_product_info().");
            $this->mark_product_unsynced($product_info, $post_id, 'bad_info' );
            return false;
        }
    }

    /**
     * Mark product as unsynced
     *
     * @param  [type] $product_info [description]
     * @param  [type] $post_id      [description]
     * @param  [type] $reason       [description]
     *
     * @return [type]               [description]
     */
    public function mark_product_unsynced($product_info, $post_id, $reason = null)
    {
        l('Marking bad product.', [$product_info, $post_id, $reason]);
        // try to find transient with this product
        $transient_key = 'twotap_marked_product_' . $post_id;
        $transient = get_transient($transient_key);
        if($transient !== false){
            if($transient > 3){
                $post = [
                    'ID' => $post_id,
                    'post_status' => 'tt-unprocessed',
                ];
                wp_update_post($post);
                delete_transient($transient_key);
                return;
            } else {
                $transient++;
            }
        } else {
            $transient = 1;
        }
        set_transient($transient_key, $transient, HOUR_IN_SECONDS);

    }

    /**
     * Add product to Two Tap category
     *
     * @param [type] $post_id [description]
     */
    public function add_to_twotap_category($post_id)
    {
        // adding product to Two Tap category
        $twotap_product_term = get_term_by('slug', TT_TERM_SLUG_PRODUCT, TT_TERM_TAXONOMY_PRODUCT);
        if($twotap_product_term){
            wp_set_post_terms( $post_id, [$twotap_product_term->term_id], TT_TERM_TAXONOMY_PRODUCT, false );
        }
    }

    /**
     * Mark product apropriately when you need it to be removed
     *
     * @param  integer $post_id         The post ID.
     * @param  boolean $forced_decision A forced decision.
     *
     * @return [type]          [description]
     */
    public function remove_product($post_id, $forced_decision = null)
    {
        $decision_maker = TT_PRODUCT_FAILED_DECISION;
        if(!is_null($forced_decision)){
            $decision_maker = $forced_decision;
        }
        if(get_post_status($post_id) === false){
            return;
        }
        switch ($decision_maker) {
            case 'trash':
                $this->mark_product_trashed($post_id);
                break;
            case 'delete':
                $this->mark_product_deleted($post_id);
                break;
            case 'outofstock':
                $this->mark_product_outofstock($post_id);
                break;
        }
    }

    /**
     * Mark product as deleted
     *
     * @param  [type] $post_id [description]
     *
     * @return [type]          [description]
     */
    public function mark_product_deleted($post_id)
    {
        l("Deleting product {$post_id}.");
        $product = wc_get_product($post_id);

        if(!$product){
            return false;
        }

        if($product->is_type('variation')){
            $parent_id = $product->get_parent_id();
            try {
                $this->wc_api->delete("products/{$parent_id}/variations/{$product->get_id()}", ['force' => true]);
            } catch ( Automattic\WooCommerce\HttpClient\HttpClientException $e ) {
                l()->error( 'Couldn\'t delete the product.', $e->getResponse() );
            }
            return true;
        } else {
            try {
                $this->wc_api->delete("products/{$post_id}", ['force' => true]);
            } catch ( Automattic\WooCommerce\HttpClient\HttpClientException $e ) {
                l()->error( 'Couldn\'t delete the product.', $e->getResponse() );
            }
            return true;
        }
     }

    /**
     * Mark product as trashed
     *
     * @param  [type] $post_id [description]
     *
     * @return [type]          [description]
     */
    public function mark_product_trashed($post_id)
    {
        l("Trashing product {$post_id}.");
        $product = wc_get_product($post_id);

        if(!$product){
            return false;
        }

        if($product->is_type('variation')){
            $parent_id = $product->get_parent_id();
            try {
                $this->wc_api->delete("products/{$parent_id}/variations/{$product->get_id()}");
            } catch ( Automattic\WooCommerce\HttpClient\HttpClientException $e ) {
                l()->error( 'Couldn\'t trash the product.', $e->getResponse() );
            }
            return true;
        } else {
            try {
                $this->wc_api->delete("products/{$post_id}");
            } catch ( Automattic\WooCommerce\HttpClient\HttpClientException $e ) {
                l()->error( 'Couldn\'t trash the product.', $e->getResponse() );
            }
            return true;
        }
    }

    /**
     * Mark product as outofstock
     *
     * @param  [type] $post_id [description]
     *
     * @return [type]          [description]
     */
    public function mark_product_outofstock($post_id)
    {
        l("Marking product {$post_id} as oos.");
        $product = wc_get_product($post_id);

        if(!$product){
            return false;
        }

        if($product->is_type('variable')){
            $response = null;
            try {
                $response = $this->wc_api->get("products/{$post_id}");
            } catch ( Automattic\WooCommerce\HttpClient\HttpClientException $e ) {
                l()->error( 'Couldn\'t get the product\'s variations.', $e->getResponse() );
            }
            if(!is_null($response) && isset($response['variations'])){
                $variations = $response['variations'];
                $batch_update = [];
                $batch_update['update'] = array_map(function($variation_id){
                    return [
                        'id' => $variation_id,
                        'in_stock' => false,
                    ];
                }, $variations);
                try {
                    $this->wc_api->post("products/{$post_id}/variations/batch", $batch_update);
                } catch ( Automattic\WooCommerce\HttpClient\HttpClientException $e ) {
                    l()->error( 'Couldn\'t mark the variations outofstock.', $e->getResponse() );
                }
                return true;
            }
        }elseif($product->is_type('variation')){
            $parent_id = $product->get_parent_id();
            try {
                $this->wc_api->post("products/{$parent_id}/variations/{$product->get_id()}", ['in_stock' => false]);
            } catch ( Automattic\WooCommerce\HttpClient\HttpClientException $e ) {
                l()->error( 'Couldn\'t mark the product OOS.', $e->getResponse() );
            }
            return true;
        } else {
            try {
                $this->wc_api->post("products/{$post_id}", ['in_stock' => false]);
            } catch ( Automattic\WooCommerce\HttpClient\HttpClientException $e ) {
                l()->error( 'Couldn\'t mark the product OOS.', $e->getResponse() );
            }
            return true;
        }

        return false;
    }

    /**
     * Add Two Tap categories to your WooCommerce store & assign the product to them
     *
     * @param [type] $post_id      [description]
     * @param [type] $product_info [description]
     */
    public function add_to_categories($post_id, $product_info)
    {
        if(is_null($post_id)){
            l('$post_id is null in add_to_categories.', $post_id);
            return;
        }

        if(!isset($product_info['site_categories']) || !is_array($product_info['site_categories'])){
            l('Bad site categories in add_to_categories.');
            return;
        }

        if(empty($product_info['site_categories'])){
            l('No site categories in add_to_categories.');
            return;
        }
        foreach ($product_info['categories'] as $categories_group) {
            $categories = explode('~~', $categories_group);

            $wp_categories = $this->create_categories_if_needed($categories);

            wp_set_object_terms($post_id, $wp_categories, 'product_cat');
        }
    }

    /**
     * Create needed categories
     *
     * @param  array $categories Two Tap categories.
     *
     * @return array             Array with id's of the categories.
     */
    public function create_categories_if_needed($categories)
    {
        $response = [];

        $parent = null;
        foreach ($categories as $category_name) {
            $category = get_term_by('name', htmlentities($category_name), 'product_cat');
            if($category === false){
                //create the product category
                $inserted = wp_insert_term($category_name, 'product_cat', ['parent' => $parent]);
                $response[] = $inserted['term_id'];
                $parent = $inserted['term_id'];
            } else {
                // push category id to response
                $response[] = $category->term_id;
                $parent = $category->term_id;
            }
        }

        return $response;
    }

    /**
     * Adds product variations
     *
     * @param  integer $post_id      The post ID.
     * @param  array   $product_info Array with the info.
     *
     * @return [type]               [description]
     */
    public function update_variations($post_id, $product_info = null)
    {
        l("Started syncing variations for {$product_info['title']} post_id: [{$post_id}]");
        if(is_null($product_info)){
            l()->error('Failed to create variations for post_id', $post_id);
            return false;
        }

        $test = false;

        $this->taxonomies = [];
        $this->processed_variations = [];
        $this->default_variation = [];

        $productAttributes = [];

        $required_field_values = $product_info['required_field_values'];
        $is_variable_product = !empty((array)$required_field_values);

        if($is_variable_product){
            $product = wc_get_product($post_id);

            // check if the wc product is variable
            if($product->is_type('variable')){
                $product_variations = $product->get_available_variations();
            } else {
                $product_variations = [];
            }

            // getting the required_field_names other than quantity
            $required_field_names = array_filter($product_info['required_field_names'], function($item){
                if($item != 'quantity'){
                    return $item;
                }
            });

            l("'{$product_info['title']}' is a variable product.");

            $t = 1;
            $variations = [];
            // getting taxonomies
            foreach ($required_field_values as $field_name => $field_options) {
                foreach ($field_options as $field_value) {
                    $this->extract_taxonomies($field_name, $field_value);
                    $variations[] = $this->create_variation_template($post_id, $product_info, $field_name, $field_value);
                    $t++;
                }
            }

            // cleaning taxonomies
            $this->clean_taxonomies();

            // making sure the needed taxonomies are present in WC
            $wc_attributes = [];
            $product_attributes = null;
            try {
                $product_attributes = $this->wc_api->get('products/attributes');
            } catch ( Automattic\WooCommerce\HttpClient\HttpClientException $e ) {
                l()->error( 'Couldn\'t fetch the product attributes.', $e->getResponse() );
            }
            if(is_null($product_attributes)){
                return;
            }
            $this->attribute_names_by_id = [];
            foreach($product_attributes as $attribute){
                $this->attribute_names_by_id[$attribute['id']] = $attribute['name'];
            }
            foreach ($this->taxonomies as $taxonomy_key => $taxonomy_values) {
                $pretty_key = ucfirst($taxonomy_key); // color => Color
                // check if it exists
                if(in_array($pretty_key, $this->attribute_names_by_id)){
                    // assign attribute term
                    $attribute_id = array_search($pretty_key, $this->attribute_names_by_id);
                } else {
                    // must create attribute term
                    $att = [
                        'name' => $pretty_key,
                        'type' => 'select',
                        'has_archives' => false
                    ];
                    $attribute = null;
                    try {
                        $attribute = $this->wc_api->post('products/attributes', $att);
                    } catch ( Automattic\WooCommerce\HttpClient\HttpClientException $e ) {
                        l()->error( 'Couldn\'t update the attribute.', $e->getResponse() );
                    }
                    if(is_null($attribute)){
                        return;
                    }
                    $attribute_id = $attribute['id'];
                    $this->attribute_names_by_id[$attribute['id']] = $attribute['name'];
                }

                $wc_attributes[] = [
                    'id' => $attribute_id,
                    'visible' => true,
                    'variation' => true,
                    'options' => $taxonomy_values,
                ];
            }
            $data = [
                'attributes' => $wc_attributes
            ];

            try {
                $this->wc_api->put("products/{$post_id}", $data);
            } catch ( Automattic\WooCommerce\HttpClient\HttpClientException $e ) {
                l()->error( 'Couldn\'t update the product\'s attributes.', $e->getResponse() );
            }

            $this->extract_possible_variations($variations);
            $processed_variation_values = array_map(function($variation){
                $values = [];
                foreach ($variation['attributes_object'] as $key => $value) {
                    $values[$key] =[
                        'text' => $value['text'],
                        'value' => $value['value']
                    ];
                }
                return $values;
            }, $this->processed_variations);

            $processed_variations = $this->processed_variations;
            $batch_update = [];
            $new_images = [];

            if(count($product_variations) > 0){
                // l('product_variations', $product_variations);

                foreach($product_variations as $product_variation_key => $product_variation){
                    $product_variation_attributes = get_post_meta($product_variation['variation_id'], 'twotap_product_variation_values', true);
                    if($product_variation_attributes){
                        // check if there are attributes with the value of 'any'
                        foreach ($processed_variations as $processed_variation_key => $processed_variation) {
                            $processed_variation_attributes = $processed_variation['attributes_object'];
                            // if there are a different number of attributes than it doesn't exist
                            $variation_exists = false;
                            if( (count($product_variation_attributes) != count($processed_variation_attributes))){
                                $variation_exists = false;
                                continue;
                            } else {
                                foreach ($processed_variation_attributes as $attribute_key => $attribute_value) {
                                    $is_any = isset($any_values[$attribute_key]);
                                    if(
                                        (isset($product_variation_attributes[$attribute_key]) &&
                                            $product_variation_attributes[$attribute_key]['text'] == $attribute_value['text'] &&
                                            $product_variation_attributes[$attribute_key]['value'] == $attribute_value['value'])
                                    ){
                                        $variation_exists = true;
                                    } else {
                                        $variation_exists = false;
                                    }
                                    break;
                                } // foreach processed_variation_attributes
                            }

                            $variation_post_data = $this->build_product_variation_product($processed_variation, $post_id);

                            if($variation_exists){
                                // variation exists. update it
                                $variation_post_data['id'] = $product_variation['variation_id'];
                                $batch_update['update'][] = $variation_post_data;
                                unset($processed_variations[$processed_variation_key]);
                                unset($product_variations[$product_variation_key]);
                            } else {
                                // variation needs to be added
                                $batch_update['create'][] = $variation_post_data;
                            }
                            break;
                        }  // foreach processed_variations
                    }
                } // foreach product_variations

            } else {
                // wc product does not have variations just import them all
                foreach ($processed_variations as $processed_variation_key => $processed_variation) {
                    $processed_variation_attributes = $processed_variation['attributes_object'];
                    // if there are a different number of attributes than it doesn't exist
                    $variation_post_data = $this->build_product_variation_product($processed_variation, $post_id);

                    // variation needs to be added
                    $batch_update['create'][] = $variation_post_data;

                    unset($processed_variations[$processed_variation_key]);
                    // break;
                }

            }

            // removing variations left
            if(count($product_variations) > 0){
                foreach ($product_variations as $product_variation) {
                    // can't trash variation. only delete.
                    // also it should be deleted. oos products don't show up anywhere
                    l("Variation no longer available. Deleting it! {$product_variation['variation_id']}.");
                    $batch_update['delete'][] = $product_variation['variation_id'];
                }
            }

            try {
                $response = $this->wc_api->post("products/{$post_id}/variations/batch", $batch_update);
            } catch (HttpClientException $e) {
                l()->error('Bad WC API response', $e->getMessage());
                l()->error('WC data.', [ $post_id, $batch_update ]);
                update_post_meta($post_id, TT_META_VARIATIONS_NOT_UPDATED, true);
            }

            if(isset($response)){
                delete_post_meta($post_id, TT_META_VARIATIONS_NOT_UPDATED);
            }

            $this->update_variation_images_metadata();
            if(count($this->default_variation) > 0){
                // setting the default selected attributes
                $data = [];
                $data['type'] = 'variable';
                foreach ($this->default_variation as $attribute_id => $attribute_option) {
                    $data['default_attributes'][] = [
                        'id' => $attribute_id,
                        'option' => $attribute_option,
                    ];
                }
                try {
                    $updated = $this->wc_api->put("products/{$post_id}", $data);
                } catch (HttpClientException $e) {
                    l()->error('Bad WC API response', $e->getMessage());
                    l()->error('WC data.', [ $post_id, $data ]);
                    update_post_meta($post_id, TT_META_DEFAULT_VARIATION_NOT_UPDATED, true);
                }
                if($updated){
                    delete_post_meta($post_id, TT_META_DEFAULT_VARIATION_NOT_UPDATED);
                }
            }

        } else {
            try {
                $data = [
                    'type' => 'simple',
                ];
                $updated = $this->wc_api->put("products/{$post_id}", $data);
            } catch (HttpClientException $e) {
                l()->error('Couldn\'t create product.', $e->getMessage());
                update_post_meta($post_id, TT_META_PRODUCT_INFO_NOT_UPDATED, true);
            }
            if($updated){
                delete_post_meta($post_id, TT_META_PRODUCT_INFO_NOT_UPDATED);
            }
        }
    }

    /**
     * Build the product variation product
     *
     * @param  array   $processed_variation. The variation data.
     * @param  integer $parent_id            The product ID.
     *
     * @return [type]                      [description]
     */
    public function build_product_variation_product($processed_variation = null, $parent_id = null)
    {
        $variation_post_data = [];

        if(is_null($processed_variation)){
            return $variation_post_data;
        }

        if(isset($processed_variation['attributes'])){

            foreach ($processed_variation['attributes'] as $attribute_key => $attribute_value) {
                $pretty_key = ucfirst($attribute_key);
                $attribute_id = array_search($pretty_key, $this->attribute_names_by_id);
                $variation_post_data['attributes'][] = [
                    'id' => $attribute_id,
                    'option' => $attribute_value,
                ];
                if(!isset($this->default_variation[$attribute_id])){
                    $this->default_variation[$attribute_id] = $attribute_value;
                }
            }
            $possible_attributes = array_values(array_map(function($attribute){
                            return strtolower($attribute);
                        },$this->attribute_names_by_id));
            foreach ($processed_variation['attributes_object'] as $attribute_key => $attribute_value) {
                if(in_array($attribute_key, $possible_attributes)){
                    $key = array_search($attribute_key, $possible_attributes);
                    unset($possible_attributes[$key]);
                }
                $twotap_product_variations[$attribute_key] = [
                    'text' => $attribute_value['text'],
                    'value' => $attribute_value['value'],
                ];
            }

            $variation_post_data['meta_data'][] = [
                'key' => 'twotap_product_variation_values',
                'value' => $twotap_product_variations,
            ];
        }
        if(isset($processed_variation['object']['extra_info'])){
            $variation_post_data['description'] = $processed_variation['object']['extra_info'];
        }
        if(isset($processed_variation['object']['image'])){
            // check to see if image exists in the DB
            $present_image_id = twotap_get_meta_id('twotap_original_url', $processed_variation['object']['image']);
            if($present_image_id && (get_post_status($present_image_id) != false)){
                $variation_post_data['image'] = [
                    'id' => $present_image_id,
                    'position' => 0,
                ];
            } else {
                $variation_post_data['meta_data'][] = [
                    'key' => 'twotap_update_image_meta',
                    'value' => $processed_variation['object']['image']
                ];
            }
        } else {
            $default_post_thumbnail_id = get_post_thumbnail_id($parent_id);
            if(get_post_status($default_post_thumbnail_id)){
                $variation_post_data['image'] = [
                    'id' => $default_post_thumbnail_id,
                    'position' => 0,
                ];
            }
        }

        $custom_price = get_post_meta($parent_id, 'twotap_custom_price', true) == 1;

        if(!$custom_price){

            if(isset($processed_variation['object']['original_price'])){
                $variation_post_data['regular_price'] = (string)$this->apply_markup(sanitize_price($processed_variation['object']['original_price']), $parent_id);
                $variation_post_data['sale_price'] = (string)$this->apply_markup(sanitize_price($processed_variation['object']['price']), $parent_id);
                $variation_post_data['price'] = (string)$this->apply_markup(sanitize_price($processed_variation['object']['price']), $parent_id);
            } else {
                $variation_post_data['regular_price'] = (string)$this->apply_markup(sanitize_price($processed_variation['object']['price']), $parent_id);
                $variation_post_data['price'] = (string)$this->apply_markup(sanitize_price($processed_variation['object']['price']), $parent_id);
            }

        }

        $variation_post_data['in_stock'] = true;

        return $variation_post_data;
    }

    /**
     * Update the variation images meta data.
     *
     * @return void
     */
    public function update_variation_images_metadata()
    {

        // get the variation_id
        $args = array(
            'post_type' => TT_POST_TYPE_PRODUCT_VARIATION,
            'post_status' => twotap_product_statuses(),
            'posts_per_page' => -1,
            'order' => 'DESC',
            'orderby' => 'post_modified',
            'meta_query' => array(
                array(
                    'key' => 'twotap_update_image_meta',
                    'compare' => 'EXISTS'
                ),
            )
        );
        $query = new WP_Query($args);

        if(!$query->have_posts()){
            return false;
        }

        foreach ($query->get_posts() as $post) {
            // get the meta value
            $twotap_original_url = get_post_meta($post->ID, 'twotap_update_image_meta', true);

            $post_id = $post->ID;

            // try and find image with that URL
            $image_id = twotap_get_meta_id('twotap_original_url', $twotap_original_url);
            // l('$image_id', $image_id);

            if($image_id){
                $updated_image = true;
                $data = [
                    'image' => [
                        'id' => $image_id,
                        'position' => 0
                    ]
                ];
            } else {
                $updated_image = false;
                $data = [
                    'image' => [
                        'src' => $twotap_original_url,
                        'position' => 0
                    ]
                ];
            }

            try {
                $response = $this->wc_api->put("products/{$post->post_parent}/variations/{$post->ID}", $data);
            } catch (HttpClientException $e) {
                l()->error('Bad WC API response', $e->getMessage());
                l()->error('WC data.', [$post->post_parent, $post->ID, $data]);
                update_post_meta($post_id, TT_META_VARIATION_IMAGE_META_NOT_UPDATED, true);
            }

            if(!isset($response)){
                return false;
            }

            delete_post_meta($post_id, TT_META_VARIATION_IMAGE_META_NOT_UPDATED);

            if($response['image']){
                // update the featured image meta value
                $updated_meta = update_post_meta($response['image']['id'], 'twotap_original_url', $twotap_original_url);
            }

            delete_post_meta($post->ID, 'twotap_update_image_meta');
            if($updated_image){
                l("Updated image ({$image_id}) meta value to: {$twotap_original_url}.");
            } else {
                l("Added image ({$response['image']['id']}) meta value to: {$twotap_original_url}.");
            }
        }
    }

    /**
     * Apply markup to the product
     *
     * @param  integer $price   [description]
     * @param  [type]  $post_id [description]
     *
     * @return [type]           [description]
     */
    public function apply_markup($price = 0, $post_id = null)
    {
        if($price == 0){
            return 0;
        }

        $custom_markup = false;

        if(!is_null($post_id)){
            $custom_markup = get_post_meta($post_id, 'twotap_custom_markup', true) == 1;
        }

        if($custom_markup){
            $markup_type = get_post_meta($post_id, 'twotap_markup_type', true);
            $markup_value = get_post_meta($post_id, 'twotap_markup_value', true);
        } else {
            $markup_type = get_option('twotap_markup_type');
            $markup_value = get_option('twotap_markup_value', 0);
        }

        if($markup_type == 'none' || $markup_value == 0 || $markup_value === false){
            return $price;
        }

        if(!is_float($price)){
            l()->error('Price value is not float.', $price);
        }

        switch ($markup_type) {
            case 'percent':
                $price_in_cents = $price * 100;
                // adding markup
                $final_price = $price_in_cents + ($price_in_cents * $markup_value) / 100;
                // flooring with two decimals
                $final_price = floor($final_price * 100) / 100;

                return $final_price / 100;
                break;
            case 'value':
                $price_in_cents = $price * 100;
                $markup_value = $markup_value * 100;
                $final_price = $price_in_cents + $markup_value;

                return $final_price / 100;
                break;
            default:
                return $price;
                break;
        }
    }

    /**
     * Extract product taxonomies.
     *
     * @param  string $name   Taxonomy name.
     * @param  object $values Taxonomy object
     *
     * @return void
     */
    public function extract_taxonomies($name, $values)
    {
        if(!empty($values['dep'])){
            foreach ($values['dep'] as $new_field_name => $field_options) {
                foreach ($field_options as $field_value) {
                    $this->extract_taxonomies($new_field_name, $field_value);
                }
            }
        }

        $this->taxonomies[$name][] = $values['text'];
    }

    /**
     * Celean duplicate taxonomies
     *
     * @return void
     */
    public function clean_taxonomies()
    {
        $this->taxonomies = array_map('array_unique', $this->taxonomies);
    }

    /**
     * Extract prossible produvt varitations
     *
     * @param  array   $variations        Variations of the product.
     * @param  array   $parent_variations Parent variations.
     * @param  integer $deep              How deep the variations are
     *
     * @return void
     */
    public function extract_possible_variations($variations, $parent_variations = [], $deep = 0)
    {
        $is_child = count($parent_variations) <= 0;
        foreach ($variations as $variation) {
            $has_variations = isset($variation['variations']) && count($variation['variations']) > 0;
            if($is_child){
                $new_parent_variation = [];
                $deep = 0;
            }
            if($has_variations){
                $new_parent_variation['attributes'][$variation['variation_key']] = $variation['variation_value'];
                $new_parent_variation['object'][$variation['variation_key']] = $variation['object'];
                $new_parent_variation['attributes_object'][$variation['variation_key']] = $variation['object'];
                $parent_variations[] = $new_parent_variation;
                $this->extract_possible_variations($variation['variations'], $parent_variations, $deep + 1);
            } else {
                $new_variation = [];
                // updating the attributes array with all of the parents values and the variation's
                foreach ($parent_variations as $parent_variation) {
                    foreach ($parent_variation['attributes'] as $att_key => $att_value) {
                        $new_variation['attributes'][$att_key] = $att_value;
                    }
                }
                $new_variation['attributes'][$variation['variation_key']] = $variation['variation_value'];
                $new_variation['object'] = $variation['object'];
                foreach ($parent_variations as $parent_variation) {
                    $parent_variation_key = key($parent_variation['object']);
                    $new_variation['attributes_object'][$parent_variation_key] = $parent_variation['object'][$parent_variation_key];
                }
                $new_variation['attributes_object'][$variation['variation_key']] = $variation['object'];
                $this->processed_variations[] = $new_variation;
            }
        }
    }

    /**
     * Create's an object for variation creation based on a pattern.
     *
     * @param  integer $post_id            The post ID.
     * @param  array   $product_info       The product info.
     * @param  string  $field_name         The name of the field.
     * @param  array   $parent_field_value Parent values.
     *
     * @return array                       Array with the template.
     */
    public function create_variation_template($post_id, $product_info, $field_name, $parent_field_value)
    {
        $variations = [];
        if(!empty($parent_field_value['dep'])){
            foreach ($parent_field_value['dep'] as $new_field_name => $field_options) {
                foreach ($field_options as $field_value) {
                    $variation = $this->create_variation_template($post_id, $product_info, $new_field_name, $field_value);
                    $depObject = $this->create_dependency_object($new_field_name, $field_value);
                    $variation['object'] = $depObject;
                    $variations[] = $variation;
                }
            }

            $newVariation = [
                'post_id' => $post_id,
                'product' => $product_info['title'],
                'variations' => $variations,
                'variation_key' => $field_name,
                'variation_value' => $parent_field_value['text'],
                'object' => $this->create_dependency_object($field_name, $parent_field_value),
            ];
        } else {
            $newVariation = [
                'post_id' => $post_id,
                'product' => $product_info['title'],
                'variations' => [],
                'variation_key' => $field_name,
                'variation_value' => $parent_field_value['text'],
                'object' => $this->create_dependency_object($field_name, $parent_field_value),
            ];
        }
        return $newVariation;
    }

    /**
     * Create dependency object based on a template
     *
     * @param  string $depName Name of object.
     * @param  array  $dep     Values of object.
     *
     * @return array           Array with proper values.
     */
    public function create_dependency_object($depName, $dep)
    {
        $response = [
            'name' => $depName,
            'text' => $dep['text'],
            'value' => $dep['value'],
        ];

        if(isset($dep['price'])){
            $response['price'] = $dep['price'];
        }
        if(isset($dep['original_price'])){
            $response['original_price'] = $dep['original_price'];
        }
        if(isset($dep['sale_price'])){
            $response['sale_price'] = $dep['sale_price'];
        }
        if(isset($dep['weight'])){
            $response['weight'] = $dep['weight'];
        }
        if(isset($dep['image'])){
            $response['image'] = $dep['image'];
        }
        if(isset($dep['extra_info'])){
            $response['extra_info'] = $dep['extra_info'];
        }

        return $response;
    }

    /**
     * Live refresh product
     *
     * @return [type] [description]
     */
    public function twotap_refresh_product()
    {
        $product_info = $_POST['product'];
        $post_id = $_POST['post_id'];
        // dd($product_info);
        if(!isset($product_info['product_md5']) || !isset($product_info['site_id'])){
            tt_send_json_error_message('Product data is invalid.');
            wp_die();
        }

        if($this->refresh_product_info($product_info, $post_id)){
            update_post_meta($post_id, TT_META_TWOTAP_LAST_SYNCED, time());
            tt_send_json_success_message('Product refreshed.');
            wp_die();
        }
        tt_send_json_error_message('Product couldn\'t be updated');
        wp_die();
    }

    /**
     * Get remaining jobs
     *
     * @return [type] [description]
     */
    public function twotap_jobs_remaining_json(){
        global $wpdb;
        $temporary_products = (integer)$wpdb->get_var("SELECT count(*) from {$wpdb->prefix}posts where post_type = 'temporary_product'");
        $unprocessed_products = (integer)$wpdb->get_var("SELECT count(*) from {$wpdb->prefix}posts where post_type = '".TT_POST_TYPE_PRODUCT."' and post_status = 'tt-processing'");
        $count = $this->twotap_jobs_remaining(TT_REFRESH_PRODUCT_INFO_JOB) + $temporary_products = $unprocessed_products;
        $response = [
            'success' => true,
            'data' => [
                'product_refresh_queue' => $count,
            ],
        ];
        wp_send_json($response);
        wp_die();
    }

    /**
     * Get remaining jobs for job name.
     *
     * @param  string $job [description]
     *
     * @return [type]      [description]
     */
    public function twotap_jobs_remaining($job = 'all')
    {
        $crons = get_option( 'cron' );
        if($job == 'all'){
            $response = [
                TT_REFRESH_PRODUCT_INFO_JOB => 0,
            ];
            if(!is_array($crons)){
                return null;
            }
            foreach ($crons as $cron) {
                if(is_array($cron) && count($cron) > 0){
                    foreach ($cron as $name => $jobs) {
                        foreach ($response as $job_name => $job_count) {
                            if($name == $job_name){
                                $response[$job_name] += count($jobs);
                            }
                        }
                    }
                }
            }
            return $response;
        } else {
            $job_count = 0;
            foreach ($crons as $cron) {
                if(is_array($cron) && count($cron) > 0){
                    foreach ($cron as $name => $jobs) {
                        if($name == $job){
                            $job_count += count($jobs);
                        }
                    }
                }
            }
            return $job_count;
        }
    }

}
