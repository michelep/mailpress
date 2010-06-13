<?php

$xevery = array (	30 	=> sprintf(__('%1$s seconds', MP_TXTDOM), '30'), 
			45 	=> sprintf(__('%1$s seconds', MP_TXTDOM), '45'), 
			60 	=> sprintf(__('%1$s minute' , MP_TXTDOM) , ''), 
			120 	=> sprintf(__('%1$s minutes', MP_TXTDOM), '2'), 
			300 	=> sprintf(__('%1$s minutes', MP_TXTDOM), '5'), 
			900 	=> sprintf(__('%1$s minutes', MP_TXTDOM), '15'), 
			1800 	=> sprintf(__('%1$s minutes', MP_TXTDOM), '30'), 
			3600 	=> sprintf(__('%1$s hour', 	MP_TXTDOM), '') ); 

$xmailboxstatus = array(	0	=>	__('no changes', MP_TXTDOM),
					1	=>	__('mark as read', MP_TXTDOM),
					2	=>	__('delete', MP_TXTDOM) );

$bounce_handling = get_option('MailPress_bounce_handling');
?>
<div id='fragment-MailPress_bounce_handling'>
	<div>
		<form name='bounce_handling.form' action='' method='post' class='mp_settings'>
			<input type='hidden' name='formname' value='bounce_handling.form' />
			<table class='form-table'>
				<tr valign='top'>
					<th scope='row'><?php _e('Return-Path', MP_TXTDOM); ?></th>
					<td class='field'>
						<input type='text' size='25' name='bounce_handling[Return-Path]' value="<?php if (isset($bounce_handling['Return-Path'])) echo $bounce_handling['Return-Path']; ?>" />
						<br /><?php printf(__('generated Return-Path will be %1$s', MP_TXTDOM), (!isset($bounce_handling['Return-Path'])) ?  __('start_of_email<i>+mail_id</i>+<i>mp_user_id</i>@mydomain.com', MP_TXTDOM) : substr($bounce_handling['Return-Path'], 0, strpos($bounce_handling['Return-Path'], '@')) . '<i>+mail_id</i>+<i>mp_user_id</i>@' . substr($bounce_handling['Return-Path'], strpos($bounce_handling['Return-Path'], '@') + 1) ); ?>
					</td>
				</tr>
				<tr valign='top'>
					<th scope='row'><?php _e('Max bounces per user', MP_TXTDOM); ?></th>
					<td class='field'>
						<select name='bounce_handling[max_bounces]'  style='width:4em;'>
<?php MP_AdminPage::select_number(0, 5, ( (isset($bounce_handling['max_bounces'])) ? $bounce_handling['max_bounces'] : 1 ) );?>
						</select>
					</td>
				</tr>
				<tr valign='top'>
					<th scope='row'><?php _e('Submit batch with', MP_TXTDOM); ?></th>
					<td>
						<table class='general'>
							<tr>
								<td class='pr10'>
									<label for='bounce_handling_wp_cron'>
										<input value='wpcron' name='bounce_handling[batch_mode]' id='bounce_handling_wp_cron' class='submit_batch tog' type='radio' <?php checked('wpcron', $bounce_handling['batch_mode']); ?> />
										&nbsp;&nbsp;
										<?php _e('WP_Cron', MP_TXTDOM); ?>
									</label>
								</td>
								<td class='wpcron pr10 toggl2<?php if ('wpcron' != $bounce_handling['batch_mode']) echo ' hide'; ?>' style='padding-left:10px;vertical-align:bottom;'>
									<?php _e('Every', MP_TXTDOM); ?>
									&nbsp;&nbsp;
									<select name='bounce_handling[every]' id='every' >
<?php MP_AdminPage::select_option($xevery, $bounce_handling['every']);?>
									</select>
								</td>
							</tr>
							<tr>
								<td class='pr10'>
									<label for='bounce_handling_other'>
										<input value='other' name='bounce_handling[batch_mode]' id='bounce_handling_other' class='submit_batch tog' type='radio' <?php checked('other', $bounce_handling['batch_mode']); ?> />
										&nbsp;&nbsp;
										<?php _e('Other', MP_TXTDOM); ?>
									</label>
								</td>
								<td class='other pr10 toggl2<?php if ('other' != $bounce_handling['batch_mode']) echo ' hide'; ?>'>
									<?php printf(__('see sample in "%1$s"', MP_TXTDOM), MP_PATH . 'mp-content/mp_bounce_handling'); ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr valign='top'>
					<th scope='row'><?php _e('POP3 Server', MP_TXTDOM); ?></th>
					<td class='field'>
						<input type='text' size='25' name='bounce_handling[server]' value="<?php if (isset($bounce_handling['server'])) echo $bounce_handling['server']; ?>" />	
					</td>
				</tr>
				<tr valign='top'>
					<th><?php _e('Port', MP_TXTDOM); ?></th>
					<td class='field'>
						<input type='text' size='4' name='bounce_handling[port]' value="<?php if (isset($bounce_handling['port'])) echo $bounce_handling['port']; ?>" />
					</td>
				</tr>
				<tr valign='top'>
					<th><?php _e('Username', MP_TXTDOM); ?></th>
					<td class='field'>
						<input type='text' size='25' name='bounce_handling[username]' value="<?php if (isset($bounce_handling['username'])) echo $bounce_handling['username']; ?>" />
					</td>
				</tr>
				<tr valign='top'>
					<th><?php _e('Password', MP_TXTDOM); ?></th>
					<td colspan='2'>
						<input type='password' size='25' name='bounce_handling[password]' value="<?php if (isset($bounce_handling['password'])) echo $bounce_handling['password']; ?>" />
					</td>
				</tr>
				<tr valign='top'>
					<th scope='row'><?php _e('Bounce in mailbox', MP_TXTDOM); ?></th>
					<td class='field'>
						<select name='bounce_handling[mailbox_status]'>
<?php MP_AdminPage::select_option($xmailboxstatus, ( (isset($bounce_handling['mailbox_status'])) ? $bounce_handling['mailbox_status'] : 2 ) );?>
						</select>
					</td>
				</tr>
			</table>
<?php MP_AdminPage::save_button(); ?>
		</form>
	</div>
</div>