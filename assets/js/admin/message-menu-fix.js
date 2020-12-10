jQuery( document ).ready( function () {
	var pageText = 'edit.php?post_type=sensei_message';
	var areWeInAdminMessages = document.location.href.includes( pageText );
	if ( areWeInAdminMessages ) {
		jQuery( '#toplevel_page_sensei' ).addClass(
			'wp-has-submenu wp-has-current-submenu wp-menu-open'
		);
	}
} );
