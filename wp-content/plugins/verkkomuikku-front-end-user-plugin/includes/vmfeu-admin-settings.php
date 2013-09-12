<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function vmfeu_settings_page(){
global $vmfeu;
?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2> <?php _e("Verkkomuikku Front-end user plugin settings" , "verkkomuikku")?> </h2>
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
				<td><?php _e("Front-end user plugin lets you customize your website by adding custom registration, login and profile pages giving your users easier way to interact with your site.", 'verkkomuikku')?></td>
			</tr>
			<tr>
				<td><?php _e("Check login, registration and profile pages from Wordpress pages and edit your custom guide texts if necessary and add them to your menu. Also there is sidebar widget available", "verkkomuikku")?></td>
			</tr>	
			<tr>
				<td><?php _e("Alternatively you can create your own pages and use the following shortcodes:", "verkkomuikku")?></td>
			</tr>
			<tr>
				<td><span style="padding-left:50px"></span>&rarr; [vmfeu-edit-profile] - <?php _e("to grant users a front-end acces to their personal information (requires user to be logged in).", "verkkomuikku")?></td>
			</tr>
			<tr>
				<td><span style="padding-left:50px"></span>&rarr; [vmfeu-login] - <?php _e("for a basic log-in menu.", "verkkomuikku")?></td>
			</tr>
			<tr>
				<td><span style="padding-left:50px"></span>&rarr; [vmfeu-lost-password] - <?php _e("to add a lost password and reset password form.", "verkkomuikku")?></td>
			</tr>
			<tr>
				<td><span style="padding-left:50px"></span>&rarr; [vmfeu-register] - <?php _e("to add a registration form.", "verkkomuikku")?></td>
			</tr>			
			<tr height="10"></tr>
			<tr>
				<td><?php _e("Also, users with administrator rights have access to the following features:", "verkkomuikku")?></td>
			</tr>
			<tr>
				<td><span style="padding-left:50px"></span>&rarr; <?php _e("add a custom stylesheet/inherit values from the current theme or use the default one, built into this plug-in.", "verkkomuikku")?></td>
			</tr>
			<tr>
				<td><span style="padding-left:50px"></span>&rarr; <?php _e("hides wp-login.php and dashboard from other than editors / admins with redirect to login page.", "verkkomuikku")?></td>
			</tr>
			<tr>
				<td><span style="padding-left:50px"></span>&rarr; <?php _e("deny access to any page by user role.", "verkkomuikku")?></td>
			</tr>
			<tr>
				<td><span style="padding-left:50px"></span>&rarr; <?php _e("select which information-field can the users see/modify. The hidden fields' values remain unmodified.", "verkkomuikku")?></td>
			</tr>
			<tr>
				<td><?php _e("NOTE: this plugin only adds/removes fields in the front-end. The default information-fields will still be visible(and thus modifiable) from the back-end, while custom fields will only be visible in the front-end.", "verkkomuikku")?></td>
			</tr>
		</tbody>
		
		</table>
		
		
		<form method="post" action="options.php">
			<?php settings_fields('VerkkomuikkuFrontEndUserSettings'); ?>
			<?php do_settings_sections('VerkkomuikkuFrontEndUserSettings'); ?>		
			<p class="submit">
				<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
			</p>
		</form>
		
</div>

<?php
}
?>