<?php

class BackToTheTheme {
	private static $version = '1.0.0';

	// See https://github.com/Automattic/mShots
	private static $mshots_url = 'https://s0.wordpress.com/mshots/v1/';
	private static $mshots_width = '1280'; // max 1280
	private static $mshots_height = '960'; // max 960

	public static function init() {
		add_filter( 'template', array( __CLASS__, 'switch_template_on_request' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
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
		$secret = wp_generate_password( 64, false, false );
		update_option( 'back-to-the-theme-secret', $secret );
		// Switching theme with this key is allowed for 10 minutes in to the future
		update_option( 'back-to-the-theme-valid', time() + 600 );
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
		wp_enqueue_script( 'back-to-the-theme' );
		wp_enqueue_style( 'back-to-the-theme' );
		$themes = wp_get_themes();

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
					foreach( $themes as $theme_slug => $theme ) {
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

			<div id="back-to-the-theme">
				<div class="theme-browser rendered">
					<div class="themes wp-clearfix">
					<?php

					if ( isset( $_POST[ 'back_to_the_theme' ] ) && isset( $_POST[ 'page_id' ] ) ) {

						if (
							! isset( $_POST['back_to_my_theme_nonce'] )
							|| ! wp_verify_nonce( $_POST['back_to_my_theme_nonce'], 'generate_previews' )
						) {
							wp_die( __( 'Sorry, there was a problem submitting your data. Try again.', 'back-to-the-theme' ) );
							exit;
						}

						$secret = self::update_secret();
						$page_id = $_POST['page_id'];

						foreach( $_POST['back_to_the_theme'] as $theme => $k ) {
							if ( isset( $themes[ $theme ] ) ) {
								?>
								<div class="theme">
									<div class="theme-screenshot">
										<img
											src="<?php echo self::get_capture_url( $theme, $page_id, $secret ); ?>"
											alt="<?php echo esc_attr( $theme ); ?>"
											scale="0"
											style="width: 100%"
										/>
									</div>
									<div class="theme-id-container">
										<h2 class="theme-name">
											<?php echo $themes[ $theme ]->get( 'Name' ); ?>
										</h2>
									</div>
								</div>
								<?php
							}
						}
						?>
						</div>
					</div>
					<?php
				}

				?>
			</div>
		</div>
		<?php
	}

	public static function add_fullscreen_container() {
		echo '<div id="back-to-the-theme-fullscreen"></div>';
	}
}
