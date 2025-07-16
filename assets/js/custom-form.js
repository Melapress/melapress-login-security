/* global ajaxurl, pws_l10n, user_profile_l10n */
( function( $ ) {
	var $pass1,
	inputEvent;

	/*
	 * Use feature detection to determine whether password inputs should use
	 * the `keyup` or `input` event. Input is preferred but lacks support
	 * in legacy browsers.
	 */
	if ( 'oninput' in document.createElement( 'input' ) ) {
		inputEvent = 'input';
	} else {
		inputEvent = 'keyup';
	}

	// Memebrpress reg.
	if ( jQuery( '.mls_pw_errors' ).length ) {
		setTimeout(() => {
			var errorString = jQuery( '.mls_pw_errors' ).attr( 'data-error-keys' );
			var errorArray = errorString.split(',');
			jQuery( '.mls_pw_errors' ).html( '' )
			jQuery.each( errorArray, function ( index, value ) {
				var errText = jQuery( '.pass-strength-result .' + jQuery.trim( value ) ).text();
				if ( 'undefined' !== typeof errText ) {
					jQuery( '.mls_pw_errors' ).append( errText + '<br>' );					
				}
			});		
		}, 50);
	}

	if ( jQuery( '.mp-hide-pw' ).length ) {
		jQuery( '.mp-hide-pw' ).css({'height': 'auto'});
	}

	if ( jQuery( '#bbp-user-body .pw-weak' ).length ) {
		jQuery( '#bbp-user-body .pw-weak' ).remove();
	}

	// PMP.
	if ( jQuery( '.pmpro-login .pmpro_error' ).length ) {
		setTimeout(() => {
			if ( window.location.href.indexOf( 'error=password_reset_empty&') > -1) {
				var isIt = window.location.href.split("error=password_reset_empty&");
				var isItReally = isIt[1].split('&')[0];
				var errorArray = isItReally.split(',');
				jQuery( '.pmpro-login .pmpro_error' ).html( '' );
				jQuery.each( errorArray, function ( index, value ) {
					var errText = jQuery( '.pass-strength-result .' + jQuery.trim( value ) ).text();
					if ( 'undefined' !== typeof errText ) {
						jQuery( '.pmpro-login .pmpro_error' ).append( errText + '<br>' );					
					}
				});	
			}
		}, 50);
	}	

	if ( jQuery( '.profilepress-myaccount-change-password' ).length ) {
		setTimeout(() => {
			if ( window.location.href.indexOf( 'error=mls_error&') > -1 ) {
				var isIt = window.location.href.split("error=mls_error&");
				var isItReally = isIt[1].split('&')[0];
				var errorArray = isItReally.split(',');
				jQuery( '.profilepress-myaccount-change-password' ).prepend( '<div class="profilepress-myaccount-alert pp-alert-danger" role="alert"></div>' );
				jQuery.each( errorArray, function ( index, value ) {
					var errText = jQuery( '.pass-strength-result .' + jQuery.trim( value ) ).text();
					if ( 'undefined' !== typeof errText ) {
						jQuery( '.profilepress-myaccount-alert' ).append( errText + '<br>' );					
					}
				});	
			}
		}, 50);
	}

	jQuery( window ).on( 'load', function() {
		if ( PPM_Custom_Form.element.length > 0 ) {
			jQuery( PPM_Custom_Form.policy ).insertAfter( PPM_Custom_Form.element );
			jQuery( PPM_Custom_Form.element ).val( '' ).on( inputEvent + ' pwupdate', function (e) {
				check_pass_strength( PPM_Custom_Form );
			} );
			jQuery( '.pass-strength-result' ).show();
	
			// Hide any elements by the classes/IDs supplied.
			var elementsToHide = PPM_Custom_Form.form_selector + ' ' + PPM_Custom_Form.elements_to_hide;
	
			if ( elementsToHide !== '' ) {
				jQuery( elementsToHide ).css( 'display', 'none' );
			}
		}	

		var basePpmForm = PPM_Custom_Form;
		
		if ( PPM_Custom_Form.custom_forms_arr.length > 0 ) {
			jQuery.each( PPM_Custom_Form.custom_forms_arr, function (e, customForm ) {
				setup_custom_forms_arr( customForm, basePpmForm );
			});
		}
	} );

	jQuery( document ).ajaxStop( function() {
		if ( PPM_Custom_Form.element.length > 0 ) {
			jQuery( PPM_Custom_Form.policy ).insertAfter( PPM_Custom_Form.element );
			jQuery( PPM_Custom_Form.element ).val( '' ).on( inputEvent + ' pwupdate', function (e) {
				check_pass_strength( PPM_Custom_Form );
			} );
			jQuery( '.pass-strength-result' ).show();
	
			// Hide any elements by the classes/IDs supplied.
			var elementsToHide = PPM_Custom_Form.form_selector + ' ' + PPM_Custom_Form.elements_to_hide;
	
			if ( elementsToHide !== '' ) {
				jQuery( elementsToHide ).css( 'display', 'none' );
			}
		}	

		var basePpmForm = PPM_Custom_Form;
		
		if ( PPM_Custom_Form.custom_forms_arr.length > 0 ) {
			jQuery.each( PPM_Custom_Form.custom_forms_arr, function (e, customForm ) {
				setup_custom_forms_arr( customForm, basePpmForm );
			});
		}
	} );
} )( jQuery );

