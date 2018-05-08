<?php
/**
 * The plugin class.
 *
 * @package wp-api-tester
 */

/*
 * Plugin Name: PHP Evaluator
 * Plugin URI: https://wp-api-libraries.com
 * Description: A plugin used for running blocks of code at the click of a button (rather than a page refresh).
 * Author: WP API Libraries
 * Version: 1.2.0
 * Author URI: https://wp-api-libraries.com
 * GitHub Plugin URI: https://github.com/wp-api-libraries
 * GitHub Branch: master
 */

if ( ! function_exists( 'pp' ) ) {

	/**
	 * Advanced error log utility.
	 *
	 * Designed for debug/testing purposes, should not be used in production environments.
	 *
	 * Example usage:
	 *
	 * pp( 2 );
	 * Results in
	 * 2
	 *
	 * pp( array( 'tj' => 5 ) );
	 * Results in:
	 * Array
	 * (
	 *   [tj] => 5
	 * )
	 *
	 * pp( 'status', array( 'code' => 404 ) );
	 * Results in:
	 * status: Array
	 * (
	 *   [tj] => 5
	 * )
	 *
	 * @param  mixed $s If $a is not null, then $s should strictly be a string. Otherwise,
	 *                   it can be anything and will have print_r( $s, true ) wrapped
	 *                   around it.
	 * @param  mixed $a (Default: null) Used when you want to have a prepended description
	 *                  for the object being printed.
	 * @return [type]    [description]
	 */
	function pp( $s, $a = null ) {
		error_log( ( null === $a ? '' : $s . ': ' ) . print_r( ( null === $a ? $s : $a ), true ) );

		return ( null === $a ? $s : $a );
	}
}

/**
 * A secret function defined for the sake of explaining scope.
 *
 * @return string The way I feel about special people.
 */
function secret_message() {
	return 'I love you.';
}

register_activation_hook( __FILE__, 'tester_settings' );

/**
 * Initialize settings if they don't exist.
 *
 * @return void
 */
function tester_settings() {
	if ( empty( get_option( 'php_evaluator' ) ) ) {
		update_option(
			'php_evaluator', array(
				'tester_code'  => "<?php

function my_first_function(){
  \$a = 3;
  \$b = 5;

  return array(
    'Hello...' => \$a + \$b,
    '...world!' => \$b - \$a,
    'other-secret-stuff' => secret_message(),
  );
}

return my_first_function();",
				'output-style' => 'json',
			)
		);
	}
}

if ( ! class_exists( 'WP_API_Tester' ) ) {

	/**
	 * Management class for the PHP Evaluator.
	 */
	class WP_API_Tester {

		/**
		 * Constructinaliddidoo
		 *
		 * @return void
		 */
		public function __construct() {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );

			add_action( 'admin_menu', array( &$this, 'wpp_admin_menu' ) );
		}

		/**
		 * Register rest routes. Called on 'rest_api_init'.
		 *
		 * @return void
		 */
		public function register_routes() {
			register_rest_route(
				'api/v1', 'exec', array(
					'methods'             => 'post',
					'callback'            => array( &$this, 'run_code' ),
					'permission_callback' => array( &$this, 'permission_callback' ),
				)
			);

			register_rest_route(
				'api/v1', 'save', array(
					'methods'             => 'post',
					'callback'            => array( &$this, 'save_code' ),
					'permission_callback' => array( &$this, 'permission_callback' ),
				)
			);
		}

		/**
		 * Run the actual code.
		 *
		 * NOTE: THIS FUNCTION USES EVAL. BE VERY CAREFUL.
		 *
		 * @param  WP_HTTP_Request $data An object containing the parameter 'code', which
		 *                               will be run through eval. NOTE: eval() does not
		 *                               expect <?php, and should not be passed.
		 * @return mixed                 If 'output' param exists and is equal to print_r,
		 *                               then a string representation of the result of
		 *                               eval. Otherwise, the return of eval.
		 */
		public function run_code( $data ) {
			// Note with eval: Do not use any variables declared before eval's ran, since
			// scope is a thing.
			if ( ! empty( $data['output'] ) && 'print_r' === $data['output'] ) {
				return print_r( eval( $data['code'] ), true );
			} else {
				return eval( $data['code'] );
			}
		}

		/**
		 * Save code.
		 *
		 * @param  WP_HTTP_Request $data The request. Should contain the param 'code'.
		 *                               NOTE: also accepts the param 'output' which can
		 *                               be either print_r or json, for how the output
		 *                               should be formatted.
		 * @return mixed                 If 'output' == 'print_r', a string. Otherwise
		 *                               an array.
		 */
		public function save_code( $data ) {
			$settings                = get_option( 'php_evaluator' );
			$settings['tester_code'] = $data['code'];

			if ( isset( $data['output'] ) ) {
				$settings['output-style'] = $data['output'];
			}

			update_option( 'php_evaluator', $settings );

			$response = array(
				'success' => true,
				'message' => 'Code successfully saved.',
			);

			if ( 'print_r' === $settings['output-style'] ) {
				$response = print_r( $response, true );
			}

			return $response;
		}

		/**
		 * Add the admin menu page.
		 *
		 * @return void
		 */
		public function wpp_admin_menu() {
			add_management_page( 'PHP Evaluator', 'PHP Evaluator', 'manage_options', 'wp-apis', array( &$this, 'wpp_settings_page' ) ); // WP API DO ME will be missed.
		}

		/**
		 * Check if the current user has access to the rest routes.
		 *
		 * @param  WP_HTTP_Request $data The current request.
		 * @return WP_Error|bool         WP_Error if disallowed, true if allowed.
		 */
		public function permission_callback( $data ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return new WP_Error(
					'rest_forbidden_context', __( 'Sorry, you are not allowed to access this endpoint.', 'wp-apis' ), array(
						'status' => rest_authorization_required_code(),
					)
				);
			}

			return true;
		}

		/**
		 * Output the content of the settings page.
		 *
		 * @return void
		 */
		public function wpp_settings_page() {
			// Check settings.
			$settings = get_option( 'php_evaluator' );
			$defaults = array(
				'tester_code'  => "<?php

function my_first_function(){
  \$a = 3;
  \$b = 5;

  return array(
    'Hello...' => \$a + \$b,
    '...world!' => \$b - \$a,
    'other-secret-stuff' => secret_message(),
  );
}

return my_first_function();",
				'output-style' => 'json',
			);

			foreach ( $defaults as $key => $val ) {
				if ( ! isset( $settings[ $key ] ) ) {
					$settings[ $key ] = $val;
				}
			}

			$nonce = wp_create_nonce( 'wp_rest' );
			?>
			<meta id="localized-info" data-rest-nonce="<?php echo esc_attr( $nonce ); ?>">

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
			<option value="json" <?php selected( 'json' === $settings['output-style'] ); ?>>JSON</option>
			<option value="print_r" <?php selected( 'print_r' === $settings['output-style'] ); ?>>Print Recursively</option>
			</select>
		</p>
		<style type="text/css" media="screen">
		#editor {
			position: absolute;
		}
		</style>
		<script src="<?php echo esc_attr( plugin_dir_url( __FILE__ ) . '/assets/js/ace.js' ); ?>" type="text/javascript" charset="utf-8"></script>
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
					code    : code,
					output  : output
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
