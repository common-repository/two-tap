<?php
    if ( ! defined( 'ABSPATH' ) ) {
      exit;
    }
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Two Tap Products</h1>

    <div id="poststuff">
        <import-products inline-template>
            <div class="import-products-component" :class="{'is-searching': searching}">
                <div class="row">
                    <div class="col-sm-9">
                        <div class="row row-search">
                            <div class="col-sm-12">
                                <div class="panel panel-default">
                                    <div class="panel-heading">Search for products <small v-if="hasQueryFilters && !searching">(filtered {{ totalProducts }} products)</small> <a href="javascript:void(0)" class="pull-right" if="hasQueryFilters && !searching" @click="openProductsImportModal">Import filtered products</a></div>
                                    <div class="panel-body">
                                        <div class="keyword-search">
                                            <input type="text" class="regular-text" v-model="queryFilters.keywords" @keyup.enter="keywordSearch"/>
                                            <button class="button button-large" @click.prevent="keywordSearch">Search</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- products row -->
                        <div class="row row-products">
                            <div class="col-sm-12">
                                <div class="row" v-show="!hasProducts">
                                    <div class="col-sm-12">No products. Please try another search</div>
                                </div>
                                <div v-show="hasProducts">
                                    <div class="row" v-for="productGroup in products">
                                        <div class="col-sm-3" v-for="product in productGroup" :key="product.md5">
                                            <div class="product" :class="{'product-added' : product.product_added}" :data-product-md5="product.md5" :data-site-id="product.site_id">
                                                <div class="product-img">
                                                    <img class="img-responsive" v-lazy="product.image"/>
                                                </div>
                                                <div class="product-body">
                                                    <a :href="product.url" class="product-title" v-text="product.title" target="_blank"></a>
                                                    <div class="product-brand" v-text="product.brand"></div>
                                                    <div class="product-price" v-if="!product.discount_percent" v-text="product.price"></div>
                                                    <div class="product-price" v-if="product.discount_percent"><strike class="text-muted" v-text="product.original_price"></strike> -<span v-text="product.discount_percent"></span>% <br> <ins v-text="product.price"></ins> </div>
                                                    <div class="clear"></div>
                                                </div>
                                                <div class="product-footer">
                                                    <button class="button button-small button-block" @click="addProductToShop(product, $event)" :disabled="product.product_added" v-text="addProductButtonLabel(product)"></button>
                                                    <div class="clear"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- pagination row -->
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="js-product-pagination"></div>
                                        <div class="per-page-group">
                                            <div>Per page</div>
                                            <div class="btn-group" role="group" aria-label="Per page group">
                                                <button type="button" class="btn btn-default btn-sm" v-for="option in perPageOptions" :class="{'active': perPage == option}" @click="changePerPage(option)" v-text="option"></button>
                                            </div>
                                        </div>
                                        <div class="sort-group">
                                            <div>Sort</div>
                                            <div class="btn-group" role="group" aria-label="Sort group">
                                                <button type="button" class="btn btn-default btn-sm" :class="{'active': sort == 'price_asc'}" @click="changeSort('price_asc')">Price ascending</button>
                                                <button type="button" class="btn btn-default btn-sm" :class="{'active': sort == 'price_desc'}" @click="changeSort('price_desc')">Price descending</button>
                                                <button type="button" class="btn btn-default btn-sm" :class="{'active': sort == 'on_sale_desc'}" @click="changeSort('on_sale_desc')">On sale</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="col-sm-3">
                        <div class="row query-filter query-filter-categories" :class="{ 'query-filter-large': hideQueryFilters }" v-if="showQueryFilter('categories')">
                            <div class="col-sm-12">
                                <div class="panel panel-primary">
                                    <div class="panel-heading hand-cursor" @click="toggleFilterSection('categories')"><span class="dashicons" :class="filtersSectionIcon('categories')"></span> Categories</div>
                                    <div class="panel-body" v-show="filterSectionOpen('categories')">
                                        <ul class="list-unstyled categories-list">
                                            <li v-for="category in filters.categories" :key="category.full_name">
                                                <category-item :name="category.full_name" :category="category" :query-filters.sync="queryFilters"></category-item>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row query-filter query-filter-promotions" v-if="showQueryFilter('promotions')">
                            <div class="col-sm-12">
                                <div class="panel panel-default">
                                    <div class="panel-heading hand-cursor" @click="toggleFilterSection('promotions')"><span class="dashicons" :class="filtersSectionIcon('promotions')"></span> Promotions ({{ filtersCount('promotions') }})</div>
                                    <div class="panel-body" v-show="filterSectionOpen('promotions')">
                                        <ul class="list-unstyled">
                                            <li v-for="promotion in filters.promotions"><label><input type="checkbox" :checked="hasFilter('promotions', promotion.name)" @change="filterChange('promotions', promotion.name)"> {{ prettyPromotionName(promotion) }}</label></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row query-filter query-filter-brands" v-if="showQueryFilter('brands')">
                            <div class="col-sm-12">
                                <div class="panel panel-default">
                                    <div class="panel-heading hand-cursor" @click="toggleFilterSection('brands')"><span class="dashicons" :class="filtersSectionIcon('brands')"></span> Brands ({{ filtersCount('brands') }})</div>
                                    <div class="panel-body" v-show="filterSectionOpen('brands')">
                                        <ul class="list-unstyled">
                                            <li v-for="brand in filters.brands"><label><input type="checkbox" :checked="hasFilter('brands', brand.name)" @change="filterChange('brands', brand.name)"> {{ prettyBrandName(brand) }}</label></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row query-filter query-filter-genders" v-if="showQueryFilter('genders')">
                            <div class="col-sm-12">
                                <div class="panel panel-default">
                                    <div class="panel-heading hand-cursor" @click="toggleFilterSection('genders')"><span class="dashicons" :class="filtersSectionIcon('genders')"></span> Genders ({{ filtersCount('genders') }})</div>
                                    <div class="panel-body" v-show="filterSectionOpen('genders')">
                                        <ul class="list-unstyled">
                                            <li v-for="gender in filters.genders"><label><input type="checkbox" :checked="hasFilter('genders', gender.name)" @change="filterChange('genders', gender.name)"> {{ prettyGenderName(gender) }}</label></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row query-filter query-filter-sizes" v-if="showQueryFilter('sizes')">
                            <div class="col-sm-12">
                                <div class="panel panel-default">
                                    <div class="panel-heading hand-cursor" @click="toggleFilterSection('sizes')"><span class="dashicons" :class="filtersSectionIcon('sizes')"></span> Sizes ({{ filtersCount('sizes') }})</div>
                                    <div class="panel-body" v-show="filterSectionOpen('sizes')">
                                        <ul class="list-unstyled">
                                            <li v-for="size in filters.sizes"><label><input type="checkbox" :checked="hasFilter('sizes', size.name)" @change="filterChange('sizes', size.name)"> {{ prettySizeName(size) }}</label></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row query-filter query-filter-site_ids" v-if="showQueryFilter('site_ids')">
                            <div class="col-sm-12">
                                <div class="panel panel-default">
                                    <div class="panel-heading hand-cursor" @click="toggleFilterSection('site_ids')"><span class="dashicons" :class="filtersSectionIcon('site_ids')"></span> Sites ({{ filtersCount('site_ids') }})</div>
                                    <div class="panel-body" v-show="filterSectionOpen('site_ids')">
                                        <ul class="list-unstyled">
                                            <li v-for="site_id in filters.site_ids"><label><input type="checkbox" :checked="hasFilter('site_ids', site_id.name)" @change="filterChange('site_ids', site_id.name)"> {{ prettySiteName(site_id) }}</label></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="panel panel-info">
                                    <div class="panel-body">
                                        <button class="btn btn-default" @click="resetFiltersAction" :disabled="!hasQueryFilters">Reset filters</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="clear"></div>

                <div class="js-import-modal modal fade" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title">Import filtered products</h4>
                            </div>
                            <div class="modal-body">
                                <p>Pressing the 'Add products to import queue' button downloads the products from the selected category to the WordPress queueing system importing them in an asynchronous fashion.</p>

                                <button class="button button-primary button-large button-centered" @click="importFilteredProducts" :disabled="importButtonDisabled"><span class="dashicons dashicons-download"></span> Add products to import queue</button>
                                <br>

                                <div v-show="importProgressVisible">
                                    <h3 class="text-center">* Please don't close this modal until the progress bar is filled</strong>
                                </div>
                                <div class="progress" v-show="importProgressVisible">
                                    <div class="progress-bar progress-bar-striped active" role="progressbar" :aria-valuenow="importProgressPercent" aria-valuemin="0" aria-valuemax="100" :style="importProgressbarStyle">
                                    {{ importProgressLabel }}
                                    </div>
                                </div>

                                <p class="description">
                                    Two Tap Pro Tip: Don't important tens of thousands of products. Too much variation can hurt conversion rates. Find the products or category of products that your consumers are interested in buying.
                                </p>
                            </div>
                        </div><!-- /.modal-content -->
                    </div><!-- /.modal-dialog -->
                </div><!-- /.modal -->

            <div class="searching-backdrop" :class="{'in': searching}"><div class="spinner is-active center-block"></div></div>
            </div>
        </import-products>
        <!-- #post-body .metabox-holder .columns-2 -->
        <br class="clear">
    </div>
    <!-- #poststuff -->
</div>
<!-- .wrap -->
