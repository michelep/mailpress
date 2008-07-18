<?php

add_action(	'mp_action_add_user_fo',	array('MP_User','mp_action_add_user_fo'));

// for ajax admin

add_action(	'mp_action_dim-user',		array('MP_User','mp_action_dim_user'));
add_action(	'mp_action_delete-user',	array('MP_User','mp_action_delete_user'));
add_action(	'mp_action_add-user',		array('MP_User','mp_action_add_user'));

// for links in mails

add_action(	'mp_action_mail_link',		array('MP_User','mp_action_mail_link'));

// for comments

if (isset($mp_general['subcomment']))
{
	add_action( 'comment_form',			array('MP_User','comment_form'));
	add_action( 'comment_post',			array('MP_User','comment_post'), 8, 1);
	add_action('wp_set_comment_status',		array('MailPress','approve_comment'));			
}

class MP_User
{

////	subscription form	////

	function form($args) {
		global $user_ID;
		$email = $message = $widget_title = '';

		if (isset($_POST['MailPress_submit']))
		{
			$bots_useragent = array('googlebot', 'google', 'msnbot', 'ia_archiver', 'lycos', 'jeeves', 'scooter', 'fast-webcrawler', 'slurp@inktomi', 'turnitinbot', 'technorati', 'yahoo', 'findexa', 'findlinks', 'gaisbo', 'zyborg', 'surveybot', 'bloglines', 'blogsearch', 'ubsub', 'syndic8', 'userland', 'gigabot', 'become.com');
			$useragent = $_SERVER['HTTP_USER_AGENT'];
			foreach ($bots_useragent as $bot) if (stristr($useragent, $bot) !== false) return false;				// goodbye bot !

			$email = ( isset($_POST['email']) ) ? $_POST['email'] : '';									//has the user entered an email 

			if ( '' == $email || __('Your email','MailPress') == $email ) 
			{																		// check for bot
				$message = "<span class='error'>" . __('Waiting for ...','MailPress') . "</span>";
				$email = __('Your email','MailPress');
			}
			else
			{
				$add = MP_User::add_user($email);
				$message = ($add['result']) ? "<span class='success'>" . $add['message'] . "</span><br/>" : "<span class='error'>" . $add['message'] . "</span><br/>";
				$email   = ($add['result']) ? $email : __('Your email','MailPress');
			}
		}
		elseif ($user_ID != 0 && is_numeric($user_ID) )
		{
			$user = get_userdata($user_ID);
			$email = $user->user_email;
			if ( MP_User::is_user($email,$user_ID) ) $email = ''; 
		}
		else
		{
			$email  = $_COOKIE['comment_author_email_' . COOKIEHASH];
			if ( MP_User::get_user_status_by_email($email) == 'active' ) $email='';
		}
		if ('' == $email) $email = __('Your email','MailPress');

		if ( is_active_widget('MailPress_widget') )
		{
			if (is_array($args)) extract($args);
			$options = get_option('MailPress_widget');
			if (isset($options['title'])) $widget_title = $options['title'];

			echo $before_widget;
			echo $before_title . $widget_title . $after_title;
		}
?>
<!-- start of code generated by MailPress -->
<style type="text/css">
div#MailPress div#container, div#MailPress div#formdiv {
position:relative;
}

div#MailPress div#loading, div#MailPress div#message {
position:absolute;
opacity:0;
}

div#MailPress div#loading, div#MailPress div#message {
filter:alpha(opacity=0);
}
</style>
<script type='text/javascript' src='<?php echo get_bloginfo('siteurl'); ?>/wp-includes/js/jquery/jquery.js?ver=1.2.3'></script>
<script type='text/javascript'> var mp_url = '<?php echo get_bloginfo('siteurl'); ?>/<?php echo MP_PATH; ?>mp-includes/action.php'; </script>
<script type='text/javascript' src='<?php echo MP_PATH; ?>mp-includes/js/form.js'></script>
<div id='MailPress'>
	<div id='container'>
		<div id='message'></div>
		<div id='loading'>
			<img src='<?php echo MP_PATH; ?>mp-includes/images/loading.gif' alt='<?php  _e('Loading...','MailPress'); ?>' title='<?php  _e('Loading...','MailPress'); ?>' />
			<?php  _e('Loading...','MailPress'); ?>
		</div>
		<div id='formdiv'>
			<?php if ('' != $message) echo $message; ?>
			<form id='form' method='post' action=''>
				<input type='text'   			name='email'  		value='<?php echo $email; ?>' size='25' />
				<input type='hidden' 			name='action' 		value='add_user_fo' />
				<input type='submit' id='submit'  	name='MailPress_submit' value="<?php  _e('Subscribe','MailPress'); ?>" />
			</form>
		</div>
	</div>
