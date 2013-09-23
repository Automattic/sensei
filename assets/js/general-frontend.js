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

		jQuery(".answer_message.has_notes").mouseover(function() {
			jQuery(this).children(".notes").show();
		});
		jQuery(".answer_message.has_notes").mouseout(function() {
			jQuery(this).children(".notes").hide();
		});
	});

});