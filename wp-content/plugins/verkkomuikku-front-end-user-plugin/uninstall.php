<?php
if( !defined( 'WP_UNINSTALL_PLUGIN' ) )			
	exit ();									// If uninstall not called from WordPress exit

delete_option( 'vmfeu_default_settings' );	    // Deprecated: Delete default settings from options table
delete_option( 'vmfeu_default_style' );			// Delete "use default css or not" settings