</div>
<!-- end of code generated by MailPress -->
<?php
		if ( is_active_widget('MailPress_widget') )
		{
			echo $after_widget;
		}
	}

////	comment subscription form	////

	function comment_form($postid) {
		global $wpdb, $mp_general;
		$checked = '';
		if (isset($mp_general['subcomment']))
		{
			$email = MailPress::get_wp_user_email();

			if (MailPress::is_email($email))
			{
				$i = MP_User::get_user_id_by_email($email);
				if ($i)
				{
					$x = $wpdb->get_var("SELECT meta_id FROM $wpdb->postmeta WHERE post_id = $postid and meta_key = '_MailPress_subscribe_to_comments_' AND meta_value = '$i';");
					if ($x) $checked = "checked='checked'";
				}
			}
?>
<!-- start of code generated by MailPress -->
<div style='clear:both;'>
	<input name='MailPress[subscribe_to_comments]' type='checkbox' <?php echo $checked; ?> style='margin:0;padding:0;width:auto;'/>
	<?php _e('Subscribe to comments on this post','MailPress'); ?>
</div>
<!-- end of code generated by MailPress -->
<?php
		}
	}

////	processing comment subscription form	////

	function comment_post($id) {
		global $wpdb, $comment;

		$comment 	= $wpdb->get_row("SELECT * FROM $wpdb->comments WHERE comment_ID = $id LIMIT 1");
		$postid 	= $comment->comment_post_ID;

		$email 	= MailPress::get_wp_user_email();

		if (MailPress::is_email($email))
		{
			$i = MP_User::get_user_id_by_email($email);
			if ($i)
			{
				$x = $wpdb->get_var("SELECT meta_id FROM $wpdb->postmeta WHERE post_id = $postid and meta_key = '_MailPress_subscribe_to_comments_' AND meta_value = '$i';");
				if ($x)
				{
					if (!isset($_POST['MailPress']['subscribe_to_comments'])) 
					{
						delete_post_meta($postid,'_MailPress_subscribe_to_comments_',$i);
						MailPress::update_stats('c',$postid,-1);
					}
				}
				else
				{
					if (isset($_POST['MailPress']['subscribe_to_comments'])) 
					{
						add_post_meta($postid,'_MailPress_subscribe_to_comments_',$i);
						MailPress::update_stats('c',$postid,1);
					}
				}
			}
			else
			{
				if (isset($_POST['MailPress']['subscribe_to_comments']))
				{
					if (MP_User::insert_user($email))
					{
						$i = MP_User::get_user_id_by_email($email);

						add_post_meta($postid,'_MailPress_subscribe_to_comments_',$i);
						MailPress::update_stats('c',$postid,1);
					}
				}
			}
		}

		if ('1' == $comment->comment_approved) MailPress::approve_comment($id);
	}

