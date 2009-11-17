<?php 
MailPress::require_class('Admin_page_list');

class MP_AdminPage extends MP_Admin_page_list
{
	const screen 	= MailPress_page_forms;
	const capability 	= 'MailPress_manage_forms';
	const help_url	= 'http://www.mailpress.org/wiki/index.php?title=Add_ons:Form:Forms';

	const add_form_id = 'add';
	const list_id 	= 'the-list';
	const tr_prefix_id = 'form';

////  Redirect  ////

	public static function redirect() 
	{
		if ( isset($_POST['action']) )    $action = $_POST['action'];
		elseif ( isset($_GET['action']) ) $action = $_GET['action'];  
		if ( isset($_GET['deleteit']) )   $action = 'bulk-delete';
		if (!isset($action)) return;

		$url_parms = self::get_url_parms(array('s', 'apage', 'id'));

		self::require_class('Forms');

		switch($action) 
		{
			case 'add':
				$e = MP_Forms::insert($_POST);

				$url_parms['message'] = ( $e ) ? 1 : 4;
				unset($url_parms['s']);
				self::mp_redirect( self::url(MailPress_forms, $url_parms) );
			break;

			case 'delete':
				MP_Forms::delete($url_parms['id']);
				unset($url_parms['id']);

				$url_parms['message'] = 2;
				self::mp_redirect( self::url(MailPress_forms, $url_parms) );
			break;

			case 'bulk-delete':
				foreach ( (array) $_GET['delete'] as $id ) 
				{
					MP_Forms::delete($id);
				}

				$url_parms['message'] = ( count($_GET['delete']) > 1) ? 6 : 2;
				self::mp_redirect( self::url(MailPress_forms, $url_parms) );
			break;

			case 'edited':
				unset($_GET['action']);
				if (!isset($_POST['cancel'])) 
				{
					$e = MP_Forms::insert($_POST);
					$url_parms['message'] = ( $e ) ? 3 : 5 ;
					$url_parms['action']  = 'edit';
				}
				else unset($url_parms['id']);

				self::mp_redirect( self::url(MailPress_forms, $url_parms) );
			break;

			case 'duplicate' :
				MP_Forms::duplicate($url_parms['id']);
				self::mp_redirect( self::url(MailPress_forms, $url_parms) );
			break;

			default:
				if ( !empty($_GET['_wp_http_referer']) )
					self::mp_redirect(remove_query_arg(array('_wp_http_referer', '_wpnonce'), stripslashes($_SERVER['REQUEST_URI'])));
			break;
		}
	}

////  Title  ////

	public static function title() { if (isset($_GET['id'])) { global $title; $title = __('Edit Form', 'MailPress'); } }

////  Styles  ////

	public static function print_styles($styles = array()) 
	{
		wp_register_style ( MailPress_page_forms,	'/' . MP_PATH . 'mp-admin/css/forms.css', array('thickbox') );
		$styles[] = MailPress_page_forms;
		parent::print_styles($styles);
	}

//// Scripts ////

	public static function print_scripts() 
	{
		wp_register_script( 'mp-ajax-response',	'/' . MP_PATH . 'mp-includes/js/mp_ajax_response.js', array('jquery'), false, 1);
		wp_localize_script( 'mp-ajax-response', 	'wpAjax', array(
			'noPerm' => __('An unidentified error has occurred.'), 
			'broken' => __('An unidentified error has occurred.'), 
			'l10n_print_after' => 'try{convertEntities(wpAjax);}catch(e){};' 
		));

		wp_register_script( 'mp-lists', 		'/' . MP_PATH . 'mp-includes/js/mp_lists.js', array('mp-ajax-response'), false, 1);
		wp_localize_script( 'mp-lists', 		'wpListL10n', array( 
			'url' => MP_Action_url
		));

		wp_register_script( 'mp-thickbox', 		'/' . MP_PATH . 'mp-includes/js/mp_thickbox.js', array('thickbox'), false, 1);

		wp_register_script( 'mp-taxonomy', 		'/' . MP_PATH . 'mp-includes/js/mp_taxonomy.js', array('mp-lists'), false, 1);
		wp_localize_script( 'mp-taxonomy', 		'MP_AdminPageL10n', array(
			'errmess' => __('Enter a valid email !', 'MailPress'), 
			'pending' => __('%i% pending'), 
			'screen' => self::screen,
			'list_id' => self::list_id,
			'add_form_id' => self::add_form_id,
			'tr_prefix_id' => self::tr_prefix_id,
			'l10n_print_after' => 'try{convertEntities(MP_AdminPageL10n);}catch(e){};' 
		));

		wp_register_script( self::screen, 		'/' . MP_PATH . 'mp-admin/js/forms.js', array('mp-taxonomy', 'mp-thickbox', 'jquery-ui-tabs'), false, 1);

		$scripts[] = self::screen;
		parent::print_scripts($scripts);
	}

//// Columns ////

	public static function get_columns() 
	{
		$columns = array(	'cb'			=> '<input type="checkbox" />',
					'name'		=> __('Label', 'MailPress'),
					'template'		=> __('Template', 'MailPress'),
					'recipient'		=> __('Recipient', 'MailPress'),
					'confirm' 		=> __('Copy', 'MailPress'));
		return $columns;
	}

//// List ////

