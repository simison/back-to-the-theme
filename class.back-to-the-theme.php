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

			if ( isset( $_GET['back-to-the-theme-hide-admin-bar'] ) ) {
				add_filter( 'show_admin_bar', '__return_false' );
			}
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

	public static function get_preview_url( $theme, $id, $secret, $hide_admin_bar, $side ) {
		$query_args = array(
			'back-to-the-theme-secret' => $secret,
			'back-to-the-theme' => $theme,
			'flux-capacitor-cache-buster' => time(),
		);

		if ( $hide_admin_bar ) {
			$query_args['back-to-the-theme-hide-admin-bar'] = true;
		}

		$url = $side === 'editor'
			? admin_url( 'post.php?post=' . absint( $id ) . '&action=edit' )
			: get_permalink( $id );

		return add_query_arg( $query_args, $url );
	}

	public static function render_admin_page() {
		wp_enqueue_style( 'back-to-the-theme' );
		$themes = wp_get_themes();

		?>
		<div class="wrap">
			<h1>Back To The Theme</h1>
			<p>See a page on different themes simultaneously, just like that!</p>
			<?php
				self::render_form( $themes );
				self::render_previews( $themes );
			?>
		</div>
		<?php
	}

	static function get_side() {
		return ! empty( $_POST['back-to-the-theme-side'] ) && in_array( $_POST['back-to-the-theme-side'], array( 'view', 'editor' ) )
			? $_POST['back-to-the-theme-side']
			: 'view';
	}

	static function render_previews( $themes ) {
		if ( empty( $_POST[ 'back_to_the_theme' ] ) ) {
			return;
		}

		if ( ! isset( $_POST[ 'page_id' ] ) && ! isset( $_POST[ 'back-to-the-theme-post-id' ] ) ) {
			return;
		}

		if (
			! isset( $_POST['back_to_my_theme_nonce'] )
			|| ! wp_verify_nonce( $_POST['back_to_my_theme_nonce'], 'generate_previews' )
		) {
			wp_die( __( 'Sorry, there was a problem submitting your data. Try again.', 'back-to-the-theme' ) );
			exit;
		}

		?>
		<div id="back-to-the-theme-previews" class="back-to-the-theme-container">
			<?php
				$secret = self::update_secret();
				$side = self::get_side();
				$hide_admin_bar = isset( $_POST['back-to-the-theme-hide-admin-bar'] );
				$id = isset( $_POST['back-to-the-theme-post-id'] ) && intval( $_POST['back-to-the-theme-post-id'] )
					? intval( $_POST['back-to-the-theme-post-id'] )
					: absint( $_POST['page_id'] );

				foreach( $_POST['back_to_the_theme'] as $theme => $k ) {
					if ( ! isset( $themes[ $theme ] ) ) {
						echo sprintf( __( 'Requested theme "%s" was not available.', 'back-to-the-theme' ), $theme );
						continue;
					}

					$url = self::get_preview_url( $theme, $id, $secret, $hide_admin_bar, $side );
					$theme_name = $themes[ $theme ]->get( 'Name' );
					$theme_version = $themes[ $theme ]->get( 'Version' );
					?>
					<div class="back-to-the-theme-preview" id="back-to-the-theme-preview-<?php esc_attr_e( $theme ); ?>">
						<h2 class="alignleft">
							<?php echo esc_html( $theme_name ); ?>
							<?php if ( ! empty( $theme_version ) ): ?>
								<small><?php
									echo sprintf( _x( 'v%s', 'version number, e.g. v1.0', 'back-to-the-theme' ), esc_html( $theme_version ) );
								?></small>
							<?php endif; ?>
						</h2>
						<p>
							<a href="<?php echo esc_url( $url ); ?>" target="_blank" class="button-secondary alignright"><?php
								esc_html_e( 'Open in new tab', 'back-to-the-theme' );
							?></a>
						</p>
						<iframe
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
		$hide_admin_bar = isset( $_POST['back-to-the-theme-hide-admin-bar'] ) || empty( $_POST );

		?>
		<form method="post" action="<?php echo admin_url('tools.php?page=back-to-the-theme'); ?>#back-to-the-theme-previews">
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
						value="<?php echo isset( $_POST['back-to-the-theme-post-id'] ) ? esc_attr( $_POST['back-to-the-theme-post-id'] ) : ''; ?>"
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

			<label>
				<input
					<?php checked( '1', $hide_admin_bar ); ?>
					id="back-to-the-theme-hide-admin-bar"
					name="back-to-the-theme-hide-admin-bar"
					type="checkbox"
					value="1"
				>
				<?php esc_html_e( 'Hide admin bar in previews', 'back-to-the-theme' ); ?>
			</label>

			<br/><br />

			<strong id="back-to-the-theme-themes"><?php esc_html_e( 'Choose Themes', 'back-to-the-theme' ); ?></strong><br/>
			<?php
				foreach( $themes as $theme_slug => $theme ) {
					$theme_checked = isset( $_POST['back_to_the_theme'] ) && isset( $_POST['back_to_the_theme'][ $theme_slug ] ) ? true : false;

					?>
					<label for="back_to_the_theme_<?php esc_attr_e( $theme_slug ); ?>">
						<input
							<?php checked( '1', $theme_checked ); ?>
							id="back_to_the_theme_<?php esc_attr_e( $theme_slug ); ?>"
							name="back_to_the_theme[<?php esc_attr_e( $theme_slug ); ?>]"
							type="checkbox"
							value="1"
						>
						<?php echo esc_html( $theme->get( 'Name' ) ); ?>
						<?php if ( ! empty( $theme->get( 'Version' ) ) ): ?>
							<small><?php
								echo sprintf( _x( 'v%s', 'version number, e.g. v1.0', 'back-to-the-theme' ), esc_html( $theme->get( 'Version' ) ) );
							?></small>
						<?php endif; ?>
					</label>
					<?php if ( $theme_checked ): ?>
						(<a href="#back-to-the-theme-preview-<?php esc_attr_e( $theme_slug ); ?>"><?php
							esc_html_e( 'Jump to preview', 'back-to-the-theme' );
						?></a>)
					<?php endif; ?>
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
	}
}