////	ajax subscription form	////

	function mp_action_add_user_fo() {

		$bots_useragent = array('googlebot', 'google', 'msnbot', 'ia_archiver', 'lycos', 'jeeves', 'scooter', 'fast-webcrawler', 'slurp@inktomi', 'turnitinbot', 'technorati', 'yahoo', 'findexa', 'findlinks', 'gaisbo', 'zyborg', 'surveybot', 'bloglines', 'blogsearch', 'ubsub', 'syndic8', 'userland', 'gigabot', 'become.com');
		$useragent = $_SERVER['HTTP_USER_AGENT'];
		foreach ($bots_useragent as $bot) if (stristr($useragent, $bot) !== false) return false;				// goodbye bot !

		$email = ( isset($_POST['email']) ) ? $_POST['email'] : '';									//has the user entered an email 

		if ( '' == $email || __('Your email','MailPress') == $email ) 
		{																		// check for bot
			$message = "<span class='error'>" . __('Waiting for ...','MailPress') . "</span>";
			$email = __('Your email','MailPress');
		}
		else
		{
			$add = MP_User::add_user($email);
			$message = ($add['result']) ? "<span class='success'>" . $add['message'] . "</span>" : "<span class='error'>" . $add['message'] . "</span>";
			$email   = ($add['result']) ? $email : __('Your email','MailPress');
		}
		ob_end_clean();
		header('Content-Type: text/xml');
		echo "<?xml version='1.0' standalone='yes'?><wp_ajax><message><![CDATA[$message]]></message><email><![CDATA[$email]]></email></wp_ajax>";
		die();
	}

	function add_user($email) {
		$return = array();

		if ( !MailPress::is_email($email) )
		{
			$return['result']  = false;
			$return['message'] = __('Enter a valid email !','MailPress');
			return $return;
		}
		
		$status = MP_User::get_user_status_by_email($email);									//Test if subscription already exists

		switch ($status)
		{
			case ('active') :
				$return['result']  = false;
				$return['message'] = __('You have already subscribed','MailPress');
				return $return;
			break;
			case ('waiting') :
				if ( MailPress::resend_confirmation_subscription($email) )
				{
					$return['result']  = true;
					$return['message'] = __('Waiting for your confirmation','MailPress') . ' <small>(2)</small>';
				}
				else
				{
					$return['result']  = false;
					$return['message'] = __('ERROR. resend confirmation email failed','MailPress') . ' <small>(2)</small>';
				}
				return $return;
			break;
			default :
				$key = md5(uniqid(rand(),1));													//generate key
				if ( MailPress::send_confirmation_subscription($email,$key) )							//email was sent
				{
					if ( MP_User::insert_user($email, $key) )
					{
						$return['result']  = true;
						$return['message'] = __('Waiting for your confirmation','MailPress');
						return $return;
					}
				}
				$return['result']  = false;
				$return['message'] = __('ERROR. send confirmation email failed','MailPress');
				return $return;
			break;
		}
	}

