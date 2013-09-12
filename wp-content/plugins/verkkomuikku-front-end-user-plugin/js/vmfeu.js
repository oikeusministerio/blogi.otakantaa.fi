jQuery(document).ready(function() {
	// If ajax is enabled
	if (typeof(VMFEUAjax) !== "undefined" && VMFEUAjax.ajax_enabled) {
		// hook to registration form
		vmfeu_ajaxify_form('form#vmfeu_add_user');
	} 
});

function vmfeu_ajaxify_form(form_id) {
	jQuery(form_id).submit(function(e){
		e.preventDefault();
		
		jQuery.post( VMFEUAjax.ajaxurl, jQuery(this).serialize(),
			function( html ) {
	    		jQuery('#vmfeu_register').replaceWith(html);
	    		
	    		// Check if the form doesn't fit the window
	    		// and scroll to top (where the error messages and userfeedback
	    		// will appear).
	    		var form_position = jQuery('div#vmfeu_register').closest('div.entry').position();
	    		var window_position = jQuery(window).scrollTop();

	    		if (window_position > form_position.top) {
	    			jQuery.scrollTo(jQuery('div#vmfeu_register'), 800 , { offset:{ top: -130, left:0 } });
	    		}
	    		
	    		// Ajaxify the form again since it got replaced
	    		vmfeu_ajaxify_form(form_id);	    		
			}
		);

		return false;
	});	
}

var vmfeu = {
	// Login user with facebook
	// This function is triggered when Facebook login popup is closed.
	login_with_facebook: function (response) {
		// Check if user authorized the App
		FB.getLoginStatus(function(response) {
			if (response.status == "connected") {
				// Logged in and connected user
				// Show ajax loader
				jQuery('.fb_login_loader').show().css({"display":"inline"});
				
				// Redirect to PHP page that will log in the user
				vmfeu.reload_with_arg('vmfeu_fb_connect', 1);
			} else {
				// User didn't log in or authorize. Do nothing..
			}
		});	
	},
		
	// Function to add query arg for current location
	reload_with_arg: function (key, value) {
	    key = escape(key); 
	    value = escape(value);

	    // split query arts into key=value. Remove ? grom the begining
	    var kvp = document.location.search.substr(1).split('&');
	
	    var i=kvp.length; 
	    var x;
	    var url;
	    
	    if (i == 0) {
	    	url = window.location.href + "?" + key + "=" + value;
	    } else {
		    // Replace value if we have the query arg already in the query args
		    while(i--) {
		    	// Split into key and value
		        x = kvp[i].split('=');
		
		        if (x[0]==key) {
		                x[1] = value;
		                kvp[i] = x.join('=');
		                break;
		        }
		    }
		
		    // The argument wasn't there, so append at the end 
		    if(i<0) { kvp[kvp.length] = [key,value].join('='); }
		    var tmp = window.location.href.split('?');
		    url = tmp[0]+"?"+kvp.join('&');
	    }
	    
	    // Reload with current location + arg
	    window.location.href = url; 
	}
};

/*
 Labels inside input fields
 Code from http://attardi.org/labels
 except the deprecated .live() jQuery function replaced with .on()
 */

/*

Copyright (c) 2009 Stefano J. Attardi, http://attardi.org/

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


(function(jQuery) {
    function toggleLabel() {
        var input = jQuery(this);
        setTimeout(function() {
            var def = input.attr('title');
            if (!input.val() || (input.val() == def)) {
                input.prev('span').css('visibility', '');
                if (def) {
                    var dummy = jQuery('<label></label>').text(def).css('visibility','hidden').appendTo('body');
                    input.prev('span').css('margin-left', dummy.width() + 3 + 'px');
                    dummy.remove();
                }
            } else {
                input.prev('span').css('visibility', 'hidden');
            }
        }, 0);
    };

    function resetField() {
        var def = jQuery(this).attr('title');
        if (!jQuery(this).val() || (jQuery(this).val() == def)) {
            jQuery(this).val(def);
            jQuery(this).prev('span').css('visibility', '');
        }
    };

    jQuery(document).on('keydown', 'input, textarea', toggleLabel);
    jQuery(document).on('paste', 'input, textarea', toggleLabel);
    jQuery(document).on('change', 'select', toggleLabel);

    jQuery(document).on('focusin', 'input, textarea', function() {
        jQuery(this).prev('span').css('color', '#ccc');
    });
    jQuery(document).on('focusout', 'input, textarea', function() {
        jQuery(this).prev('span').css('color', '#999');
    });

    jQuery(document).ready(function() {
        jQuery('.vmfeu_wrapper').find('input, textarea').each(function() { toggleLabel.call(this); });
    });

})(jQuery);
*/