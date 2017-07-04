<?php

define('OTWC_OPTIONS', 'OTWC__options');
// Fields that we can customize...
define('OTWC_BASE_URL', 'OTWC__cotorra_url');
define('OTWC_PROJECT_UUID', 'OTWC__project_uuid');
define('OTWC_MAIN_CONTACT_NAME', 'OTWC__main_contact_name');
define('OTWC_ROOM_SELECTOR', 'OTWC__main_selector');
define('OTWC_MENU_CONFIG', 'OTWC__menu_config');
// End fields

define('OTWC_PREFIX', 'OTWC_');
define('OTWC_FIELD_CB', 'OTWC__field_cb');
define('OTWC_SECTION_COTORRA_CB', 'OTWC__section_cotorra_cb');
define('OTWC_SECTION_NAME', 'OTWC__section_cotorra');
const MENU_TYPE_GENERAL_CONTACT = 'GC';
const MENU_TYPE_PERSONAL_CONTACT = 'PC';
const MENU_TYPE_GENERAL_ROOM = 'GR';
const MENU_TYPE_PERSONAL_ROOM = 'PR';

if (!class_exists('OTWC_Constants')) {
  class OTWC_Constants {

    const MENU_ITEM_TYPES_DESC = [
      MENU_TYPE_GENERAL_CONTACT => 'General Appointment',
      MENU_TYPE_PERSONAL_CONTACT => 'Personal Appointment',
      MENU_TYPE_GENERAL_ROOM => 'Access to General Room',
      MENU_TYPE_PERSONAL_ROOM => 'Access to Personal Room'
    ];

    const MENU_ITEM_CHECK = [
      MENU_TYPE_GENERAL_CONTACT => array('OTWC_Constants', 'always'),
      MENU_TYPE_PERSONAL_CONTACT => array('OTWC_Constants', 'always'),
      MENU_TYPE_GENERAL_ROOM => array('OTWC_Constants', 'can_own_a_room'),
      MENU_TYPE_PERSONAL_ROOM => array('OTWC_Constants', 'can_open_personal_room')
    ];

    const FIELDS = [
      OTWC_BASE_URL => [
        'label' => 'URL of the Cotorra server',
        'params' =>[
          'input_type' => 'url',
          'field_size' => 40,
          'field_description' => 'Please enter the URL of your Generic WebConference Server'
        ]
      ],
      OTWC_PROJECT_UUID => [
        'label' => 'UUID of the cotorra project',
        'params' => [
          'input_type' => 'text',
          'field_size' => 85,
          'field_description' => 'Please enter the project UUID'
      ]
      ],
      OTWC_MAIN_CONTACT_NAME => [
        'label' => 'Main Contact Name',
        'params' => [
          'input_type' => 'text',
          'field_size' => 30,
          'field_description' => 'Please enter the name of the main contact.'
        ]
      ],
      OTWC_ROOM_SELECTOR => [
        'label' => 'Conference room element',
        'params' => [
          'input_type' => 'text',
          'field_size' => 30,
          'field_description' => 'Please enter a query selector for the parent element of the video conference window.'
        ]
      ],
      OTWC_MENU_CONFIG => [
        'label' => 'Menu customization',
        'params' => [
          'custom_generator' => array('OTWC_Constants', 'generate_menu_settings_layout'),
          'input_type' => 'text',
          'field_size' => 120,
          'field_description' =>
            'Please enter the menus where you want to add the options, and the customization for ' .
            'each of the menus.'
        ]
      ]
    ];

    // A single capability that's checked to give a user his own room
    const HOST_CAPABILITY = 'delete_pages';
    const DEFAULT_OPTIONS = [
      OTWC_BASE_URL => 'https://ot-webconf.herokuapp.com',
      OTWC_PROJECT_UUID => '',
      OTWC_MAIN_CONTACT_NAME => 'Main Site Contact',
      OTWC_ROOM_SELECTOR => '',
      OTWC_MENU_CONFIG =>
       'social|<span class="screen-reader-text">TokBox</span> <svg class="icon icon-skype" aria-hidden="true" role="img"> <use href="#icon-skype" xlink:href="#icon-skype"></use></svg>,Video Contact Room,C;' .
       'top|Main Room,Main Contact Room,GR;' .
       'top|Personal Room,Personal Contact Room,PCR'
    ];

    const ROOM_URL = 'room_URL'; // Name for the meta key
    const MAIN_CONTACT_ID = 'maincontact'; // Id of the main contact for the site

    public static function parse_menu_options($menu_config) {

      $menus = explode(';',  $menu_config);
      $returned_menus = [];
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
        if (!array_key_exists($menu, $returned_menus)) {
          $returned_menus[$menu] = [];
        }
        array_push($returned_menus[$menu], [
          'type' => $type,
          'literal' => $literal,
          'title' => $title
        ]);
      }
      return $returned_menus;
    }

    public static function generate_menu_settings_row($trid, $main_id, $menu_element, $menu_options) {
        ?><tr id="<?php echo esc_attr($trid); ?>"><?php
        foreach ($menu_options as $option) {
          ?>
<td><input type="text" size="10" class="<?php echo esc_attr($main_id); ?>__menu" value="<?php echo esc_attr($menu_element); ?>" onchange="__genValue['<?php echo esc_attr($main_id); ?>'].onchange();"/></td>
<td><select class='<?php echo esc_attr($main_id); ?>__type'  onchange="__genValue['<?php echo esc_attr($main_id); ?>'].onchange();">
<?php
          foreach(self::MENU_ITEM_TYPES_DESC as $type => $desc) {
            ?>
            <option value="<?php echo esc_attr($type); ?>"<?php echo $option['type'] == $type ? "selected": ""; ?>><?php echo esc_attr($desc); ?></option>
<?php
          }

?>
</select></td>
<td><input type="text" size="20" class="<?php echo esc_attr($main_id); ?>__title" value="<?php echo esc_attr($option['title']); ?>" onchange="__genValue['<?php echo esc_attr($main_id); ?>'].onchange();"/></td>
<td><textarea rows="4" cols="40" class="<?php echo esc_attr($main_id); ?>__literal" onchange="__genValue['<?php echo esc_attr($main_id); ?>'].onchange();">
<?php echo esc_attr($option['literal']); ?>
</textarea></td>
<?php
        ?></tr><?php
        }

    }

    // I can't say I'm a fan of PHP all things considered...
    public static function generate_menu_settings_layout($elem, $main_id) {
      $menus = self::parse_menu_options($elem);
      write_log($elem);
      write_log($menus);
      ?>
      <script>
      var __genValue = __genValue || {};
      __genValue['<?php echo esc_attr($main_id); ?>'] = {
        onchange: function() {
          console.log('onchange!');

        },
        genRow: function() {
          var row =
            document.getElementById('<?php echo esc_attr($main_id); ?>__fakeRow').cloneNode(true);
          document.getElementById('<?php echo esc_attr($main_id); ?>__table').appendChild(row);
        }
      };
      </script>
      <table id="fakeTable" style="display:none">
      <?php self::generate_menu_settings_row($main_id . '__fakeRow', $main_id, '', [
        [
          'type' => MENU_TYPE_GENERAL_CONTACT,
          'literal' => '',
          'title' => 'Video Room'
        ]]); ?>
      </table>
      <table id="<?php echo esc_attr($main_id); ?>__table">
      <tr>
        <th>Menu</th>
        <th>Link Type</th>
        <th>Window Title</th>
        <th>Link Markup</th>
      </tr>
      <?php
      foreach($menus as $menu_element => $menu_options) {
        self::generate_menu_settings_row('', $main_id, $menu_element, $menu_options);
      }
      ?>
      </table>
      <p style="text-align:center"><input type="button" value="Add Row" onclick="__genValue['<?php echo esc_attr($main_id); ?>'].genRow();"/></p>

      <?php
    }

    /**
    * Callbacks for checking if a link must be added or not
    */
    public static function always() {
      return true;
    }

    public static function can_own_a_room($user) {
      return $user && $user->has_cap(self::HOST_CAPABILITY);
    }

    public static function can_open_personal_room($user) {
      $user_room_url = get_user_meta($user->ID, OTWC_Constants::ROOM_URL, true);
      return !empty($user_room_url);
    }

    public static function generate_cotorra_href($type) {
       return  call_user_func(self::MENU_ITEM_HREF_GENERATOR[$option['type']], $user);
    }

    public static function should_add_options($type, $user) {
       return  call_user_func(self::MENU_ITEM_CHECK[$type], $user);
    }

  }
}
?>