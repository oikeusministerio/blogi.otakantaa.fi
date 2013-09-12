<?php
/*
Plugin Name: GravatarLocalCache
Website link: http://blog.splash.de/
Author URI: http://blog.splash.de/
Plugin URI: http://blog.splash.de/2010/01/04/gravatarlocalcache
Description: Local cache for gravatar images (saves dnsqueries and let you control cache/proxysettings of the images).
Author: Oliver Schaal
Version: 1.1.2

    This is a WordPress plugin (http://wordpress.org) and widget
    (http://automattic.com/code/widgets/).
*/

if (!function_exists('is_admin')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

// we need the downloadfunction of wordpress
require_once(ABSPATH . 'wp-admin/includes/file.php');

define('GLCPATH', WP_PLUGIN_DIR.'/'.dirname(plugin_basename( __FILE__ )));
define('GLCURL', WP_PLUGIN_URL.'/'.dirname(plugin_basename( __FILE__ )));

// now outside the plugin directory, otherwise it would be deleted on every plugin update
define('GLCCACHEPATH', WP_CONTENT_DIR.'/glc_cache/');
define('GLCCACHEURL', WP_CONTENT_URL.'/glc_cache/');

if (!class_exists("GravatarLocalCache")) {
    class GravatarLocalCache {

        // vars
        private static $defaultAvatar = 'mystery';

        // put all options in
        private static $options = array();

        // download counter
        private static $count = 0;
        
        // admin menu...
	private $slug = 'gravatarlocalcache';
	private $admin_page;
	private $admin_screen;

        /* __construct */
        public function __construct() {
            // get other options/vars
            self::$defaultAvatar = get_option('avatar_default');

            //load language
            if (function_exists('load_plugin_textdomain'))
                load_plugin_textdomain('glc', GLCPATH.'/lang/', '/gravatarlocalcache/lang/');

            // get options
            self::$options = get_option('glcOptions');
            (!is_array(self::$options) && !empty(self::$options)) ? self::$options = unserialize(self::$options) : self::$options = false;

            // install default options
            register_activation_hook(__FILE__, array(&$this, 'activatePlugin'));

            // plugin administration stuff
            register_deactivation_hook(__FILE__, array(&$this, 'deactivatePlugin'));

            // admin menu
            add_action('admin_menu', array(&$this, 'showAdminMenuLink'));

            // dashboard widget
            add_action('wp_dashboard_setup', array(&$this, 'initAdminDashboard'));

            // TODO: Rechte einschränken -> abhängig von php (CGI vs mod_php) - get_current_user(), php_sapi_name(), $processUser = posix_getpwuid(posix_geteuid());
            // plugin stuff
            if ( is_dir(GLCCACHEPATH) || ( umask(0022) && @mkdir( GLCCACHEPATH , 0755, true ) ) ) {
                // print '<!-- GravatarLocalCache: loaded -->';
                add_filter('get_avatar', array(&$this, 'getGravatarLocalCacheFilter'), 1, 2);
            }
        }
        /* __construct */

        /* activatePlugin */
        public function activatePlugin() {
            if (empty($this->options)) {
                add_option('glcOptions', serialize(array(
                        'cache_time' => '72',          // 3 days...
                )));
            } else {
                // update options after plugin update
                $this->update();
            }
        }
        /* activatePlugin */

        /* deactivatePlugin */
        public function deactivatePlugin() {
            delete_option('glcOptions');
        }
        /* deactivatePlugin */

        private function update() {
            // add option, if they does not exist
            if(empty(self::$options['cache_time']))
                self::$options['cache_time'] = '72';        // 3 days...

            // update options
            update_option('glcOptions', serialize($this->options));
        }

        /* initAdminDashboard */
        public function initAdminDashboard() {
            wp_add_dashboard_widget( 'GravatarLocalCacheWidget', __( 'GravatarLocalCache Stats', 'glc' ), array(&$this, 'showAdminDashboard') );
        }
        /* initAdminDashboard */

        /* showAdminDashboard */
        public function showAdminDashboard() {
            ?>

<div class="inside">
    <ul>
            <?php
            if (is_dir(GLCCACHEPATH)) {
                self::cleanupCache(GLCCACHEPATH);
                $_filesInCache = self::filesInDirectory(GLCCACHEPATH);
                $_fileInCacheCount = count($_filesInCache);
                if ($_fileInCacheCount > 0) {

                    echo '<li><strong>'
                         .__('Images in cache', 'glc').':</strong> '
                         .$_fileInCacheCount.'</li>';

                    // total size
                    foreach ($_filesInCache as $_key => $_row) {
                        $_size[$_key]  = $_row['size'];
                    }
                    $_totalSize = 0;
                    foreach($_size as $_key => $_value) {
                        $_totalSize += $_value;
                    }
                    echo '<li><strong>'.__('Total size', 'glc').':</strong> '.self::fileSizeHumanReadable($_totalSize).'</li>';

                    // size
                    $_filesInCache = self::array_msort($_filesInCache, array('size'=>SORT_ASC, 'name'=>SORT_ASC));
                    reset($_filesInCache);
                    $_first=current($_filesInCache);
                    $_last=end($_filesInCache);
                    echo '<li><strong>'.__('Size (smallest/largest)', 'glc').':</strong></li><li>'
                            .self::fileSizeHumanReadable($_first['size']).' / '
                            .self::fileSizeHumanReadable($_last['size']).'</li>';

                    // date
                    $_filesInCache = self::array_msort($_filesInCache, array('mdate'=>SORT_ASC, 'name'=>SORT_ASC));
                    reset($_filesInCache);
                    $_first=current($_filesInCache);
                    $_last=end($_filesInCache);
                    echo '<li><strong>'.__('Date (youngest/oldest)', 'glc').':</strong></li><li>'
                            .date ("d-m-Y H:i:s", $_last['mdate']).' / '
                            .date ("d-m-Y H:i:s", $_first['mdate']).'</li>';

                    /*
                    echo '<li></li><li><strong>'.__('path', 'glc').':</strong></li><li>'.GLCCACHEPATH.'</li>';
                    echo '<li><strong>'.__('URL', 'glc').':</strong></li><li>'.GLCCACHEURL.'</li>';
                    */


                } else {
                    _e('There are no cached images.', 'glc');
                }
            }
            ?>
    </ul>
</div>
            <?php

        }
        /* showAdminDashboard */


        /* callbackGravatarLocalCache */
        private static function callbackGravatarLocalCache($matches) {
            // print '<!-- GravatarCallback: ';
            // print_r($matches);
            // print '-->';

            $_imageurl = $matches[1];

            preg_match('|http://[0-9a-z]{1,3}.gravatar.com/avatar/([^\?]+)\?s=([0-9]+)|i', $_imageurl, $_img);

            // Set variables for storage
            $_name = $_img[1];
            $_file = GLCCACHEPATH.$_name.'-'.$_img[2].'.jpg';
            $_url = GLCCACHEURL.$_name.'-'.$_img[2].'.jpg';

            // print '<!-- GravatarHASH: ';
            // print $_hash[1];
            // print '-->';

            // print '<!-- GravatarLocalCache: callback -->';

            if (file_exists($_file) && ((time() - filemtime($_file))/60/60) < self::$options['cache_time']) {
                // print '<!-- GravatarLocalCache: image cached -->';
                $_retVal = $_url;
            } else {

                if (!empty($_imageurl) && self::$count < 3 ) {
                    // update counter, even if it doesn't work...
                    self::$count++;

                    // Download file to temp location
                    $_tmp = download_url(str_replace(utf8_encode('&amp;'), '&', $_imageurl));

                    // If error storing temporarily, unlink
                    if ( is_wp_error($_tmp) ) {
                        @unlink($_tmp);

                        // lets try to fetch the default avatar in the url
                        preg_match('/&amp;d=(.*)/i',$_imageurl,$_defaultavatar);
                        $_tmp = download_url(urldecode($_defaultavatar[1]));
                        if ( is_wp_error($_tmp) ) {
                            // print '<!-- GravatarLocalCache: default image (url param) couldn't be fetched -->';
                            @unlink($_tmp);

                            // last chance, lets try the default gravatar set in wordpress
                            if ( self::$defaultAvatar == 'mystery') {
                                $_defavatar = 'http://www.gravatar.com/avatar/'.md5('unknown@gravatar.com').'?s='.$_img[2];
                            } elseif (self::$defaultAvatar == 'blank') {
                                $_defavatar = includes_url('images/blank.gif');
                            } else {
                                // should never reach this point
                                $_defavatar = 'http://www.gravatar.com/avatar/?d='.self::$defaultAvatar.'&s='.$_img[2];
                            }

                            $_tmp = download_url($_defavatar);
                            if ( is_wp_error($_tmp) ) {
                                // print '<!-- GravatarLocalCache: default image (wordpress setting) couldn't be fetched -->';
                                @unlink($_tmp);
                                return $matches[0];
                            }
                        }
                    }

                    // do the validation and storage stuff
                    if ( false === @rename( $_tmp, $_file ) ) {
                        // print '<!-- GravatarLocalCache: image fetched, but we have a problem -->';
                        return $matches[0];
                    } else {
                        // print '<!-- GravatarLocalCache: image fetched and cached -->';
                        $_stat = stat( dirname( $_file ));
                        $_perms = $_stat['mode'] & 0000666;
                        @chmod( $_file, $_perms );

                        $_retVal = $_url;
                    }
                    @unlink($_tmp);

                } elseif (file_exists($_file)) {
                    // if the file exists and we exceeded the download limit -> cachelink
                    // print '<!-- GravatarLocalCache: image cached, but should be renewed -->';
                    $_retVal = $_url;
                } else {
                    // if the file doesn't exists -> gravatarlink
                    // print '<!-- GravatarLocalCache: no cached image and downloadlimit exceeded -->';
                    return $matches[0];
                }
            }

            // we need to replace the url to the local image
            return str_replace($_imageurl,$_retVal,$matches[0]);
        }
        /* callbackGravatarLocalCache */

        /* getGravatarLocalCacheFilter */
        public function getGravatarLocalCacheFilter($content, $arg) {
            return preg_replace_callback('|<img.*src=\'([^\']+)\'.*/>|i', array( &$this, 'callbackGravatarLocalCache'), $content);
        }
        /* getGravatarLocalCacheFilter */

        /* showAdminMenuLink */
        public function showAdminMenuLink() {
            $this->admin_page = add_options_page( 
                'GravatarLocalCache', 
                (version_compare($GLOBALS['wp_version'], '2.6.999', '>') ? '<img src="' . GLCURL.'/icon.jpg' . '" width="10" height="10" alt="GravatarLocalCache - Icon" /> ' : '') . 'GravatarLocalCache',
		'manage_options', 
		$this->slug, 
		array(&$this, 'showAdminMenu') 
            );
            add_action("load-{$this->admin_page}",array(&$this,'create_help_screen'));            
            
            
            
            /*
            $hook = add_options_page('GravatarLocalCache',
                    (version_compare($GLOBALS['wp_version'], '2.6.999', '>') ? '<img src="' . GLCURL.'/icon.jpg' . '" width="10" height="10" alt="GravatarLocalCache - Icon" /> ' : '') . 'GravatarLocalCache',
                    'manage_options',
                    plugin_basename(__FILE__),
                    array(&$this,
                    'showAdminMenu'
                    )
            );
            */
            /*
            if (function_exists('add_contextual_help') === true) {
                add_contextual_help($hook,
                        sprintf('<a href="http://trac.splash.de/glc">%s</a><a href="http://board.splash.de/forumdisplay.php?f=105">%s</a>',
                        __('Ticketsystem/Wiki', 'glc'),
                        __('Support-Forum', 'glc')
                        )
                );
            }
                */
        }
        /* showAdminMenuLink */
        
	public function create_help_screen() {
		/** 
		 * Create the WP_Screen object against your admin page handle
		 * This ensures we're working with the right admin page
		 */
		$this->admin_screen = WP_Screen::get($this->admin_page);
                
		/**
		 * Content specified inline
		 */
		$this->admin_screen->add_help_tab(
			array(
				'title'    => 'Help',
				'id'       => 'help_tab',
				'content'  => sprintf('<p>%s</p><p>%s</p>',
                                                       __('With this plugin you can cache gravatar images on your server (and they will be delivered with the cache settings of your webserver / if you don\'t know how to do this, take a look at the readme.txt).','glc'),
                                                       __('For any further questions or help using GravataLocalCache please use the support forum.', 'glc')
                                                     ),
				'callback' => false
			)
		);
                
		$this->admin_screen->set_help_sidebar(
			sprintf('<p>%s:<br /><a href="http://trac.splash.de/glc">%s</a><br /><a href="http://board.splash.de/forumdisplay.php?f=105">%s</a></p>',
                                                       __('Links', 'glc'),
                                                       __('Ticketsystem/Wiki', 'glc'),
                                                       __('Support-Forum', 'glc')
                                                     )
		);

	}

        /* showAdminMenu */
        public function showAdminMenu() {

            if (!empty($_POST)) {

                if(!empty($_POST['cache_time'])) {
                    self::$options['cache_time'] = $_POST['cache_time'];
                }
                // update options
                update_option('glcOptions', serialize(self::$options));

                // echo successfull update
                echo '<div id="message" class="updated fade"><p><strong>' . __('Options saved.', 'glc') . '</strong></p></div>';
            }

            if ( !(is_dir(GLCCACHEPATH) || ( umask(0022) && @mkdir( GLCCACHEPATH, 0755, true ) ) ) ) {
                if (( umask(0022) && @mkdir( GLCCACHEPATH, 0700, true ) )) {
                    echo '<div id="alert" class="error"><p><strong>' . __('The plugin may not work correctly, cause there is a problem with the directory permissions.', 'glc') . '</strong></p></div>';
                    echo '<div id="cachemessage" class="updated fade"><p><strong> Debug:</strong> ' . __('Cachedirectory', 'glc') . ': ' . substr(sprintf('%o', fileperms(GLCCACHEPATH)), -4) . ' ' . __('(should be 0755)', 'glc') . ', ' .  __('Plugindirectory', 'glc') . ': ' . substr(sprintf('%o', fileperms(GLCPATH)), -4) . ' (' . GLCPATH . ')</p></div>';
                    echo '<div id="notice" class="updated fade"><p><strong>' . __('If you need some help, check the support-forum: ', 'glc') . sprintf('<a href="http://board.splash.de/forumdisplay.php?f=105">%s</a>',
                            __('Link', 'glc')) . ' (' . __('Please include the debug information in your post', 'glc') . ').</strong></p></div>';
                } else {
                    echo '<div id="alert" class="error"><p><strong>' . __('Cache directory doesn\'t exists or isn\'t writable.', 'glc') . '</strong></p></div>';
                    echo '<div id="cachemessage" class="updated fade"><p><strong> Debug:</strong> ' . __('Cachedirectory', 'glc') . ': ' . substr(sprintf('%o', fileperms(GLCCACHEPATH)), -4) . ' ' . __('(should be 0755)', 'glc') . ', ' .  __('Plugindirectory', 'glc') . ': ' . substr(sprintf('%o', fileperms(GLCPATH)), -4) . ' (' . GLCPATH . ')</p></div>';
                    echo '<div id="notice" class="updated fade"><p><strong>' . __('If you need some help, check the support-forum: ', 'glc') . sprintf('<a href="http://board.splash.de/forumdisplay.php?f=105">%s</a>',
                            __('Link', 'glc')) . ' (' . __('Please include the debug information in your post', 'glc') . ').</strong></p></div>';
                }
            }
           
            ?>
<div class="wrap">
    <div class="icon32" id="icon-options-general">&nbsp;</div>
    <h2>GravatarLocalCache</h2>

    <h3><?php _e('Cache Settings', 'glc');  ?></h3>

    <form action="<?php echo admin_url( 'options-general.php?page=' . $this->slug ); ?>" method="post">

        <table class="form-table">
            <tbody>

                            <?php // cache time ?>
                <tr valign="top">
                    <th scope="row">
                        <label><?php echo _e('caching time', 'glc'); ?></label>
                    </th>
                    <td>
                        <input type="text" value="<?php echo self::$options['cache_time'] ?>" name="cache_time" id="cache_time" size="5" maxlength="35" />
                        <br />
                                    <?php _e('maximum time (in hours) the gravatar images will be cached.', 'glc'); ?>
                    </td>
                </tr>

            </tbody>
        </table>

        <input type="hidden" name="glc_submit" id="glc_submit" value="1" />
        <p class="submit">
            <input class="button-primary" type="submit" name="Submit" value="<?php _e('Update Options', 'glc'); ?> »" />
        </p>
    </form>

    <h3><?php _e('Cache Stats', 'glc');  ?></h3>
    <ul style="padding-left:20px;">
            <?php
            if (is_dir(GLCCACHEPATH)) {
                self::cleanupCache(GLCCACHEPATH);
                $_filesInCache = self::filesInDirectory(GLCCACHEPATH);
                $_fileInCacheCount = count($_filesInCache);
                if ($_fileInCacheCount > 0) {

                    echo '<li><strong>'
                         .__('Images in cache', 'glc').':</strong> '
                         .$_fileInCacheCount.'</li>';

                    // total size
                    foreach ($_filesInCache as $_key => $_row) {
                        $_size[$_key]  = $_row['size'];
                    }
                    $_totalSize = 0;
                    foreach($_size as $_key => $_value) {
                        $_totalSize += $_value;
                    }
                    echo '<li><strong>'.__('Total size', 'glc').':</strong> '.self::fileSizeHumanReadable($_totalSize).'</li>';

                    // size
                    $_filesInCache = self::array_msort($_filesInCache, array('size'=>SORT_ASC, 'name'=>SORT_ASC));
                    reset($_filesInCache);
                    $_first=current($_filesInCache);
                    $_last=end($_filesInCache);
                    echo '<li><strong>'.__('Size (smallest/largest)', 'glc').':</strong></li><li>'
                            .self::fileSizeHumanReadable($_first['size']).' / '
                            .self::fileSizeHumanReadable($_last['size']).'</li>';

                    // date
                    $_filesInCache = self::array_msort($_filesInCache, array('mdate'=>SORT_ASC, 'name'=>SORT_ASC));
                    reset($_filesInCache);
                    $_first=current($_filesInCache);
                    $_last=end($_filesInCache);
                    echo '<li><strong>'.__('Date (youngest/oldest)', 'glc').':</strong></li><li>'
                            .date ("d-m-Y H:i:s", $_last['mdate']).' / '
                            .date ("d-m-Y H:i:s", $_first['mdate']).'</li>';


                    echo '<li><strong>'.__('path', 'glc').':</strong></li><li>'.GLCCACHEPATH.'</li>';
                    echo '<li><strong>'.__('URL', 'glc').':</strong></li><li>'.GLCCACHEURL.'</li>';



                } else {
                    _e('There are no cached images.', 'glc');
                }
            }
            ?>
    </ul>

</div>
            <?php
        }
        /* showAdminMenu */
        
        /* cleanupCache */
        private static function cleanupCache ($path) {
            if ($handle = opendir($path)) {
                    while (false !== ($file = readdir($handle))) {
                            if ($file[0] == '.' || is_dir($path.'/'.$file)) {
                                    continue;
                            }
                            if ((time() - filemtime($path.'/'.$file)) > (self::$options['cache_time'] *60*60)) {
                                    unlink($path.'/'.$file);
                            }
                    }
                    closedir($handle);
            }            
        }
        /* cleanupCache */

        /* array functions */
        function array_msort($array, $cols) {
            $colarr = array();
            foreach ($cols as $col => $order) {
                $colarr[$col] = array();
                foreach ($array as $k => $row) {
                    $colarr[$col]['_'.$k] = strtolower($row[$col]);
                }
            }
            $params = array();
            foreach ($cols as $col => $order) {

                $params[] =&$colarr[$col];
                $order=(array)$order;
                foreach($order as $order_element) {
                    //pass by reference, as required by php 5.3
                    $params[]=&$order_element;
                }
            }
            call_user_func_array('array_multisort', $params);
            $ret = array();
            $keys = array();
            $first = true;
            foreach ($colarr as $col => $arr) {
                foreach ($arr as $k => $v) {
                    if ($first) {
                        $keys[$k] = substr($k,1);
                    }
                    $k = $keys[$k];

                    if (!isset($ret[$k])) {
                        $ret[$k] = $array[$k];
                    }

                    $ret[$k][$col] = $array[$k][$col];
                }
                $first = false;
            }
            return $ret;
        }
        /* array functions */

        /* fileSizeHumanReadable */
        private static function fileSizeHumanReadable($bytes, $precision = 2) {
            $units = array('B', 'KB', 'MB', 'GB', 'TB');

            $bytes = max($bytes, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);

            $bytes /= pow(1024, $pow);

            return round($bytes, $precision) . ' ' . $units[$pow];
        }
        /* fileSizeHumanReadable */

        /* filesInDirectory */
        private static function filesInDirectory($path, $exclude = ".|..", $recursive = false) {
            $path = rtrim($path, "/") . "/";
            $folder_handle = opendir($path);
            $exclude_array = explode("|", $exclude);
            $result = array();
            $i=0;
            while(false !== ($filename = readdir($folder_handle))) {
                if(!in_array(strtolower($filename), $exclude_array)) {
                    if(is_dir($path . $filename)) {
                        if($recursive) $result[] = file_array($path, $exclude, true);
                    } else {
                        $i++;
                        $result[$i]['name'] = $filename;
                        $result[$i]['size'] = filesize($path.$filename);
                        $result[$i]['mdate'] = filemtime($path.$filename);
                    }
                }
            }
            return $result;
        }
        /* filesInDirectory */

    }
}

if (class_exists("GravatarLocalCache")) {
    $gravatarLocalCache = new GravatarLocalCache();
}