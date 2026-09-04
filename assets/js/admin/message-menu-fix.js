jQuery( document ).ready( function () {
	const pageText = 'edit.php?post_type=sensei_message';
	const areWeInAdminMessages = document.location.href.includes( pageText );
	if ( areWeInAdminMessages ) {
		jQuery( '#toplevel_page_sensei' ).addClass(
			'wp-has-submenu wp-has-current-submenu wp-menu-open'
		);
	}
} );
