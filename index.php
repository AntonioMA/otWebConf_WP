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
  include_once('constants.php');
  include_once('WebConference.php');
  include_once('settings.php');
  include_once('error_log.php');
  include_once('menu_options.php');


  class OTWC_Plugin {
    public static function OTWC_activate_plugin() {
      $options = get_option(OTWC_OPTIONS);
      if (empty($options)) {
        $options = OTWC_Constants::DEFAULT_OPTIONS;
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
      //write_log([$old_options, $this->options]);

      if ($this->should_change($old_options, $this->options)) {
        write_log('build_web_conference: Creating new WebConference');
        $this->wc =
          new WebConference($this->options[OTWC_BASE_URL], $this->options[OTWC_PROJECT_UUID]);
        // I could probably cache this...
        $this->site_url =
          $this->wc->getHostURL($this->options[OTWC_MAIN_CONTACT_NAME],
                                OTWC_Constants::MAIN_CONTACT_ID, false)->url;
        $this->menu_options = new OTWC_Menu_Options($this->options, $this->wc, $this->site_url);

      } else {
        write_log('build_web_conference: Keeping the old instance live');
      }
    }

    //add_user_meta( $user_id, '_level_of_awesomeness', $awesome_level);
    //          do_action( 'edit_user_created_user', $user_id, $notify );
    public function user_meta_filter($meta, $user, $update = false) {
      write_log("user_meta_filter called with:");
      write_log([$meta, $user]);
      if (OTWC_Constants::can_own_a_room($user)) {
        // We will update the room url even if it already exists, in case the display name changed
        $room = $this->wc->getHostURL($user->data->display_name, $user->ID, false);
        $meta[OTWC_Constants::ROOM_URL] = $room->url;
        //add_user_meta($user_id, 'room_URL', $room->url);
      }
      return $meta;
    }


    public function cotorra_menu_item($items, $args) {
      write_log('cotorra_menu_item');
      //write_log([$items, $args]);
      return $this->menu_options->parse_menu_items($items, $args);
    }

    public function add_cotorra_client_script() {
      write_log('add_cotorra_client_script: ' . $this->wc->server_url);
      wp_enqueue_script('OTWC_client_script', $this->wc->server_url . '/js/opentokWidgetV2.js');
    }

    public function cotorra_menu_objects($menu_objects, $args) {
      write_log('cotorra_menu_objects:');
      //write_log([$menu_objects, $args]);
      return $menu_objects;
    }

    private function __construct() {
      $this->options = OTWC_Constants::DEFAULT_OPTIONS;
      add_action('activated_plugin', array($this, 'build_web_conference'));
      add_action('update_option_' . OTWC_OPTIONS, array($this, 'build_web_conference'));
      add_filter('insert_user_meta', array($this, 'user_meta_filter'), 10, 3);
      add_filter('wp_nav_menu_items', array($this, 'cotorra_menu_item'), 10, 2);
      add_filter('wp_nav_menu_objects', array($this, 'cotorra_menu_objects'), 10, 2);
      add_action('wp_enqueue_scripts', array($this, 'add_cotorra_client_script'));
      $this->build_web_conference();
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
