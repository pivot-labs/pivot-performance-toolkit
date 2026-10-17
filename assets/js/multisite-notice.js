( function () {
	'use strict';

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.pivot-performance-toolkit-multisite-notice .notice-dismiss' );
		if ( ! button ) {
			return;
		}

		var notice = button.closest( '.pivot-performance-toolkit-multisite-notice' );
		var data = new window.FormData();
		data.append( 'action', 'pivot_performance_toolkit_dismiss_multisite_notice' );
		data.append( '_ajax_nonce', notice.getAttribute( 'data-nonce' ) );

		window.fetch( notice.getAttribute( 'data-ajax-url' ), {
			method: 'POST',
			credentials: 'same-origin',
			body: data,
		} );
	} );
}() );
