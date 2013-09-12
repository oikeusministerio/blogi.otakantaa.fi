jQuery(document).ready(function() {
	  // Load Tweet Button Script
	  var e = document.createElement('script');
	  e.type="text/javascript"; e.async = true;
	  e.src = 'http://platform.twitter.com/widgets.js';
	  document.getElementsByTagName('head')[0].appendChild(e);
	  
	  // Load Plus One Button
	  var e = document.createElement('script');
	  e.type="text/javascript"; e.async = true;
	  e.src = 'https://apis.google.com/js/plusone.js';
	  document.getElementsByTagName('head')[0].appendChild(e);
});