////	user functions 	////

	function insert_user($email,$key=false, $status='waiting') {
		global $wpdb;

		MailPress::update_stats('u','waiting',1);

		if ($key === false)
		{
		 	$key = md5(uniqid(rand(),1));	
			MailPress::update_stats('u','comment',1);
		}

		$now	  	= date('Y-m-d H:i:s');
		$userid 	= MailPress::get_wp_user_id();
		$ip		= $_SERVER['REMOTE_ADDR'];
		$agent	= trim(strip_tags($_SERVER['HTTP_USER_AGENT']));

		$ip2country = MP_User::get_ip2country($ip);

		$ip2USstate = ('US' == $ip2country) ? MP_User::get_ip2USstate($ip) : 'ZZ' ;

		$query = "INSERT INTO $wpdb->mp_users (email, status, confkey, created, created_IP, created_agent, created_user_id, created_country, created_US_state) ";
		$query .= "VALUES ('$email','$status','$key', '$now', '$ip', '$agent', $userid, '$ip2country', '$ip2USstate');";
      	$results = $wpdb->query( $query );

		return ('' != $results);
	}

	function is_user($email='', $userID=null) {
		if ( '' != $email && '' != MP_User::get_user_status_by_email($email) && 'delete' != MP_User::get_user_status_by_email($email) ) return true; 
		return false;
	}

	function has_subscribed_to_comments($id) {
		global $wpdb;
		return $wpdb->get_var("SELECT count(*) FROM $wpdb->postmeta WHERE meta_key = '_MailPress_subscribe_to_comments_' AND meta_value = '$id';");
	}

	function get_user_id($key) {
		global $wpdb;
		return $wpdb->get_var("SELECT id FROM $wpdb->mp_users WHERE confkey = '$key';");
	}

	function get_user_email($id) {
		global $wpdb;
		return $wpdb->get_var("SELECT email FROM $wpdb->mp_users WHERE id = '$id';");
	}

	function get_user_id_by_email($email) {
		global $wpdb;
		return $wpdb->get_var("SELECT id FROM $wpdb->mp_users WHERE email = '$email';");
	}

	function get_user_status($id) {
      	global $wpdb;
	      $result = $wpdb->get_var("SELECT status FROM $wpdb->mp_users WHERE id='$id' LIMIT 1");
		return ($result == NULL) ? 'deleted' : $result;
	}

	function get_user_status_by_email($email) {
		global $wpdb;
		return $wpdb->get_var("SELECT status FROM $wpdb->mp_users WHERE email = '$email'");
	}

	function get_key_by_email($email) {
		global $wpdb;
		return $wpdb->get_var("SELECT confkey FROM $wpdb->mp_users WHERE email = '$email'");
	}

	function get_blog_subs($id) {

		$x['new_post']['lib']	= __('Mail when new post published','MailPress');
		$x['new_post']['type'] 	= false;
		$x['daily']['lib']	= __("Mail 'previous day'",'MailPress');
		$x['daily']['type'] 	= false;
		$x['weekly']['lib']	= __("Mail 'previous week'",'MailPress');
		$x['weekly']['type'] 	= false;
		$x['monthly']['lib'] 	= __("Mail 'previous month'",'MailPress');
		$x['monthly']['type'] 	= false;

		global $mp_general;
		if (isset($mp_general['new_post']))		$x['new_post']['type'] 	= true;
		if (isset($mp_general['daily']))		$x['daily']['type'] 	= true;
		if (isset($mp_general['weekly']))		$x['weekly']['type'] 	= true;
		if (isset($mp_general['monthly']))		$x['monthly']['type'] 	= true;
		return $x;		
	}

	function get_comment_subs($id) {
		global $wpdb;

		$query = "SELECT a.meta_id, a.post_id, b.post_title FROM $wpdb->postmeta a, $wpdb->posts b WHERE a.meta_key = '_MailPress_subscribe_to_comments_' AND a.meta_value = '$id' AND a.post_id = b.ID;";
		return $wpdb->get_results( $query );
	}

	function set_user_status($id, $status) {
		switch($status) 
		{
			case 'active':
					return MP_User::activate_user($id);
			break;
			case 'waiting':
					return MP_User::deactivate_user($id);
			break;
			case 'delete':
					return MP_User::delete_user($id);
			break;
		}
		return true;
	}

	function activate_user($id) {
		global $wpdb, $mp_general;
		
		$query  = "SELECT * FROM $wpdb->mp_users WHERE id='$id';";
		$result = $wpdb->get_row( $query );
		$now	  = date('Y-m-d H:i:s');

		if ( $result && 'waiting' == $result->status )
		{
			if ( MailPress::send_succesfull_subscription($result->email,$result->confkey) )
			{
				MailPress::update_stats('u','active',1);
				if (MP_User::has_subscribed_to_comments($id)) MailPress::update_stats('u','comment',-1);

				$userid 	= MailPress::get_wp_user_id();
				$ip		= $_SERVER['REMOTE_ADDR'];
				$agent	= trim(strip_tags($_SERVER['HTTP_USER_AGENT']));

				$query = "UPDATE $wpdb->mp_users SET status = 'active', laststatus = '$now', laststatus_IP = '$ip', laststatus_agent = '$agent', laststatus_user_id = $userid WHERE id='$id';";
				$results = $wpdb->query( $query );
				if ($results == 1) 	return $now;
				else 				return false;
			}
			else
			{
				return false;
			}
		}
		return true;
	}

	function deactivate_user($id) {
		global $wpdb;
		
		$query  = "SELECT * FROM $wpdb->mp_users WHERE id='$id';";
		$result = $wpdb->get_row( $query );
		$now	  = date('Y-m-d H:i:s');

		if ( '' != $result && 'active' == $result->status )
		{
			MailPress::update_stats('u','active',-1);
			if (MP_User::has_subscribed_to_comments($id)) MailPress::update_stats('u','comment',1);

			$userid 	= MailPress::get_wp_user_id();
			$ip		= $_SERVER['REMOTE_ADDR'];
			$agent	= trim(strip_tags($_SERVER['HTTP_USER_AGENT']));

			$query = "UPDATE $wpdb->mp_users SET status = 'waiting', laststatus = '$now', laststatus_IP = '$ip', laststatus_agent = '$agent', laststatus_user_id = $userid WHERE id='$id';";
			$results = $wpdb->query( $query );
			return ($results == 1) ? $now : false;
		}
		return true;
	}

	function delete_user($id) {
		global $wpdb;

		if ('active' == MP_User::get_user_status($id)) 		MailPress::update_stats('u','active',-1);
		elseif (MP_User::has_subscribed_to_comments($id)) 	MailPress::update_stats('u','comment',-1);
		MailPress::update_stats('u','waiting',-1);

		$query = "DELETE FROM $wpdb->mp_users WHERE id = '$id';";
		$results = $wpdb->query( $query );
		$query = "DELETE FROM $wpdb->postmeta    WHERE meta_key = '_MailPress_subscribe_to_comments_' and meta_value = '$id';";
		$results = $wpdb->query( $query );
		return true;
	}

