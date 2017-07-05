<?php
if (!class_exists('OTWC_Menu_Options')) {
  include_once('constants.php');
  include_once('error_log.php');
  class OTWC_Menu_Options {


    const MENU_ITEM_HREF_GENERATOR = [
      MENU_TYPE_GENERAL_CONTACT => 'get_appointment_url_for_server',
      MENU_TYPE_PERSONAL_CONTACT => 'get_appointment_url_for_users',
      MENU_TYPE_GENERAL_ROOM => 'get_room_url_for_server',
      MENU_TYPE_PERSONAL_ROOM => 'get_room_url_for_user'
    ];

    private function get_room_url_for_server() {
      return $this->site_url;
    }
    private function get_room_url_for_user($user) {
      return get_user_meta($user->ID, OTWC_Constants::ROOM_URL, true);
    }

    private function get_appointment_url_for_server() {
      return $this->wc->getAppointmentURL(OTWC_Constants::MAIN_CONTACT_ID, uniqid('', true),
                                          'Web User', 'Unspecified question')->url;
    }

    private function get_appointment_url_for_users() {
      // This just gets a list of all the users that have a room!
      return plugins_url('content/html/personal_conf.html', __FILE__);
    }

    private function get_main_menu_element($type, $user, $description, $title) {
      return $this->get_cotorra_anchor($type, $user, $title) . " $description </a> </li>";
    }

    private function get_cotorra_url($type, $user) {
      return  call_user_func(array($this, self::MENU_ITEM_HREF_GENERATOR[$type]), $user);
    }

    /**
    * returns the text of a very simple anchor starter to launch cotorra
    */
    public function get_cotorra_anchor($user, $type, $title, $dom_element = false) {
      $url = $this-> get_cotorra_url($type, $user);
      if (empty($dom_element)) {
        $dom_element = $this->options[OTWC_ROOM_SELECTOR];
      }
      $style = ""; // TO-DO? Make this customizable
      $onclick = "window.__otWebConf.startConference('$url', '$dom_element', '$style', '$title')";

      return "<a href=\"#\" onclick=\"return $onclick && false;\">";
    }

    // $option = { literal: 'What to write inside the A', title: 'title of the popup',
    //             type: menu_type_option }
    // To-do: We might want to generate the output using the right anchor pattern...
    private function add_menu_option($user, $li_id_pattern, $li_class, $anchor_pattern, $option) {
        return "<li id=\"$li_id_pattern\" class=\"$li_class\">" .
               $this->get_cotorra_anchor($user, $option['type'], $option['title']) .
               $option['literal'] . '</a></li>';
    }

    private function add_menu_options($items, $menu) {
      $menu_elems = '';
      write_log('add_menu_options: ' . $menu);
      $menu_options = $this->menus[$menu];
      $user = wp_get_current_user();

      $items_arr = explode(htmlspecialchars_decode('</li>'), $items);

      $regex = '%<li\s+id="([^"]+)"\s+class="([^"]+)"\s*>\s*\<a\s+href="([^"]+)"\s*>(.+)</a>%';

      if (preg_match($regex, $items_arr[0], $matches)) {
        $li_id_pattern = $matches[2];
        $li_class = $matches[2];
        $anchor = $matches[3];
        $anchor_pattern = $matches[4];
      } else {
        write_log('Does not match: ' . $items_arr[0]);
        return $menu_elems;
      }

      foreach($menu_options as $option) {
        if (OTWC_Constants::should_add_options($option['type'], $user)) {
          $menu_elems .=
            $this->add_menu_option($user, $li_id_pattern, $li_class, $anchor_pattern, $option);
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
    }

    public function __construct($options, $wc, $site_url) {
      $this->options = $options;
      $this->menus = OTWC_Constants::parse_menu_options($this->options[OTWC_MENU_CONFIG]);
      $this->wc = $wc;
      $this->site_url = $site_url;
    }
  }
}

?>