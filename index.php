<?php
/**
 * @package OTWebConf_Integration
 * @version 1.6
 */
/*
Plugin Name: Opentok WebConferencing Integration
Plugin URI: https://github.com/AntonioMA/otWebConf_WP
Description: This allows you to easily integrate TokBox's Generic Web Conferencing software on your site. It will allow viewers of the site to contact the site authenticated users.
Author: Antonio M. Amaya
Version: 0.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Note: this is hack-level code. Meaning this should be encapsulated, and check that we don't
// fuck up somebody's else methods and remove cursing in comments and so on.
if (!class_exists('OTWC_Plugin')) {
  include_once('WebConference.php');
  include_once('settings.php');

  if (!function_exists('write_log')) {
    function write_log ( $log )  {
        if ( true === WP_DEBUG ) {
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
        }
    }
  }

  class OTWC_Plugin {
     // A single capability that's checked to give a user his own room
    const HOST_CAPABILITY = 'delete_pages';
    const DEFAULT_OPTIONS = [
      OTWC_BASE_URL => 'https://ot-webconf.herokuapp.com',
      OTWC_PROJECT_UUID => ''
    ];

    const ROOM_URL = 'room_URL'; // Name for the meta key

    public static function OTWC_activate_plugin() {
      $options = get_option(OTWC_OPTIONS);
      if (empty($options)) {
        $options = self::DEFAULT_OPTIONS;
        add_option(OTWC_OPTIONS, $options);
      }
    }

    public static function OTWC_deactivate_plugin() {
    }

    public static function OTWC_uninstall_plugin() {
    }

    public function should_change($old_options, $new_options) {
      return (!empty($new_options[OTWC_BASE_URL]) && !empty($new_options[OTWC_PROJECT_UUID]) &&
              ($old_options[OTWC_BASE_URL] != $new_options[OTWC_BASE_URL] ||
               $old_options[OTWC_PROJECT_UUID] != $new_options[OTWC_PROJECT_UUID]));
    }

    public function build_web_conference() {
      $old_options = $this->options;
      $this->options = get_option(OTWC_OPTIONS);
      write_log('build_web_conference called:');
      write_log([$old_options, $this->options]);

      if ($this->should_change($old_options, $this->options)) {
        write_log('build_web_conference: Creating new WebConference');
        $this->wc =
          new WebConference($this->options[OTWC_BASE_URL], $this->options[OTWC_PROJECT_UUID]);
      } else {
        write_log('build_web_conference: Keeping the old instance live');
      }
    }

    //add_user_meta( $user_id, '_level_of_awesomeness', $awesome_level);
    //          do_action( 'edit_user_created_user', $user_id, $notify );
    public function user_meta_filter($meta, $user, $update = false) {
      write_log("user_meta_filter called with:");
      write_log([$meta, $user]);
      if ($user && $user->has_cap(self::HOST_CAPABILITY)) {
        // We will update the room url even if it already exists, in case the display name changed
        if (!$this->wc) {
          write_log('user_meta_filter: webConference does not exist!');
          $this->build_web_conference();
        }
        $room = $this->wc->getHostURL($user->data->display_name, $user->ID, false);
        $meta[self::ROOM_URL] = $room->url;
        //add_user_meta($user_id, 'room_URL', $room->url);
      }
      return $meta;
    }

    private function __construct() {
      $this->options = self::DEFAULT_OPTIONS;
      add_action('activated_plugin', array($this, 'build_web_conference'));
      add_action('update_option_' . OTWC_OPTIONS, array($this, 'build_web_conference'));
      add_filter('insert_user_meta', array($this, 'user_meta_filter'), 10, 3);
      $this->wc = null;
    }

    public static function init() {
      // current_user_can('edit_user') => admin
      register_activation_hook(__FILE__, array('OTWC_Plugin', 'OTWC_activate_plugin'));
      register_deactivation_hook(__FILE__, array('OTWC_Plugin', 'OTWC_deactivate_plugin'));
      register_uninstall_hook(__FILE__, array('OTWC_Plugin', 'OTWC_uninstall_plugin'));
      new OTWC_Plugin();
    }
  }

  OTWC_Plugin::init();

}

?>