// recipients

	function get_recipients($query,$mail_id)
	{
		global $wpdb;
		$users 	= $wpdb->get_results( $query );

		if ($users)
		{
			$replacements  	= array ();
			foreach($users as $user) $replacements [$user->email] 	= array ( 	'{{toemail}}'	=> $user->email ,
															'{{unsubscribe}}' => MP_User::get_unsubscribe_url($user->confkey) ,
															'{{viewhtml}}' 	=> MP_User::get_view_url($user->confkey,$mail_id)
														  );
			return $replacements;
		}
		else return array();
	}

	function get_subscribe_url($key)
	{
		global $mp_general;
		$x = ('ajax' == $mp_general['subscription_mngt']) ? '/' . MP_PATH . 'mp-includes/action.php/?action=mail_link&add=' . $key : '/?' . $mp_general['subscription_mngt'] . '=' . $mp_general['id'] . '&add=' . $key ;
		return get_bloginfo('siteurl') . $x;
	}

	function get_unsubscribe_url($key)
	{
		global $mp_general;
		$x = ('ajax' == $mp_general['subscription_mngt']) ? '/' . MP_PATH . 'mp-includes/action.php/?action=mail_link&del=' . $key : '/?' . $mp_general['subscription_mngt'] . '=' . $mp_general['id'] . '&del=' . $key ;
		return get_bloginfo('siteurl') . $x;
	}

	function get_view_url($key,$id)
	{
		global $mp_general;
		$x = ('ajax' == $mp_general['subscription_mngt']) ? '/' . MP_PATH . 'mp-includes/action.php/?action=mail_link&view=' . $key . '&id=' . $id : '/?' . $mp_general['subscription_mngt'] . '=' . $mp_general['id'] . '&view=' . $key . '&id=' . $id ;
		return get_bloginfo('siteurl') . $x;
	}

	function get_delall_url($key)
	{
		global $mp_general;
		$x = ('ajax' == $mp_general['subscription_mngt']) ? '/' . MP_PATH . 'mp-includes/action.php/?action=mail_link&delall=' . $key : '/?' . $mp_general['subscription_mngt'] . '=' . $mp_general['id'] . '&delall=' . $key ;
		return get_bloginfo('siteurl') . $x;
	}

// manage subscription links

	function mp_action_mail_link() {

		include(MP_TMP . '/mp-includes/mp-mail-links.php');
		$results = mp_mail_links();

		if (isset($_GET['view']))
		{
			@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
	<head profile="http://gmpg.org/xfn/11">
		<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
		<script type='text/javascript' src='<?php echo get_option('siteurl') . '/' . MP_PATH . 'mp-includes/js/iframe.js' ?>'></script>
		<title><?php echo $results ['title']; ?></title>
	</head>
	<body>
		<div>
			<div>
				<b><?php echo $results ['title']; ?></b>
			</div>
			<?php echo $results ['content']; ?>
		</div>
	</body>
</html>
<?php
		}
		else
		{
			get_header();
?>
	<div id='content' class='widecolumn'>
		<div>
			<h2><?php echo $results ['title']; ?></h2>
			<div>
				<?php echo $results ['content']; ?>
			</div>
		</div>
	</div>
<?php
			get_footer();
		}
	}

