<?php

/**
 * TT_Admin class.
 */
class Two_Tap_Purchase_Post_Type
{

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

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        do_action( 'two_tap_purchase_post_type_loaded' );

    }

    /*
    Register the purchase post type
     */
    public function purchase_post_type()
    {
        $labels = array(
            'name'                  => _x( 'Purchases', 'Post type general name', 'textdomain' ),
            'singular_name'         => _x( 'Purchase', 'Post type singular name', 'textdomain' ),
            'menu_name'             => _x( 'Purchases', 'Admin Menu text', 'textdomain' ),
            'name_admin_bar'        => _x( 'Purchase', 'Add New on Toolbar', 'textdomain' ),
            'add_new'               => __( 'Add New', 'textdomain' ),
            'add_new_item'          => __( 'Add New Purchase', 'textdomain' ),
            'new_item'              => __( 'New Purchase', 'textdomain' ),
            'edit_item'             => __( 'View Purchase', 'textdomain' ),
            'view_item'             => __( 'View Purchase', 'textdomain' ),
            'all_items'             => __( 'All Purchases', 'textdomain' ),
            'search_items'          => __( 'Search Purchases', 'textdomain' ),
            'parent_item_colon'     => __( 'Parent Purchases:', 'textdomain' ),
            'not_found'             => __( 'No purchases found.', 'textdomain' ),
            'not_found_in_trash'    => __( 'No purchases found in Trash.', 'textdomain' ),
        );

        $args = array(
            'labels'               => $labels,
            'public'               => false,
            'publicly_queryable'   => false,
            'show_ui'              => true,
            'show_in_menu'         => false,
            'query_var'            => true,
            'rewrite'              => [ 'slug' => TT_POST_TYPE_PURCHASE ],
            'capability_type'      => ['tt_purchase', 'tt_purchases'],
            'has_archive'          => true,
            'hierarchical'         => false,
            'supports'             => [ 'title' ],
            'register_meta_box_cb' => [$this, 'purchase_info_meta_boxes'],
            'capabilities' => array(
                'create_posts' => false,
                'delete_posts' => false,
                'delete_others_posts' => false,
                'delete_private_posts' => false,
                'delete_published_posts' => false,
            ),
            'map_meta_cap' => true,
        );

        register_post_type( TT_POST_TYPE_PURCHASE, $args );
    }

    public function purchase_info_meta_boxes()
    {
        global $post;
        $purchase_status = get_post_meta($post->ID, TT_META_TWOTAP_LAST_STATUS, true);

        remove_meta_box( 'submitdiv', TT_POST_TYPE_PURCHASE, 'side' );
        remove_meta_box( 'slugdiv', TT_POST_TYPE_PURCHASE, 'normal' );
        if($purchase_status){
        }
        //fields input
        add_meta_box('fields_input', 'Purchase products', [$this, 'render_fields_input_meta_box'], TT_POST_TYPE_PURCHASE, 'normal', 'default');
        //cart info
        add_meta_box('cart_info', 'Cart info', [$this, 'render_cart_info_meta_box'], TT_POST_TYPE_PURCHASE, 'normal', 'default');
        //purchase info
        add_meta_box('purchase_info', 'Purchase info', [$this, 'render_purchase_info_meta_box'], TT_POST_TYPE_PURCHASE, 'side', 'default');
    }

    public function render_fields_input_meta_box()
    {
        global $post;
        $purchase_status = get_post_meta($post->ID, TT_META_TWOTAP_LAST_STATUS, true);
        $request_params = get_post_meta($post->ID, 'twotap_request_params', true);
        $cart = get_twotap_cart_by_twotap_cart_id($request_params['twotap_cart_id']);
        $cart_status = get_post_meta($cart->ID, TT_META_TWOTAP_LAST_STATUS, true);
        $cart_products = $cart->twotap_cart_products;
        $fields_input = $request_params['fields_input'];
        $first_site_id = array_keys($fields_input)[0];
        $order_id = isset($notes[TT_META_ORDER_ID]) ? $notes[TT_META_ORDER_ID] : null;

        include(TT_ABSPATH . '/admin/partials/purchase/fields-input-meta-box.php');
    }

    public function render_cart_info_meta_box()
    {
        global $post;
        $purchase_status = get_post_meta($post->ID, TT_META_TWOTAP_LAST_STATUS, true);
        $request_params = get_post_meta($post->ID, 'twotap_request_params', true);
        $cart = get_twotap_cart_by_twotap_cart_id($request_params['twotap_cart_id']);
        $cart_status = get_post_meta($cart->ID, TT_META_TWOTAP_LAST_STATUS, true);
        $cart_products = $cart->twotap_cart_products;

        $site_ids = array_keys($cart_status['sites']);

        $message = isset($purchase_status['message']) ? $purchase_status['message'] : null;

        $purchase_id = isset($purchase_status['purchase_id']) ? $purchase_status['purchase_id'] : null;

        if($purchase_id){
            // purchase was requested
            $purchase_requested = true;

            if($purchase_status['message'] == 'done'){
                $purchase_done = true;
                $sites = isset($purchase_status['sites']) ? $purchase_status['sites'] : [];
            } else {
                $purchase_done = false;
            }
        } else {
            // purchase failed
            $purchase_requested = false;
        }

        include(TT_ABSPATH . '/admin/partials/purchase/cart-info-meta-box.php');
        ?>
        <?php
    }

    public function render_purchase_info_meta_box()
    {
        global $post;
        $purchase_status = get_post_meta($post->ID, TT_META_TWOTAP_LAST_STATUS, true);
        // $purchase_status = json_decode(file_get_contents(TT_ABSPATH.'/sample/purchase-still_processing.json'), true);
        $request_params = get_post_meta($post->ID, 'twotap_request_params', true);
        $twotap_cart_id = $request_params['twotap_cart_id'];
        $cart = get_twotap_cart_by_twotap_cart_id($request_params['twotap_cart_id']);
        $cart_status = get_post_meta($cart->ID, TT_META_TWOTAP_LAST_STATUS, true);
        $cart_products = get_post_meta($cart->ID, 'twotap_cart_products', true);

        $message = isset($purchase_status['message']) ? $purchase_status['message'] : null;
        $purchase_id = isset($purchase_status['purchase_id']) ? $purchase_status['purchase_id'] : null;
        $notes = json_decode($cart_status['notes'], true);
        $order_id = isset($notes[TT_META_ORDER_ID]) ? $notes[TT_META_ORDER_ID] : null;
        $message = isset($purchase_status['message']) ? $purchase_status['message'] : null;
        $final_message = isset($purchase_status['final_message']) ? nl2br($purchase_status['final_message']) : null;
        $description = isset($purchase_status['description']) ? nl2br($purchase_status['description']) : null;
        $status_messages = null;

        include(TT_ABSPATH . '/admin/partials/purchase/info-meta-box.php');
    }

    public function purchase_statuses()
    {
        register_post_status( 'tt-done', array(
            'label'                     => _x( 'Done', TT_POST_TYPE_PURCHASE ),
            'public'                    => true,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Done <span class="count">(%s)</span>', 'Done <span class="count">(%s)</span>' ),
        ) );
        register_post_status( 'tt-still_processing', array(
            'label'                     => _x( 'Still processing', TT_POST_TYPE_PURCHASE ),
            'public'                    => true,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Still processing <span class="count">(%s)</span>', 'Still processing <span class="count">(%s)</span>' ),
        ) );
        register_post_status( 'tt-has_failures', array(
            'label'                     => _x( 'Has failures', TT_POST_TYPE_PURCHASE ),
            'public'                    => true,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Has failures <span class="count">(%s)</span>', 'Has failures <span class="count">(%s)</span>' ),
        ) );
        // must be set tt-bad_fields rather than tt-bad_required_fields
        // because wordpress doesn't allow post statuses greater than
        // a certain amount of characters
        register_post_status( 'tt-bad_fields', array(
            'label'                     => _x( 'Bad required fields', TT_POST_TYPE_PURCHASE ),
            'public'                    => true,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Bad required fields <span class="count">(%s)</span>', 'Bad required fields <span class="count">(%s)</span>' ),
        ) );
    }

    public function tt_purchase_posts_columns($existing_columns) {
        $columns = [];
        $columns['cb'] = $existing_columns['cb'];
        $columns['purchase_title'] = 'Purchase';
        $columns['purchase_status'] = 'Status';
        $columns['date'] = $existing_columns['date'];
        return $columns;
    }

    // SHOW THE FEATURED IMAGE
    public function render_tt_purchase_posts_columns($column)
    {
        global $post;
        $purchase_status = get_post_meta($post->ID, TT_META_TWOTAP_LAST_STATUS, true);

        switch ( $column ) {
            case 'purchase_title':
            ?>
                <strong><a class="row-title" href="/wp-admin/post.php?post=<?=$post->ID?>&amp;action=edit" aria-label="“<?=$post->title?>” (Edit)"><?=$post->post_title?></a></strong>
            <?php
            break;
            case 'purchase_status':
            if(isset($purchase_status['message'])){
                if($purchase_status['message'] != 'still_processing'){
                    if(isset($purchase_status['sites'])){
                        $sites_count = count($purchase_status['sites']);
                        // $failed_to_add_to_purchase_count = count($purchase_status['failed_to_add_to_purchase']);
                        $product_count = array_sum(array_map(function($site){
                                return count($site['add_to_purchase']);
                            }, $purchase_status['sites']));
                    }
                }
            ?>
                <span title="<?=$purchase_status['description']?>"><?=$this->purchase_status_icon($purchase_status)?> <?=$purchase_status['message']?></span><br>
                <?php if(isset($product_count)): ?>
                    <?=$product_count?> products
                <?php endif; ?>
            <?php
            }
            break;
        }
    }

    public function purchase_status_icon($purchase_status = null)
    {
        if(is_null($purchase_status) || !isset($purchase_status)){
            return;
        }
        switch ($purchase_status['message']) {
            case 'done':
                return '✅';
                break;
            case 'still_processing':
                return '💤';
                break;
            case 'has_failures':
                return '❗';
                break;
        }
        return '⚠️';
    }
}