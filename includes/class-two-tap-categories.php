<?php

/**
 * Main TwoTap Class.
 *
 * @class TwoTap
 * @version 1.0.0
 */
class Two_Tap_Categories {

    protected static $_instance = null;

    protected $categories = [];

    protected $menu = [];

    public function __construct()
    {
        $this->init_hooks();

        global $tt_api;
        $this->api = $tt_api;

        do_action( 'two_tap_categories_loaded' );
    }

    public function init_hooks()
    {
        add_action( 'wp_ajax_twotap_get_categories', array($this, 'get_categories') );
    }


    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function get_categories()
    {
        wp_send_json($this->getMenu());
        wp_die();
    }

    public function getMenu($categories = null)
    {

        if(is_null($categories)){
            // search the cache for the response
            $cached_categories = get_transient(TT_TRANSIENT_KEY_CATEGORIES);
            if($cached_categories){
                $this->categories = $cached_categories;
            } else {
                $api_response = $this->api->product()->filters();
                if($api_response['message'] == 'done'){
                    $this->categories = $api_response['categories'];
                    set_transient(TT_TRANSIENT_KEY_CATEGORIES, $api_response['categories'], HOUR_IN_SECONDS);
                } else {
                    $this->categories = [];
                }
            }
            $categories = $this->categories;
        // } else {

        }
        sort($categories);
        foreach ($categories as $category) {
            $this->addItem($category);
        }
        return $this->array_values_recursive(array_values($this->menu));
    }

    private function addItem($category)
    {
        if(strpos($category['name'], '~~')){
            $index = 0;
            $cats = explode('~~', $category['name']);
            $category['nested'] = $cats;
            $category['label'] = $this->endc($cats);
            foreach ($cats as $cat) {
                $this->addSubitem($category, $cat);
            }
        } else {
            $category['label'] = $category['name'];
            $this->menu[$category['name']]['full_name'] = $category['name'];
            $this->menu[$category['name']]['label'] = $this->makeName($category);
            $this->menu[$category['name']]['count'] = $category['count'];
        }

    }

    private function addSubitem($category, $cat)
    {
        $response = [];
        $nestedLevel = count($category['nested']);
        switch ($nestedLevel) {
            case 2:
                $this->menu[$category['nested'][0]]['children'][$category['label']] = [
                    'label' => $this->makeName($category),
                    'full_name' => $category['name'],
                    'count' => $category['count'],
                ];
                break;
            case 3:
                $this->menu[$category['nested'][0]]['children'][$category['nested'][1]]['children'][$category['label']] = [
                    'label' => $this->makeName($category),
                    'full_name' => $category['name'],
                    'count' => $category['count'],
                ];
                break;
            case 4:
                $this->menu[$category['nested'][0]]['children'][$category['nested'][1]]['children'][$category['nested'][2]]['children'][$category['label']] = [
                    'label' => $this->makeName($category),
                    'full_name' => $category['name'],
                    'count' => $category['count'],
                ];
                break;
            case 5:
                $this->menu[$category['nested'][0]]['children'][$category['nested'][1]]['children'][$category['nested'][2]]['children'][$category['nested'][3]]['children'][$category['label']] = [
                    'label' => $this->makeName($category),
                    'full_name' => $category['name'],
                    'count' => $category['count'],
                ];
                break;
            case 6:
                $this->menu[$category['nested'][0]]['children'][$category['nested'][1]]['children'][$category['nested'][2]]['children'][$category['nested'][3]]['children'][$category['nested'][4]]['children'][$category['label']] = [
                    'label' => $this->makeName($category),
                    'full_name' => $category['name'],
                    'count' => $category['count'],
                ];
                break;
            case 7:
                $this->menu[$category['nested'][0]]['children'][$category['nested'][1]]['children'][$category['nested'][2]]['children'][$category['nested'][3]]['children'][$category['nested'][4]]['children'][$category['nested'][5]]['children'][$category['label']] = [
                    'label' => $this->makeName($category),
                    'full_name' => $category['name'],
                    'count' => $category['count'],
                ];
                break;
            case 8:
                $this->menu[$category['nested'][0]]['children'][$category['nested'][1]]['children'][$category['nested'][2]]['children'][$category['nested'][3]]['children'][$category['nested'][4]]['children'][$category['nested'][5]]['children'][$category['nested'][6]]['children'][$category['label']] = [
                    'label' => $this->makeName($category),
                    'full_name' => $category['name'],
                    'count' => $category['count'],
                ];
                break;
            case 9:
                $this->menu[$category['nested'][0]]['children'][$category['nested'][1]]['children'][$category['nested'][2]]['children'][$category['nested'][3]]['children'][$category['nested'][4]]['children'][$category['nested'][5]]['children'][$category['nested'][6]]['children'][$category['nested'][7]]['children'][$category['label']] = [
                    'label' => $this->makeName($category),
                    'full_name' => $category['name'],
                    'count' => $category['count'],
                ];
                break;
            case 10:
                $this->menu[$category['nested'][0]]['children'][$category['nested'][1]]['children'][$category['nested'][2]]['children'][$category['nested'][3]]['children'][$category['nested'][4]]['children'][$category['nested'][5]]['children'][$category['nested'][6]]['children'][$category['nested'][7]]['children'][$category['nested'][8]]['children'][$category['label']] = [
                    'label' => $this->makeName($category),
                    'full_name' => $category['name'],
                    'count' => $category['count'],
                ];
                break;
        }
    }

    private function makeName($category)
    {
        $response = '';
        if(isset($category['label'])){
            $response .= $category['label'];
        }

        // if(isset($category['count'])){
        //     $response .= ' ['.$category['count'].']';
        // }
        return $response;

    }

    private function array_values_recursive($arr)
    {
        foreach ($arr as $key => $value)
        {
            if (is_array($value))
            {
                $arr[$key] = $this->array_values_recursive($value);
            }
        }

        if (isset($arr['children']))
        {
            $arr['children'] = array_values($arr['children']);
        }

        return $arr;
    }

    private function endc( $array ) { return end( $array ); }

}
