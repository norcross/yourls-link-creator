//********************************************************************************************************************************
// reusable message function
//********************************************************************************************************************************
function yourlsMessage( msgType, msgText ) {
	jQuery( 'div#wpbody h2:first' ).after( '<div id="message" class="' + msgType + ' below-h2"><p>' + msgText + '</p></div>' );
}

//********************************************************************************************************************************
// start the engine
//********************************************************************************************************************************
jQuery(document).ready( function($) {

//********************************************************************************************************************************
// quick helper to check for an existance of an element
//********************************************************************************************************************************
	$.fn.divExists = function(callback) {
		// slice some args
		var args = [].slice.call( arguments, 1 );
		// check for length
		if ( this.length ) {
			callback.call( this, args );
		}
		// return it
		return this;
	};

//********************************************************************************************************************************
// set some vars
//********************************************************************************************************************************
	var yrlSocial   = '';
	var yrlAdminBox = '';
	var yrlClickBox = '';
	var yrlClickRow = '';
	var yrlKeyword  = '';
	var yrlPostID   = '';
	var yrlNonce    = '';

//********************************************************************************************************************************
// social link in a new window because FANCY
//********************************************************************************************************************************
	$( 'div.yourls-sidebox' ).on( 'click', 'a.admin-twitter-link', function() {
		// only do this on larger screens
		if ( $( window ).width() > 765 ) {
			// get our link
			yrlSocial = $( this ).attr( 'href' );
			// open our fancy window
			window.open( yrlSocial, 'social-share-dialog', 'width=626,height=436' );
			// and finish
			return false;
		}
	});

//********************************************************************************************************************************
// do the password magic
//********************************************************************************************************************************
	$( 'td.apikey-field-wrapper' ).divExists( function() {

		// hide it on load
		$( 'input#yourls-api' ).hidePassword( false );

		// now check for clicks
		$( 'td.apikey-field-wrapper' ).on( 'click', 'span.password-toggle', function () {

			// if our password is not visible
			if ( ! $( this ).hasClass( 'password-visible' ) ) {
				$( this ).addClass( 'password-visible' );
				$( 'input#yourls-api' ).showPassword( false );
			} else {
				$( this ).removeClass( 'password-visible' );
				$( 'input#yourls-api' ).hidePassword( false );
			}

		});
	});

//********************************************************************************************************************************
// other external links in new tab
//********************************************************************************************************************************
	$( 'div.yourls-sidebox' ).find( 'a.external' ).attr( 'target', '_blank' );

//********************************************************************************************************************************
// show / hide post types on admin
//********************************************************************************************************************************
	$( 'tr.setting-item-types' ).divExists( function() {

		// see if our box is checked
		yrlAdminBox = $( this ).find( 'input#yourls-cpt' ).is( ':checked' );

		// if it is, show it
		if ( yrlAdminBox === true ) {
			$( 'tr.secondary' ).show();
		}

		// if not, hide it and make sure boxes are not checked
		if ( yrlAdminBox === false ) {
			$( 'tr.secondary' ).hide();
			$( 'tr.secondary' ).find( 'input:checkbox' ).prop( 'checked', false );
		}

		// now the check for clicking
		$( 'tr.setting-item-types' ).on( 'change', 'input#yourls-cpt', function() {

			// check the box (again)
			yrlAdminBox = $( this ).is( ':checked' );

			// if it is, show it
			if ( yrlAdminBox === true ) {
				$( 'tr.secondary' ).fadeIn( 700 );
			}

			// if not, hide it and make sure boxes are not checked
			if ( yrlAdminBox === false ) {
				$( 'tr.secondary' ).fadeOut( 700 );
				$( 'tr.secondary' ).find( 'input:checkbox' ).prop( 'checked', false );
			}
		});

	});

//********************************************************************************************************************************
// create YOURLS on call
//********************************************************************************************************************************
	$( 'div#yourls-post-display').on( 'click', 'input.yourls-api', function () {

		// get my post ID and my nonce
		yrlPostID   = $( this ).data( 'post-id' );
		yrlNonce    = $( this ).data( 'nonce' );

		// bail without post ID or nonce
		if ( yrlPostID === '' || yrlNonce === '' ) {
			return;
		}

		// remove any existing messages
		$( 'div#wpbody div#message' ).remove();
		$( 'div#wpbody div#setting-error-settings_updated' ).remove();

		// adjust buttons
		$( 'div#yourls-post-display' ).find( 'span.yourls-spinner' ).css( 'visibility', 'visible' );
		$( 'div#yourls-post-display' ).find( 'input.yourls-api').attr( 'disabled', 'disabled' );

		// get my optional keyword
		yrlKeyword  = $( 'div#yourls-post-display' ).find( 'input.yourls-keyw' ).val();

		// set my data array
		var data = {
			action:  'create_yourls',
			keyword: yrlKeyword,
			post_id: yrlPostID,
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			$( 'div#yourls-post-display' ).find( 'span.yourls-spinner' ).css( 'visibility', 'hidden' );
			$( 'div#yourls-post-display' ).find( 'input.yourls-api').removeAttr( 'disabled' );

			var obj;

			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				yourlsMessage( 'error', yourlsAdmin.defaultError );
			}

			if( obj.success === true ) {
				yourlsMessage( 'updated', obj.message );
			}

			else if( obj.success === false && obj.message !== null ) {
				yourlsMessage( 'error', obj.message );
			}

			else {
				yourlsMessage( 'error', yourlsAdmin.defaultError );
			}

			// add in the new YOURLS box if it comes back
			if( obj.success === true && obj.linkbox !== null ) {

				// remove the submit box
				$( 'div#yourls-post-display' ).find( 'p.yourls-submit-block' ).remove();

				// swap out our boxes
				$( 'div#yourls-post-display' ).find( 'p.yourls-input-block' ).replaceWith( obj.linkbox );

				// add our shortlink button
				$( 'div#edit-slug-box' ).append( '<input type="hidden" value="' + obj.linkurl + '" id="shortlink">' );
				$( 'div#edit-slug-box' ).append( yourlsAdmin.shortSubmit );
			}
		});

	});

