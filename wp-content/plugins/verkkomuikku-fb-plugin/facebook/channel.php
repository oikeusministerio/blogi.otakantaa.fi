<?php
/**
 * Custom channel URL for FB.init
 * See:
 * http://developers.facebook.com/blog/post/530/
 * http://developers.facebook.com/docs/reference/javascript/FB.init/
 * 
 */
  $cache_expire = 60*60*24*365;
  header("Pragma: public");
  header("Cache-Control: maxage=".$cache_expire);
  header('Expires: '.gmdate('D, d M Y H:i:s', time()+$cache_expire).' GMT');
  
  //global $vmfeu_fb_connect;
  //echo $vmfeu_fb_connect->fb_script_locale();
?>

<script src="//connect.facebook.net/en_US/all.js"></script>