function setup_custom_forms_arr( customForm, PPM_Custom_Form ) {

	if ( jQuery( 'div.pass-strength-result' ).length ) {
		// We are there - bounce.
		return;
	}

	var policy = PPM_Custom_Form.policy;
	var PPM_Custom_Form = customForm;
	PPM_Custom_Form.element = customForm.form_selector + ' ' + customForm.pw_field_selector;
	PPM_Custom_Form.button_class = customForm.form_selector  + ' ' + customForm.form_submit_selector;
	var elementsToHide = customForm.form_selector + ' ' + customForm.elements_to_hide;

	if ( 'oninput' in document.createElement( 'input' ) ) {
		inputEvent = 'input';
	} else {
		inputEvent = 'keyup';
	}

	if ( jQuery( PPM_Custom_Form.element ).hasClass( 'pmpro_form_input-password' ) ) {
		jQuery( policy ).insertAfter( jQuery( PPM_Custom_Form.element ).parent().parent() );
	} else {
		if( customForm.hasOwnProperty( 'after_element' ) ) {
			jQuery( policy ).insertAfter( jQuery( customForm.after_element ) );
		} else {
			if ( jQuery( PPM_Custom_Form.element ).length ) {
				jQuery( policy ).insertAfter( PPM_Custom_Form.element );
			}
		}
		if( customForm.hasOwnProperty( 'elements_to_enable' ) ) {
			jQuery( document ).ready( function() {
				jQuery( customForm.elements_to_enable ).prop("disabled", false);
			});
		}

		if ( customForm.hasOwnProperty( 'keyup_events_to_remove' ) ) {
			jQuery('body').off('keyup', customForm.keyup_events_to_remove );
		}

		if ( customForm.hasOwnProperty( 'type_custom_form' ) ) {
			if ( 'learndash' === customForm.type_custom_form ) {
				jQuery( document.body )
				.on(
					'keyup change',
					jQuery( customForm.pw_field_selector ),
					function () { jQuery( customForm.elements_to_enable ).prop("disabled", false); }
				);
			}
			if ( 'buddypress-register' === customForm.type_custom_form ) {
				jQuery( document.body )
				.on(
					'keyup change',
					jQuery( customForm.pw_field_selector ),
					function () { 
						jQuery( customForm.elements_to_enable ).prop("disabled", false); 
						jQuery( customForm.elements_to_hide ).prop("disabled", true);
						jQuery( customForm.elements_to_hide ).css( 'display', 'none' );
					}
				);
			}
			if ( 'buddypress-update-form' === customForm.type_custom_form ) {
				jQuery( document.body )
				.on(
					'keyup change',
					jQuery( customForm.pw_field_selector ),
					function () { 
						jQuery( customForm.elements_to_enable ).prop("disabled", false);
						jQuery( customForm.elements_to_hide ).prop("disabled", true);
						jQuery( elementsToHide ).css( 'display', 'none' );
					 }
				);
			}
			if ( 'wc-reset-form' === customForm.type_custom_form ) {
				jQuery( document.body )
				.on(
					'keyup change',
					jQuery( customForm.pw_field_selector ),
					function () { jQuery( customForm.elements_to_enable ).prop("disabled", false); }
				);
			}
		}
	}	

	jQuery( PPM_Custom_Form.element ).val( '' ).on( inputEvent + ' pwupdate', function (e) {
		if ( customForm.hasOwnProperty( 'type_custom_form' ) ) {
			if ( 'learndash' === customForm.type_custom_form ) {
				e.stopPropagation();
				e.stopImmediatePropagation();
			}
		}
		check_pass_strength( PPM_Custom_Form, true );
	} );

	jQuery( '.pass-strength-result' ).show();

	if ( elementsToHide !== '' ) {
		jQuery( elementsToHide ).css( 'display', 'none' );
		// Backup, as some forms may re-add hints etc via JS.
		jQuery('head').append('<style type="text/css">'+ elementsToHide +' { display: none !important; visibility: hidden !important; }</style>');
	}
}

