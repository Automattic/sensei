jQuery( function($){

	"use strict";

	/** Handler code for the certificate primary image certificate_image_meta_box() **/

	// save the default WP media browser callback
	window.send_to_editor_default = window.send_to_editor;

	// Uploading files
	var file_frame;
	var el;

	// original image dimensions
	var imageWidth  = sensei_certificate_templates_params.primary_image_width;
	var imageHeight = sensei_certificate_templates_params.primary_image_height;

	$('#set-certificate-image, #set-additional-image, #add-alternative-certificate-image').live('click', function(event){

		event.preventDefault();

		// save the element that was clicked on so we can set the image
		el = $(this);

		// If the media frame already exists, reopen it.
		if (file_frame) {
			file_frame.open();
			return;
		}

		// Create the media frame.
		file_frame = wp.media.frames.file_frame = wp.media({
			title: "Select an Image",
			button: {
				text: "Set Image",
			},
			multiple: false,
		});

		// When an image is selected, run a callback.
		file_frame.on( 'select', function() {
			// We set multiple to false so only get one image from the uploader
			var attachment = file_frame.state().get('selection').first().toJSON();

			// grab the original image height/width for the image area select
			imageWidth  = attachment.width;
			imageHeight = attachment.height;

			if ('set-certificate-image' == el.attr('id')) {
				// primary (first page) certificate image
				$('#upload_image_id_0').val(attachment.id);
				$('#remove-certificate-image').show();
				$('img#certificate_image_0').attr('src', attachment.url);
			} else if ('set-additional-image' == el.attr('id')) {
				// additional (second page) certificate image
				$('#upload_additional_image_id_0').val(attachment.id);
				$('#set-additional-image').hide();
				$('#remove-additional-image').show();
				$('img#certificate_additional_image').attr('src', attachment.url);
			} else if ('add-alternative-certificate-image' == el.attr('id')) {
				var imgindex = $('#certificate_alternative_images li').size() + 1;

				$('#certificate_alternative_images').append(
					'<li class="alternative_image">' +
						'<a href="#" class="remove-alternative-certificate-image">' +
							'<img style="max-width:100px;max-height:100px;" src="' + attachment.url + '" />' +
							'<input type="hidden" name="upload_image_id[' + imgindex + ']" class="upload_image_id" value="' + attachment.id + '" />' +
							'<span class="overlay"></span>' +
						'</a>' +
					'</li>');
				setRemoveAlternativeCertificateImageHandler();
			}
		});

		// Finally, open the modal
		file_frame.open();
	});

	// remove the Certificate Background Image
	$('#remove-certificate-image').click(function() {

		$('#upload_image_id_0').val('');
		$('img#certificate_image_0').attr('src', '');
		$(this).hide();

		return false;
	});

	// redraw the positioned certificate fields on the primary image as the browser is scaled
	$(window).resize(function() {
		redrawCertificateFieldPlaceholders();
	});

	// draw any positioned Certificate fields on the primary image
	function redrawCertificateFieldPlaceholders() {
		$('.field_pos').each(function(index,el) {
			el = $(el);
			var field = $('#field'+el.attr('id'));
			var image = $('#certificate_image_0');

			// if the image is removed, hide all fields
			if ('' == image.attr('src')) {
				if (field) field.hide();
				return;
			}

			// is the image resized due to the browser being shrunk?
			var scale = 1;
			if (imageWidth != image.width()) {
				scale = image.width() / imageWidth;
			}

			// get the scaled field position
			var position = el.val() ? el.val().split(',').map(function(n) { return parseInt(n) * scale }) : null;

			// create the field element if needed
			if (0 == field.length) {
				var name = el.prev().find('label').html();
				name = name.substr(0, name.length - 9);
				$('#certificate_image_wrapper').append('<span id="field'+el.attr('id')+'" class="certificate_field" style="display:none;">'+name+'</span>');

				// clicking on the fields allows them to be edited
				$('#field'+el.attr('id')).click( function(el) {
					certificate_field_area_select(el.target.id.substr(6));  // remove the leading 'field_' to create the field name
				});

				field = $('#field'+el.attr('id'));
			}

			if (position) {
				field.css({left:position[0]+'px', top:position[1]+'px', width:position[2]+'px', height:position[3]+'px'});
				field.show();
			} else {
				field.hide();
			}
		});
	}

	// initial setup of the field placeholders
	redrawCertificateFieldPlaceholders();


	/** Handler code for the certificate data fields certificate_data_meta_box() **/

	// Note on the image area select:  I have to be very brute force
	// with this thing unfortunately and create/remove it with every
	// selection start, because otherwise I can't get the thing to
	// update the selection position, or to resize properly if the
	// browser window is resized.
	// And it still doesn't resize the selection box as the image is
	// resized due to the browser window shrinking/growing, but oh well
	// can't have it all

	var ias;

	// a coordinate field gained focus, enable the image area select overlay on the certificate main image and scroll it into the viewport if needed
	$('input.set_position').click(function() {
		certificate_field_area_select(this.id);
	});

	// display the imgAreaSelect tool on top of the Certificate Background Image so that the field_name position can be defined
	// field_name: ie 'product_name_post'
	function certificate_field_area_select(field_name) {
		// no certificate image
		if (!$("img#certificate_image_0").attr('src')) return;

		// always clear the image select area, if any
		removeImgAreaSelect();

		// clicked 'done', return the button to normal and remove the area select overlay
		if ($('#'+field_name).val() == sensei_certificate_templates_params.done_label) {
			$('#'+field_name).val(sensei_certificate_templates_params.set_position_label);
			return;
		}

		// make sure the certificate field placeholder for this field is hidden
		$('#field_'+field_name).hide();

		var coords = $('#_' + field_name).val() ? $('#_' + field_name).val().split(',').map(function(n) { return parseInt(n) }) : [null,null,null,null];

		// reset all position set buttons and set the current
		$('input.set_position').val(sensei_certificate_templates_params.set_position_label);
		$('#'+field_name).val(sensei_certificate_templates_params.done_label);

		// create the image area select element
		ias = $('img#certificate_image_0').imgAreaSelect({
			show: true,
			handles: true,
			instance: true,
			imageWidth: imageWidth,
			imageHeight: imageHeight,
			x1: coords[0],
			y1: coords[1],
			x2: coords[0] + coords[2],
			y2: coords[1] + coords[3],
			onSelectEnd: function(img, selection) { areaSelect(selection, field_name); }
		});

		// scroll into viewport if needed
		if ($(document).scrollTop() > $("img#certificate_image_0").offset().top + $("img#certificate_image_0").height() * (2/3)) {
			$('html, body').animate({
				scrollTop: $("#title").offset().top
			}, 500);
		}
	}

	// disable the img area select overlay
	function removeImgAreaSelect() {
		$('img#certificate_image_0').imgAreaSelect({remove:true});
		redrawCertificateFieldPlaceholders();
	}

	// certificate image selection made, save it to the coordinate field and show the 'remove' button
	function areaSelect(selection, field_name) {
		$('#_' + field_name).val(selection.x1 + ',' + selection.y1 + ',' + selection.width + ',' + selection.height);
		$('#remove_' + field_name).show();
	}

	// position remove button clicked
	$('input.remove_position').click(function() {
		$(this).hide();
		$('#_' + this.id.substr(7)).val('');  // remove the coordinates
		$('#' + this.id.substr(7)).val(sensei_certificate_templates_params.set_position_label);
		removeImgAreaSelect();  // make sure the overlay is gone
		return;
	});

	// remove an alternative certificate image
	function setRemoveAlternativeCertificateImageHandler() {
		$('.remove-alternative-certificate-image').click(function() {

			var parent = $(this).parent();
			var current_field_wrapper = parent;

			$('input', current_field_wrapper).val('');
			$('img', current_field_wrapper).attr('src', '');
			parent.hide();

			return false;
		});
	}
	setRemoveAlternativeCertificateImageHandler();

	$('#remove-additional-image').click(function() {

		$('#upload_additional_image_id_0').val('');
		$('img#certificate_additional_image').attr('src', '');
		$(this).hide();
		$('#set-additional-image').show();

		return false;
	});

});
