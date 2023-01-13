// used to hold data in a higher scope for popup notices.
var ppmwpNoticeData = {};

jQuery( 'document' ).ready( function( $ ) {
	function display( value, id ) {
		var $li_item = $( "<li>" )
			.addClass( 'ppm-exempted-list-item user-btn button button-secondary' )
			.attr( 'data-id', id )
			.append( '<a href="#" class="remove remove-item"></a>' );

		$li_item.prepend( value ).prependTo( "ul#ppm-exempted-list" );

		if ( parseInt( id ) > 0 ) {
			add_exemption( $li_item, id, 'users' );
		} else {
			add_exemption( $li_item, id, 'roles' );
		}
		$( "#ppm-exempted-list" ).scrollTop( 0 );
	}

	function add_exemption( $li_item, $id, $type ) {
		var $existing_val;
		$li_item.addClass( "ppm-exempted-" + $type );
		$existing_val = $( "#ppm-exempted-" + $type ).val();
		if ( $existing_val === '' ) {
			$existing_val = [ ];
		} else {
			$existing_val = JSON.parse( $existing_val );
		}
		$existing_val.push( $id );
		$( "#ppm-exempted-" + $type ).val( JSON.stringify( $existing_val ) );

	}

	function remove_exemption( $id, $type ) {
		var $existing_val;
		$existing_val = $( "#ppm-exempted-" + $type ).val();

		if ( $existing_val === '' ) {
			return;
		} else {
			$existing_val = JSON.parse( $existing_val );
			var index = $existing_val.indexOf( $id );
			if ( index > -1 ) {
				$existing_val.splice( index, 1 );
			}
		}
		$( "#ppm-exempted-" + $type ).val( JSON.stringify( $existing_val ) );
	}

	$( "#ppm-exempted" ).autocomplete( {
		source: function( request, response ) {
			$.get( {
				url: ppm_ajax.ajax_url,
				dataType: 'json',
				data: {
					action: 'get_users_roles',
					search_str: request.term,
					user_role: $( '#ppm-exempted-role' ).val(),
					exclude_users: JSON.stringify( $( "#ppm-exempted-users" ).val() ),
					_wpnonce: ppm_ajax.settings_nonce
				},
				success: function( data ) {
					response( data );
				}
			} );
		},
		minLength: 2,
		select: function( event, ui ) {
			display( ui.item.value, ui.item.id );
			$( this ).val( "" );
			return false;

		}
	} );

	$( '#ppm-exempted' ).on( 'keypress', function( e ) {
		var code = ( e.keyCode ? e.keyCode : e.which );
		if ( code == 13 ) { //Enter keycode
			return false;
		}
	} );

	$( "#ppm-exempted-list" ).on( 'click', 'a.remove', function( event ) {
		event.preventDefault();
		var $list_item = $( this ).closest( 'li.ppm-exempted-list-item' );

		var $id = $list_item.data( 'id' ).toString();

		if ( $list_item.hasClass( 'ppm-exempted-users' ) ) {
			remove_exemption( $id, 'users' );
		} else {
			remove_exemption( $id, 'roles' );
		}

		$list_item.remove();

	} );

	// Inactive exempted.
	function display_inactive_exempted( value, id ) {
		var $li_item = $( "<li>" )
			.addClass( 'ppm-exempted-list-item user-btn button button-secondary' )
			.attr( 'data-id', id )
			.append( '<a href="#" class="remove remove-item"></a>' );

		$li_item.prepend( value ).prependTo( "ul#ppm-inactive-exempted-list" );

		if ( parseInt( id ) > 0 ) {
			add_inactive_exemption( $li_item, id, 'users' );
		} else {
			add_inactive_exemption( $li_item, id, 'roles' );
		}
		$( "#ppm-inactive-exempted-list" ).scrollTop( 0 );
	}

	function add_inactive_exemption( $li_item, $id, $type ) {
		var $existing_val;
		$li_item.addClass( "ppm-exempted-user" );
		$existing_val = $( "#ppm-inactive-exempted" ).val();
		if ( $existing_val === '' ) {
			$existing_val = [ ];
		} else {
			$existing_val = JSON.parse( $existing_val );
		}
		$existing_val.push( $id );
		$( "#ppm-inactive-exempted" ).val( JSON.stringify( $existing_val ) );
	}

	$( "#ppm-inactive-exempted-search" ).autocomplete( {
		source: function( request, response ) {
			$.get( {
				url: ppm_ajax.ajax_url,
				dataType: 'json',
				data: {
					action: 'get_users_roles',
					search_str: request.term,
					_wpnonce: ppm_ajax.settings_nonce
				},
				success: function( data ) {
					response( data );
				}
			} );
		},
		minLength: 2,
		select: function( event, ui ) {
			display_inactive_exempted( ui.item.value, ui.item.value );
			$( this ).val( "" );
			return false;
		}
	} );

	$( "#ppm-inactive-exempted-list" ).on( 'click', 'a.remove', function( event ) {
		event.preventDefault();
		var $list_item = $( this ).closest( 'li.ppm-exempted-list-item' );
		var $id = $list_item.text().trim().toString();
		console.log($id);
		remove_inactive_exemption( $id, 'users' );
		$list_item.remove();
	} );

	function remove_inactive_exemption( $id, $type ) {
		var $existing_val;
		$existing_val = $( "#ppm-inactive-exempted" ).val();
		if ( $existing_val === '' ) {
			return;
		} else {
			$existing_val = JSON.parse( $existing_val );
			var index = $existing_val.indexOf( $id );
			if ( index > -1 ) {
				$existing_val.splice( index, 1 );
			}
		}
		$( "#ppm-inactive-exempted" ).val( JSON.stringify( $existing_val ) );
	}

	$( '#ppm-wp-test-email' ).on( 'click', function ( event ) {
		$( this ).prop( 'disabled', true );
		$( '#ppm-wp-test-email-loading' ).css( 'visibility', 'visible' );
		$.get( {
			url: ppm_ajax.ajax_url,
			dataType: 'json',
			data: {
				action: 'ppm_wp_send_test_email',
				_wpnonce: ppm_ajax.test_email_nonce
			},
			success: function ( data ) {
				console.log( data );
				$( '.ppm-email-notice' ).remove();
				$( '#ppm-wp-test-email-loading' ).css( 'visibility', 'hidden' );
				$( "html, body" ).animate( { scrollTop: 0 } );
				if ( data.success ) {
					$( '.wrap .page-head h2' ).after( '<div class="notice notice-success ppm-email-notice"><p>' + data.data.message + '</p></div>' );
				} else {
					$( '.wrap .page-head h2' ).after( '<div class="notice notice-error ppm-email-notice"><p>' + data.data.message + '</p></div>' );
				}
				$( '#ppm-wp-test-email' ).prop( 'disabled', false );
			}
		} );
	} );

	$('#ppm_master_switch').change(function() {
		if ( $( this ).parents( 'table' ).data( 'id' ) !='' ) {
			if( $(this).is(':checked') ) {
				$('input[id!=ppm_master_switch]input[id!=ppm_enforce_password][name!=_ppm_save][name!=_ppm_reset], select, button, #ppm-excluded-special-chars','#ppm-wp-settings').attr('disabled', 'disabled');
				$('.ppm-settings').slideUp( 300 ).addClass('disabled');
				$(this).val( 1 );
				$( '#inherit_policies' ).val( 1 );
			}
			else {
				$('input[id!=ppm_master_switch]input[id!=ppm_enforce_password][name!=_ppm_save][name!=_ppm_reset], select, button, #ppm-excluded-special-chars','#ppm-wp-settings').removeAttr('disabled');
				$('.ppm-settings').slideDown( 300 ).removeClass('disabled');
				$(this).val( 0 );
				$( '#inherit_policies' ).val( 0 );
			}
		} else {
			if( $(this).is(':checked') ) {
				$('input[id!=ppm_master_switch]input[id!=ppm_enforce_password][name!=_ppm_save][name!=_ppm_reset], select, button, #ppm-excluded-special-chars','#ppm-wp-settings').removeAttr('disabled');
				$(' .nav-tab-wrapper').fadeIn( 300 ).removeClass('disabled');
				$('.ppm-settings').slideDown( 300 ).removeClass('disabled');
				$(this).val( 1 );
			}
			else {
				$('input[id!=ppm_master_switch]input[id!=ppm_enforce_password][name!=_ppm_save][name!=_ppm_reset], select, button, #ppm-excluded-special-chars','#ppm-wp-settings').attr('disabled', 'disabled');
				$('.nav-tab-wrapper').fadeOut( 300 ).addClass('disabled');
				$('.ppm-settings').slideUp( 300 ).addClass('disabled');
				$(this).val( 0 );
			}
		}
		$(this).removeAttr('disabled');
		$('#ppm-wp-settings input[type="hidden"]').removeAttr('disabled');
		// trigger change so it's disabled state is not broken by the code above.
		$( '#ppm-exclude-special' ).change();
		// trigger a change to ensure initial state of inactive users is correct.
		$( '#ppm-expiry-value' ).change();

		// Check status of failed login options.
		disable_enabled_failed_login_options();
	}).change();

	// enforce password
	$( '#ppm_enforce_password' ).change( function() {
		if ( $( this ).is( ':checked' ) ) {
			$( this ).parents( 'form' ).find( 'input, select, button' ).not('input[name=_ppm_save],input[type="hidden"], input#_ppm_reset').not( this ).attr( 'disabled', 'disabled' );
			$('.ppm-settings, .master-switch').addClass('disabled');
			$( '#inherit_policies' ).val( 0 );
		} else {
			if ( $( '#inherit_policies' ).val() == 0 ) {
				// Set value
				if ( $( '#ppm_master_switch' ).is( ':checked' ) ) {
					$( '#inherit_policies' ).val( 1 );
					$( this ).parents( 'form' ).find( 'button, #ppm_master_switch' ).removeAttr( 'disabled' );
					$('.master-switch').removeClass('disabled');
				} else {
					$( '#inherit_policies' ).val( 0 );
					$('input[id!=ppm_enforce_password][name!=_ppm_save][name!=_ppm_reset], select, button','#ppm-wp-settings').removeAttr('disabled');
					$('.ppm-settings, .master-switch').removeClass('disabled');
				}
			}
		}
	} ).change();

	// Exclude Special Characters Input.
	$( '#ppm-exclude-special' ).change(
		function() {
			if ( $( '.ppm-settings.disabled' ).length > 0 ) {
				return;
			}
			if ( $( '#ppm_master_switch' ).is( ':checked' ) && $( this ).is( ':checked' ) ) {
				$( '#ppm-excluded-special-chars' ).prop( 'disabled', false );
			} else if ( $( '#ppm_master_switch' ).is( ':checked' ) ) {
				$( '#ppm-excluded-special-chars' ).prop( 'disabled', true );
			}
		}
	).change();

	$( '#disable-self-reset' ).change(
		function() {
			if ( $( '.ppm-settings.disabled' ).length > 0 ) {
				return;
			}
			if ( $( this ).is( ':checked' ) ) {
				$( '.disabled-reset-message-wrapper' ).removeClass( 'disabled' );
			} else {
				$( '.disabled-reset-message-wrapper' ).addClass( 'disabled' );
			}
		}
	).change();

	// trigger change so it's initial state is set.
	$( '#ppm-exclude-special' ).change();

	// trigger a change to ensure initial state of inactive users is correct.
	$( '#ppm-expiry-value' ).change();

	$( 'input#_ppm_reset' ).on( 'click', function( event ) {
		// If check class exists OR not
		if ( $( '#ppm-wp-settings' ).hasClass( 'ppm_reset_all' ) ) return true;
		// Remove current user field
		$( '#ppm-wp-settings' ).find( '.current_user' ).remove();
		var Message = ppm_ajax.terminate_session_password != 1 ? ppmwpSettingsStrings.resetPasswordsDelayedMessage : ppmwpSettingsStrings.resetPasswordsInstantlyMessage;
		$( '#reset-all-dialog' ).dialog( {
				title: '',
				dialogClass: 'wp-dialog',
				autoOpen: false,
				draggable: false,
				width: 'auto',
				modal: true,
				resizable: false,
				closeOnEscape: false,
				position: {
					my: "center",
					at: "center",
					of: window
				},
				buttons: {
					Yes: function () {
						if ( $( '#ppm-wp-settings' ).hasClass( 'ppm_reset_all' ) ) {
							$( 'input#_ppm_reset' ).trigger( 'click' );
						} else {
							$( this ).dialog( "close" );
							$( '#reset-all-dialog' ).html( '<p>' + ppmwpSettingsStrings.resetOwnPasswordMessage + '</p>' );
							$( '#ppm-wp-settings' ).addClass( 'ppm_reset_all' );
							$( this ).dialog( "open" );
						}
					},
					No: function () {
						if ( $( '#ppm-wp-settings' ).hasClass( 'ppm_reset_all' ) ) {
							$( '<input type="hidden" name="current_user" value="yes" class="current_user">' ).appendTo( $( '#ppm-wp-settings' ) );
							$( 'input#_ppm_reset' ).trigger( 'click' );
						} else {
							$( this ).dialog("close");
						}
					}
				},
				open: function( event, ui ) {
					$(".ui-dialog-titlebar-close", ui.dialog | ui).hide();
				},
				create: function() {
				// style fix for WordPress admin
				$( '.ui-dialog-titlebar-close' ).addClass( 'ui-button' );
			},
		} );

		$(".ui-dialog-titlebar").hide();
		$( '#reset-all-dialog' ).html( '<p>' + Message + '</p>' );
		$( '#reset-all-dialog' ).dialog( 'open' );
		return false;
	} );

	// if there is a modal on page to display then show it now.
	// NOTE: a small delay is required to ensure DOM is fully ready.
	setTimeout(
		function() {
			var modalEl = jQuery( '#notice_modal' );
			if ( modalEl.length > 0 ) {
				if ( jQuery( modalEl ).data( 'redirect' ) ) {
					ppmwpNoticeData.redirect        = jQuery( modalEl ).data( 'redirect' );
					ppmwpNoticeData.tb_unload_count = 1;
					// bind to the close of the modal.
					jQuery( window ).bind(
						'tb_unload',
						function() {
							// because this event fires twice need to count.
							if ( ppmwpNoticeData.tb_unload_count > 1) {
								ppmwpNoticeData.tb_unload_count = 1;
							} else {
								ppmwpNoticeData.tb_unload_count = ppmwpNoticeData.tb_unload_count + 1;
								// do the redirect.
								window.location = ppmwpNoticeData.redirect;
							}
						}
					);
				}
				var title = ( jQuery( '#notice_modal' ).data( 'windowtitle' ).length )
					? jQuery( '#notice_modal' ).data( 'windowtitle' )
					: '';
				// set some details used when opening.
				var height = 155;
				var width  = 400;

				tb_show( title, '#TB_inline?height=' + height + '&width=' + width + '&inlineId=notice_modal' );
			}
		},
		200
	);

	disable_enabled_failed_login_options();
	$( '#ppm-failed-login-policies-enabled' ).change(function() {
		disable_enabled_failed_login_options();
	});

	// Handle multiple role setting.
	check_multiple_roles_status();
	$( '#ppm-users-have-multiple-roles' ).change( check_multiple_roles_status ).change();

	jQuery( "#roles_sortable" ).sortable({
		update: function(event, ui) {       
			var roles = [];
			jQuery( '#roles_sortable [data-role-key]' ).each(function () {
				roles.push( '"' + $(this).attr( 'data-role-key') + '"' );
			});
			jQuery( '#multiple-role-order' ).val( jQuery.parseJSON( '[' + roles + ']' ) );
		},
	});
	jQuery( "#roles_sortable" ).disableSelection();
} );

