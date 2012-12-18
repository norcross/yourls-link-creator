
jQuery(document).ready( function($) {

// **************************************************************
//  show / hide post types on admin
// **************************************************************

	$('input#yourls_cpt').each(function() { // for initial load
		var checkval = $(this).is(':checked');

		if (checkval === true)
			$('tr.secondary').show();

		if (checkval === false) {
			$('tr.secondary').hide();
			$('tr.secondary input:checkbox').prop('checked', false);
		}

	});

	$('input#yourls_cpt').change( function() { // for value change
		var checkval = $(this).is(':checked');

		if (checkval === true)
			$('tr.secondary').slideToggle(200);

		if (checkval === false) {
			$('tr.secondary').hide(200);
			$('tr.secondary input:checkbox').prop('checked', false);
		}

	});

// **************************************************************
//  create YOURLS on call
// **************************************************************

$('div#yourls-post-display').on('click', 'input.yourls-api', function (event) {

	// remove any existing messages
	$('div#wpbody div#message').remove();
	$('div#wpbody div#setting-error-settings_updated').remove();

	// adjust buttons
	$('div#yourls-post-display img.btn-yourls').css('visibility', 'visible');
	$('div#yourls-post-display input.yourls-api').attr('disabled', 'disabled');

	var keyword	= $('div#yourls-post-display').find('input.yourls-keyw').val();
	var post_id	= $('form#post input#post_ID').prop('value');

	var data = {
		action: 'create_yourls',
		keyword: keyword,
		postID: post_id
	};

	jQuery.post(ajaxurl, data, function(response) {

		$('div#yourls-post-display img.btn-yourls').css('visibility', 'hidden');
		$('div#yourls-post-display input.yourls-api').removeAttr('disabled');

		var obj;
		try {
			obj = jQuery.parseJSON(response);
		}
		catch(e) {
			$('div#wpbody h2:first').after('<div id="message" class="error below-h2 yourls-message"><p><strong>We could not retrieve your url. Please try again later.</strong></p></div>');
			$('div.yourls-message').delay(3000).fadeOut('slow');
			}

		if(obj.success === true) {
			$('div#yourls-post-display').find('p.yourls-create-block').replaceWith( obj.inputs );

			$('div#edit-slug-box').append('<input type="hidden" value="' + obj.link + '" id="shortlink">');
			$('div#edit-slug-box').append('<a onclick="prompt(\'URL:\', jQuery(\'#shortlink\').val()); return false;" class="button button-small" href="#">Get Shortlink</a>');


			$('div#wpbody h2:first').after('<div id="message" class="updated below-h2 yourls-message"><p>' + obj.message + '</p></div>');
			$('div.yourls-message').delay(3000).fadeOut('slow');
		}
		else {
			$('div#wpbody h2:first').after('<div id="message" class="error below-h2 yourls-message"><p>We could not retrieve your url. Please try again later.</p></div>');
			$('div.yourls-message').delay(3000).fadeOut('slow');
			}
		});

	});

// **************************************************************
//  delete YOURLS on call
// **************************************************************

$('div#yourls-post-display').on('click', 'input.yourls-delete', function (event) {

	// remove any existing messages
	$('div#wpbody div#message').remove();
	$('div#wpbody div#setting-error-settings_updated').remove();

	// adjust buttons
	$('div#yourls-post-display input.yourls-delete').attr('disabled', 'disabled');

	var post_id	= $('form#post input#post_ID').prop('value');

	var data = {
		action: 'delete_yourls',
		postID: post_id
	};

	jQuery.post(ajaxurl, data, function(response) {

		$('div#yourls-post-display input.yourls-delete').removeAttr('disabled');

		var obj;
		try {
			obj = jQuery.parseJSON(response);
		}
		catch(e) {
			$('div#wpbody h2:first').after('<div id="message" class="error below-h2 yourls-message"><p><strong>We could not delete your url. Please try again later.</strong></p></div>');
			$('div.yourls-message').delay(3000).fadeOut('slow');
			}

		if(obj.success === true) {
			$('div#yourls-post-display').find('p.howto').remove();
			$('div#yourls-post-display').find('p.yourls-exist-block').replaceWith(obj.inputs);

			$('div#edit-slug-box').find('input#shortlink').remove();
			$('div#edit-slug-box').find('a:contains("Get Shortlink")').remove();


			$('div#wpbody h2:first').after('<div id="message" class="updated below-h2 yourls-message"><p>' + obj.message + '</p></div>');
			$('div.yourls-message').delay(3000).fadeOut('slow');
		}
		else {
			$('div#wpbody h2:first').after('<div id="message" class="error below-h2 yourls-message"><p>' + obj.error + '</p></div>');
			$('div.yourls-message').delay(3000).fadeOut('slow');
			}
		});

	});

// **************************************************************
//  get YOURLS stats
// **************************************************************

$('input.yourls-stats').click(function (event) {

	// remove any existing messages
	$('div#wpbody div#message').remove();
	$('div#wpbody div#setting-error-settings_updated').remove();

	// adjust buttons
	$('div#yourls-post-display img.btn-yourls').css('visibility', 'visible');
	$('div#yourls-post-display input.yourls-stats').attr('disabled', 'disabled');

	var post_id	= $('form#post input#post_ID').prop('value');

	var data = {
		action: 'stats_yourls',
		postID: post_id
	};

	jQuery.post(ajaxurl, data, function(response) {

		$('div#yourls-post-display img.btn-yourls').css('visibility', 'hidden');
		$('div#yourls-post-display input.yourls-stats').removeAttr('disabled');

		var obj;
		try {
			obj = jQuery.parseJSON(response);
		}
		catch(e) {
			$('div#wpbody h2:first').after('<div id="message" class="error below-h2 yourls-message"><p>We could not retrieve your stats. Please try again later.</p></div>');
			$('div.yourls-message').delay(3000).fadeOut('slow');
			}

		if(obj.success === true) {
			alert(obj.message);
		}
		else {
			$('div#wpbody h2:first').after('<div id="message" class="error below-h2 yourls-message"><p>We could not retrieve your stats. Please try again later.</p></div>');
			$('div.yourls-message').delay(3000).fadeOut('slow');
			}
		});

	});


// **************************************************************
//  run click update from admin
// **************************************************************

$('input.yourls-click-updates').click(function (event) {

	// remove any existing messages
	$('div#wpbody div#message').remove();
	$('div#wpbody div#setting-error-settings_updated').remove();

	// adjust buttons
	$('div#yourls-data-refresh img.btn-yourls').css('visibility', 'visible');
	$('div#yourls-data-refresh input.yourls-click-updates').attr('disabled', 'disabled');

	var data = {
		action: 'clicks_yourls'
	};

	jQuery.post(ajaxurl, data, function(response) {

		$('div#yourls-data-refresh img.btn-yourls').css('visibility', 'hidden');
		$('div#yourls-data-refresh input.yourls-click-updates').removeAttr('disabled');

		var obj;
		try {
			obj = jQuery.parseJSON(response);
		}
		catch(e) {
			$('div#wpbody h2:first').after('<div id="message" class="error below-h2 yourls-message"><p>We could not update your data. Please try again later.</p></div>');
			$('div.yourls-message').delay(3000).fadeOut('slow');
			}

		if(obj.success === true) {
			$('div#wpbody h2:first').after('<div id="message" class="updated below-h2 yourls-message"><p>' + obj.message + '</div>');
			$('div.yourls-message').delay(3000).fadeOut('slow');
		}
		else {
			$('div#wpbody h2:first').after('<div id="message" class="error below-h2 yourls-message"><p>We could not update your data. Please try again later.</p></div>');
			$('div.yourls-message').delay(3000).fadeOut('slow');
			}
		});

	});

// **************************************************************
//  change meta key from old plugin
// **************************************************************

	$('input.yourls-convert').click(function(event) {

		$('img.btn-convert').css('visibility', 'visible');
		$('div#wpbody div#message').remove();
		$('div#wpbody div#setting-error-settings_updated').remove();

		var data = {
			action: 'key_change'
		};

		jQuery.post(ajaxurl, data, function(response) {
			var obj;

			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				$('img.btn-convert').css('visibility', 'hidden');
				$('div#wpbody h2:first').after('<div id="message" class="error below-h2 yourls-message"><p>There was an error. Please try again.</p></div>');
			}

			if(obj.success === true) {
				$('img.btn-convert').css('visibility', 'hidden');
				$('div#wpbody h2:first').after('<div id="message" class="yourls-message updated below-h2"><p>' + obj.message + '</p></div>');
				$('div.yourls-message').delay(3000).fadeOut('slow');
			}

			else if(obj.success === false && obj.errcode == 'KEY_MISSING') {
				$('img.btn-convert').css('visibility', 'hidden');
				$('div#wpbody h2:first').after('<div id="message" class="updated below-h2 yourls-message"><p>' + obj.message + '</p></div>');
			}

			else if(obj.success === false && obj.errcode == 'API_MISSING') {
				$('img.btn-convert').css('visibility', 'hidden');
				$('div#wpbody h2:first').after('<div id="message" class="updated below-h2 yourls-message"><p>' + obj.message + '</p></div>');
			}

			else {
				$('img.btn-convert').css('visibility', 'hidden');
				$('div#wpbody h2:first').after('<div id="message" class="error below-h2 yourls-message"><p>There was an error. Please try again.</p></div>');
			}
		});
	});

//********************************************************
// you're still here? it's over. go home.
//********************************************************
});
