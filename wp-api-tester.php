<?php
/*
 * Plugin Name: Bradley's Test es bueno
 * Plugin URI: https://imforza.com
 * Description: Perform API requests.
 * Author: WP API Libraries
 * Version: 1.0.0
 * Author URI: https://wp-api-libraries.com
 * GitHub Plugin URI: https://github.com/bradleymoore111
 * GitHub Branch: master
 */

// add_action("admin_init", "do_one_one_one_stuff");

function first_button(){
  $zenapi = get_new_zendesk_instance();
	$postmarkapi = new PostMarkAPI( '', 'an-api-key-here', true );

  $response = $postmarkapi->get_bounce_dump(1080459824);

  return $response;
}

function second_button(){
  $zenapi = get_new_zendesk_instance();
	$postmarkapi = new PostMarkAPI( 'an-api-key-here', '', true );

  $response = $postmarkapi->add_server( array(
   	'Name' => 'Test Server 2',
   	'Color' => 'Red',
  ));

  return $response;
}

function pp($s, $a = ''){
  error_log(($a == ''?'':$a.': ').print_r($s, true));
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'hostops/v1', 'poke/me/', array(
		'methods'	 => 'get',
		'callback' => 'first_button',
	));
});

add_action( 'rest_api_init', function () {
	register_rest_route( 'hostops/v1', 'list/me/', array(
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
    <a id="first-button" href="#">Actually that button</a><br>
    <a id="second-button" href="#">The second button (list tickets)</a>
    <script>
      jQuery(document).ready(function(){

        jQuery("#first-button").on('click', function(){

          jQuery.ajax({
            type: 'get',
            dataType: 'json',
            url: '/wp-json/hostops/v1/poke/me/',
            success: function(response) {
              if(response.duccess == false){
                alert(response.data);
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
            url: '/wp-json/hostops/v1/list/me/',
            success: function(response) {
              if(response.duccess == false){
                alert(response.data);
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
