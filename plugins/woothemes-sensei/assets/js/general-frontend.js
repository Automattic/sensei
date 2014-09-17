jQuery(document).ready( function($) {

	/***************************************************************************************************
	 * 	1 - General Functions.
	 ***************************************************************************************************/

	jQuery(function() {
	    jQuery(".meter > span").each(function() {
	    	jQuery(this)
	    		.data("origWidth", jQuery(this).css('width'))
	    		.width(0)
	    		.animate({
	    			width: jQuery(this).data("origWidth")
	    		}, 1200);
		});
	});

});