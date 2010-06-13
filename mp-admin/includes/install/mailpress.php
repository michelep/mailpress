<?php
//
//
//	New install
//
		global $wpdb;

		self::require_class('Newsletter');
		add_option ('MailPress_daily',  		array('threshold'=>date('Ymd')));
		add_option ('MailPress_weekly', 		array('threshold'=>MP_Newsletter::get_yearweekofday(date('Y-m-d'))));
		add_option ('MailPress_monthly', 		array('threshold'=>date('Ym')));

		add_option ('MailPress_template', 		'default');
		add_option ('MailPress_stylesheet', 	'default');
		add_option ('MailPress_current_theme', 	'MailPress Default');

		$charset_collate = '';
		if ( $wpdb->supports_collation() ) 
		{
			if ( ! empty($wpdb->charset) ) $charset_collate  = "DEFAULT CHARACTER SET $wpdb->charset";
			if ( ! empty($wpdb->collate) ) $charset_collate .= " COLLATE $wpdb->collate";
		}

  		$sql = "CREATE TABLE $wpdb->mp_users (
									id  				bigint(20) 				UNSIGNED NOT NULL AUTO_INCREMENT, 
									email  			varchar(100) 			NOT NULL, 
									name 	 			varchar(100) 			NOT NULL, 
									status  			enum('waiting', 'active', 'bounced', 'unsubscribed')	NOT NULL, 
									confkey  			varchar(100)			NOT NULL, 
									created 			timestamp 				NOT NULL default '0000-00-00 00:00:00',
									created_IP  		varchar(100) 			NOT NULL default '',
									created_agent 		varchar(255) 			NOT NULL default '',
									created_user_id  		bigint(20) 				UNSIGNED NOT NULL default 0,
									created_country 		char(2)				NOT NULL default 'ZZ',
									created_US_state 		char(2)				NOT NULL default 'ZZ',
									laststatus 			timestamp 				NOT NULL default '0000-00-00 00:00:00',
									laststatus_IP  		varchar(100) 			NOT NULL default '',
									laststatus_agent 		varchar(255) 			NOT NULL default '',
									laststatus_user_id  	bigint(20) 				UNSIGNED NOT NULL default 0,
									UNIQUE KEY id (id)
								    ) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  		dbDelta($sql);

  		$sql = "CREATE TABLE $wpdb->mp_stats (
								  	sdate 			date 					NOT NULL,
									stype 			char(1) 				NOT NULL,
									slib 				varchar(45) 			NOT NULL,
									scount 			bigint 				NOT NULL,
									PRIMARY KEY(sdate, stype, slib)
								    ) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  		dbDelta($sql);

  		$sql = "CREATE TABLE $wpdb->mp_mails (
									id 				bigint(20) 				UNSIGNED NOT NULL AUTO_INCREMENT, 
									status 			enum('draft', 'sent', 'unsent', 'sending', '')	NOT NULL, 
									theme 			varchar(255) 			NOT NULL default '',
									themedir 			varchar(255) 			NOT NULL default '',
									template 			varchar(255) 			NOT NULL default '',
									fromemail 	 		varchar(255) 			NOT NULL default '',
									fromname 	 		varchar(255) 			NOT NULL default '',
									toname 	 		varchar(255) 			NOT NULL default '',
									charset 	 		varchar(255) 			NOT NULL default '',
									parent 			bigint(20)				UNSIGNED NOT NULL default 0,
									child  			bigint(20)				NOT NULL default 0,
									subject 			varchar(255) 			NOT NULL default '',
									created 			timestamp 				NOT NULL default '0000-00-00 00:00:00',
									created_user_id 		bigint(20) 				UNSIGNED NOT NULL default 0,
									sent 				timestamp 				NOT NULL default '0000-00-00 00:00:00',
									sent_user_id  		bigint(20) 				UNSIGNED NOT NULL default 0,
									toemail 	 		longtext				NOT NULL,
								  	plaintext 			longtext 				NOT NULL,
								  	html 				longtext 				NOT NULL,
									UNIQUE KEY id (id)
								    ) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  		dbDelta($sql);

  		$sql = "CREATE TABLE $wpdb->mp_usermeta (
									umeta_id 			bigint(20) 				NOT NULL auto_increment,
									user_id 			bigint(20) 				NOT NULL default '0',
									meta_key  			varchar(255) 			default NULL,
									meta_value 			longtext,
									PRIMARY KEY  (umeta_id),
									KEY user_id  (user_id),
									KEY meta_key (meta_key)
								     ) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  		dbDelta($sql);

  		$sql = "CREATE TABLE $wpdb->mp_mailmeta (
									mmeta_id  			bigint(20) 				NOT NULL auto_increment,
									mail_id  			bigint(20) 				NOT NULL default '0',
									meta_key  			varchar(255) 			default NULL,
									meta_value 			longtext,
									PRIMARY KEY  (mmeta_id),
									KEY user_id  (mail_id),
									KEY meta_key (meta_key)
								     ) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  		dbDelta($sql);

