jQuery(document).ready( function($) {

	/***************************************************************************************************
	 * 	1 - Helper Functions.
	 ***************************************************************************************************/

	/**
	 * exists checks if selector exists
	 * @since  1.2.0
	 * @return boolean
	 */
	jQuery.fn.exists = function() {
		return this.length>0;
	}

	/**
	 * JS version of PHP htmlentities.
	 *
	 * @since 1.0.8
	 * @access public
	 */
 	jQuery.fn.htmlentities = function( str ) {
 		return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}

	jQuery.fn.ucwords = function( str ) {
		str = str.toLowerCase();
		str = str.replace( '-', ' ' );
		str = str.replace( 'boolean', 'True/False' );
	    return str.replace(/(^([a-zA-Z\p{M}]))|([ -][a-zA-Z\p{M}])/g,
	        function($1){
	            return $1.toUpperCase();
	        });
	}

	/***************************************************************************************************
	 * 	2 - Grading Overview Functions.
	 ***************************************************************************************************/



	/***************************************************************************************************
	 * 	3 - Grading User Profile Functions.
	 ***************************************************************************************************/



	/***************************************************************************************************
	 * 	4 - Load Chosen Dropdowns.
	 ***************************************************************************************************/

	// Grading Overview Drop Downs
	if ( jQuery( '#lesson-complexity-options' ).exists() ) { jQuery( '#lesson-complexity-options' ).chosen(); }


});