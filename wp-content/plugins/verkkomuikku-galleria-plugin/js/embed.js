/**
Embed the gallery into another site.
Hardcoded to work with otakantaa.fi only!
Use this code to embed:
<div id="blogi_karuselli"></div>
<script type="text/javascript">
(function() {
    function async_load(){
        var s = document.createElement('script');
        s.type = 'text/javascript';
        s.async = true;
        s.src = '//blogi.otakantaa.fi/galleria.js';
        var x = document.getElementsByTagName('script')[0];
        x.parentNode.insertBefore(s, x);
    }
    if (window.attachEvent)
        window.attachEvent('onload', async_load);
    else
        window.addEventListener('load', async_load, false);
})();
</script>

First, loads jQuery from google cdn
Then, load galleria.io script from blogi.otakantaa.fi
Then, load galleria.io theme script
Then, load galleria.io theme css
Then, load data and init galleria.io

TODO: Parameters? http://feather.elektrum.org/book/src.html
*/
(function() {
	
	var jQuery;

	var gallery_container_id = ('blogi_karuselli');
	var gallery_container = document.getElementById(gallery_container_id);
	
	// Don't bother if no container found
	if (typeof gallery_container == undefined || gallery_container == null)
		return;
	
	// Load jQuery
	if (window.jQuery === undefined || window.jQuery.fn.jquery !== '1.10.2') {
		var script_tag = document.createElement('script');
		script_tag.setAttribute("src","//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js");
		 
		if (script_tag.readyState) {
			script_tag.onreadystatechange = function () { // For old versions of IE
				if (this.readyState == 'complete' || this.readyState == 'loaded') {
					jQueryScriptLoadHandler();
				}
			};
		} else { // Other browsers
		   script_tag.onload = jQueryScriptLoadHandler;
		}
		
		(document.getElementsByTagName("head")[0] || document.documentElement).appendChild(script_tag);    
	} else {
		jQuery = window.jQuery;
		jQueryScriptLoadHandler(false);
	}
	
	// After jQuery loaded, load Galleria.io
	function jQueryScriptLoadHandler(jQueryNoConflict = true) {
		
		if (jQueryNoConflict)
			jQuery = window.jQuery.noConflict(true);
		
		if (window.Galleria === undefined) {
			var script_tag = document.createElement('script');
			script_tag.setAttribute("src","//blogi.otakantaa.fi/galleria.io.js");
			 
			if (script_tag.readyState) {
				script_tag.onreadystatechange = function () { // For old versions of IE
					if (this.readyState == 'complete' || this.readyState == 'loaded') {
						GalleriaScriptLoadHandler();
					}
				};
			} else { // Other browsers
			   script_tag.onload = GalleriaScriptLoadHandler;
			}
			
			(document.getElementsByTagName("head")[0] || document.documentElement).appendChild(script_tag);    
		} else {
			GalleriaScriptLoadHandler();
		}
	}
	
	// Load galleria theme js
	function GalleriaScriptLoadHandler() {
		// First load theme CSS that is required by the theme JS
		var css_link = jQuery("<link>", { 
			rel: "stylesheet", 
			type: "text/css", 
			href: "//blogi.otakantaa.fi/galleria.content_slider.css" 
		});
		
		//inject galleria.io theme CSS
		css_link.appendTo('head');
		
		var script_tag = document.createElement('script');
		script_tag.setAttribute("src","//blogi.otakantaa.fi/galleria.content_slider.js");
		 
		if (script_tag.readyState) {
			script_tag.onreadystatechange = function () { // For old versions of IE
				if (this.readyState == 'complete' || this.readyState == 'loaded') {
					GalleriaThemeScriptLoadHandler();
				}
			};
		} else { // Other browsers
		   script_tag.onload = GalleriaThemeScriptLoadHandler;
		}
		
		(document.getElementsByTagName("head")[0] || document.documentElement).appendChild(script_tag);    		
	}
	
	// All dependencies loaded.
	function GalleriaThemeScriptLoadHandler() {
		// Start the main thing
		main();
	}
	
	function main() {
		
		jQuery(document).ready(function() {
			
			//setup vars
			var homeURL = '//blogi.otakantaa.fi/';
			
			/*
			// Get data 
			var data = [
			            { image: "http://blogi.otakantaa.fi/wp-content/uploads/2013/09/OK_roadshow_banneri.jpg", layer: "<p>Jeah</p>" },
			            { image: "http://blogi.otakantaa.fi/wp-content/uploads/2013/09/KUA-etusivun-kuvat.jpg", layer: "<h1>Very cool</h1>" },
			        ];

		    // Initialize Galleria
		    Galleria.run('#'+gallery_container_id, {
		    	width: 'auto',
		                height: 0.5625,
				responsive: true,
				swipe:		true,
				transition: "slide",
				transitionSpeed: 400,
				autoplay: 10000,
		    	thumbCrop: false,
				imageCrop: 'width',
				carousel: false,
				imagePan: true,
				clicknext: false,
				thumbnails: "empty",
				showInfo: false,
				showCounter: false,
				popupLinks: false,
				dataSource: data
		    });
		    */

			//an initial JSON request for data
			jQuery.getJSON(homeURL + '/wp-admin/admin-ajax.php?action=vm_galleria_data&callback=?', function(response) {
				jQuery(gallery_container).html(response.html);
			});
		});
	}
		
})();	   
