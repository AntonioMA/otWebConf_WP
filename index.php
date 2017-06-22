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
      OTWC_PROJECT_UUID => '',
      OTWC_MAIN_CONTACT_NAME => 'Main Site Contact',
    ];

    const ROOM_URL = 'room_URL'; // Name for the meta key
    const MAIN_CONTACT_ID = 'maincontact'; // Id of the main contact for the site

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
      //write_log([$old_options, $this->options]);

      if ($this->should_change($old_options, $this->options)) {
        write_log('build_web_conference: Creating new WebConference');
        $this->wc =
          new WebConference($this->options[OTWC_BASE_URL], $this->options[OTWC_PROJECT_UUID]);
        // I could probably cache this...
        $this->site_url =
          $this->wc->getHostURL($this->options[OTWC_MAIN_CONTACT_NAME],
                                self::MAIN_CONTACT_ID, false)->url;
      } else {
        write_log('build_web_conference: Keeping the old instance live');
      }
    }

    private static function can_own_a_room($user) {
      return $user && $user->has_cap(self::HOST_CAPABILITY);
    }

    //add_user_meta( $user_id, '_level_of_awesomeness', $awesome_level);
    //          do_action( 'edit_user_created_user', $user_id, $notify );
    public function user_meta_filter($meta, $user, $update = false) {
      write_log("user_meta_filter called with:");
      write_log([$meta, $user]);
      if (self::can_own_a_room($user)) {
        // We will update the room url even if it already exists, in case the display name changed
        $room = $this->wc->getHostURL($user->data->display_name, $user->ID, false);
        $meta[self::ROOM_URL] = $room->url;
        //add_user_meta($user_id, 'room_URL', $room->url);
      }
      return $meta;
    }

    /**
    * returns the text of a very simple anchor starter to launch cotorra
    */
    private static function get_cotorra_anchor($url, $dom_element = false) {
      if ($dom_element) {
        $dom_element = "'$dom_element'";
      } else {
        $dom_element = 'null';
      }
      $url = "'$url'";
      return "<a href=\"#\" onclick=\"return opentok.widget.start($url, $dom_element) && false;\">";
    }

    /**
    * Note: The following two functions need some (or a whole lot) of love. Currently they work
    * well only if the site is customized exactly as I have it!
    */
    private function get_social_icon() {
      write_log('get_social_icon');
      // This sucks. You know it, I know it, everybody knows it.
      // TO-DO: Check if it's a registered user and get his real name!
      // We could also add a form here to get the contact information
      $site_cotorra_url =
        $this->wc->getAppointmentURL(self::MAIN_CONTACT_ID, uniqid('', true), 'Web User',
                                     'Unspecified question');
      return
         '<li id="menu-item-27" class="menu-item menu-item-type-custom menu-item-object-custom menu-item-27">' .
         self::get_cotorra_anchor($site_cotorra_url->url) .
         '  <span class="screen-reader-text">TokBox</span>' .
         '  <svg class="icon icon-skype" aria-hidden="true" role="img">' .
         '    <use href="#icon-skype" xlink:href="#icon-skype"></use>' .
         '  </svg>' .
         ' </a>'.
         '</li>';
    }

    private function get_main_contact_url() {
      $user = wp_get_current_user();
      write_log('get_main_contact_url');
      //write_log($user);
      $rooms = '';
      if (self::can_own_a_room($user)) {
        $rooms .=
          '<li id="menu-item-224" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-224">' .
          $this->get_cotorra_anchor($this->site_url) . ' Load Main Contact Room </a>' .
          '</li>';
        $user_room_url = get_user_meta($user->ID, self::ROOM_URL, true);
        if (!empty($user_room_url)) {
          $rooms .=
          '<li id="menu-item-221" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-221">' .
          $this->get_cotorra_anchor($this->site_url) . ' Load Personal Contact Room </a>' .
          '</li>';
        }
      }
      return $rooms;
    }

    public function cotorra_menu_item($items, $args) {
      write_log('cotorra_menu_item');
      //write_log([$items, $args]);
      if ($args->theme_location == 'social') {
        $items .= $this->get_social_icon();
      } else if ($args->theme_location == 'top') {
        $items .= $this->get_main_contact_url();
      }

      return $items;
    }

    public function add_cotorra_client_script() {
      write_log('add_cotorra_client_script: ' . $this->wc->server_url);
      wp_enqueue_script('OTWC_client_script', $this->wc->server_url . '/js/opentokWidget.js');
    }

    public function cotorra_menu_objects($menu_objects, $args) {
      write_log('cotorra_menu_objects:');
      //write_log([$menu_objects, $args]);
      return $menu_objects;
    }

    private function __construct() {
      $this->options = self::DEFAULT_OPTIONS;
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
