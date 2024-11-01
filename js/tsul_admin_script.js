
jQuery( document ).ready(function() {
	jQuery('.reset-options').click(function() {
	    return window.confirm(this.title);
	});


	jQuery(document).ready(function() {
		jQuery('.datepicker').datetimepicker({
			dateFormat: "yy-mm-dd"
		});

		jQuery('.datepicker').click(function() {
			jQuery(this).prev('#tsul_first_fetch2').prop("checked", true);
		});
	});
});





function NumbersDisplay (check) {
	if ( check.find('input').is(':checked')) {
		jQuery(check).next('.tsul-widget-numbers').show();
	} else {
		jQuery(check).next('.tsul-widget-numbers').hide();
	}
}


function StylingOptionsDisplay (check) {
	if ( check.find('input').is(':checked')) {
		jQuery(check).next('.styling-options').show();
	} else {
		jQuery(check).next('.styling-options').hide();
	}
}






jQuery( document ).ready(function() {
	jQuery('.tsul-loading').hide();

	jQuery('.tsul-widget-numbers-checkbox').each(function(){
		NumbersDisplay(jQuery(this));
	});

	jQuery('.tsul-widget-numbers-checkbox').change(function(){
		NumbersDisplay(jQuery(this));
	});


	jQuery('.styling-options-title').each(function(){
		StylingOptionsDisplay(jQuery(this));
	});

	jQuery('.styling-options-title').change(function(){
		StylingOptionsDisplay(jQuery(this));
	});


	jQuery('.my-color-field').wpColorPicker();
});






jQuery( document ).ajaxComplete( function( event, XMLHttpRequest, ajaxOptions ) {
    var request = {}, pairs = ajaxOptions.data.split('&'), i, split, widget;
    for( i in pairs ) {
        split = pairs[i].split( '=' );
        request[decodeURIComponent( split[0] )] = decodeURIComponent( split[1] );
    }

    if( request.action && ( request.action === 'save-widget' ) ) {
        widget = jQuery('input.widget-id[value="' + request['widget-id'] + '"]').parents('.widget');
        if( !XMLHttpRequest.responseText )
            wpWidgets.save(widget, 0, 1, 0);
        else {
			jQuery('.tsul-widget-numbers-checkbox').each(function(){
				NumbersDisplay(jQuery(this));
			});

			jQuery('.tsul-widget-numbers-checkbox').change(function(){
				NumbersDisplay(jQuery(this));
			});


			jQuery('.styling-options-title').each(function(){
				StylingOptionsDisplay(jQuery(this));
			});

			jQuery('.styling-options-title').change(function(){
				StylingOptionsDisplay(jQuery(this));
			});


			jQuery('.my-color-field').wpColorPicker();
		}
    }
});
