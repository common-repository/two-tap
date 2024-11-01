<?php

/**
 * TT_Admin class.
 */
class Two_Tap_Product_Post_Type
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

        // do_action( 'two_tap_product_post_type_loaded' );

    }

    public function product_statuses()
    {
        register_post_status( 'tt-processing', array(
            'label'                     => _x( 'Processing', TT_POST_TYPE_PRODUCT ),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => false,
            'show_in_admin_status_list' => false,
            'label_count'               => _n_noop( 'Processing <span class="count">(%s)</span>', 'Processing <span class="count">(%s)</span>' ),
        ) );
        register_post_status( 'tt-unprocessed', array(
            'label'                     => _x( 'Unprocessed', TT_POST_TYPE_PRODUCT ),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => false,
            'show_in_admin_status_list' => false,
            'label_count'               => _n_noop( 'Unprocessed <span class="count">(%s)</span>', 'Unprocessed <span class="count">(%s)</span>' ),
        ) );
    }

    public function taxonomies()
    {
        $labels = array(
            'name'                       => _x( 'Two Tap Products', 'taxonomy general name', 'textdomain' ),
            'singular_name'              => _x( 'Two Tap Product', 'taxonomy singular name', 'textdomain' ),
            'search_items'               => __( 'Search Two Tap Products', 'textdomain' ),
            'popular_items'              => __( 'Popular Two Tap Products', 'textdomain' ),
            'all_items'                  => __( 'All Two Tap Products', 'textdomain' ),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __( 'Edit Two Tap Product', 'textdomain' ),
            'update_item'                => __( 'Update Two Tap Product', 'textdomain' ),
            'add_new_item'               => __( 'Add New Two Tap Product', 'textdomain' ),
            'new_item_name'              => __( 'New Two Tap Product Name', 'textdomain' ),
            'separate_items_with_commas' => __( 'Separate Two Tap Products with commas', 'textdomain' ),
            'add_or_remove_items'        => __( 'Add or remove Two Tap Products', 'textdomain' ),
            'choose_from_most_used'      => __( 'Choose from the most used Two Tap Products', 'textdomain' ),
            'not_found'                  => __( 'No Two Tap Products found.', 'textdomain' ),
            'menu_name'                  => __( 'Two Tap Products', 'textdomain' ),
        );
        $args = array(
            'hierarchical'      => false,
            'labels'            => $labels,
            'show_ui'           => false,
            'show_admin_column' => false,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => TT_TERM_SLUG_PRODUCT ),
        );
        register_taxonomy(TT_TERM_TAXONOMY_PRODUCT, TT_POST_TYPE_PRODUCT , $args);
    }

    public function subsubsub_edit($views)
    {
        if( ( is_admin() ) && ( $_GET['post_type'] == TT_POST_TYPE_PRODUCT ) ) {

        $twotap_product_term = get_term_by('slug', TT_TERM_SLUG_PRODUCT, TT_TERM_TAXONOMY_PRODUCT);
        $count = 0;
        if($twotap_product_term){
            $count = $twotap_product_term->count;
        }

         $class = (isset($_GET[TT_TERM_TAXONOMY_PRODUCT]) && $_GET[TT_TERM_TAXONOMY_PRODUCT] == TT_TERM_SLUG_PRODUCT) ? ' class="current"' : '';

         $views[TT_TERM_TAXONOMY_PRODUCT] = sprintf(__('<a href="%s"'. $class .'>'. TT_TERM_TITLE_PRODUCT .' <span class="count">(%d)</span></a>', TT_TERM_TITLE_PRODUCT ), admin_url('edit.php?post_status=publish&post_type='.TT_POST_TYPE_PRODUCT.'&'.TT_TERM_TAXONOMY_PRODUCT.'='.TT_TERM_SLUG_PRODUCT), $count);
         $views['publish'] = str_replace(' class="current"', '', $views['publish']);

        }

        return $views;
    }

    /**
     * Define custom columns for orders.
     * @param  array $existing_columns
     * @return array
     */
    public function product_columns( $existing_columns ) {
        $date = $existing_columns['date'];
        unset($existing_columns['date']);
        $existing_columns['twotap'] = '<span class="order-twotap tips dashicons dashicons-before dashicons-twotap_circle" data-tip="Two Tap Order"></span>';
        $existing_columns['date'] = $date;

        return $existing_columns;
    }

    public function render_product_columns($column)
    {
        switch ( $column ) {
            case 'twotap' :
                global $post;
                $post_id = $post->ID;
                if(has_term(TT_TERM_SLUG_PRODUCT, TT_TERM_TAXONOMY_PRODUCT, $post_id)){
                    echo "<span class=\"note-on tips\" data-tip=\"This is a Two Tap product\">✅</span>";
                        return;
                }
                echo "<span class=\"note-on tips\" data-tip=\"This is not a Two Tap product\">❌</span>";
                break;
        }
    }

    public function meta_boxes($post_type, $post)
    {
        add_meta_box('twotap_options', '<span class="icon-two-tap-logo"></span> Two Tap Options', [$this, 'twotap_options_meta_box_html'], TT_POST_TYPE_PRODUCT, 'side', 'default');

        wp_enqueue_script( $this->plugin_name . 'two-tap-options', TT_PLUGIN_URL . '/public/js/partials/product/two-tap-options.js', array( 'jquery' ), $this->version, true );

        if( $post_type == TT_POST_TYPE_PRODUCT ){
            wp_localize_script( $this->plugin_name . 'two-tap-options', 'tt_product_vars', [
                'woocommerce_currency' => get_woocommerce_currency(),
                'woocommerce_currency_symbol' => get_woocommerce_currency_symbol(),
                'post_id' => $post->ID,
                TT_META_TWOTAP_PRODUCT_MD5 => get_post_meta($post->ID, TT_META_TWOTAP_PRODUCT_MD5, true),
                TT_META_TWOTAP_SITE_ID => get_post_meta($post->ID, TT_META_TWOTAP_SITE_ID, true),
            ]);
        }

    }

    public function twotap_options_meta_box_html()
    {
        global $post;
        // Noncename needed to verify where the data originated
        echo '<input type="hidden" name="twotap_product_meta_nonce" id="twotap_product_meta_nonce" value="' .
        wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

        $has_custom_markup = get_post_meta($post->ID, 'twotap_custom_markup', true) == 1;
        $custom_markup_type = get_post_meta($post->ID, 'twotap_markup_type', true);
        $custom_markup_value = get_post_meta($post->ID, 'twotap_markup_value', true);

        include(TT_ABSPATH . '/admin/partials/product/two-tap-options.php');
    }

    function save_twotap_options($post_id, $post) {
        if (!isset($_POST['twotap_product_meta_nonce']) || !wp_verify_nonce( $_POST['twotap_product_meta_nonce'], plugin_basename(__FILE__) )) {
            return $post->ID;
        }

        // Is the user allowed to edit the post or page?
        if ( !current_user_can( 'edit_post', $post->ID ))
            return $post->ID;

        // OK, we're authenticated: we need to find and save the data
        // We'll put it into an array to make it easier to loop though.
        $product_meta = [];
        $product_meta['twotap_custom_markup'] = $_POST['twotap_custom_markup'];
        $product_meta['twotap_markup_type'] = $_POST['twotap_markup_type'];
        $product_meta['twotap_markup_value'] = $_POST['twotap_markup_value'];
        $product_meta['twotap_markup_percent'] = $_POST['twotap_markup_percent'];
        $product_meta['twotap_custom_price'] = $_POST['twotap_custom_price'];
        $product_meta['twotap_custom_title'] = $_POST['twotap_custom_title'];
        $product_meta['twotap_custom_description'] = $_POST['twotap_custom_description'];

        // Cycle through the $product_meta array!
        foreach ($product_meta as $key => $value) {
            if( $post->post_type == 'revision' ) {
                return; // Don't store custom data twice
            }

            $value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)

            if(get_post_meta($post->ID, $key, FALSE)) {
                // If the custom field already has a value
                update_post_meta($post->ID, $key, $value);
            } else {
                // If the custom field doesn't have a value
                add_post_meta($post->ID, $key, $value);
            }
            if(!$value){
                // Delete if blank
                delete_post_meta($post->ID, $key);
            }
        }

    }

}
