<?php
    if ( ! defined( 'ABSPATH' ) ) {
      exit;
    }
    global $tt_product;
?>
<?php /*
<pre>

<?php
    var_dump(get_option( 'cron' ));
 ?>
</pre>
*/ ?>

<div class="wrap">
    <div class="js-notices"></div>
    <h1 class="wp-heading-inline">Two Tap</h1>

    <h2>Sync products</h2>

    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <!-- main content -->
            <div id="post-body-content">
                <div class="meta-box-sortables ui-sortable">
                    <div class="postbox">
                        <h2><span><?php esc_attr_e( 'Products' ); ?></span> <span class="js-for-current-category-label"></span></h2>
                        <div class="inside js-current-category-contents">
                            <button class="button js-sync-products">Sync</button>
                        <br>
                        <br>
                        <strong>Products refresh remaining:</strong> <span class="js-product-count"><?= $tt_product->product_refresh_jobs_remaining(); ?></span>
                        </div>
                        <!-- .inside -->
                    </div>
                    <!-- .postbox -->
                </div>
                <!-- .meta-box-sortables .ui-sortable -->
            </div>
            <!-- post-body-content -->

            <!-- sidebar -->
            <div id="postbox-container-1" class="postbox-container">
                <div class="meta-box-sortables">
                    <div class="postbox">
                        <h2><span><?php esc_attr_e(
                                    'Categories', 'wp_admin_style'
                                ); ?></span></h2>
                        <div class="inside">
                            <button class="button action js-get-categories">Get categories</button>
                            <ul class="js-tt-categories">
                                <li><a href="javascript:void(0)" class="js-expand-category" data-category="Apparel &amp; Accessories">Apparel &amp; Accessories [339039]</a></li>
                                <li>no categories loaded yet</li>
                            </ul>
                        </div>
                        <!-- .inside -->
                    </div>
                    <!-- .postbox -->
                </div>
                <!-- .meta-box-sortables -->
            </div>
            <!-- #postbox-container-1 .postbox-container -->
        </div>
        <!-- #post-body .metabox-holder .columns-2 -->
        <br class="clear">
    </div>
    <!-- #poststuff -->
</div>
<!-- .wrap -->




<script type="text/javascript">
    var $ = jQuery;
    var App = {
        // data
        categories: {}, // all categories with product count
        currentCategory: null, // current category
        products: [], // temporarely holding the products
        products: <?php get_twotap_template('/../sample/index-page-json.php') ?>,

        // methods

        alert: function(message, type){
            if (_.isUndefined(type)){
                type = 'info';
            }
            var rand = getRandomInt(100000, 999999);
            var template = '<div class="notice notice-'+type+'" data-random="'+rand+'"><p>'+message+'</p></div>';
            var $notices = $('.js-notices');
            $notices.html(template);
            setTimeout(function(){
                $('.notice[data-random="'+rand+'"]').fadeOut();
                setTimeout(function(){
                    $notices.slideUp()
                    setTimeout(function(){
                        $notices.html('');
                    },400)
                },100)
            },5000)
        },

        // products

        syncProducts: function(){
            var data = {}
            data.action = 'twotap_sync_products';

            return $.post(ajaxurl, data, function(response){
                console.log(response);
                if( !_.isUndefined(response.message) ){
                    App.alert(response.message);
                }
                if( !_.isUndefined(response.product_count) ){
                    $('.js-product-count').text(response.product_count);
                }
            });
        }

    }

    jQuery(document).ready(function($) {
        $(document).on('click', '.js-sync-products', App.syncProducts);
    });

    /**
     * utils
     */
    function getRandomInt(min, max) {
      return Math.floor(Math.random() * (max - min + 1)) + min;
    }
</script>
<script src="<?=plugins_url('twotap/js/lodash.min.js')?>"></script>