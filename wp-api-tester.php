<?php
/*
 * Plugin Name: A plugin used for running blocks of code at the click of a button (rather than a page refresh).
 * Plugin URI: https://wp-api-libraries.com
 * Description: Perform API requests.
 * Author: WP API Libraries
 * Version: 1.0.1
 * Author URI: https://wp-api-libraries.com
 * GitHub Plugin URI: https://github.com/wp-api-libraries
 * GitHub Branch: master
 */

if( !function_exists( 'pp' ) ){
  function pp($s, $a = ''){
    error_log(($a == ''?'':$a.': ').print_r($s, true));
  }
}

function secret_message(){
  return "I love you.";
}

register_activation_hook( __FILE__, 'tester_settings' );

function tester_settings(){
  update_option('tester_code', "<?php

function my_first_function(){
    $" . "a = 3;
    $" . "b = 5;

    return array(
        'Hello...' => $" . "a + $" . "b,
        '...world!' => $" . "b - $" . "a,
        'other-secret-stuff' => secret_message(),
    );
}

return my_first_function();" );
}

if( !class_exists( 'WP_API_Tester' ) ){
  class WP_API_Tester{

    public function __construct(){
      add_action( 'rest_api_init', function () {
      	register_rest_route( 'api/v1', 'first', array(
      		'methods'	 => 'get',
      		'callback' => array( &$this, 'run_code' ),
          'permission_callback' => array( &$this, 'permission_callback' ),
      	));
      });

      add_action( 'rest_api_init', function () {
      	register_rest_route( 'api/v1', 'second', array(
      		'methods'	 => 'get',
      		'callback' => array( &$this, 'save_code' ),
          'permission_callback' => array( &$this, 'permission_callback' ),
      	));
      });

      add_action( 'admin_menu', array( &$this, 'wpp_admin_menu' ) );
    }

    public function run_code( $data ){
      return eval( $data['code'] );
    }

    public function save_code( $data ){
      update_option( 'tester_code', $data['code'] );
      return rest_ensure_response( array("success" => true, "message" => "Code successfully saved." ));
    }

    public function wpp_admin_menu(){
      register_setting( 'wpp_defaults', 'wpp_defaults' );
      add_management_page( 'WP API Do Me', 'WP API Do Me', 'manage_options', 'wp-apis', array( &$this, 'wpp_settings_page' ) );
    }

    public function permission_callback( $data ){
      return isset( $data['_wpnonce'] );
    }

    public function wpp_settings_page(){
      $nonce = wp_create_nonce( 'wp_rest' );
      ?>
      <meta id="localized-info" data-rest-nonce="<?php echo $nonce; ?>">

      <div class="wrap">
        <h1>That button</h1>
        <hr>
        <h2>It's that button</h2>
        <p>This editor is evaluated within the wp-api-tester.php plugin file, and has access to all functions and GLOBAL variables that would otherwise be available at that time.</p>
        <p>As a demonstration, go ahead and click the First Button! The secret_message() function is defined to the rest of PHP, to help illustrate my point.</p>
        <div style="width: 80%;height: 400px;">
          <div style="width: 80%;height: 400px;" id="editor"><?php esc_html_e(get_option( 'tester_code' )); ?></div>
        </div>
        <p>
          <input class="button-primary button" type="button" id="first-button" value="Run Code">
          <input class="button-secondary button" type="button" id="second-button" value="Save Code">
        </p>
        <style type="text/css" media="screen">
          #editor {
            position: absolute;
          }
        </style>
        <script src="<?php echo plugin_dir_url( __FILE__ ) . '/assets/js/ace.js'; ?>" type="text/javascript" charset="utf-8"></script>
        <script>

          var editor = ace.edit("editor");

          jQuery(document).ready(function(){

            editor.setTheme("ace/theme/tomorrow_night_eighties");
            editor.getSession().setMode("ace/mode/php");

            jQuery("#first-button").on('click', function(){
              var wpnonce = jQuery('#localized-info').attr('data-rest-nonce');

              var code = editor.getValue();

              var code = code.substring( code.indexOf("<" + "?php") + 5, code.length);

              jQuery.ajax({
                type: 'get',
                dataType: 'json',
                url: '/wp-json/api/v1/first',
                data: {
                  _wpnonce: wpnonce,
                  code: code,
                },
                success: function(response) {
                  if(response.success == false){
                    console.log(response.data);
                  }else{
                    if( response.body && typeof response.body == 'string' ){
                      response.body = JSON.parse( response.body );
                    }

                    jQuery("#domain-output").html( '<pre>' + JSON.stringify( response, null, 4 ) + '</pre>');
                  }
                },
                error: function(response){
                  console.log( response );
                  jQuery("#domain-output").html( '<pre>' + JSON.stringify( response, null, 4 ) + '</pre>');
                }
              }); // end ajax

            }); // end button on click

            jQuery("#second-button").on('click', function(){
              var wpnonce = jQuery('#localized-info').attr('data-rest-nonce');

              var code = editor.getValue();

              jQuery.ajax({
                type: 'get',
                dataType: 'json',
                url: '/wp-json/api/v1/second',
                data: {
                  _wpnonce: wpnonce,
                  code: code,
                },
                success: function(response) {
                  if(response.success == false){
                    console.log(response.data);
                  }else{
                    if( response.body && typeof response.body == 'string' ){
                      response.body = JSON.parse( response.body );
                    }

                    jQuery("#domain-output").html( '<pre>' + JSON.stringify( response, null, 4 ) + '</pre>');
                  }
                },
                error: function(response){
                  console.log( response );
                  jQuery("#domain-output").html( '<pre>' + JSON.stringify( response, null, 4 ) + '</pre>');
                }
              }); // end ajax

            }); // end button on click

            jQuery(document).on('keydown', function(e){
              // ctrl + q or ctrl + e
              // Avoid cmnd + q, since that kills the crab.
              if( ( e.ctrlKey || e.metaKey ) && ( e.which === 81 || e.which === 69 ) ){
                jQuery("#first-button").trigger('click');
                e.preventDefault();
                return false;
              }
              // ctrl + s
              if( ( e.ctrlKey || e.metaKey ) && e.which === 83){ // Check for the Ctrl key being pressed, and if the key = [S] (83)
                jQuery("#second-button").trigger('click');
                e.preventDefault();
                return false;
              }
            });

          }); // end document on ready
        </script>
        <div id="domain-output"></div>
      </div>

      <?php
    }

  }
}

new WP_API_Tester();
