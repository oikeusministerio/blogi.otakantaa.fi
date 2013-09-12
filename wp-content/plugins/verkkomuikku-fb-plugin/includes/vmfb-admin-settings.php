<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function vmfb_settings_page(){
global $vmfb;
?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2> <?php _e("Verkkomuikku Facebook plugin settings" , "verkkomuikku")?> </h2>
		<?php  if ($_GET["settings-updated"] == 'true')	
						echo'<div id="message" class="updated below-h2">
							<p>
							'.__("Settings saved", "verkkomuikku").'
							</p>
						</div>';
		?>
		<h3><?php _e('Basic Information', 'verkkomuikku'); ?> </h3>
		<table class="wp-list-table widefat fixed pages" cellspacing="0">
		
		<tbody class="plugins">
			<tr height="10"></tr>
			<tr>
				<td><?php _e("Include Open Graph meta into your site and turn your site into a Facebook page.", 'verkkomuikku')?></td>
			</tr>
			<tr>
				<td><?php _e("NOTE: this plugin requires Facebook application for your site. Also some functionality requires user info (Facebook Connect). The simplest way is to use Verkkomuikku Front end user plugin", "verkkomuikku")?></td>
			</tr>
		</tbody>
		
		</table>
		
		
		<form method="post" action="options.php">
			<?php settings_fields('VerkkomuikkuFbSettings'); ?>
			<?php do_settings_sections('VerkkomuikkuFbSettings'); ?>		
			<p class="submit">
				<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
			</p>
		</form>
		
</div>

<?php
}
?>