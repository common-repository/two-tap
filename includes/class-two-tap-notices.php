<?php
/**
 * Main TwoTap Class.
 *
 * @class TwoTap
 * @version 1.0.0
 */
final class Two_Tap_Notices {

    protected $option_name = 'twotap_notices';

    protected $notices = [];

    public function __construct()
    {
        $this->init();

        do_action( 'two_tap_notices_loaded' );
    }

    public function init()
    {
        $this->notices = get_option($this->option_name, []);
    }

    public function get_notices()
    {
        return $this->notices;
    }

    protected function set_notices($notices)
    {
        if(!is_array($notices)){
            return false;
        }
        return update_option($this->option_name, $notices);
    }

    public function clear_notices()
    {
        return update_option($this->option_name, []);
    }

    public function add_notice($name = null, $message = '', $type = 'info', $dismissible = false)
    {
        $notices = $this->get_notices();
        if(is_null($name)){
            $name = 'twotap_notice_' . rand(10000, 99999);
        }
        $this->notices[] = [
            'name' => $name,
            'message' => $message,
            'type' => $type,
            'dismissible' => $dismissible,
        ];
    }

    public function render()
    {
        return implode('', array_map(function($notice){
                $rand = rand(10000, 99999);
                $classes = '';
                $classes .= 'notice';
                if(isset($notice['dismissible']) && $notice['dismissible']){
                    $classes .= ' is-dismissible';
                }
                $classes .= ' notice-'.$notice['type'];
                $name = isset($notice['name']) ? $notice['name'] : '';
                return "<div class='{$classes}' data-name='{$name}' data-random=\"{$rand}\"><p>{$notice['message']}</p></div>";

            }, $this->get_notices()));
    }

}