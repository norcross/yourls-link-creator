
jQuery(document).ready( function($) {

// **************************************************************
//  create YOURLS on call
// **************************************************************

$('input.yourls-api').click(function (event) {

	// remove any existing messages
	$('#wpbody div#message').remove();

	// adjust buttons
	$('div#yours-post-display img.btn-yourls').css('visibility', 'visible');
	$('div#yours-post-display input.yourls-api').attr('disabled', 'disabled');

	var keyword	= $('div#yours-post-display').find('input.yourls-keyw').val();
	var post_id	= $('form#post input#post_ID').prop('value');

	var data = {
		action: 'create_yourls',
		keyword: keyword,
		postID: post_id
	};

	jQuery.post(ajaxurl, data, function(response) {

		$('div#yours-post-display img.btn-yourls').css('visibility', 'hidden');
		$('div#yours-post-display input.yourls-api').removeAttr('disabled');

		var obj;
		try {
			obj = jQuery.parseJSON(response);
		}
		catch(e) {
			$('div#wpbody h2:first').after('<div id="message" class="error below-h2"><p><strong>We could not retrieve your url. Please try again later.</strong></p></div>');
			}

		if(obj.success === true) {
			$('div#wpbody h2:first').after('<div id="message" class="updated below-h2"><p><strong>' + obj.message + '</strong></p></div>');
			$('div#yours-post-display').find('p.yourls-create-block').replaceWith('<p class="yourls-exist-block"><input id="yourls_link" class="widefat" type="text" name="yourls_link" value="' + obj.link + '" readonly="readonly" tabindex="501" onclick="this.focus();this.select()" /></p>');
			$('div#yours-post-display').find('p.howto').replaceWith('<p class="howto">Your custom YOURLS link.</p>');
		}
		else {
			$('div#wpbody h2:first').after('<div id="message" class="error below-h2"><p><strong>We could not retrieve your url. Please try again later.</strong></p></div>');
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
	$('div#yours-post-display img.btn-yourls').css('visibility', 'visible');
	$('div#yours-post-display input.yourls-stats').attr('disabled', 'disabled');

	var post_id	= $('form#post input#post_ID').prop('value');

	var data = {
		action: 'stats_yourls',
		postID: post_id
	};

	jQuery.post(ajaxurl, data, function(response) {

		$('div#yours-post-display img.btn-yourls').css('visibility', 'hidden');
		$('div#yours-post-display input.yourls-stats').removeAttr('disabled');

		var obj;
		try {
			obj = jQuery.parseJSON(response);
		}
		catch(e) {
			$('div#wpbody h2:first').after('<div id="message" class="error below-h2"><p><strong>We could not retrieve your stats. Please try again later.</strong></p></div>');
			}

		if(obj.success === true) {
			alert(obj.message);
		}
		else {
			$('div#wpbody h2:first').after('<div id="message" class="error below-h2"><p><strong>We could not retrieve your stats. Please try again later.</strong></p></div>');
			}
		});

	});


// **************************************************************
//  run click update from admin
// **************************************************************

$('input.yours-click-updates').click(function (event) {

	// remove any existing messages
	$('#wpbody div#message').remove();

	// adjust buttons
	$('div#yourls-data-refresh img.btn-yourls').css('visibility', 'visible');
	$('div#yourls-data-refresh input.yours-click-updates').attr('disabled', 'disabled');

	var data = {
		action: 'clicks_yourls'
	};

	jQuery.post(ajaxurl, data, function(response) {

		$('div#yourls-data-refresh img.btn-yourls').css('visibility', 'hidden');
		$('div#yourls-data-refresh input.yours-click-updates').removeAttr('disabled');

		var obj;
		try {
			obj = jQuery.parseJSON(response);
		}
		catch(e) {
			$('div#wpbody h2:first').after('<div id="message" class="error below-h2"><p><strong>We could not update your data. Please try again later.</strong></p></div>');
			}

		if(obj.success === true) {
			$('div#wpbody h2:first').after('<div id="message" class="updated below-h2"><p>' + obj.message + '</div>');
		}
		else {
			$('div#wpbody h2:first').after('<div id="message" class="error below-h2"><p><strong>We could not update your data. Please try again later.</strong></p></div>');
			}
		});

	});

//********************************************************
// you're still here? it's over. go home.
//********************************************************
});
