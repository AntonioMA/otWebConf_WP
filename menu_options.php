<?php
if (!class_exists('OTWC_Menu_Options')) {
  include_once('constants.php');
  include_once('error_log.php');
  class OTWC_Menu_Options {


    const MENU_ITEM_HREF_GENERATOR = [
      MENU_TYPE_GENERAL_CONTACT => 'get_appointment_url_for_server',
      MENU_TYPE_PERSONAL_CONTACT => 'get_appointment_url_for_user',
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

    private function get_appointment_url_for_user($user) {
      return
        $this->wc->getAppointmentURL($user->ID, uniqid('', true),
                                     'Web User', 'Unspecified question')->url;
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
      write_log('items_arr:');
      write_log($items_arr);

      $regex = '%<li\s+id="([^"]+)"\s+class="([^"]+)"\s*>\s*\<a\s+href="([^"]+)"\s*>(.+)</a>%';
      write_log('RE:' . $regex);

      if (preg_match($regex, $items_arr[0], $matches)) {
        write_log('Matches: ' );
        write_log($matches);
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
        if (count($menu_item) > 2) {
          $title = $menu_item[1];
          $type = $menu_item[2];
        } else {
          $title = $literal;
        }
        if (!array_key_exists($menu, $this->menus)) {
          $this->menus[$menu] = [];
        }
        array_push($this->menus[$menu], [
          'type' => $type,
          'literal' => $literal,
          'title' => $title
        ]);
      }
    }

    public function __construct($options, $wc, $site_url) {
      $this->options = $options;
      $this->parse_menu_options();
      $this->wc = $wc;
      $this->site_url = $site_url;
    }
  }
}

?>