<?php
$tracking = get_option('MailPress_tracking');

MailPress::require_class('Tracking_modules');
foreach(array('mail', 'user') as $folder)
{
	$MP_Tracking_modules = new MP_Tracking_modules($folder, array());
	$tracking_reports[$folder] = $MP_Tracking_modules->get_all($folder);
}

global $mp_general;
if (!isset($mp_general['gmapkey']) || empty($mp_general['gmapkey'])) unset($tracking_reports['user']['u006'], $tracking_reports['mail']['m006']);
?>
<div id='fragment-MailPress_tracking'>
	<div>
		<form name='tracking.form' action='' method='post' class='mp_settings'>
			<input type='hidden' name='formname' value='tracking.form' />
			<table class='form-table'>
				<tr valign='top' class="rc_role" >
					<th scope='row'><strong><?php _e('User', MP_TXTDOM); ?></strong></th>
					<td class='field'>
<?php
foreach ($tracking_reports['user'] as $k => $v)
{
?>
<input type='checkbox' id='<?php echo $k; ?>' name='tracking[<?php echo $k; ?>]' value='<?php echo $k; ?>' <?php if (isset($tracking[$k])) checked($k,$tracking[$k]); ?> /><label for='<?php echo $k; ?>'>&nbsp;<?php echo $v['title']; ?></label><br />
<?php
}
?>
					</td>
				</tr>
				<tr valign='top' class="rc_role" >
					<th scope='row'><strong><?php _e('Mail', MP_TXTDOM); ?></strong></th>
					<td class='field'>

<?php
foreach ($tracking_reports['mail'] as $k => $v)
{
?>
<input type='checkbox' id='<?php echo $k; ?>' name='tracking[<?php echo $k; ?>]' value='<?php echo $k; ?>' <?php if (isset($tracking[$k])) checked($k,$tracking[$k]); ?> /><label for='<?php echo $k; ?>'>&nbsp;<?php echo $v['title']; ?></label><br />
<?php
}
?>
					</td>
				</tr>
			</table>
<?php MP_AdminPage::save_button(); ?>
		</form>
	</div>
</div>