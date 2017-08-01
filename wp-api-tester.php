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

/**
 * First function. To be filled with code that will be run upon hitting the first button (assuming PERMALINKS are enabled properly).
 * @return do you like pina coladas
 */
function first_button(){
  $response = '';

  return $response;
}

/**
 * Second function. To be filled with code that will be run upon hitting the second button (assuming PERMALINKS are enabled properly).
 * @return and getting caught in the rain
 */
function second_button(){
  $response = '';

  return $response;
}

function pp($s, $a = ''){
  error_log(($a == ''?'':$a.': ').print_r($s, true));
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'api/v1', 'first', array(
		'methods'	 => 'get',
		'callback' => 'first_button',
	));
});

add_action( 'rest_api_init', function () {
	register_rest_route( 'api/v1', 'second', array(
		'methods'	 => 'get',
		'callback' => 'second_button',
	));
});

add_action( 'admin_menu', 'wpp_admin_menu' );
function wpp_admin_menu(){
  register_setting( 'wpp_defaults', 'wpp_defaults' );
  add_management_page( 'WP API Do Me', 'WP API Do Me', 'manage_options', 'wp-apis', 'wpp_settings_page' );
}

function wpp_settings_page(){
  ?>

  <div class="wrap">
    <h1>That button</h1>
    <hr>
    <h2>It's that button</h2>
    <p><input class="button-primary button" type="button" id="first-button" value="First Button"></p>
    <p><input class="button-secondary button" type="button" value="Second Button" id="second-button"></p>
    <script>
      jQuery(document).ready(function(){

        jQuery("#first-button").on('click', function(){

          jQuery.ajax({
            type: 'get',
            dataType: 'json',
            url: '/wp-json/api/v1/first',
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

          jQuery.ajax({
            type: 'get',
            dataType: 'json',
            url: '/wp-json/api/v1/second',
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
