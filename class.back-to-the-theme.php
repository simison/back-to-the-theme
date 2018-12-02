<?php

class BackToTheTheme {
	private static $mshots_height = '960'; // max 960
	private static $mshots_url = 'https://s0.wordpress.com/mshots/v1/'; // See https://github.com/Automattic/mShots
	private static $mshots_width = '1280'; // max 1280
	private static $secret_length = 64; // How long secret strings are?
	private static $secret_valid = 600; // How long secrets are valid? (in seconds)
	private static $version = '1.0.0';
	private static $site_themes;

	public static function init() {
		add_filter( 'template', array( __CLASS__, 'switch_template_on_request' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'initialize_api' ) );
	}

	public static function initialize_api() {
		register_rest_route( 'back-to-the-theme/v1', '/screenshots', array(
			'methods' => 'GET',
			'callback' => array( __CLASS__, 'handle_api_request' ),
			'args' => array(
				'secret' => array(
					'validate_callback' => function( $param, $request, $key ) {
						return self::is_valid_secret( $param );
					},
				),
				'themes' => array(
					'validate_callback' => function( $param, $request, $key ) {
						return is_string( $param ) && ! empty( $param );
					},
				),
				'page_id' => array(
					'validate_callback' => function( $param, $request, $key ) {
						return is_numeric( $param );
					},
				),
			),
		) );
	}

	public static function pick_valid_themes( $themes_string ) {
		$themes = explode( ',', $themes_string );
		self::$site_themes = wp_get_themes();;

		return array_filter( $themes, function( $theme ) {
			return (boolean) self::$site_themes[ $theme ];
		} );
	}

	public static function handle_api_request( $request ) {
		$themes = self::pick_valid_themes( $request->get_param( 'themes' ) );

		if ( ! count( $themes ) ) {
			return rest_ensure_response( new WP_Error( 'invalid-themes', __( 'Provide at least one theme that has been installed at your site.', 'back-to-the-themes' ) ) );
		}

		$page_id = $request->get_param( 'page_id' );
		$secret = $request->get_param( 'secret' );

		$response = array();

		foreach( $themes as $theme ) {
			// https://codex.wordpress.org/Function_Reference/wp_get_http_headers
			$url = self::get_capture_url( $theme, $page_id, $secret );
			$response = wp_remote_get( $url, array(
				'redirection' => 0,
			) );
			$code = wp_remote_retrieve_response_code( $response );
			$headers = wp_remote_retrieve_headers( $response );
			$headers_remote = wp_get_http_headers( $url );
			l('----------',$response, $code, $headers, $headers_remote );
		}

		return rest_ensure_response( $response );
	}

	public static function register_assets() {
		wp_register_script(
			'back-to-the-theme',
			plugins_url( 'back-to-the-theme.js', __FILE__ ),
			array( 'jquery' ),
			self::$version,
			true
		);
		wp_register_style(
			'back-to-the-theme',
			plugins_url( 'back-to-the-theme.css', __FILE__ ),
			array(),
			self::$version
		);
	}

	public static function add_menu() {
		add_submenu_page(
			'tools.php',
			'Back To The Theme',
			'Back To The Theme',
			'manage_options',
			'back-to-the-theme',
			array( __CLASS__, 'render_page' )
			# ,
		);
	}

	public static function update_secret() {
		$secret = wp_generate_password( self::$secret_length, false, false );
		update_option( 'back-to-the-theme-secret', $secret );
		// Switching theme with this key is allowed for 10 minutes in to the future
		update_option( 'back-to-the-theme-valid', time() + self::$secret_valid );
		return $secret;
	}

	public static function is_valid_secret( $secret ) {
		return ! empty( $secret )
			&& $secret === get_option( 'back-to-the-theme-secret' )
			&& time() < get_option( 'back-to-the-theme-valid' );
	}

	public static function switch_template_on_request( $template ) {
		if( isset( $_GET['back-to-the-theme-secret'] )
			&& self::is_valid_secret( $_GET['back-to-the-theme-secret'] )
			&& isset( $_GET['back-to-the-theme'] )
			&& ! empty( $_GET['back-to-the-theme'] ) ) {
			$theme = wp_get_theme( $_GET['back-to-the-theme'] );
			return $theme ? $theme->template : $template;
		}
		return $template;
	}

	public static function get_capture_url( $theme, $id, $secret ) {
		$url = add_query_arg( array(
			'back-to-the-theme-secret' => $secret,
			'back-to-the-theme' => $theme,
			'flux-capacitor' => time(), // cache buster
		), get_permalink( $id ) );

		$api_url = self::$mshots_url . urlencode( $url );

		return add_query_arg( array(
			'w' => self::$mshots_width,
			'h' => self::$mshots_height,
		), $api_url );
	}

