<?php

/**
 * TT_Admin class.
 */
class Two_Tap_Cart_Post_Type
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

        do_action( 'two_tap_cart_post_type_loaded' );

    }

    public function cart_post_type()
    {
        $labels = array(
            'name'                  => _x( 'Carts', 'Post type general name', 'textdomain' ),
            'singular_name'         => _x( 'Cart', 'Post type singular name', 'textdomain' ),
            'menu_name'             => _x( 'Carts', 'Admin Menu text', 'textdomain' ),
            'name_admin_bar'        => _x( 'Cart', 'Add New on Toolbar', 'textdomain' ),
            'add_new'               => __( 'Add New', 'textdomain' ),
            'add_new_item'          => __( 'Add New Cart', 'textdomain' ),
            'new_item'              => __( 'New Cart', 'textdomain' ),
            'edit_item'             => __( 'View Cart', 'textdomain' ),
            'view_item'             => __( 'View Cart', 'textdomain' ),
            'all_items'             => __( 'All Carts', 'textdomain' ),
            'search_items'          => __( 'Search Carts', 'textdomain' ),
            'parent_item_colon'     => __( 'Parent Carts:', 'textdomain' ),
            'not_found'             => __( 'No carts found.', 'textdomain' ),
            'not_found_in_trash'    => __( 'No carts found in Trash.', 'textdomain' ),
        );

        $args = array(
            'labels'               => $labels,
            'public'               => false,
            'publicly_queryable'   => false,
            'show_ui'              => true,
            'show_in_menu'         => false,
            'query_var'            => true,
            'rewrite'              => array( 'slug' => TT_POST_TYPE_CART ),
            'capability_type'      => ['tt_cart', 'tt_carts'],
            'has_archive'          => true,
            'hierarchical'         => false,
            'supports'             => array( 'title' ),
            'register_meta_box_cb' => [$this, 'cart_info_meta_boxes'],
            'capabilities' => array(
                'create_posts' => false,
                'delete_posts' => false,
                'delete_others_posts' => false,
                'delete_private_posts' => false,
                'delete_published_posts' => false,
            ),
            'map_meta_cap' => true,
        );

        register_post_type( TT_POST_TYPE_CART, $args );
    }

    public function cart_statuses()
    {
        register_post_status( 'tt-done', array(
            'label'                     => _x( 'Done', TT_POST_TYPE_CART ),
            'public'                    => true,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Done <span class="count">(%s)</span>', 'Done <span class="count">(%s)</span>' ),
        ) );
        register_post_status( 'tt-still_processing', array(
            'label'                     => _x( 'Still processing', TT_POST_TYPE_CART ),
            'public'                    => true,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Still processing <span class="count">(%s)</span>', 'Still processing <span class="count">(%s)</span>' ),
        ) );
        register_post_status( 'tt-has_failures', array(
            'label'                     => _x( 'Has failures', TT_POST_TYPE_CART ),
            'public'                    => true,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Has failures <span class="count">(%s)</span>', 'Has failures <span class="count">(%s)</span>' ),
        ) );
    }

    public function tt_cart_posts_columns($existing_columns) {
        $columns = [];
        $columns['cb'] = $existing_columns['cb'];
        $columns['cart_title'] = 'Cart';
        $columns['cart_status'] = 'Status';
        $columns['date'] = $existing_columns['date'];
        return $columns;
    }

    // SHOW THE FEATURED IMAGE
    public function render_tt_cart_posts_columns($column)
    {
        global $post;
        $cart_status = get_post_meta($post->ID, TT_META_TWOTAP_LAST_STATUS, true);

        switch ( $column ) {
            case 'cart_title':
            ?>
                <strong><a class="row-title" href="/wp-admin/post.php?post=<?=$post->ID?>&amp;action=edit" aria-label="“<?=$post->title?>” (Edit)"><?=$post->post_title?></a></strong>
            <?php
            break;
            case 'cart_status':
            if($cart_status['message'] != 'still_processing'){
                $sites_count = count($cart_status['sites']);
                // $failed_to_add_to_cart_count = count($cart_status['failed_to_add_to_cart']);
                $products_count = array_sum(array_map(function($site)
                {
                    if(!isset($site['add_to_cart'])){
                        return 0;
                    }
                    return count($site['add_to_cart']);
                }, $cart_status['sites']));
            }
            ?>
                <span title="<?=$cart_status['description']?>"><?=$this->cart_status_icom($cart_status)?> <?=$cart_status['message']?></span><br>
                <?php if(isset($product_count)): ?>
                    <?=$product_count?> products
                <?php endif; ?>

            <?php
            break;
        }
    }

    public function cart_info_meta_boxes()
    {
        remove_meta_box( 'submitdiv', TT_POST_TYPE_CART, 'side' );
        remove_meta_box( 'slugdiv', TT_POST_TYPE_CART, 'normal' );
        add_meta_box('cart_products', 'Info', [$this, 'cart_products_meta_box_html'], TT_POST_TYPE_CART, 'normal', 'default');
        add_meta_box('cart_info', 'Cart info', [$this, 'cart_info_meta_box_html'], TT_POST_TYPE_CART, 'side', 'default');
    }

    public function cart_info_meta_box_html()
    {
        global $post;
        $cart_status = get_post_meta($post->ID, TT_META_TWOTAP_LAST_STATUS, true);

        $message = $cart_status['message'];
        if($message == 'done'){
            $cart_done = true;
        } else {
            $cart_done = false;
        }
        if($cart_done){
            $sites = $cart_status['sites'];
            $notes = json_decode($cart_status['notes'], true);
            $order_id = isset($notes[TT_META_ORDER_ID]) ? $notes[TT_META_ORDER_ID] : null;
        }
        $message = isset($cart_status['message']) ? $cart_status['message'] : null;
        $final_message = isset($cart_status['final_message']) ? nl2br($cart_status['final_message']) : null;
        $description = isset($cart_status['description']) ? nl2br($cart_status['description']) : null;
        $unknown_urls = isset($cart_status['unknown_urls']) ? ($cart_status['unknown_urls']) : null;

        ?>
        <?php if(isset($order_id)): ?>
            <strong>WooCommerce Order ID: </strong><?=$order_id
        ?>
        <br>
        <?php endif; ?>
        <?php if($final_message): ?>
        <strong>Final message: </strong><?=$final_message?>
        <br>
        <?php endif; ?>
        <?php if($message): ?>
        <strong>Message: </strong><?=$message?>
        <br>
        <?php endif; ?>
        <?php if($description): ?>
        <strong>Description: </strong><?=$description?>
        <br>
        <?php endif; ?>
        <?php if($unknown_urls): ?>
        <strong class="text-danger">Unknown URLS: </strong><?=implode(', ', $unknown_urls)?>
        <br>
        <?php endif; ?>
        <?php

    }
    public function cart_products_meta_box_html()
    {
        global $post;
        $cart_status = get_post_meta($post->ID, TT_META_TWOTAP_LAST_STATUS, true);
        $cart_products = get_post_meta($post->ID, 'twotap_cart_products', true);
        $cart_id = isset($cart_status['cart_id']) ? $cart_status['cart_id'] : null;
        $message = $cart_status['message'];

        if($message == 'done'){
            $cart_done = true;
        } else {
            $cart_done = false;
        }

        $sites = isset($cart_status['sites']) ? $cart_status['sites'] : null;
        ?>
        <?php if(isset($cart_id)): ?>
            <h1>Cart ID: <?=$cart_id?></h1>
            <br>
        <?php endif; ?>

        <?php if(!is_null($sites)) : ?>

            <table class="tt-products-table widefat">
                <thead>
                    <tr>
                        <th class="row-title" width="100">Image</th>
                        <th class="row-title">Title</th>
                        <th class="row-title">Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        foreach ($sites as $site_id => $site_response) {

                            if(isset($site_response['add_to_cart']) && count($site_response['add_to_cart']) > 0){
                            foreach ($site_response['add_to_cart'] as $product_md5 => $product) {
                                $url = $product['original_url'];
                                $possible_product_md5 = md5($product['original_url']);
                                $cart_product = isset($cart_products[$possible_product_md5]) ? $cart_products[$possible_product_md5] : null;
                                $wc_product = wc_get_product($cart_product['product_id']);

                                $parent_id = null;
                                // d($wc_product, $cart_product['product_id']);
                                if(isset($wc_product) && $wc_product){
                                    if($wc_product->is_type('variation')){
                                        $parent_id = $wc_product->get_parent_id();
                                    } else {
                                        $parent_id = $wc_product->get_id();
                                    }
                                }
                        ?>
                        <tr>
                            <td><img src="<?=$product['image']?>" style="max-height: 60px;"></td>
                            <td>
                                <?php if($wc_product):?>
                                    <a href="<?=get_edit_post_link($parent_id)?>" target="_blank"><?=$product['title']?></a>
                                <?php else:?>
                                    <?=$product['title']?>
                                <?php endif;?>
                                <br>
                                <small>
                                site_id: <?=$site_id?>
                                <br />
                                product_md5: <?=$product_md5?>
                                <?php if(isset($cart_products[$product_md5])): ?>
                                    <?php foreach($cart_products[$product_md5]['chosen_attributes'] as $att_key => $att_value ): ?>
                                    <br>
                                    <?=$att_key?>: <?=$att_value?>
                                    <?php endforeach;?>
                                <?php endif;?>
                                </small>
                            </td>
                            <td><?=$product['price']?></td>
                        </tr>
                        <?php
                            }
                        }
                        if(isset($site_response['failed_to_add_to_cart']) && count($site_response['failed_to_add_to_cart']) > 0){

                            foreach ($site_response['failed_to_add_to_cart'] as $product_md5 => $product) {
                                // dump($product);
                                // $db_product = get_product_by_site_id_and_product_md5($site_id, $product_md5);
                                // dump($db_product);
                        ?>
                        <tr>
                            <td><img src="<?=$product['image']?>" style="max-height: 50px;"></td>
                            <td>
                                'OOS product'
                                <br>
                                <small>
                                    url: <?=$product['url']?>
                                    <br>
                                    site_id: <?=$site_id?>
                                    <br>
                                    product_md5: <?=$product_md5?>
                                    <br>
                                    reason: <strong><?=$product['status_reason']?></strong>
                                </small>
                            </td>
                            <td><?=$product['price']?></td>
                        </tr>
                        <?php
                            }
                        }
                    } ?>
                </tbody>
            </table>

        <?php endif; ?>

        <?php
    }

    public function cart_status_icom($cart_status = null)
    {
        if(is_null($cart_status)){
            return;
        }
        switch ($cart_status['message']) {
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