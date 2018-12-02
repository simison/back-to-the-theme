( function( $ ) {
	const settings = window && window.backToTheTheme ? window.backToTheTheme : false;

	if ( settings ) {
		const apiUrl = `${ settings.apiRoot }back-to-the-theme/v1/screenshots?${ $.param( settings.apiParams ) }`;

		[ 5, 10, 15 ].forEach( function( sec ) {
			setTimeout( function() {
				$.get( apiUrl, function( data ) {
					console.log(data);
				} );
			}, sec * 1000 );
		} );
	}
/*
	var $themeWrapper = $( '#back-to-the-theme' );


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

		[ 5, 10, 15 ].forEach( function( sec ) {
			setTimeout( function() {
				refreshImages();
			}, sec * 1000 );
		} );
	}
	*/
} )( jQuery );
