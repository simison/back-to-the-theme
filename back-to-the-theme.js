( function( $ ) {
	var $themeImages = $( '#back-to-the-theme img' );
	var $themes = $( '#back-to-the-theme .theme' );
	var $fullScreenContainer = $( '#back-to-the-theme-fullscreen' );

	function showFullScreen( img ) {
		$fullScreenContainer
			.css( 'background-image', 'url( ' + img + ')' )
			.fadeIn( 200 );
	}

	function refreshImages() {
		$themeImages.each( function( $index, theme ) {
			var src = $( theme ).attr( 'src' );
			$( theme ).attr( 'src', src + '&refresh=' + Date.now() );
		} );
	}

	if ( $themeImages && $themeImages.length ) {

		$fullScreenContainer.click( function( e ) {
			e.preventDefault();
			$( this ).fadeOut( 200 );
		} );

		$themes.each( function( $index, theme ) {
			$( theme ).click( function( e ) {
				e.preventDefault();
				showFullScreen( $( theme ).find( 'img' ).attr( 'src' ) );
			} );
		} );

		[ 5, 10, 15, 20, 25, 30, 35 ].forEach( function( sec ) {
			setTimeout( function() {
				refreshImages();
			}, sec * 1000 );
		} );
	}
} )( jQuery );
