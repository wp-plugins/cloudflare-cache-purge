<?php

$ccpurge = new CCPURGE_API;

$ccpurge_options_post = isset($_POST['ccpurge_options']) ? $_POST['ccpurge_options'] : false;

if($ccpurge_options_post){
	update_option('ccpurge_options', $ccpurge_options_post);
	ccpurge_transaction_logging('Updated CloudFlare Purge Settings');
	ccpurge_transaction_logging('email=' . $ccpurge_options_post['email'] . ' & token=' . substr($ccpurge_options_post['token'], 0, 10) . '[...]' . ' & domain=' . ( isset($ccpurge_options_post['account']) ? $ccpurge_options_post['account'] : '') . ' & auto_purge=' . ( isset($ccpurge_options_post['auto_purge']) ? $ccpurge_options_post['auto_purge'] : '') );
}
$ccpurge_options 					= get_option('ccpurge_options');
$ccpurge_email 						= isset($ccpurge_options['email']) ? $ccpurge_options['email'] : '';
$ccpurge_token 						= isset($ccpurge_options['token']) ? $ccpurge_options['token'] : '';
$ccpurge_account 					= $ccpurge_options['account'] != "" ? $ccpurge_options['account'] : $ccpurge->get_wordpress_domain();;
$ccpurge_console_details 	= isset($ccpurge_options['console_details']) ? $ccpurge_options['console_details'] : "0";
$ccpurge_console_debugger = isset($ccpurge_options['console_debugger']) ? $ccpurge_options['console_debugger'] : "0";
$ccpurge_console_calls 		= isset($ccpurge_options['console_calls']) ? $ccpurge_options['console_calls'] : "0";
$ccpurge_auto_purge 			= isset($ccpurge_options['auto_purge']) ? $ccpurge_options['auto_purge'] : "0";
$show_debugging 			    = isset($ccpurge_options['show_debugging']) ? $ccpurge_options['show_debugging'] : "0";
$ccpurge_url 							= isset($ccpurge_options['ccpurge_url']) ? $ccpurge_options['ccpurge_url'] : get_home_url();

?>

<script>

function show_hide_debug(){
	var show;
	if( jQuery('input[name="ccpurge_options[show_debugging]"]:checked').val() == '1' ){ show = true; }
	else{ show = false; }
	if( show ){ jQuery('.debugging-block').show(); }
	else{ jQuery('.debugging-block').hide(); }
}

jQuery(document).ready(function($){
	show_hide_debug();
	jQuery('input[name="ccpurge_options[show_debugging]"]').change(function() {
		show_hide_debug();
	});

});

</script>

<div class="wrap">
	<div class="icon32" id="icon-options-general"><br></div><h2>CloudFlare&reg; Cache Purge</h2>

	<p style="text-align: left;">
		CloudFlare Cache Purge clears a single file cache or the entire thing<br />
	</p>

	<div id="ccpurge-options-form">

	<?php if($ccpurge_token == ''): ?>
		<div class="updated" id="message"><p><strong>Alert!</strong> You must get an Authentication Token from CloudFlare to start<br />If you don't already have a CloudFlare Cache account, you can <a target="_blank" href="https://www.cloudflare.com/sign-up">sign up for one here</a></p></div>
	<?php elseif($ccpurge_account == ''): ?>
		<div class="updated" id="message"><p><strong>Alert!</strong> You must identify which CloudFlare Domain to target</p></div>
	<?php endif; ?>

	<form action="" id="ccpurge-form" method="post">
		<table class="ccpurge-table">
			<tbody>

				<tr>
					<th><label for="category_base">CloudFlare Email Address</label></th>
					<td class="col1"></td>
					<td class="col2">
						<input type="text" class="regular-text code" value="<?php echo $ccpurge_email; ?>" id="ccpurge-email" name="ccpurge_options[email]">
					</td>
				</tr>
				<tr>
					<th><label for="tag_base">CloudFlare API Token</label></th>
					<td class="col1"></td>
					<td class="col2">
						<input type="text" class="regular-text code" value="<?php echo $ccpurge_token; ?>" id="ccpurge-token" name="ccpurge_options[token]">
					</td>
				</tr>
				<tr>
					<th><label for="tag_base">CloudFlare Domain</label></th>
					<td class="col1"></td>
					<td class="col2">
						<input type="text" class="regular-text code" value="<?php echo $ccpurge_account; ?>" id="ccpurge-account" name="ccpurge_options[account]">
					</td>
				</tr>

				<?php if($ccpurge_token): ?>
				<tr>
					<th><label for="category_base">Auto Purge on Update</label></th>
					<td class="col1"></td>
					<td class="col2">
						<input type=checkbox name="ccpurge_options[auto_purge]"  value="1" <?php checked( "1", $ccpurge_auto_purge); ?>> Purge url when a post is updated<br />
					</td>
				</tr>
				<tr>
					<th><label for="category_base">Purge URL</label></th>
					<td class="col1"></td>
					<td class="col2">
						<input type="text" class="regular-text code" value="<?php echo $ccpurge_url; ?>" name="ccpurge_options[ccpurge_url]" id="ccpurge-url">
						<input type="button" value="Purge URL" id="ccpurge-purge-url" class="button-primary"/>
					</td>
				</tr>
				<tr>
					<th><label for="category_base">Purge Entire Cache</label></th>
					<td class="col1"></td>
					<td class="col2">
						<input type="button" value="Purge Entire Cache" id="ccpurge-entire-cache" class="button-primary"/>
					</td>
				</tr>
				<tr>
					<th><label for="category_base">Debugging</label></th>
					<td class="col1"></td>
					<td class="col2">
						<input type="radio" name="ccpurge_options[show_debugging]" value="1" <?php checked( $show_debugging, '1' ); ?>/> Show Debugging Sections
						<span style="width:40px;height:10px;display:inline-block"></span>
						<input type="radio" name="ccpurge_options[show_debugging]" value="0" <?php checked( $show_debugging, '0' ); ?>/> Hide Debugging Sections
					</td>
				</tr>
				<tr class="debugging-block">
					<th><label for="category_base">Debugging Options</label></th>
					<td class="col1"></td>
					<td class="col2">
						<input type=checkbox name="ccpurge_options[console_details]"  value="1" <?php checked( "1", $ccpurge_console_details); ?>> Details to console (debug)<br />
						<!-- input type=checkbox name="ccpurge_options[console_debugger]"  value="1" <?php checked( "1", $ccpurge_console_debugger); ?>> errors to console (debug)<br / -->
						<input type=checkbox name="ccpurge_options[console_calls]"  value="1" <?php checked( "1", $ccpurge_console_calls); ?>> API calls to console (debug)<br />
					</td>
				</tr>
				<?php endif; ?>

				<tr>
					<th>&nbsp;</th>
					<td class="col1"></td>
					<td class="col2">
						<input type="submit" value="Update / Save" class="button-secondary"/>
					</td>
				</tr>

				<tr class="debugging-block">
					<th><hr /></th>
					<td colspan="2"><hr /></td>
				</tr>

			</tbody>
		</table>
	</form>

	<div id="spinner"></div>

	<div id="ccpurge_table_logging_container" class="debugging-block">
		<div id="ccpurge_table_logging"></div>
	</div>

	</div><!-- ccpurge-form-wrapper -->

	<div style="clear:both;display:block;padding:40px 20px 0px;width:200px"><a href="/wp-admin/edit.php?post_type=ccpurge_log_entries">Manage Cache Purge Log Entries</a></div>

</div>