////	ADMIN user 	////

	function get_list( $url_parms, $start, $num ) {
		global $wpdb;

		$start = abs( (int) $start );
		$num = (int) $num;

		$where = '';
		if (isset($url_parms['s']) && !empty($url_parms['s']))
		{
			$s = $wpdb->escape($url_parms['s']);
			if (!empty($where)) $where = $where . ' AND ';
			if ($s) $where .= " (email LIKE '%$s%') OR (laststatus_IP = '%$s%') OR (created_IP like '%$s%')  "; 
		}
		if (isset($url_parms['status']) && !empty($url_parms['status']))
		{
			if (!empty($where)) $where = $where . ' AND ';
			$where .= "status = '" . $url_parms['status'] . "'";
		}
		if (isset($url_parms['author']) && !empty($url_parms['author']))
		{
			if (!empty($where)) $where = $where . ' AND ';
			$where .= "( created_user_id = " . $url_parms['author'] . "  OR laststatus_user_id = " . $url_parms['author'] . " ) ";
		}
		if ($where) $where = ' WHERE ' . $where;

		$users = $wpdb->get_results( "SELECT SQL_CALC_FOUND_ROWS * FROM $wpdb->mp_users  $where ORDER BY created DESC LIMIT $start, $num" );

		MP_Admin::update_cache($users,'mp_user');

		$total = $wpdb->get_var( "SELECT FOUND_ROWS()" );

		return array($users, $total);
	}

	function get_row( $id, $url_parms, $checkbox = true ) {

		global $mp_user;

		$mp_user = $user = MP_User::get_user( $id );
		$the_user_status = $user->status;
// url's
		$delete_url  	= clean_url(MP_Admin::url( MailPress_user	."&action=delete&id=$id",	"delete-user_$id" ,	$url_parms ));
		$activate_url 	= clean_url(MP_Admin::url( MailPress_user	."&action=activate&id=$id",	"activate-user_$id",	$url_parms ));
		$deactivate_url 	= clean_url(MP_Admin::url( MailPress_user	."&action=deactivate&id=$id",	"deactivate-user_$id",	$url_parms ));

		$x 			= $url_parms['s'];
		$url_parms['s'] 	= MP_User::get_user_author_IP();
		$ip_url 		= clean_url(MP_Admin::url( MailPress_users, false, $url_parms ));
		$url_parms['s'] 	= $x;

		$author = ( 0 == $user->laststatus_user_id) ? $user->created_user_id : $user->laststatus_user_id;
		if ($author != 0 && is_numeric($author)) {
			unset($url_parms['author']);
			$wp_user = get_userdata($author);
			$author_url 	= clean_url(MP_Admin::url( MailPress_users  	."&author=" . $author, false, $url_parms ));
		}

		$actions = array();

		$actions['approve']   = "<a href='$activate_url' 	class='dim:the-user-list:user-$id:unapproved:e7e7d3:e7e7d3:?mode=" . $url_parms['mode'] . "' title='" . __( 'Activate this user','MailPress' ) 	. "'>" . __( 'Activate','MailPress' ) 	. '</a> | ';
		$actions['unapprove'] = "<a href='$deactivate_url' 	class='dim:the-user-list:user-$id:unapproved:e7e7d3:e7e7d3:?mode=" . $url_parms['mode'] . "' title='" . __( 'Deactivate this user','MailPress' ) 	. "'>" . __( 'Deactivate','MailPress' ) 	. '</a> | ';

		if ( 'waiting' == $url_parms['status']) 
		{
			$actions['approve']   = "<a href='$activate_url' class='delete:the-user-list:user-$id:e7e7d3:action=dim-user'   title='" . __( 'Activate this user','MailPress' )   . "'>" . __( 'Activate','MailPress' ) 	. '</a> | ';
			unset($actions['unapprove']);
		} elseif ( 'active' == $url_parms['status']) {
			$actions['unapprove'] = "<a href='$deactivate_url' class='delete:the-user-list:user-$id:e7e7d3:action=dim-user' title='" . __( 'Deactivate this user','MailPress' ) . "'>" . __( 'Deactivate','MailPress' ) . '</a> | ';
			unset($actions['approve']);
		}

		$actions['delete']    = "<a href='$delete_url' class='delete:the-user-list:user-$id delete'>" . __('Delete','MailPress') . '</a>';

		$class = ('waiting' == $the_user_status) ? 'unapproved' : '';

		$email_display = $user->email;
		if ( strlen($email_display) > 50 )	$email_display = substr($email_display, 0, 49) . '...';
		$edit_url= clean_url(MailPress_write . '&toemail=' . $user->email);
?>
<tr id="user-<?php echo $id; ?>" class='<?php echo $class; ?>'>
<?php if ( $checkbox ) : ?>
	<td style='text-align:left;'><input style='margin:0 0 0 8px;'type='checkbox' name='delete_users[]' value='<?php echo $id; ?>' /></td>
<?php endif; ?>
	<td class='user'>
			<p class='user-author'>
				<strong>
					<a class='row-title' href='<?php echo $edit_url; ?>' title='<?php printf( __('Write to "%1$s"','MailPress'), $user->email); ?>'>
						<?php if (('detail' == $url_parms['mode']) && (get_option('show_avatars'))) echo get_avatar( $user->email, 32 ); ?>
						<?php echo $email_display; ?>
					</a>
				</strong>
<?php
		if ('detail' == $url_parms['mode'])
		{
?>
				<br/>
				<a href='<?php echo $ip_url; ?>'>
					<?php MP_User::user_author_IP() ?>
				</a>
				&nbsp; 
				<?php MP_User::flag_IP() ?>
<?php
		}
?>
			</p>
	</td>
	<td>
<?php 	if ($author != 0 && is_numeric($author)) { ?>
				<a href='<?php echo $author_url; ?>' title='<?php printf( __('Users by "%1$s"','MailPress'), $wp_user->display_name); ?>'><?php echo $wp_user->display_name; ?></a>
<?php 	} else  	_e("(unknown)",'MailPress');
?>
	</td>
	<td id='user-td-now-<?php echo $id; ?>'>
		<?php MP_User::user_date('Y-m-d H:i:s'); ?></td>
	<td>
<?php
		foreach ( $actions as $action => $link )
			echo "<span class='$action'>$link</span>";
?>
	</td>
</tr>
<?php
	}

	function &get_user(&$user, $output = OBJECT) {
		global $wpdb;

		switch (true)
		{
			case ( empty($user) ) :
				if ( isset($GLOBALS['mp_user']) ) 	$_user = & $GLOBALS['mp_user'];
				else						$_user = null;
			break;
			case ( is_object($user) ) :
				wp_cache_add($user->id, $user, 'mp_user');
				$_user = $user;
			break;
			default :
				if ( isset($GLOBALS['mp_user']) && ($GLOBALS['mp_user']->id == $user) ) 
				{
					$_user = & $GLOBALS['mp_user'];
				} 
				elseif ( ! $_user = wp_cache_get($user, 'mp_user') ) 
				{
					$_user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->mp_users WHERE id = %d LIMIT 1", $user));
					wp_cache_add($_user->id, $_user, 'mp_user');
				}
			break;
		}

		if ( $output == OBJECT ) {
			return $_user;
		} elseif ( $output == ARRAY_A ) {
			return get_object_vars($_user);
		} elseif ( $output == ARRAY_N ) {
			return array_values(get_object_vars($_user));
		} else {
			return $_user;
		}
	}

	function count_users() {
		global $wpdb;
		$stats = array('waiting' => 0, 'active' => 0);

		$query = "SELECT status, COUNT( * ) AS count FROM $wpdb->mp_users GROUP BY status";
		$counts = $wpdb->get_results( $query );

		if ($counts) foreach( $counts as $count ) $stats[$count->status] = $count->count;

		return (object) $stats;
	}

	function user_date( $d = '' ) {
		echo MP_User::get_user_date( $d );
	}

	function get_user_date( $d = '' ) {
		global $mp_user;
		$sdate = ( '0000-00-00 00:00:00' == $mp_user->laststatus) ? $mp_user->created : $mp_user->laststatus;
		$date  = ( '' == $d ) ? mysql2date( get_option('date_format'), $sdate) : mysql2date($d, $sdate);
		return apply_filters('get_comment_date', $date, $d);
	}

	function user_author_IP() {
		echo MP_User::get_user_author_IP();
	}

	function get_user_author_IP() {
		global $mp_user;
		$ip = ( '' == $mp_user->laststatus_IP) ? $mp_user->created_IP : $mp_user->laststatus_IP;
		return apply_filters('get_comment_author_IP', $ip);
	}

	function flag_IP() {
		echo MP_User::get_flag_IP();
	}

	function get_flag_IP() {
		global $mp_user;
		return ('ZZ' == $mp_user->created_country) ? '' : "<img class='flag' alt='" . strtolower($mp_user->created_country) . "' src='" . get_option('siteurl') . '/' . MP_PATH . '/mp-includes/images/flags/' . strtolower($mp_user->created_country) . ".gif'/>\n";
	}

