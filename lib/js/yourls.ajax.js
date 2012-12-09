
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

$('input.yourls-api').click(function (event) {

	// remove any existing messages
	$('#wpbody div#message').remove();

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
			$('div#yourls-post-display').find('p.yourls-create-block').replaceWith('<p class="yourls-exist-block"><input id="yourls_link" class="widefat" type="text" name="yourls_link" value="' + obj.link + '" readonly="readonly" tabindex="501" onclick="this.focus();this.select()" /></p>');
			$('div#yourls-post-display').find('p.howto').replaceWith('<p class="howto">Your custom YOURLS link.</p>');

			$('div#wpbody h2:first').after('<div id="message" class="updated below-h2 yourls-message"><p><strong>' + obj.message + '</strong></p></div>');
			$('div.yourls-message').delay(3000).fadeOut('slow');
		}
		else {
			$('div#wpbody h2:first').after('<div id="message" class="error below-h2 yourls-message"><p><strong>We could not retrieve your url. Please try again later.</strong></p></div>');
			$('div.yourls-message').delay(3000).fadeOut('slow');
			}
		});

	});

// **************************************************************
//  get YOURLS stats
// **************************************************************

$('input.yourls-stats').click(function (event) {

	// remove any existing messages
	$('#wpbody div#message').remove();

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
			$('div#wpbody h2:first').after('<div id="message" class="error below-h2 yourls-message"><p><strong>We could not retrieve your stats. Please try again later.</strong></p></div>');
			$('div.yourls-message').delay(3000).fadeOut('slow');
			}

		if(obj.success === true) {
			alert(obj.message);
		}
		else {
			$('div#wpbody h2:first').after('<div id="message" class="error below-h2 yourls-message"><p><strong>We could not retrieve your stats. Please try again later.</strong></p></div>');
			$('div.yourls-message').delay(3000).fadeOut('slow');
			}
		});

	});


// **************************************************************
//  run click update from admin
// **************************************************************

$('input.yourls-click-updates').click(function (event) {

	// remove any existing messages
	$('#wpbody div#message').remove();

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
			$('div#wpbody h2:first').after('<div id="message" class="error below-h2 yourls-message"><p><strong>We could not update your data. Please try again later.</strong></p></div>');
			$('div.yourls-message').delay(3000).fadeOut('slow');
			}

		if(obj.success === true) {
			$('div#wpbody h2:first').after('<div id="message" class="updated below-h2 yourls-message"><p>' + obj.message + '</div>');
			$('div.yourls-message').delay(3000).fadeOut('slow');
		}
		else {
			$('div#wpbody h2:first').after('<div id="message" class="error below-h2 yourls-message"><p><strong>We could not update your data. Please try again later.</strong></p></div>');
			$('div.yourls-message').delay(3000).fadeOut('slow');
			}
		});

	});

//********************************************************
// you're still here? it's over. go home.
//********************************************************
});
