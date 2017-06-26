<?php

define('OTWC_OPTIONS', 'OTWC__options');
// Fields that we can customize...
define('OTWC_BASE_URL', 'OTWC__cotorra_url');
define('OTWC_PROJECT_UUID', 'OTWC__project_uuid');
define('OTWC_MAIN_CONTACT_NAME', 'OTWC__main_contact_name');
define('OTWC_ROOM_SELECTOR', 'OTWC__main_selector');
// End fields

define('OTWC_PREFIX', 'OTWC_');
define('OTWC_FIELD_CB', 'OTWC__field_cb');
define('OTWC_SECTION_COTORRA_CB', 'OTWC__section_cotorra_cb');
define('OTWC_SECTION_NAME', 'OTWC__section_cotorra');

if (!class_exists('OTWC_Constants')) {
  class OTWC_Constants {
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
      ]
    ];

    // A single capability that's checked to give a user his own room
    const HOST_CAPABILITY = 'delete_pages';
    const DEFAULT_OPTIONS = [
      OTWC_BASE_URL => 'https://ot-webconf.herokuapp.com',
      OTWC_PROJECT_UUID => '',
      OTWC_MAIN_CONTACT_NAME => 'Main Site Contact',
      OTWC_ROOM_SELECTOR => ''
    ];

    const ROOM_URL = 'room_URL'; // Name for the meta key
    const MAIN_CONTACT_ID = 'maincontact'; // Id of the main contact for the site

    public static function can_own_a_room($user) {
      return $user && $user->has_cap(self::HOST_CAPABILITY);
    }

  }
}
?>