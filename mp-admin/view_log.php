<?php 
MailPress::require_class('Admin_page');

class MP_AdminPage extends MP_Admin_page
{
	const screen 	= 'mailpress_viewlog';
	const capability	= 'MailPress_view_logs';
	const help_url	= 'http://www.mailpress.org/wiki/index.php?title=Add_ons:View_logs';

	// for path
	public static function get_path() 
	{
		return MP_PATH . 'tmp';
	}

////  Title  ////

	public static function title() { global $title; $title = __('View Log', MP_TXTDOM); }

////  Styles  ////

	public static function print_styles($styles = array()) 
	{
		wp_register_style ( self::screen, 	'/' . MP_PATH . 'mp-admin/css/view_log.css',       array('thickbox') );
		$styles[] = self::screen;

		parent::print_styles($styles);
	}

////  Scripts  ////

	public static function print_scripts() 
	{
		$batch_send_config = get_option('MailPress_batch_send');
		$bounce_handling_config = get_option('MailPress_bounce_handling');
		if (('wpcron' != $batch_send_config['batch_mode']) || ('wpcron' != $bounce_handling_config['batch_mode'])) return;
		$every   = apply_filters('MailPress_autorefresh_every', $batch_send_config['every']);

		$checked = (isset($_GET['autorefresh'])) ?  " checked='checked'" : '';
		$time    = (isset($_GET['autorefresh'])) ?  $_GET['autorefresh'] : $every;
		$time    = (is_numeric($time) && ($time > $every)) ? $time : $every;
		$time    = "<input type='text' value='$time' maxlength='3' id='MP_Refresh_every' class='screen-per-page'/>";
		$option  = '<h5>' . __('Auto refresh for Auto scrolling', MP_TXTDOM) . '</h5>';
		$option .= "<div><input id='MP_Refresh' type='checkbox'$checked style='margin:0 5px 0 2px;' /><span class='MP_Refresh'>" . sprintf(__('%1$s Autorefresh %2$s every %3$s sec', MP_TXTDOM), "<label for='MP_Refresh' style='vertical-align:inherit;'>", '</label>', $time) . "</span></div>";

		$f = $_GET['id'];
		$view_url 	=  get_option('siteurl') . '/' . self::get_path() . '/' . $f;

		wp_register_script( 'mp-refresh-i', 	'/' . MP_PATH . 'mp-includes/js/mp_refresh_i.js', array('schedule'), false, 1);
		wp_localize_script( 'mp-refresh-i', 	'adminMpRefreshL10n', array(
			'iframe'	=> 'mp',
			'src'		=> $view_url,
			'screen' 	=> self::screen,
			'every' 	=> $every,
			'message' 	=> __('Autorefresh in %i% sec', MP_TXTDOM), 
			'option'	=> $option,
			'url' 	=> MP_Action_url,
			'l10n_print_after' => 'try{convertEntities(adminmailsL10n);}catch(e){};'
		) );

		$scripts[] = 'mp-refresh-i';
		parent::print_scripts($scripts);
	}

//// Body ////

	public static function body()
	{
		include (MP_TMP . 'mp-admin/includes/view_log.php');
	}
}
?>