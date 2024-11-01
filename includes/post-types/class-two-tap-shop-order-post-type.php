<?php

/**
 * TT_Admin class.
 */
class Two_Tap_Shop_Order_Post_Type
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

        do_action( 'two_tap_shop_order_post_type_loaded' );

    }

    public function meta_boxes($post_type, $post)
    {
        $order_id = $post->ID;
        $tt_products = get_twotap_products_in_order($order_id);
        if( $post_type == TT_POST_TYPE_ORDER && $tt_products && count($tt_products) > 0 ){
            add_meta_box('twotap_options', '<span class="icon-two-tap-logo"></span> Two Tap Info', [$this, 'twotap_options_meta_box_html'], TT_POST_TYPE_ORDER, 'side', 'default');

            wp_enqueue_script( $this->plugin_name . 'two-tap-info', TT_PLUGIN_URL . '/public/js/partials/order/two-tap-info.js', array( 'jquery' ), $this->version, true );
        }
    }

    public function twotap_options_meta_box_html()
    {
        global $post;
        // Noncename needed to verify where the data originated
        echo '<input type="hidden" name="twotap_product_meta_nonce" id="twotap_product_meta_nonce" value="' .
        wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
        $order_id = $post->ID;
        $twotap_cart_id = get_post_meta($order_id, 'twotap_cart_id', true);
        $db_purchase_id = get_post_meta($order_id, 'db_purchase_id', true);
        $db_purchase = get_post($db_purchase_id);
        if($db_purchase){
            $purchase_status = get_post_meta($db_purchase->ID, TT_META_TWOTAP_LAST_STATUS, true);
            $purchase_state = get_post_meta($db_purchase->ID, TT_META_TWOTAP_SENT_STATE, true);
            $db_cart = get_twotap_cart_by_twotap_cart_id($twotap_cart_id);
            $cart_status = get_post_meta($db_cart->ID, TT_META_TWOTAP_LAST_STATUS, true);
            $request_params = get_post_meta($db_purchase->ID, 'twotap_request_params', true);

            $last_message = '';
            $percent_done = 0;

            $message = isset($purchase_status['message']) ? $purchase_status['message'] : null;
            $purchase_id = isset($purchase_status['purchase_id']) ? $purchase_status['purchase_id'] : null;
            $notes = json_decode($cart_status['notes'], true);
            $order_id = isset($notes[TT_META_ORDER_ID]) ? $notes[TT_META_ORDER_ID] : null;
            $message = isset($purchase_status['message']) ? $purchase_status['message'] : null;
            $final_message = isset($purchase_status['final_message']) ? nl2br($purchase_status['final_message']) : null;
            $description = isset($purchase_status['description']) ? nl2br($purchase_status['description']) : null;
            $status_messages = null;
        }

        include( TT_ABSPATH . '/admin/partials/order/two-tap-info.php' );
    }

    /**
     * Define custom columns for orders.
     * @param  array $existing_columns
     * @return array
     */
    public function shop_order_columns( $existing_columns ) {
        $order_actions = $existing_columns['order_actions'];
        unset($existing_columns['order_actions']);
        $existing_columns['twotap'] = '<span class="order-twotap tips dashicons dashicons-before dashicons-twotap_circle" data-tip="Two Tap Order"></span>';
        $existing_columns['order_actions'] = $order_actions;

        return $existing_columns;
    }

    public function render_shop_order_columns($column)
    {
        switch ( $column ) {
            case 'twotap' :
                global $post;
                $post_id = $post->ID;
                $order = wc_get_order($post_id);
                $response = '';
                if($order){
                    $tt_products_count = count(get_twotap_products_in_order($order));
                    // d($tt_products_count, $order->get_items(), $post_id);
                    if($tt_products_count > 0){
                        $purchase = get_twotap_purchase_by_order_id($order->get_id());
                        if($purchase){
                            $purchase_status = get_post_meta($purchase->ID, TT_META_TWOTAP_LAST_STATUS, true);
                            // d($purchase, $order->get_id());
                            if($purchase && $purchase_status){
                                $status = twotap_pretty_status($purchase_status['message']);
                                $message = '';
                                if(isset($purchase_status['description'])){
                                    $message = $purchase_status['description'];
                                }
                                if(isset($purchase_status['final_message'])){
                                    $message = $purchase_status['final_message'];
                                }
                                // d($purchase_status);
                                $emoji = '✅';

                                if($purchase_status['message'] == 'has_failures'){
                                    $emoji = '❗️';
                                }

                                $response .= "<a href=\"javascript:void(0)\" target=\"_blank\"><span class=\"note-on tips\" data-tip=\"{$tt_products_count} Two Tap product".($tt_products_count == 1 ? '' : 's')."\">{$emoji}</span></a>";
                                if($status){
                                    $response .= " <span target=\"_blank\"><span class=\"note-on tips\" data-tip=\"{$message}\">{$status}</span>";
                                }
                            }
                        }
                    }
                } else {
                    $response = "<span class=\"note-on tips\" data-tip=\"No Two Tap products\">0️⃣ </span>";
                }
                echo $response;
                break;
        }
    }

    public function taxonomies()
    {
        $labels = array(
            'name'                       => _x( 'Two Tap Orders', 'taxonomy general name', 'textdomain' ),
            'singular_name'              => _x( 'Two Tap Order', 'taxonomy singular name', 'textdomain' ),
            'search_items'               => __( 'Search Two Tap Orders', 'textdomain' ),
            'popular_items'              => __( 'Popular Two Tap Orders', 'textdomain' ),
            'all_items'                  => __( 'All Two Tap Orders', 'textdomain' ),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __( 'Edit Two Tap Order', 'textdomain' ),
            'update_item'                => __( 'Update Two Tap Order', 'textdomain' ),
            'add_new_item'               => __( 'Add New Two Tap Order', 'textdomain' ),
            'new_item_name'              => __( 'New Two Tap Order Name', 'textdomain' ),
            'separate_items_with_commas' => __( 'Separate Two Tap Orders with commas', 'textdomain' ),
            'add_or_remove_items'        => __( 'Add or remove Two Tap Orders', 'textdomain' ),
            'choose_from_most_used'      => __( 'Choose from the most used Two Tap Orders', 'textdomain' ),
            'not_found'                  => __( 'No Two Tap Orders found.', 'textdomain' ),
            'menu_name'                  => __( 'Two Tap Orders', 'textdomain' ),
        );
        $args = array(
            'hierarchical'      => false,
            'labels'            => $labels,
            'show_ui'           => false,
            'show_admin_column' => false,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => TT_TERM_SLUG_ORDER ),
        );
        register_taxonomy(TT_TERM_TAXONOMY_ORDER, TT_POST_TYPE_ORDER , $args);
    }

    public function subsubsub_edit($views)
    {

        if( ( is_admin() ) && ( $_GET['post_type'] == TT_POST_TYPE_ORDER ) ) {
        $twotap_order_term = get_term_by('slug', TT_TERM_SLUG_ORDER, TT_TERM_TAXONOMY_ORDER);
        if($twotap_order_term){
            $count = $twotap_order_term->count;
        }

         $class = (isset($_GET[TT_TERM_TAXONOMY_ORDER]) && $_GET[TT_TERM_TAXONOMY_ORDER] == TT_TERM_SLUG_ORDER) ? ' class="current"' : '';

         $views[TT_TERM_TAXONOMY_ORDER] = sprintf(__('<a href="%s"'. $class .'>'. TT_TERM_TITLE_ORDER .' <span class="count">(%d)</span></a>', TT_TERM_TITLE_ORDER ), admin_url('edit.php?post_type='.TT_POST_TYPE_ORDER.'&'.TT_TERM_TAXONOMY_ORDER.'='.TT_TERM_SLUG_ORDER), $count);

        }

        return $views;
    }
}
