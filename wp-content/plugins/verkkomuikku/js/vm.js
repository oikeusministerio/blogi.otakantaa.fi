/**
 * Cookie plugin
 *
 * Copyright (c) 2006 Klaus Hartl (stilbuero.de)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 */

/**
 * Create a cookie with the given name and value and other optional parameters.
 *
 * @example $.cookie('the_cookie', 'the_value');
 * @desc Set the value of a cookie.
 * @example $.cookie('the_cookie', 'the_value', { expires: 7, path: '/', domain: 'jquery.com', secure: true });
 * @desc Create a cookie with all available options.
 * @example $.cookie('the_cookie', 'the_value');
 * @desc Create a session cookie.
 * @example $.cookie('the_cookie', null);
 * @desc Delete a cookie by passing null as value. Keep in mind that you have to use the same path and domain
 *       used when the cookie was set.
 *
 * @param String name The name of the cookie.
 * @param String value The value of the cookie.
 * @param Object options An object literal containing key/value pairs to provide optional cookie attributes.
 * @option Number|Date expires Either an integer specifying the expiration date from now on in days or a Date object.
 *                             If a negative value is specified (e.g. a date in the past), the cookie will be deleted.
 *                             If set to null or omitted, the cookie will be a session cookie and will not be retained
 *                             when the the browser exits.
 * @option String path The value of the path atribute of the cookie (default: path of page that created the cookie).
 * @option String domain The value of the domain attribute of the cookie (default: domain of page that created the cookie).
 * @option Boolean secure If true, the secure attribute of the cookie will be set and the cookie transmission will
 *                        require a secure protocol (like HTTPS).
 * @type undefined
 *
 * @name $.cookie
 * @cat Plugins/Cookie
 * @author Klaus Hartl/klaus.hartl@stilbuero.de
 */

/**
 * Get the value of a cookie with the given name.
 *
 * @example $.cookie('the_cookie');
 * @desc Get the value of a cookie.
 *
 * @param String name The name of the cookie.
 * @return The value of the cookie.
 * @type String
 *
 * @name $.cookie
 * @cat Plugins/Cookie
 * @author Klaus Hartl/klaus.hartl@stilbuero.de
 */
jQuery.cookie = function(name, value, options) {
    if (typeof value != 'undefined') { // name and value given, set cookie
        options = options || {};
        if (value === null) {
            value = '';
            options.expires = -1;
        }
        var expires = '';
        if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
            var date;
            if (typeof options.expires == 'number') {
                date = new Date();
                date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
            } else {
                date = options.expires;
            }
            expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
        }
        // CAUTION: Needed to parenthesize options.path and options.domain
        // in the following expressions, otherwise they evaluate to undefined
        // in the packed version for some reason...
        var path = options.path ? '; path=' + (options.path) : '';
        var domain = options.domain ? '; domain=' + (options.domain) : '';
        var secure = options.secure ? '; secure' : '';
        document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
    } else { // only name given, get cookie
        var cookieValue = null;
        if (document.cookie && document.cookie != '') {
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var cookie = jQuery.trim(cookies[i]);
                // Does this cookie string begin with the name we want?
                if (cookie.substring(0, name.length + 1) == (name + '=')) {
                    cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                    break;
                }
            }
        }
        return cookieValue;
    }
};

//--------------------------------------------------

/**
 * jQuery fontscale - A plugin to alter the font size of DOM elements 
 * Copyright (c) 2010 Ben Byrne - ben(at)fireflypartners(dot)com | http://www.fireflypartners.com
 * Dual licensed under MIT and GPL.
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 * Date: 07/21/2010
 * @author Ben Byrne
 * @version 0.2
 *
 */

/**
 * For complete documentation, visit http://byrnecreative.com/blog/fontscale
 * @example $("#fontgrow").fontscale("p","+");
 * @desc Bind scaling up the font size of all P elements to the element #fontgrow with default settings.
 * @example $("#fontshrink").fontscale("p","-",{unit:"percent",increment:25,useCookie:false,adjustLeading:true});
 * @desc Bind scaling down the font size of all P elements to the element #fontshrink with custom settings.
 * @example $("#reset").fontscale("p","reset");
 * @desc Eliminate all fontscale resizing 
 */
