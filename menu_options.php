<?php
if (!class_exists('OTWC_Menu_Options')) {
  include_once('constants.php');
  include_once('error_log.php');
  class OTWC_Menu_Options {

    /**
    * returns the text of a very simple anchor starter to launch cotorra
    */
    public function get_cotorra_anchor($url, $title, $dom_element = false) {
      $onclick='';
      if (empty($url)) {
        $onclick = "opentok.widget.stop()";
      } else {
        $style = "style: ''"; // TO-DO? Make this customizable
        if (empty($dom_element)) {
          $dom_element = $this->options[OTWC_ROOM_SELECTOR];
        }
        $options = "{ target: '$dom_element', $style, title: '$title' }";
        $url = "'$url'";
        $onclick = "opentok.widget.start($url, $options)";
      }
      return "<a href=\"#\" onclick=\"return $onclick && false;\">";
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
        $this->wc->getAppointmentURL(OTWC_Constants::MAIN_CONTACT_ID, uniqid('', true), 'Web User',
                                     'Unspecified question');
      return
         '<li id="menu-item-27" class="menu-item menu-item-type-custom menu-item-object-custom menu-item-27">' .
         $this->get_cotorra_anchor($site_cotorra_url->url, 'Video Request') .
         '  <span class="screen-reader-text">TokBox</span>' .
         '  <svg class="icon icon-skype" aria-hidden="true" role="img">' .
         '    <use href="#icon-skype" xlink:href="#icon-skype"></use>' .
         '  </svg>' .
         ' </a>'.
         '</li>';
    }

    const MAIN_MENU_HEADER =
      '<li id="menu-item-224" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-224">';
    const MAIN_CONTACT_LIT = 'Load Main Contact Room';
    const PERSONAL_CONTACT_LIT = 'Load Personal Contact Room';
    const CLOSE_CONTACT_ROOM = 'Close Video Contact Room';
    const PERSONAL_VC = 'Personal Video Room';
    const MAIN_VC = 'Main Contact Video Room';

    public function get_main_menu_element($url, $description, $title) {
      return OTWC_Constants::MAIN_MENU_HEADER .
        $this->get_cotorra_anchor($url, $title) . " $description </a> </li>";
    }

    public function get_main_contact_url() {
      $user = wp_get_current_user();
      write_log('get_main_contact_url');
      //write_log($user);
      $rooms = '';
      if (OTWC_Constants::can_own_a_room($user)) {
        $rooms .= $this->get_main_menu_element($this->site_url, OTWC_Constants::MAIN_CONTACT_LIT,
                                               OTWC_Constants::MAIN_VC);
        $user_room_url = get_user_meta($user->ID, OTWC_Constants::ROOM_URL, true);
        if (!empty($user_room_url)) {
          $rooms .= $this->get_main_menu_element($user_room_url,
                                                 OTWC_Constants::PERSONAL_CONTACT_LIT,
                                                 OTWC_Constants::PERSONAL_VC);
        }
      }
      return $rooms;
    }

    // $option = { literal: 'Title', type: menu_type_option }
    private function add_menu_option($items, $option) {


    }

    private function add_menu_options($items, $menu) {
      $menu_elems = '';
      write_log('add_menu_options: ' . $menu);
      $menu_options = $this->menus[$menu];
      $user = wp_get_current_user();
      foreach($menu_options as $option) {
        if (call_user_func(OTWC_Constants::MENU_ITEM_CHECK[$option['type']], $user)) {
          $menu_elems .= $this->add_menu_option($items, $option);
        }
      }
      return $menu_elems;
    }

    public function parse_menu_items($items, $args) {
      $menu = $args->theme_location;
      if (array_key_exists($menu, $this->menus)) {
        $items .= $this->add_menu_options($items, $menu);
      }
      return $items;

/*
      if ($args->theme_location == 'social') {
        $items .= $this->get_social_icon();
      } else if ($args->theme_location == 'top') {
        $items .= $this->get_main_contact_url();
      }
*/
      return $items;
    }

    private function parse_menu_options() {
      $menu_config = $this->options[OTWC_MENU_CONFIG];

      $menus = explode(';',  $menu_config);
      $this->menus = [];
      foreach($menus as $option) {
        $config = explode('|', $option);
        $menu = $config[0];
        $menu_item = explode(',', $config[1]);
        $literal = $menu_item[0];
        $type = $menu_item[1];
        if (!array_key_exists($menu, $this->menus)) {
          $this->menus[$menu] = [];
        }
        array_push($this->menus[$menu], ['literal' => $menu_item[0], 'type' => $menu_item[1]]);
      }
    }

    public function __construct($options, $wc) {
      $this->options = $options;
      $this->parse_menu_options();
      $this->wc = $wc;
    }
  }
}

?>