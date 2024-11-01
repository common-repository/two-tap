<?php

/**
 * Main TwoTap Class.
 *
 * @class TwoTap
 * @version 1.0.0
 */
class Two_Tap_Logger {

    private $logger;

    public function __construct($args = array()) {
        $defaults = array(
          'name'   => 'Two_Tap',
          'path'   => wp_upload_dir()['basedir'] . '/wc-logs/two-tap-log.log',
          'level'  => \Monolog\Logger::DEBUG
        );
        $args = wp_parse_args($args, $defaults);
        $this->logger = new \Monolog\Logger($args['name']);
        $this->logger->pushHandler(new Monolog\Handler\RotatingFileHandler($args['path'], $args['level']));
        do_action( 'two_tap_logger_loaded' );
    }

    public function __call($method, $args)
    {
        if(is_array($args) && count($args) >= 2){
            list($message, $arg) = $args;
        } else {
            $message = $args[0];
            $arg = [];
        }
        if(is_object($message)){
            $arg = $message;
            $message = get_class($message);
        }
        return $this->logger->$method($message, (array)$arg);
    }
}