////	ADMIN user ajax 	////

	function mp_action_delete_user() {
		$id = isset($_POST['id'])? (int) $_POST['id'] : 0;
		$r = MP_User::set_user_status( $id, 'delete' );
		die( $r ? '1' : '0' );
	}

	function mp_action_dim_user() {
		$id = isset($_POST['id'])? (int) $_POST['id'] : 0;
		switch (MP_User::get_user_status($id))
		{
			case 'waiting' : 
				$now =  MP_User::set_user_status( $id, 'active' );
				if ($now) 
				{
					ob_end_clean();
					header('Content-Type: text/xml');
					echo "<?xml version='1.0' standalone='yes'?><wp_ajax>";
					echo "<id><![CDATA[$id]]></id>";
					echo "<now><![CDATA[$now]]></now>";
					echo '</wp_ajax>';
					die();
				}
			break;
			case 'active'   : 
				$now =  MP_User::set_user_status( $id, 'waiting' );
				if ($now) 
				{
					ob_end_clean();
					header('Content-Type: text/xml');
					echo "<?xml version='1.0' standalone='yes'?><wp_ajax>";
					echo "<id><![CDATA[$id]]></id>";
					echo "<now><![CDATA[$now]]></now>";
					echo '</wp_ajax>';
					die();
				}
			break;
			default :
				die('0');
			break;
		}
		die('-1');
	}

	function mp_action_add_user() {
		$url_parms = MP_Admin::get_url_parms(array('mode','status','s'));

		$start = isset($_POST['apage']) ? intval($_POST['apage']) * 25 - 1: 24;

		list($users, $total) = MP_User::get_list( $url_parms, $start, 1 );

		if ( !$users ) die('1');

		$x = new WP_Ajax_Response();
		foreach ( (array) $users as $user ) {
			MP_User::get_user( $user );
			ob_start();
				MP_User::get_row( $user->id, $url_parms, false );
				$user_list_item = ob_get_contents();
			ob_end_clean();
			$x->add( array(
				'what' 	=> 'user',
				'id' 		=> $user->id,
				'data' 	=> $user_list_item
			) );
		}
		$x->send();
	}

////	ip2country	////

	function get_ip2country($ip)
	{
		$x = @file_get_contents("http://api.hostip.info/country.php/?ip=$ip");
		if ('XX' == $x)
		{
			$x = @file_get_contents("http://www.infosniper.net/xml.php?ip_address=$ip");
			if ($x)
			{
				$xml = new SimpleXMLElement ( $x );
				if (($xml->result[0]->countrycode) && (2 == strlen($xml->result[0]->countrycode)))
					return strtoupper($xml->result[0]->countrycode);
			}
		 	return 'ZZ';
		}
		return $x;
	}

	function get_ip2USstate($ip)
	{
		$x = @file_get_contents("http://api.hostip.info/get_html.php?ip=$ip");
		if (2 < strlen($x)) 	return substr($x,strlen($x)-2,2);
		else 				return 'ZZ';
	}
}
?>