//********************************************************************************************************************************
// delete YOURLS on call
//********************************************************************************************************************************
	$( 'div#yourls-post-display' ).on( 'click', 'span.yourls-delete', function () {

		// get my post ID and nonce
		yrlPostID   = $( this ).data( 'post-id' );
		yrlNonce    = $( this ).data( 'nonce' );

		// bail without post ID or nonce
		if ( yrlPostID === '' || yrlNonce === '' ) {
			return;
		}

		// remove any existing messages
		$( 'div#wpbody div#message' ).remove();
		$( 'div#wpbody div#setting-error-settings_updated' ).remove();

		// set my data array
		var data = {
			action:  'delete_yourls',
			post_id: yrlPostID,
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				yourlsMessage( 'error', yourlsAdmin.defaultError );
			}

			if( obj.success === true ) {
				yourlsMessage( 'updated', obj.message );
			}

			else if( obj.success === false && obj.message !== null ) {
				yourlsMessage( 'error', obj.message );
			}
			else {
				yourlsMessage( 'error', yourlsAdmin.defaultError );
			}

			// add in the new YOURLS box if it comes back
			if( obj.success === true && obj.yourlsbox !== null ) {

				$( 'div#yourls-post-display' ).find( 'p.howto' ).remove();
				$( 'div#yourls-post-display' ).find( 'p.yourls-exist-block' ).replaceWith( obj.linkbox );

				$( 'div#edit-slug-box' ).find( 'input#shortlink' ).remove();
				$( 'div#edit-slug-box' ).find( 'a:contains("Get Shortlink")' ).remove();
			}
		});

	});

//********************************************************************************************************************************
// update YOURLS click count
//********************************************************************************************************************************
	$( 'div.row-actions' ).on( 'click', 'a.yourls-admin-update', function (e) {

		// stop the hash
		e.preventDefault();

		// get my nonce
		yrlNonce    = $( this ).data( 'nonce' );

		// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}

		// get my post ID
		yrlPostID   = $( this ).data( 'post-id' );

		// bail without post ID
		if ( yrlPostID === '' ) {
			return;
		}

		// set my row and box as a variable for later
		yrlClickRow = $( this ).parents( 'div.row-actions' );
		yrlClickBox = $( this ).parents( 'tr.entry' ).find( 'td.yourls-click' );

		// set my data array
		var data = {
			action:  'stats_yourls',
			post_id: yrlPostID,
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			// hide the row actions
			yrlClickRow.removeClass( 'visible' );

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				return false;
			}

			// add in the new number box if it comes back
			if( obj.success === true && obj.clicknm !== null ) {
				yrlClickBox.find( 'span' ).text( obj.clicknm );
			}
		});
	});

//********************************************************************************************************************************
// create YOURLS inline
//********************************************************************************************************************************
	$( 'div.row-actions' ).on( 'click', 'a.yourls-admin-create', function (e) {

		// stop the hash
		e.preventDefault();

		// get my nonce
		yrlNonce    = $( this ).data( 'nonce' );

		// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}

		// get my post ID
		yrlPostID   = $( this ).data( 'post-id' );

		// bail without post ID
		if ( yrlPostID === '' ) {
			return;
		}

		// set my row and box as a variable for later
		yrlClickRow = $( this ).parents( 'div.row-actions' );
		yrlClickBox = $( this ).parents( 'div.row-actions' ).find( 'span.create-yourls' );

		// set my data array
		var data = {
			action:  'inline_yourls',
			post_id: yrlPostID,
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			// hide the row actions
			yrlClickRow.removeClass( 'visible' );

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				return false;
			}

			// add in the new click box if it comes back
			if( obj.success === true && obj.rowactn !== null ) {
				yrlClickBox.replaceWith( obj.rowactn );
			}
		});
	});