function check_pass_strength( form, is_known_single = false ) {
	// Empty vars we will fill later.
	var strength;
	var pass1;

	if ( typeof form.form_selector !== 'undefined' && form.form_selector.length ) {
		var selectPrefix = form.form_selector + ' ';
	} else {
		var selectPrefix = '';
	}

	jQuery( selectPrefix + '.pass-strength-result' ).removeClass( 'short bad good strong' );

	// Try to separate the list of items.
	var possibleInputsToCheck = form.element.split(',');

	if ( ! is_known_single ) {
		possibleInputsToCheck = jQuery.map(possibleInputsToCheck, function(){
			return possibleInputsToCheck.toString().replace(/ /g, '');
		});
	}
	// Not possible to split, so treat as if only 1 class/id is provided.
	if ( ! possibleInputsToCheck ) {
		// pass1 is a single class/id.
		pass1 = jQuery( form.element ).val();
	} else {
		// pass1 is an array of classes/ids to check.
		jQuery.each( possibleInputsToCheck, function( index, input ) {
			// If we have something, lets pass it to pass1.
			pass1 = jQuery( input ).val();
		});
	}

	// By this point, we should have a value (password) to check.
	if ( !pass1 ) {			
		jQuery( selectPrefix + '.pass-strength-result' ).html( form.policy );
		jQuery( selectPrefix + "input[type*='submit'], " + selectPrefix + "button" ).prop( "disabled", false ).removeClass( 'button-disabled' );
		return;
	}

	strength = wp.passwordStrength.policyCheck( pass1, wp.passwordStrength.userInputDisallowedList(), pass1 );

	var errors = '';
	var err_pfx = '';
	var err_sfx = '';
	var ErrorData = [];

	if ( !jQuery.isEmptyObject( wp.passwordStrength.policyFails ) ) {
		err_pfx = "<ul>";
		err_sfx = "</ul>";
	}
	jQuery.each( wp.passwordStrength.policyFails, function( $namespace, $value ) {
		errors = errors + '<li>' + ppmJSErrors[$namespace] + '</li>';
		ErrorData.push( $value );
	} );
	errors = err_pfx + errors + err_sfx;

	if ( ErrorData.length == 0 ) {
		jQuery( selectPrefix +'.pass-strength-result li' ).css('color', '#21760c');
	} else {
		jQuery.each( ErrorData, function( i, val ) {
			if ( jQuery( selectPrefix +'.pass-strength-result li' ).hasClass( val ) ) {
				jQuery( selectPrefix +'.pass-strength-result li.' + val ).css('color', '#F00');
			} else {
				jQuery( selectPrefix +'.pass-strength-result li' ).css('color', '#21760c');
			}
		} );
	}
	if ( ErrorData.length <= 1 ) {
		jQuery( selectPrefix + "input[type*='submit'], " + selectPrefix + "button" ).not( '.mp-hide-pw' ).not( '.gform_show_password' ).prop( "disabled", false ).removeClass( 'button-disabled' );
		if ( 'learndash' === form.type_custom_form ) {
			jQuery( selectPrefix +  "#wp-submit-register").css({'pointer-events': 'auto', 'cursor': 'auto'});
			jQuery( selectPrefix +  "#wp-submit-register").prop( "disabled", false )
		}
		jQuery( selectPrefix + form.button_class ).prop( "disabled", false ).removeClass( 'button-disabled' );
	} else {
		jQuery( selectPrefix + "input[type*='submit'], " + selectPrefix + "button" ).not( '.mp-hide-pw' ).not( '.gform_show_password' ).prop( "disabled", true ).addClass( 'button-disabled' );
		if ( 'learndash' === form.type_custom_form ) {
			jQuery( selectPrefix +  "#wp-submit-register").css({'pointer-events': 'none', 'cursor': 'not-allowed'});
		}
		jQuery( selectPrefix + form.button_class ).prop( "disabled", true ).addClass( 'button-disabled' );
	}
}

function get_ppm_custom_form_base() {
	return PPM_Custom_Form;
}

// Memberpress reg fix.
jQuery( document ).on( 'click', '.button.mp-hide-pw', function ( event ) {
	jQuery( '#mepr_user_password1' ).attr( 'type', jQuery( '.pass-strength-result' ).attr( 'type' ) );
} );

// EDD reg fix.
if ( jQuery( '.edd-blocks__checkout-register' ).length ) {
	jQuery( document ).on( 'click', '.edd-blocks__checkout-register', function ( event ) {
		setTimeout(() => {
			if ( PPM_Custom_Form.custom_forms_arr.length > 0 ) {
				var basePpmForm = PPM_Custom_Form;
				jQuery.each( PPM_Custom_Form.custom_forms_arr, function (e, customForm ) {
					setup_custom_forms_arr( customForm, basePpmForm );
				});
			}
		}, 500);
	});
}