	public static function render_page() {
		add_action( 'admin_footer', array( __CLASS__, 'add_fullscreen_container' ) );
		wp_enqueue_style( 'back-to-the-theme' );
		$site_themes = wp_get_themes();

		?>
		<div class='wrap'>
			<h1>Back To The Theme</h1>

			<p>Render screenshots of a page on different themes.</p>

			<img
				src="<?php echo plugins_url( 'delorean.jpg', __FILE__ ); ?>"
				alt="If my calculations are correct, when this baby hits 88 miles per hour... you're gonna see some serious shit."
				class="alignright back-to-the-theme-delorean"
			/>

			<form method="post" action="<?php echo admin_url('tools.php?page=back-to-the-theme'); ?>">
				<?php wp_nonce_field( 'generate_previews', 'back_to_my_theme_nonce' ); ?>

				<label>
					<strong><?php esc_html_e( 'Choose a page', 'back-to-the-theme' ); ?></strong><br/>
					<?php wp_dropdown_pages(); ?>
				</label>

				<br/><br />

				<strong><?php esc_html_e( 'Choose Themes', 'back-to-the-theme' ); ?></strong><br/>
				<?php
					foreach( $site_themes as $theme_slug => $theme ) {
						$theme_checked = isset( $_POST['back_to_the_theme'] ) && isset( $_POST['back_to_the_theme'][ $theme_slug ] ) ? true : false;
						$theme_version = $theme->get( 'Version' );
						?>
						<label for="back_to_the_theme_<?php esc_attr_e( $theme_slug ); ?>">
							<input
								<?php checked( '1', $theme_checked ); ?>
								id="back_to_the_theme_<?php esc_attr_e( $theme_slug ); ?>"
								name="back_to_the_theme[<?php esc_attr_e( $theme_slug ); ?>]"
								type="checkbox"
								value="1"
							>
							<?php esc_html_e( $theme->get( 'Name' ) ); ?>
							<?php if ( ! empty( $theme_version ) ): ?>
								<small>v<?php esc_html_e( $theme_version ); ?></small>
							<?php endif; ?>
						</label>
						<br/>
						<?php
					}
				?>

				<br/><br />

				<button class="button button-primary" type="submit">
					<?php esc_html_e( 'Do it!', 'back-to-the-theme' ); ?>
				</button>

			</form>
			<?php

			if ( isset( $_POST['back_to_the_theme'] )
				&& ! empty ( $_POST['back_to_the_theme'] )
				&& isset( $_POST['page_id'] )
				&& ! empty( $_POST['page_id'] ) ) {

				if (
					! isset( $_POST['back_to_my_theme_nonce'] )
					|| ! wp_verify_nonce( $_POST['back_to_my_theme_nonce'], 'generate_previews' )
				) {
					wp_die( __( 'Sorry, there was a problem submitting your data. Try again.', 'back-to-the-theme' ) );
					exit;
				}

				$secret = self::update_secret();
				$page_id = $_POST['page_id'];
				$themes = array_keys( $_POST['back_to_the_theme'] );

				$render_themes = array(
					'apiParams' => array (
						'secret'   => esc_html( $secret ),
						'page_id'  => esc_html( $page_id ),
						'themes'   => esc_html( implode( ',', $themes ) ),
					),
					'apiRoot' => esc_url_raw( rest_url() ),
				);

				wp_localize_script( 'back-to-the-theme', 'backToTheTheme', $render_themes );

				?>
				<div
					data-page_id="<?php esc_attr_e( $page_id ); ?>"
					data-secret="<?php esc_attr_e( $secret ); ?>"
					data-themes="<?php esc_attr_e( implode( ',', $themes ) ); ?>"
					id="back-to-the-theme"
				>
					<div class="theme-browser rendered">
						<div class="themes wp-clearfix">
						<?php

						foreach( $themes as $theme => $k ) {
							if ( isset( $site_themes[ $theme ] ) ) {
								?>
								<div class="theme">
									<div class="theme-screenshot">
										<img
											alt="<?php echo esc_attr( $theme ); ?>"
											class="back-to-the-theme-screenshot"
											data-theme="<?php esc_attr_e( $theme ); ?>"
											scale="0"
											src="<?php echo plugins_url( 'delorean.gif', __FILE__ ); ?>"
										/>
									</div>
									<div class="theme-id-container">
										<h2 class="theme-name">
											<?php esc_html_e( $site_themes[ $theme ]->get( 'Name' ) ); ?>
											<?php
											$theme_version = $site_themes[ $theme ]->get( 'Version' );
											if ( ! empty( $theme_version ) ): ?>
												<small>v<?php esc_html_e( $theme_version ); ?></small>
											<?php endif; ?>
										</h2>
									</div>
								</div>
								<?php
							}
						}

						?>
						</div>
					</div>
				</div>
			<?php
			}
		?>
		</div>
		<?php

		wp_enqueue_script( 'back-to-the-theme' );
	}

	public static function add_fullscreen_container() {
		echo '<div id="back-to-the-theme-fullscreen"></div>';
	}
}
