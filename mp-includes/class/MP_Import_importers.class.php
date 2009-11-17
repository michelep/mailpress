<?php
MailPress::require_class('Options');

class MP_Import_importers extends MP_Options
{
	var $path = 'import/importers';

	public static function get_all()
	{
		$x = apply_filters('MailPress_import_importers_register', array());
		uasort($x, create_function('$a, $b', 'return strcmp($a[0], $b[0]);'));
		return $x;
	}
}
$MP_Import_importers = new MP_Import_importers();
?>