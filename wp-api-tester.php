<?php
/*
 * Plugin Name: PHP Evaluator
 * Plugin URI: https://wp-api-libraries.com
 * Description: A plugin used for running blocks of code at the click of a button (rather than a page refresh).
 * Author: WP API Libraries
 * Version: 1.1.1
 * Author URI: https://wp-api-libraries.com
 * GitHub Plugin URI: https://github.com/wp-api-libraries
 * GitHub Branch: master
 */

if( !function_exists( 'pp' ) ){
  function pp($s, $a = null){
    error_log(($a === null?'':$s.': ').print_r(($a === null?$s:$a), true));

    return ($a === null?$s:$a);
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
      add_action( 'rest_api_init', array( $this, 'register_routes' ) );

      add_action( 'admin_menu', array( &$this, 'wpp_admin_menu' ) );
    }

    public function register_routes() {
    	register_rest_route( 'api/v1', 'exec', array(
    		'methods'	 => 'post',
    		'callback' => array( &$this, 'run_code' ),
        'permission_callback' => array( &$this, 'permission_callback' ),
    	));

    	register_rest_route( 'api/v1', 'save', array(
    		'methods'	 => 'post',
    		'callback' => array( &$this, 'save_code' ),
        'permission_callback' => array( &$this, 'permission_callback' ),
    	));
    }

    public function run_code( $data ){
      $result = eval( $data['code'] );

      if( ! empty( $data['output'] ) && $data['output'] == 'print_r' ){
        $result = print_r( $result, true );
      }

      return $result;
    }

    public function save_code( $data ){
      $settings = get_option( 'php_evaluator' );
      $settings['tester_code'] = $data['code'];
      if( isset( $data['output'] ) ){
        $settings['output-style'] = $data['output'];
      }
      update_option( 'php_evaluator', $settings );
      $response = array("success" => true, "message" => "Code successfully saved." );

      if( $settings['output-style'] == 'print_r' ){
        $response = print_r( $response, true );
      }

      return $response;
    }

    public function wpp_admin_menu(){
      add_management_page( 'PHP Evaluator', 'PHP Evaluator', 'manage_options', 'wp-apis', array( &$this, 'wpp_settings_page' ) ); // WP API DO ME will be missed.
    }

    public function permission_callback( $data ){
      if ( ! current_user_can( 'manage_options' ) ) {
  			 return new WP_Error(
  				 'rest_forbidden_context', __( 'Sorry, you are not allowed to access this endpoint.', 'wp-apis' ), array(
  					 'status' => rest_authorization_required_code(),
  				 )
  			 );
  		}

      return true;
    }

    public function wpp_settings_page(){
      // Check settings.
      $settings = get_option( 'php_evaluator' );
      $defaults = array(
        'tester_code' => "<?php

function my_first_function(){
    $" . "a = 3;
    $" . "b = 5;

    return array(
        'Hello...' => $" . "a + $" . "b,
        '...world!' => $" . "b - $" . "a,
        'other-secret-stuff' => secret_message(),
    );
}

return my_first_function();",
        'output-style' => 'json'
      );

      foreach( $defaults as $key => $val ){
        if( ! isset( $settings[$key] ) ){
          $settings[$key] = $val;
        }
      }

      $nonce = wp_create_nonce( 'wp_rest' );
      ?>
      <meta id="localized-info" data-rest-nonce="<?php echo $nonce; ?>">

      <div class="wrap">
        <h1>That button!</h1>
        <hr>
        <h2><?php esc_html_e( 'It\'s that button', 'wp-apis' ); ?></h2>
        <p><?php esc_html_e( 'This editor is evaluated within the wp-api-tester.php plugin file, and has access to all functions and GLOBAL variables that would otherwise be available at that time.', 'wp-apis' ); ?></p>
        <p>As a demonstration, go ahead and click the first Run Code button! The <code>secret_message()</code> function is defined to the rest of PHP, to help illustrate my point.</p>
        <div style="width: 80%;height: 550px;">
          <div style="width: 80%;height: 550px;" id="editor"><?php echo esc_html( $settings['tester_code'] ); ?></div>
        </div>
        <p>
          <input class="button-primary button" type="button" id="exec-button" value="Run Code">
          <input class="button-secondary button" type="button" id="save-button" value="Save Code">
          <select id="output-style">
            <option value="json" <?php selected( $settings['output-style'] == 'json' ); ?>>JSON</option>
            <option value="print_r" <?php selected( $settings['output-style'] == 'print_r' ); ?>>Print Recursively</option>
          </select>
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

            jQuery("#exec-button").on('click', function(){
              var wpnonce = jQuery('#localized-info').attr('data-rest-nonce');

              var code = editor.getValue();

              code = code.substring( code.indexOf("<" + "?php") + 5, code.length);

              var output = jQuery("#output-style").val();

              jQuery.ajax({
                type: 'post',
                dataType: 'json',
                url: '/wp-json/api/v1/exec',
                data: {
                  _wpnonce: wpnonce,
                  code: code,
                  output: output
                },
                success: function(response) {
                  if(response && response.success == false){
                    console.log(response.data);
                  }else{
                    if( output == 'json' ){
                      if( response && response.body && typeof response.body == 'string' ){
                        response.body = JSON.parse( response.body );
                      }

                      jQuery("#domain-output").html( '<pre>' + JSON.stringify( response, null, 4 ) + '</pre>');
                    }else{ // print_r
                      jQuery("#domain-output").html( '<pre>' + response + '</pre>' );
                    }
                  }
                },
                error: function(response){
                  console.log( response );
                  jQuery("#domain-output").html( '<pre>' + JSON.stringify( response, null, 4 ) + '</pre>');
                }
              }); // end ajax

            }); // end button on click

            jQuery("#save-button").on('click', function(){
              var wpnonce = jQuery('#localized-info').attr('data-rest-nonce');

              var code = editor.getValue();
              var output = jQuery("#output-style").val();

              jQuery.ajax({
                type: 'post',
                dataType: 'json',
                url: '/wp-json/api/v1/save',
                data: {
                  _wpnonce: wpnonce,
                  code: code,
                  output: output
                },
                success: function(response) {
                  if(response && response.success == false){
                    console.log(response.data);
                  }else{
                    if( output == 'json' ){
                      if( response && response.body && typeof response.body == 'string' ){
                        response.body = JSON.parse( response.body );
                      }

                      jQuery("#domain-output").html( '<pre>' + JSON.stringify( response, null, 4 ) + '</pre>');
                    }else{ // print_r
                      jQuery("#domain-output").html( '<pre>' + response + '</pre>' );
                    }
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
                jQuery("#exec-button").trigger('click');
                e.preventDefault();
                return false;
              }
              // ctrl + s
              if( ( e.ctrlKey || e.metaKey ) && e.which === 83){ // Check for the Ctrl key being pressed, and if the key = [S] (83)
                jQuery("#save-button").trigger('click');
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