//********************************************************************************************************************************
// run click update from admin
//********************************************************************************************************************************
	$( 'div#yourls-data-refresh' ).on( 'click', 'input.yourls-click-updates', function () {

		// get my nonce first
		yrlNonce    = $( 'input#yourls_refresh' ).val();

		// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}

		// remove any existing messages
		$( 'div#wpbody div#message' ).remove();
		$( 'div#wpbody div#setting-error-settings_updated' ).remove();

		// adjust buttons
		$( 'div#yourls-data-refresh' ).find( 'span.yourls-refresh-spinner' ).css( 'visibility', 'visible' );
		$( 'div#yourls-data-refresh' ).find( 'input.yourls-click-updates').attr( 'disabled', 'disabled' );

		// set my data array
		var data = {
			action:  'refresh_yourls',
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			// adjust buttons
			$( 'div#yourls-data-refresh' ).find( 'span.yourls-refresh-spinner' ).css( 'visibility', 'hidden' );
			$( 'div#yourls-data-refresh' ).find( 'input.yourls-click-updates').removeAttr( 'disabled' );

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				yourlsMessage( 'error', yourlsAdmin.defaultError );
			}

			if( obj.success === true && obj.message !== '' ) {
				yourlsMessage( 'updated', obj.message );
			}
			else if( obj.success === false && obj.message !== null ) {
				yourlsMessage( 'error', obj.message );
			}
			else {
				yourlsMessage( 'error', yourlsAdmin.defaultError );
			}
		});
	});

//********************************************************************************************************************************
// attempt data import
//********************************************************************************************************************************
	$( 'div#yourls-data-refresh' ).on( 'click', 'input.yourls-click-import', function () {

		// get my nonce first
		yrlNonce    = $( 'input#yourls_import' ).val();

		// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}

		// remove any existing messages
		$( 'div#wpbody div#message' ).remove();
		$( 'div#wpbody div#setting-error-settings_updated' ).remove();

		// adjust buttons
		$( 'div#yourls-data-refresh' ).find( 'span.yourls-import-spinner' ).css( 'visibility', 'visible' );
		$( 'div#yourls-data-refresh' ).find( 'input.yourls-click-import' ).attr( 'disabled', 'disabled' );

		// set my data array
		var data = {
			action:  'import_yourls',
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			// adjust buttons
			$( 'div#yourls-data-refresh' ).find( 'span.yourls-import-spinner' ).css( 'visibility', 'hidden' );
			$( 'div#yourls-data-refresh' ).find( 'input.yourls-click-import' ).removeAttr( 'disabled' );

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				yourlsMessage( 'error', yourlsAdmin.defaultError );
			}

			if( obj.success === true && obj.message !== '' ) {
				yourlsMessage( 'updated', obj.message );
			}
			else if( obj.success === false && obj.message !== null ) {
				yourlsMessage( 'error', obj.message );
			}
			else {
				yourlsMessage( 'error', yourlsAdmin.defaultError );
			}
		});
	});

//********************************************************************************************************************************
// change meta key from old plugin
//********************************************************************************************************************************
	$( 'div#yourls-data-refresh' ).on( 'click', 'input.yourls-convert', function () {

		// get my nonce first
		yrlNonce    = $( 'input#yourls_convert' ).val();

		// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}

		// remove any existing messages
		$( 'div#wpbody div#message' ).remove();
		$( 'div#wpbody div#setting-error-settings_updated' ).remove();

		// adjust buttons
		$( 'div#yourls-data-refresh' ).find( 'span.yourls-convert-spinner' ).css( 'visibility', 'visible' );
		$( 'div#yourls-data-refresh' ).find( 'input.yourls-convert').attr( 'disabled', 'disabled' );

		// set my data array
		var data = {
			action:  'convert_yourls',
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			// adjust buttons
			$( 'div#yourls-data-refresh' ).find( 'span.yourls-convert-spinner' ).css( 'visibility', 'hidden' );
			$( 'div#yourls-data-refresh' ).find( 'input.yourls-convert').removeAttr( 'disabled' );

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				yourlsMessage( 'error', yourlsAdmin.defaultError );
			}

			if( obj.success === true && obj.message !== '' ) {
				yourlsMessage( 'updated', obj.message );
			}
			else if( obj.success === false && obj.message !== null ) {
				yourlsMessage( 'error', obj.message );
			}
			else {
				yourlsMessage( 'error', yourlsAdmin.defaultError );
			}
		});
	});

//********************************************************************************************************************************
// you're still here? it's over. go home.
//********************************************************************************************************************************
});
