<?php

/**
 * Main TwoTap Class.
 *
 * @class TwoTap
 * @version 1.0.0
 */
final class Two_Tap_Mail {

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

        $this->define_constants();

        do_action( 'two_tap_mail_loaded' );
    }

    public function define_constants()
    {
        tt_define('TT_MAIL_TEMPLATE', TT_ABSPATH . '/admin/partials/mail-template.php');
    }


    public function send($type = '', $args = [])
    {
        switch ($type) {
            case 'insufficient_funds':
                return $this->send_insufficient_funds_notice($args);
                break;
            case 'logistics_settings_failed':
                return $this->send_logistics_settings_failed($args);
                break;
        }
    }

    public function send_insufficient_funds_notice($args)
    {
        $to = get_option('admin_email');
        $subject = 'A Two Tap purchase failed because you have insufficient funds';
        $body = 'There\'s a new Two Tap purchase and you have insufficient funds in your account. Please deposit funds.';
        $headers = 'Content-Type: text/html; charset=utf-8;';
        $attachments = [];

        $fields = [
            'title' => $subject,
            'greeting' => 'Hello there,',
            'body' => $body,
            'call_to_action_link' => site_url('/wp-admin/edit.php?post_type=shop_order'),
            'call_to_action_label' => 'Review orders',
            'closing' => 'Kind regards,',
        ];

        $template = file_get_contents(TT_MAIL_TEMPLATE);
        $template = $this->populate_template($fields, $template);

        return wp_mail($to, $subject, $template, $headers, $attachments);
    }

    public function send_logistics_settings_failed($args)
    {
        $to = get_option('admin_email');
        $subject = 'A Two Tap purchase failed because the logistics settings are invalid';
        $body = 'Please head to the plugin settings page and fix the invalid configuration.';
        $headers = 'Content-Type: text/html; charset=utf-8;';
        $attachments = [];

        $fields = [
            'title' => $subject,
            'greeting' => 'Hello there,',
            'body' => $body,
            'call_to_action_link' => site_url('/wp-admin/admin.php?page='.TT_SETTINGS_PAGE.'&tab=logistics'),
            'call_to_action_label' => 'Plugin settings page',
            'closing' => 'Kind regards,',
        ];

        $template = file_get_contents(TT_MAIL_TEMPLATE);
        $template = $this->populate_template($fields, $template);

        return wp_mail($to, $subject, $template, $headers, $attachments);
    }

    private function populate_template($fields = null, $template)
    {
        if(is_null($fields)){
            return '';
        }

        foreach ($fields as $field => $value) {
            $template = str_replace('{{ ' . strtoupper($field) . ' }}', $value, $template);
        }

        return $template;
    }

}