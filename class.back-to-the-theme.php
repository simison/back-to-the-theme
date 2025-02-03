<?php

class BackToTheTheme {
	private static $version = '1.2.0';

	public static function init() {
		if( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		}

		if( self::is_render_request() ) {
			// @TODO don't do the switch if requested theme is already currently active theme
			add_filter( 'template', array( __CLASS__, 'switch_template' ) );
			add_filter( 'stylesheet', array( __CLASS__, 'switch_stylesheet' ) );

			// always hide the admin bar in the preview.
			add_filter( 'show_admin_bar', '__return_false' );

		}
	}

	/**
	 * Determine if current request is to switch and render preview
	 */
	public static function is_render_request() {
		return isset( $_GET['back-to-the-theme-secret'] )
			&& self::is_valid_secret( $_GET['back-to-the-theme-secret'] )
			&& isset( $_GET['back-to-the-theme'] )
			&& ! empty( $_GET['back-to-the-theme'] );
	}

	public static function register_assets() {
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
			'switch_themes',
			'back-to-the-theme',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	public static function update_secret() {
		$secret = wp_generate_password( 64, false, false );
		update_option( 'back-to-the-theme-secret', $secret );
		// Switching theme with this key is allowed for 24 hours in to the future
		update_option( 'back-to-the-theme-valid', time() + DAY_IN_SECONDS );
		return $secret;
	}

	public static function is_valid_secret( $secret ) {
		return ! empty( $secret )
			&& $secret === get_option( 'back-to-the-theme-secret' )
			&& time() < get_option( 'back-to-the-theme-valid' );
	}

	public static function switch_template( $template ) {
		$theme = wp_get_theme( $_GET['back-to-the-theme'] );
		return $theme ? $theme->template : $template;
	}

	public static function switch_stylesheet( $stylesheet ) {
		$theme = wp_get_theme( $_GET['back-to-the-theme'] );
		return $theme ? $theme->stylesheet : $stylesheet;
	}

	public static function get_preview_url( $theme, $id, $secret, $side ) {

		$query_args = array(
			'back-to-the-theme-secret' 			=> $secret,
			'back-to-the-theme' 				=> $theme,
			'flux-capacitor-cache-buster' 		=> time(),
			'back-to-the-theme-hide-admin-bar'  => true,
		);

		if ( empty( $id ) ) {
			$url = home_url();
		} else {
			$url = $side === 'editor'
			? admin_url( 'post.php?post=' . absint( $id ) . '&action=edit' )
			: get_permalink( $id );
		}

		return add_query_arg( $query_args, $url );
	}

	public static function render_admin_page() {
		wp_enqueue_style( 'back-to-the-theme' );
		$themes = wp_get_themes();

		?>
		<div class="wrap">
			<h1 id="back-to-the-theme-themes">Back To The Theme</h1>
			<p>See a page on different themes simultaneously, just like that!</p>
			<?php
				self::render_form( $themes );
				self::render_previews( $themes );
			?>
		</div>
		<?php
	}

	static function get_side() {
		return isset( $_GET['back-to-the-theme-side'] ) && in_array( $_GET['back-to-the-theme-side'], array( 'view', 'editor' ) )
			? $_GET['back-to-the-theme-side']
			: 'view';
	}

	static function render_previews( $themes ) {

		?>
		<div id="back-to-the-theme-previews" class="back-to-the-theme-container">
		<script type="text/javascript">
		function resizeIframe(iframe) {
			document.getElementById(iframe.id + '-loading').style.display = 'none';
			iframe.height = iframe.contentWindow.document.body.scrollHeight + "px";
		}
		</script>
			<?php
				$secret = self::update_secret();
				$side = self::get_side();


				$id = isset( $_GET['back-to-the-theme-post-id'] ) && intval( $_GET['back-to-the-theme-post-id'] )
					? intval( $_GET['back-to-the-theme-post-id'] )
					: absint( $_GET['page_id'] );

				$tags = isset( $_GET['tags'] ) ? explode(',', $_GET['tags'] ) : [];

				foreach( $themes as $theme => $k ) {

					if( ! empty( $tags ) && ! array_intersect( $tags, $k->get( 'Tags' ) ) ) {
						continue;
					}

					$url = self::get_preview_url( $theme, $id, $secret, $side );
					$theme_name = $themes[ $theme ]->get( 'Name' );
					$theme_version = $themes[ $theme ]->get( 'Version' );

					?>
					<div class="back-to-the-theme-preview-info" >
						<span id="theme-<?php esc_attr_e( $theme ); ?>-loading" class="spinner is-active"></span>
						<p><strong><?php echo esc_html( $theme_name ); ?></strong>  <small><?php
									echo sprintf( _x( 'v%s', 'version number, e.g. v1.0', 'back-to-the-theme' ), esc_html( $theme_version ) );
								?></small> theme.</p>
								<p style="max-width: 80%"> Tags: <?php echo implode( ', ',  $k->get( 'Tags' ) ); ?></p> </a>
								<a href="<?php echo esc_url( $url ); ?>" target="_blank" class="button-secondary alignright"><?php
								esc_html_e( 'Open in new tab', 'back-to-the-theme' );
							?></a>
					</div>
					<div class="back-to-the-theme-preview" id="back-to-the-theme-preview-<?php esc_attr_e( $theme ); ?>">
						<iframe
							id="theme-<?php esc_attr_e( $theme ); ?>"
							onload="resizeIframe(this)"
							loading="lazy"
							frameborder="0"
							src="<?php echo esc_url( $url ); ?>"
							title="<?php esc_attr_e( $theme ); ?>"
						></iframe>
						<p>
							<a href="#back-to-the-theme-themes" class="button-secondary">↑ <?php
								esc_html_e( 'Back to the list', 'back-to-the-theme' );
							?></a>
						</p>
					</div>
					<?php
				}
			?>
		</div>
		<?php
	}

	static function render_form( $themes ) {
		$side = self::get_side();
		$hide_admin_bar = isset( $_GET['back-to-the-theme-hide-admin-bar'] ) || empty( $_GET );

		?>
		<form method="get" action="<?php echo admin_url('tools.php?page=back-to-the-theme'); ?>#back-to-the-theme-previews">
			<input type="hidden" name="page" value="back-to-the-theme" />
			<?php wp_nonce_field( 'generate_previews', 'back_to_my_theme_nonce' ); ?>

			<label>
				<strong><?php esc_html_e( 'Choose a page', 'back-to-the-theme' ); ?></strong><br/>
				<?php wp_dropdown_pages(); ?>
			</label>
			<br />
			<label>
					<strong><?php esc_html_e( 'Or enter a Post ID', 'back-to-the-theme' ); ?></strong><br/>
					<input
						id="back-to-the-theme-post-id"
						name="back-to-the-theme-post-id"
						type="text"
						value="<?php echo isset( $_GET['back-to-the-theme-post-id'] ) ? esc_attr( $_GET['back-to-the-theme-post-id'] ) : ''; ?>"
					>
			</label>

			<br /><br />

			<p>
				<label>
					<input type="radio" name="back-to-the-theme-side" value="view" <?php checked( 'view', $side ); ?> />
					<?php _e( 'Show view side', 'back-to-the-theme' ); ?>
				</label>
				<br />
				<label>
					<input type="radio" name="back-to-the-theme-side" value="editor" <?php checked( 'editor', $side ); ?> />
					<?php _e( 'Show editor side', 'back-to-the-theme' ); ?>
				</label>
			</p>

			<br/>

			<button class="button button-primary" type="submit">
				<?php esc_html_e( 'Do it!', 'back-to-the-theme' ); ?>
			</button>
		</form>
		<?php
	}
}
