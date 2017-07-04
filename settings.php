<?php

include_once('constants.php');

/**
 * @internal never define functions inside callbacks.
 * these functions could be run multiple times; this would result in a fatal error.
 */


/**
 * custom option and settings
 */
function OTWC__settings_init() {
  // register a new setting for "OTWC_" page
  register_setting(OTWC_PREFIX, OTWC_OPTIONS);

  // register a new section in the "OTWC_" page
  add_settings_section(
    OTWC_SECTION_NAME,
     __( 'TokBox\'s Cotorra Settings.', OTWC_PREFIX),
     OTWC_SECTION_COTORRA_CB,
     OTWC_PREFIX
  );

  // register a new field in the "OTWC__section_developers" section, inside the "OTWC_" page
  add_settings_field(
   OTWC_BASE_URL,
   __('URL of the Cotorra server', OTWC_PREFIX),
   OTWC_FIELD_CB, OTWC_PREFIX, OTWC_SECTION_NAME,
   [
     'label_for' => OTWC_BASE_URL,
      'input_type' => 'url',
      'field_size' => 40,
      'field_description' => 'Please enter the URL of your Generic WebConference Server'
   ]
  );
  foreach(OTWC_Constants::FIELDS as $field => $field_config) {
    $field_config['params']['label_for'] = $field;
    add_settings_field($field,
      __($field_config['label'], OTWC_PREFIX),
      OTWC_FIELD_CB, OTWC_PREFIX, OTWC_SECTION_NAME,
      $field_config['params']
    );
  }

}

/**
 * custom option and settings:
 * callback functions
 */

// developers section cb

// section callbacks can accept an $args parameter, which is an array.
// $args have the following keys defined: title, id, callback.
// the values are defined at the add_settings_section() function.
function OTWC__section_cotorra_cb( $args ) {
 ?>
 <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e('Please fill in the base URL of your Cotorra server and the project UUID you wish to use:', OTWC_PREFIX ); ?></p>
 <?php
}

// pill field cb

// field callbacks can accept an $args parameter, which is an array.
// $args is defined at the add_settings_field() function.
// wordpress has magic interaction with the following keys: label_for, class.
// the "label_for" key value is used for the "for" attribute of the <label>.
// the "class" key value is used for the "class" attribute of the <tr> containing the field.
// you can add custom key value pairs to be used inside your callbacks.
function OTWC__field_cb($args) {
  // get the value of the setting we've registered with register_setting()
  $options = get_option(OTWC_OPTIONS);
  if (!array_key_exists($args['label_for'], $options)) {
    $options[$args['label_for']] = OTWC_Constants::DEFAULT_OPTIONS[$args['label_for']];
  }
  // output the field
 ?>
 <input
   type="<?php echo esc_attr($args['input_type']); ?>"
   size="<?php echo esc_attr($args['field_size']); ?>"
   id="<?php echo esc_attr( $args['label_for'] ); ?>"
   name="OTWC__options[<?php echo esc_attr($args['label_for']); ?>]"
   value="<?php echo esc_attr($options[$args['label_for']]); ?>"
 />
 <p class="description">
 <?php esc_html_e($args['field_description'], OTWC_PREFIX ); ?>
 </p>
 <?php
}

/**
 * top level menu
 */
function OTWC__options_page() {
  // add top level menu page
  //add_menu_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '', string $icon_url = '', int $position = null )
  add_menu_page(
    'OpenTok Generic WebConferencing',
    'OpenTok Options',
    'manage_options',
    OTWC_PREFIX,
    'OTWC__options_page_html'
  );
}

/**
 * top level menu:
 * callback functions
 */
function OTWC__options_page_html() {
   // check user capabilities
   if ( !current_user_can( 'manage_options' ) ) {
     return;
   }

   // add error/update messages

   // check if the user have submitted the settings
   // wordpress will add the "settings-updated" $_GET parameter to the url
   if ( isset( $_GET['settings-updated'] ) ) {
     // add settings saved message with the class of "updated"
     add_settings_error('OTWC__messages', 'OTWC__message', __( 'Settings Saved', OTWC_PREFIX ),
                        'updated');
   }

   // show error/update messages
   settings_errors('OTWC__messages');
 ?>
 <div class="wrap">
 <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
 <form action="options.php" method="post">
 <?php
   // output security fields for the registered setting "OTWC_"
   settings_fields(OTWC_PREFIX);
   // output setting sections and their fields
   // (sections are registered for "OTWC_", each field is registered to a specific section)
   do_settings_sections(OTWC_PREFIX);
   // output save settings button
   submit_button('Save Settings');
 ?>
 </form>
 </div>
 <?php

}

/**
 * register our OTWC__settings_init to the admin_init action hook
 */
add_action( 'admin_init', 'OTWC__settings_init' );

/**
 * register our OTWC__options_page to the admin_menu action hook
 */
add_action( 'admin_menu', 'OTWC__options_page' );

