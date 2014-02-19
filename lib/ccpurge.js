(function($){

	window.ccpurge = {};
	var ccpurge = window.ccpurge;

	ccpurge.initialize = function() {
		ccpurge.setElements();
		ccpurge.purgeEntireCache();
		ccpurge.purgeUrl();
		jQuery(document).ajaxStart(function() {
			jQuery('#spinner').show();
		})
		jQuery(document).ajaxStop(function() {
			jQuery('#spinner').hide();
		})

	};

	ccpurge.setElements = function() {
		ccpurge.elems = {};
		ccpurge.elems.form = {};
		ccpurge.elems.form.form = jQuery('#ccpurge-form');
		ccpurge.elems.form.username = ccpurge.elems.form.form.find('#ccpurge-email');
		ccpurge.elems.form.account = ccpurge.elems.form.form.find('#ccpurge-account');
		ccpurge.elems.form.token = ccpurge.elems.form.form.find('#ccpurge-token');
		ccpurge.elems.form.url = ccpurge.elems.form.form.find('#ccpurge-url');
		ccpurge.elems.entire_cache_btn = jQuery('#ccpurge-entire-cache');
		ccpurge.elems.purge_url_btn = jQuery('#ccpurge-purge-url');
		ccpurge.elems.logging_container = jQuery('#ccpurge_table_logging');

		ccpurge.properties = {};
	};

	ccpurge.handleJsonResponse = function(response, status) {
		if( status === undefined ){ status = 'success'; }
		if( status == 'success' ){
			alert('CloudFlare API Connect: Success\n\nSee log for details');
		}
		else{
			alert('CloudFlare API Connect: Error\n\n' + response);
		}
		ccpurge.refreshLog(0);
	}

	ccpurge.purgeEntireCache = function() {
		ccpurge.elems.entire_cache_btn.bind('click', function(e) {
			e.preventDefault();
			if( confirm('It may take up to 48 hours for the cache to rebuild and optimum speed to resume\nso this function should be used sparingly\n\nAre you sure you want to continue?') ) {
				jQuery.ajax({
					'type'  : 'post',
					'url'		: ajaxurl,
					'data'	: {
									'action'	: 'ccpurge_entire_cache'
								  },
					'success'	: function(response) { ccpurge.handleJsonResponse(response, 'success'); },
					'error'	: function(response) { ccpurge.handleJsonResponse(response, 'error'); }
				});
			}
		});
	}

	ccpurge.purgeUrl = function() {
		ccpurge.elems.purge_url_btn.bind('click', function(e) {
			e.preventDefault();
			jQuery.ajax({
				'type'  : 'post',
				'url'		: ajaxurl,
				'data'	: {
								'action'	: 'ccpurge_purge_url',
								'url'			: ccpurge.elems.form.url.val()
							  },
				'success'	: function(response) { ccpurge.handleJsonResponse(response, 'success'); },
				'error'	: function(response) { ccpurge.handleJsonResponse(response, 'error'); }
			})
		});
	}

	ccpurge.ccpurge_transaction_logging = function(message, status) {
		jQuery.ajax({
			'type'  : 'post',
			'url'		: ajaxurl,
			'data'	: {
							'action'	: 'ccpurge_transaction_logging',
							'message'	: message,
							'status'  : status
						  },
			'success'	: function(response) { ccpurge.refreshLog(0); },
			'error'		: function(response) { console.log(response); }
		});
	};

	ccpurge.refreshLog = function(page) {
		jQuery.ajax({
			'type'  : 'post',
			'url'		: ajaxurl,
			'data'	: {
							'action'	: 'ccpurge_get_table_logging',
							'd_page' : page
						  },
			'success'	: function(response) { ccpurge.elems.logging_container.html(response); },
			'error'	: function(response) { console.log(response); }
		})
	}


	jQuery(document).ready(function() {
		ccpurge.initialize();
		ccpurge.refreshLog(0);
		console.log('ccpurge loaded');
	});


})(jQuery);