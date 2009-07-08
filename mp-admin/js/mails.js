// mails

var mp_mails = {
	theList : null,
	theExtraList : null,

	init : function() {
		mp_mails.theList 		= jQuery('#the-mail-list').wpList( { alt: '', dimAfter: mp_mails.dimAfter, delBefore: mp_mails.delBefore, delAfter: mp_mails.delAfter, addColor: 'none' } );
		mp_mails.theExtraList 	= jQuery('#the-extra-mail-list').wpList( { alt: '', delColor: 'none', addColor: 'none' } );

		// delete
		jQuery('.delete a[class^="delete"]').click(function(){return false;});
	},

	dimAfter : function( r, settings ) {
	 	var id = jQuery('id',r).text();
	 	var item = jQuery('item',r).text();
	 	var rc = jQuery('rc',r).text();
	
		if (rc == 0)
		{
			jQuery('tr#mail-' + id).after(item).remove();
		}
		if (rc == 2)
		{
			jQuery('#the-mail-list tr:first').before(item).add();
			mp_thickbox.aclass = 'tr#mail-' + id + ' a.thickbox';
			tb_init(mp_thickbox.aclass);
			mp_thickbox.init();
		}
	},

	delBefore : function(s) {
		if ( 'undefined' != showNotice ) return showNotice.warn() ? s : false;
		return s;
	},

	delAfter : function( r, settings ) {
		jQuery('li span.mail-count').each( function() {
			var a = jQuery(this);
			var n = parseInt(a.html(),10);
			n--;
			if ( n < 0 ) { n = 0; }
			a.html( n.toString() );
		});

		if ( mp_mails.theExtraList.size() == 0 || mp_mails.theExtraList.children().size() == 0 ) {
			return;
		}

		mp_mails.theList.get(0).wpList.add( mp_mails.theExtraList.children(':eq(0)').remove().clone() );
		jQuery('#get-extra-mails').submit();
	}
};
jQuery(document).ready( function() { mp_mails.init(); });