var $ = jQuery.noConflict();
(function($) {
  $.fn.fontscale = function(selectors, adjustment, parameters) {

    var settings = $.extend( $.fn.fontscale.defaults, parameters);
    
    //only use cookies if we can
		if ( ! $.isFunction( $.cookie )  ) settings.useCookie = false;
		
    // if the cookie exists, we're supposed to use it, and we haven't before, then load it 
	  if (!settings.cookieLoaded && $.cookie(settings.cookieName)  && settings.useCookie) {
      cookieSettings = $.fn.fontscale.readcookie( settings.cookieName );
	    //only actually apply the data from the cookie if its unit settings match!
			if (cookieSettings.unit == settings.unit && !settings.cookieLoaded) $.fn.fontscale.scale( selectors, cookieSettings.delta, settings, true );
	  }
		
    this.each( function() {

		  // bind to elements
		  $(this).bind(settings.event, function() {
        $.fn.fontscale.scale( selectors, adjustment, settings, false);
        if ($.isFunction(settings.onAfter)) settings.onAfter(selectors, adjustment, settings); //is this okay?				
		  });
		});
		
		return this;
		
  };

  $.fn.fontscale.reset = function( object, settings ) {
    
    //remove any scaling done inline (assumed to be from this plugin)
    $(object).each(function(i) {
      $(this).css('font-size','');
      if (settings.adjustLeading) $(this).css('line-height','');
    });
    
    //if we're using a cookie, reset it too
    if ( settings.useCookie ) {
      $.fn.fontscale.savecookie("delete", settings );
    }
  }

	$.fn.fontscale.scale = function( object, adj, settings, fromcookie) {
	
    //make delta an int that changes nothing to start
    var delta = 0;
	
    if (adj == "+" || adj == "up") {
      //set the delta as an increase
      delta = settings.increment;
    } else if (adj == "-" || adj == "down") {
      //set the delta as a decrease
      delta = settings.increment * -1;
    } else if (adj == "reset") {
      //remove applied changes and do nothing else
      return $.fn.fontscale.reset( object, settings );
    } else if (fromcookie) {
      //get a pre-calibrated delta from the cookie if 
      delta = parseFloat(adj);
      settings.cookieLoaded = true;
	  }
	 	 
    //change the value into a percent if we have to
    if (settings.unit == "percent" && !fromcookie) {
      delta = 1 + (delta / 100);
    }
    	 
    $(object).each(function(i) {

      var currentSize = parseInt($(this).css("font-size"));
      var currentLeading = parseInt($(this).css("line-height"));
      
      if (settings.unit == "percent") {
        $(this).css("font-size", Math.round( currentSize * delta));
        if (settings.adjustLeading) $(this).css("line-height", Math.round( currentLeading * delta));
      } else {
        $(this).css("font-size", currentSize + delta);
        if (settings.adjustLeading) $(this).css("line-height", currentLeading + delta);
      }
  
	 });

  if (settings.useCookie && !fromcookie)  $.fn.fontscale.savecookie( delta, settings );
 
  return;
  
  };
  
  $.fn.fontscale.savecookie = function( delta, settings ) {

    //delete the cookie if we're performing a reset, do nothing else
    if (delta == "delete") {
      $.cookie( settings.cookieName, null, settings.cookieParams );
      return true;
    }
        
    if ($.cookie( settings.cookieName )) {
      properties = $.fn.fontscale.readcookie( settings.cookieName );
    } else {
      properties = {"delta":0}
    }
        
    //if we have a cookie that matches, just change the delta
    if (settings.unit == properties.unit) {  

      if (settings.unit == "percent") {
        properties.delta = (delta) ? properties.delta * delta : 1 ;
      } else {
        properties.delta = parseInt(properties.delta) + delta;
      }
    
      return $.cookie( settings.cookieName, "delta="+properties.delta+"&unit="+properties.unit, settings.cookieParams);
    
    //no cookie that matches, create a new     
    } else {
      $.cookie( settings.cookieName, "delta="+delta+"&unit="+settings.unit, settings.cookieParams);
      return true;
    }
      
  };
  
  $.fn.fontscale.readcookie = function( the_cookie ) {
  
    val_string = $.cookie( the_cookie );
                
    var objResult = {};
    $.each(val_string.split("&"), function() { 
      var prm=this.split("=");
      objResult[prm[0]] = prm[1]; 
    });
    return objResult;
  };

})(jQuery);

$.fn.fontscale.defaults = {
  useCookie:true,
  cookieName:'fontscale',
  cookieParams:{
    expires:30,
    path:"/"},
  increment:2,
  unit:"px",
  adjustLeading:false,
  event:"click",
  cookieLoaded:false
};

//-----------------------------------------------

/**
 * The vm.js 
 */
jQuery('document').ready(function() {
	// Prevent comment form sending if no content. Otherwise comes ugly die...
	if (jQuery('#commentform')) {
		jQuery('#commentform').submit(function(event) {
			if ("" == jQuery('#commentform').find('#comment').val().trim()) {
				event.preventDefault();
			}
		});
	}

	// Font resize
	init_font_resize();
	
});

function init_font_resize() {
	jQuery("#text_size_up").fontscale("p, #content h3, #content h3 a, #content ul li","up",
	  {unit:"pixels",
	  increment:2,
	  useCookie:true,
	  cookieName:"osallistumisymparisto_fontsize",
	  adjustLeading:false
	});
	/*
	jQuery("#text_size_down").fontscale("p, #content h3, #content h3 a","down",
			  {unit:"pixels",
			  increment:2,
			  useCookie:true,
			  cookieName:"osallistumisymparisto_fontsize",
			  adjustLeading:false
			});	*/
	
	jQuery("#text_size_reset").fontscale("p, .menu a, #content h3, #content h3 a, #content ul li","reset");
	
	/**
	 * Assign collapse functionality to divs. Used in loop, on page "Projects"
	 */
	jQuery(".vm_collapse_trigger").click(function() {
		var id = jQuery(this).attr("id");
		id = id.replace("vm_collapse_target_", "");
		jQuery("#vm_collapse_content_"+id).toggle("fast");
	});
}

