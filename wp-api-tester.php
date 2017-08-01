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

if( !class_exists( 'WP_API_Tester' ) ){
  class WP_API_Tester{

    public function __construct(){
      add_action( 'rest_api_init', function () {
      	register_rest_route( 'api/v1', 'first', array(
      		'methods'	 => 'get',
      		'callback' => array( &$this, 'first_button' ),
          'permission_callback' => array( &$this, 'permission_callback' ),
      	));
      });

      add_action( 'rest_api_init', function () {
      	register_rest_route( 'api/v1', 'second', array(
      		'methods'	 => 'get',
      		'callback' => array( &$this, 'second_button' ),
          'permission_callback' => array( &$this, 'permission_callback' ),
      	));
      });

      add_action( 'admin_menu', array( &$this, 'wpp_admin_menu' ) );
    }

    /**
     * First function. To be filled with code that will be run upon hitting the first button (assuming PERMALINKS are enabled properly).
     * @return do you like pina coladas
     */
    public function first_button(){
      $response = '';

      return $response;
    }

    /**
     * Second function. To be filled with code that will be run upon hitting the second button (assuming PERMALINKS are enabled properly).
     * @return and getting caught in the rain
     */
    public function second_button(){
      $response = '';

      return $response;
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
        <p><input class="button-primary button" type="button" id="first-button" value="First Button"></p>
        <p><input class="button-secondary button" type="button" value="Second Button" id="second-button"></p>
        <script>
          jQuery(document).ready(function(){

            jQuery("#first-button").on('click', function(){
              var wpnonce = jQuery('#localized-info').attr('data-rest-nonce');
              jQuery.ajax({
                type: 'get',
                dataType: 'json',
                url: '/wp-json/api/v1/first',
                data: {
                  _wpnonce: wpnonce,
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
                }
              }); // end ajax

            }); // end button on click

            jQuery("#second-button").on('click', function(){
              var wpnonce = jQuery('#localized-info').attr('data-rest-nonce');
              jQuery.ajax({
                type: 'get',
                dataType: 'json',
                url: '/wp-json/api/v1/second',
                data: {
                  _wpnonce: wpnonce,
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
                }
              }); // end ajax

            }); // end button on click

          }); // end document on ready
        </script>
        <div id="domain-output"></div>
      </div>

      <?php
    }

  }
}

function pp($s, $a = ''){
  error_log(($a == ''?'':$a.': ').print_r($s, true));
}

new WP_API_Tester();
