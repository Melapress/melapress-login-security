/* global ajaxurl, pwsL10n, userProfileL10n */
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

	function check_pass_strength() {

		// Empty vars we will fill later.
		var strength;
		var pass1;
		$( '.pass-strength-result' ).removeClass( 'short bad good strong' );

		// Try to seperate the list of items.
		var possibleInputsToCheck = PPM_Custom_Form.element.split(',');
		possibleInputsToCheck = $.map(possibleInputsToCheck, function(){
		  return possibleInputsToCheck.toString().replace(/ /g, '');
		});
		// Not possible to split, so treat as if only 1 class/id is provided.
		if ( ! possibleInputsToCheck ) {
			// pass1 is a single class/id.
		    pass1 = $( PPM_Custom_Form.element ).val();
		} else {
			// pass1 is an array of classes/ids to check.
			$.each( possibleInputsToCheck, function( index, input ) {
				// If we have something, lets pass it to pass1.
				pass1 = $( input ).val();
			});
		}

		// By this point, we should have a value (password) to check.
		if ( !pass1 ) {
			$( '.pass-strength-result' ).html( PPM_Custom_Form.policy );
			$( "input[type*='submit'], button" ).prop( "disabled", false ).removeClass( 'button-disabled' );
			return;
		}

		strength = wp.passwordStrength.policyCheck( pass1, wp.passwordStrength.userInputBlacklist(), pass1 );

		var errors = '';
		var err_pfx = '';
		var err_sfx = '';
		var ErrorData = [];

		if ( !$.isEmptyObject( wp.passwordStrength.policyFails ) ) {
			err_pfx = "<ul>";
			err_sfx = "</ul>";
		}
		$.each( wp.passwordStrength.policyFails, function( $namespace, $value ) {
				errors = errors + '<li>' + ppmJSErrors[$namespace] + '</li>';
			ErrorData.push( $value );
		} );
		errors = err_pfx + errors + err_sfx;
		if ( ErrorData.length == 0 ) {
			$( '.pass-strength-result li' ).css('color', '#21760c');
		} else {
			$.each( ErrorData, function( i, val ) {
				if ( $( '.pass-strength-result li' ).hasClass( val ) ) {
					$( '.pass-strength-result li.' + val ).css('color', '#F00');
				} else {
					$( '.pass-strength-result li' ).css('color', '#21760c');
				}
			} );
		}
		if ( ErrorData.length <= 1 ) {
			$( "input[type*='submit'], button" ).prop( "disabled", false ).removeClass( 'button-disabled' );
			$( PPM_Custom_Form.button_class ).prop( "disabled", false ).removeClass( 'button-disabled' );
		} else {
			$( "input[type*='submit'], button" ).prop( "disabled", true ).addClass( 'button-disabled' );
			$( PPM_Custom_Form.button_class ).prop( "disabled", true ).addClass( 'button-disabled' );
		}
	}
	$( document ).ready( function() {
		$( PPM_Custom_Form.policy ).insertAfter( PPM_Custom_Form.element );
		$( PPM_Custom_Form.element ).val( '' ).on( inputEvent + ' pwupdate', check_pass_strength );
		$( '.pass-strength-result' ).show();

		// Hide any elements by the classes/IDs supplied.
		var elementsToHide = PPM_Custom_Form.elements_to_hide;

		if ( elementsToHide !== '' ) {
			jQuery( elementsToHide ).css( 'display', 'none' );
		}
	} );
} )( jQuery );