//
//	From older versions installed
//

		global $mp_general;

		$mp_general = get_option('MailPress_general');

		if (!isset($mp_general['newsletters']))
		{
			$x = array('new_post','daily','weekly','monthly');
			$newsletters = array();

			foreach ($x as $n)
			{
				if (isset($mp_general[$n]))
				{
					$newsletters[$n] = true;
					unset($mp_general[$n]);
				}
			} 

			$mp_general['newsletters'] = $newsletters;
			update_option ('MailPress_general', $mp_general);
		}
		$x = false;
		$x = get_option ('MailPress_daily');
		if ($x && !is_array($x)) update_option('MailPress_daily', array('threshold'=>$x));
		$x = false;
		$x = get_option ('MailPress_weekly');
		if ($x && !is_array($x)) update_option('MailPress_weekly', array('threshold'=>$x));
		$x = false;
		$x = get_option ('MailPress_monthly');
		if ($x && !is_array($x)) update_option('MailPress_monthly', array('threshold'=>$x));

//
//	To avoid mailing existing published post
//
		$post_meta = '_MailPress_prior_to_install';
		$query = "SELECT ID FROM $wpdb->posts WHERE post_status = 'publish' ;";
		$ids = $wpdb->get_results( $query );
		if ($ids) foreach ($ids as $id) if (!get_post_meta($id->ID, $post_meta, true)) add_post_meta($id->ID, $post_meta, 'yes', true);

//
//	Some Clean Up
//
		$sql = " DELETE FROM $wpdb->mp_mails WHERE status = '' AND theme <> ''; ";
		$wpdb->query( $sql );
		$sql = " UPDATE FROM $wpdb->mp_mailmeta SET meta_key = '_MailPress_attached_file' WHERE meta_key = '_mp_attached_file'; ";
		$wpdb->query( $sql );
		$sql = " DELETE FROM $wpdb->mp_mailmeta WHERE mail_id NOT IN (SELECT id FROM $wpdb->mp_mails ); ";
		$wpdb->query( $sql );

//
//	For MailPress 4
//

		global $mp_general;
		$mp_general = get_option('MailPress_general');

		if (isset($mp_general['subscription_mngt']))
		{
			$mp_subscriptions = get_option('MailPress_subscriptions');
			if (!$mp_subscriptions)
			{
				$mp_subscriptions = $mp_general;
				$parms = array('subcomment', 'newsletters', 'default_newsletters');
				foreach($parms as $parm) unset($mp_general[$parm]);
				foreach($mp_general as $k => $v) unset($mp_subscriptions[$k]);
				update_option ('MailPress_general', $mp_general);
				$mailinglist = get_option('MailPress_mailinglist');
				if ($mailinglist && is_array($mailinglist)) $mp_subscriptions = array_merge($mp_subscriptions, $mailinglist);
				if (!isset($mp_subscriptions['default_newsletters'])) $mp_subscriptions['default_newsletters'] = array();
				delete_option('MailPress_mailinglist');
				update_option('MailPress_subscriptions', $mp_subscriptions);
			}
		}

		$logs = get_option('MailPress_logs');

		if (!$logs)
		{
			$parms = array('level', 'lognbr', 'lastpurge');
			$_settings = array('MailPress_general' => 'general', 'MailPress_batch_send' => 'batch_send', 'MailPress_import' => 'import', 'MailPress_autoresponder' => 'autoresponder');	
			$logs = array();
			foreach($_settings as $_setting => $_target)
			{
				$x = get_option($_setting);
				if ($x)
				{
					foreach($parms as $parm)
					{
						if (isset($x[$parm])) $logs[$_target][$parm] = $x[$parm];
						unset($x[$parm]);
					}
					if (empty($x)) 	delete_option($_setting);
					else			update_option($_setting, $x);
				}
			}
			add_option('MailPress_logs', $logs);
		}

		$x = get_option('MailPress_widget');
		if ($x)
		{
			if (isset($x['jQ']))
			{
				$x['jq'] = $x['jQ'];
				unset($x['jQ']);
			}
			add_option('widget_mailpress', $x);
			delete_option('MailPress_widget');
		}
?>