function check_multiple_roles_status() {
	if ( jQuery( '#ppm-users-have-multiple-roles' ).prop('checked') ) {
		jQuery( '#sortable_roles_holder' ).removeClass( 'disabled' ).slideDown( 300 );
	} else {
		jQuery( '#sortable_roles_holder' ).slideUp( 300 );
	}
}

function disable_enabled_failed_login_options() {
	jQuery( '.ppmwp-login-block-options' ).addClass( 'disabled' );
	jQuery( '.ppmwp-login-block-options :input' ).prop( 'disabled', true );

	var inheritPoliciesElm = jQuery( '#inherit_policies' );
	if ( inheritPoliciesElm.val() == 1 || inheritPoliciesElm.prop('checked') ) {
		return;
	}

	if ( jQuery( '#ppm-failed-login-policies-enabled' ).prop('checked') ) {
		jQuery( '.ppmwp-login-block-options' ).removeClass( 'disabled' );
		jQuery( '.ppmwp-login-block-options :input' ).prop( 'disabled', false );
	}
}

/**
 * Shows confirm dialog after click on checkbox with two types of messages: one for checked stated and one for unchecked state.
 *
 * @param obj 		Should be the html input tag
 * @param message_disable		Message to show if checkbox is in checked state and user trying to uncheck it
 * @param message_enable 		Message to show if checkbox is in unchecked state and user trying to check it
 * @returns {boolean}
 */
function confirm_custom_messages(obj, message_disable, message_enable){
	var message;
	if( jQuery(obj).is(':checked') ){
		message = message_enable;
	}
	else{
		message = message_disable;
	}
	return confirm(message);
}

/**
 * Allow only a set of predefined characters to be typed into the input.
 */
function accept_only_special_chars_input( event ) {
	var ch     = String.fromCharCode( event.charCode );
	var filter = new RegExp( ppm_ajax.special_chars_regex );
	if ( ! filter.test( ch ) ) {
		event.preventDefault();
	}
}

/**
 * Closes the thickbox or redirects users depending on what type of notice is
 * currently on display.
 *
 * @method ppmwp_close_thickbox
 * @since  2.1.0
 * @param  {string} redirect a url to redirect users to on clicking ok.
 */
function ppmwp_close_thickbox( redirect ) {
	if ( 'undefined' !== typeof redirect && redirect.length > 0 ) {
		window.location = redirect;
	} else {
		tb_remove();
	}
}