	public static function get_list($start, $num, $url_parms) 
	{
		global $wpdb;
		$where = '';
		if (isset($url_parms['s']) && !empty($url_parms['s']))
		{
			$s = $wpdb->escape($url_parms['s']);
			if (!empty($where)) $where = $where . ' AND ';
			if ($s) $where .= " (a.label LIKE '%$s%' OR a.description LIKE '%$s%') "; 
		}

		if ($where) $where = ' WHERE ' . $where;

		$query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS a.id FROM $wpdb->mp_forms a $where ";

		return parent::get_list($start, $num, $query, 'mp_forms');
	}

////  Row  ////

	public static function get_row( $form, $url_parms ) 
	{
		self::require_class('Forms');

		static $row_class = '';

		$form = MP_Forms::get( $form );

// url's
		$url_parms['action'] 	= 'edit';

		$url_parms['id'] 	= $form->id;

		$edit_url = clean_url(self::url( MailPress_forms, $url_parms ));
		$url_parms['action'] 	= 'duplicate';
		$duplicate_url = clean_url(self::url( MailPress_forms, $url_parms, 'duplicate-form_' . $form->id ));

		$url_parms['action'] 	= 'edit_fields';
		$url_parms['form_id'] = $url_parms['id']; unset($url_parms['id']); 
		$edit_fields_url = clean_url(self::url( MailPress_fields, $url_parms ));
		$url_parms['id'] = $url_parms['form_id']; unset($url_parms['form_id']); 

		$edit_templates_url = clean_url(self::url( MailPress_templates, array('action' => 'edit', 'template' => $form->template)));

		$args = array();
		$args['id'] 	= $form->id;
		$args['action'] 	= 'ifview';
		$args['KeepThis'] = 'true'; $args['TB_iframe']= 'true'; $args['width'] = '600'; $args['height']	= '400';
		$view_url		= clean_url(self::url(MP_Action_url, $args));

		$url_parms['action'] 	= 'delete';
		$delete_url = clean_url(self::url( MailPress_forms, $url_parms, 'delete-form_' . $form->id ));

// actions
		$actions = array();
		$actions['edit'] = '<a href="' . $edit_url . '">' . __('Edit') . '</a>';
		$actions['edit_templates'] = '<a href="' . $edit_templates_url . '">' . __('Templates', 'MailPress') . '</a>';
		$actions['edit_fields'] = '<a href="' . $edit_fields_url . '">' . __('Fields', 'MailPress') . '</a>';
		$actions['duplicate'] = "<a class='dim:" . self::list_id . ":" . self::tr_prefix_id . "-" . $form->id . ":unapproved:e7e7d3:e7e7d3' href='$duplicate_url'>" . __('Duplicate', 'MailPress') . "</a>";
		$actions['delete'] = "<a class='delete:" . self::list_id . ":" . self::tr_prefix_id . "-" . $form->id . " submitdelete' href='$delete_url'>" . __('Delete') . "</a>";
		$actions['view'] = "<a class='thickbox' href='$view_url' title=\"" . sprintf(__('Form preview #%1$s (%2$s)', 'MailPress'), $form->id, stripslashes($form->label)) . "\" >" . __('Preview', 'MailPress') . "</a>";

		$row_class = 'alternate' == $row_class ? '' : 'alternate';

		$out = '';
		$out .= "<tr id='" . self::tr_prefix_id . "-$form->id' class='iedit $row_class'>";

		$columns = self::get_columns();
		$hidden  = self::get_hidden_columns();

		foreach ( $columns as $column_name => $column_display_name ) 
		{
			$class = "class='$column_name column-$column_name'";

			$style = '';
			if ( in_array($column_name, $hidden) )
				$style = ' style="display:none;"';

			$attributes = "$class$style";
			switch ($column_name) 
			{
				case 'cb':
					$out .= '<th scope="row" class="check-column"> <input type="checkbox" name="delete[]" value="' . $form->id . '" /></th>';
				break;
				case 'name':
					$out .= '<td ' . $attributes . '><strong><a class="row-title" href="' . $edit_url . '" title="' . attribute_escape(sprintf(__('Edit "%s"'), $form->label)) . '">' . $form->label . '</a></strong><br />';
					$out .= self::get_actions($actions);
					$out .= '</td>';
				break;
	 			case 'template':
	 				$out .= "<td $attributes>" . $form->template . "</td>\n";
	 			break;
	 			case 'Theme':
	 				$out .= "<td $attributes>" . $form->settings['recipient']['theme'];
					if (!empty($form->settings['recipient']['template'])) $out .= '<br />(' . $form->settings['recipient']['template'] . ')'; 
					$out .= "</td>\n";
	 			break;
	 			case 'recipient':
	 				$out .= "<td $attributes>" . $form->settings['recipient']['toemail'];
					if (!empty($form->settings['recipient']['toname'])) $out .= '<br />(' . $form->settings['recipient']['toname'] . ')'; 
					$out .= "</td>\n";
	 			break;
				case 'confirm':
	 				$out .= "<td $attributes>";
					$mail = (isset($form->settings['visitor']['mail'])) ? $form->settings['visitor']['mail'] : 0;
					switch ($mail)
					{
						case 1 :
							$out .= __('t.b.c.', 'MailPress');
						break;
						case 2 :
							$out .= __('yes', 'MailPress');
						break;
						default :
							$out .= __('no', 'MailPress');
						break;
					}
	 				$out .= "</td>\n";
	 			break;
			}
		}
		$out .= "</tr>\n";

		return $out;
	}

//// Body ////

	public static function body()
	{
		include (MP_TMP . 'mp-admin/includes/forms.php');
	}
}
?>