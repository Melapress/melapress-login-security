/**
 * Known Devices management on user profile pages.
 *
 * @package MelapressLoginSecurity
 * @since 2.5.0
 */
jQuery( document ).ready( function( $ ) {

	// Rename device button handler.
	$( '#mls-known-devices-table' ).on( 'click', '.mls-rename-device', function( e ) {
		e.preventDefault();

		var $button     = $( this );
		var $row        = $button.closest( '.mls-known-device-row' );
		var $nameSpan   = $row.find( '.mls-device-name' );
		var currentName = $nameSpan.text().trim();

		// If already in rename mode, bail.
		if ( $row.find( '.mls-rename-input' ).length ) {
			return;
		}

		// Replace name with input.
		var $input = $( '<input type="text" class="mls-rename-input regular-text" />' ).val( currentName );
		var $save  = $( '<button type="button" class="button button-primary mls-rename-save">' + mlsKnownDevices.saveLabel + '</button>' );
		var $cancel = $( '<button type="button" class="button button-secondary mls-rename-cancel">' + mlsKnownDevices.cancelLabel + '</button>' );

		$nameSpan.hide().after( $input, ' ', $save, ' ', $cancel );
		$input.trigger( 'focus' );

		// Save handler.
		$save.on( 'click', function() {
			var newLabel = $input.val().trim();
			if ( ! newLabel ) {
				return;
			}

			$save.prop( 'disabled', true );
			$.post( mlsKnownDevices.ajaxUrl, {
				action:       'mls_rename_known_device',
				nonce:        mlsKnownDevices.nonce,
				user_id:      $button.data( 'user-id' ),
				device_index: $button.data( 'device-index' ),
				new_label:    newLabel
			}, function( response ) {
				if ( response.success ) {
					$nameSpan.text( response.data.new_label ).show();
					$input.remove();
					$save.remove();
					$cancel.remove();
				} else {
					alert( response.data.message );
					$save.prop( 'disabled', false );
				}
			} ).fail( function() {
				$save.prop( 'disabled', false );
			} );
		} );

		// Cancel handler.
		$cancel.on( 'click', function() {
			$nameSpan.show();
			$input.remove();
			$save.remove();
			$cancel.remove();
		} );

		// Enter key triggers save.
		$input.on( 'keydown', function( ev ) {
			if ( ev.which === 13 ) {
				ev.preventDefault();
				$save.trigger( 'click' );
			}
			if ( ev.which === 27 ) {
				$cancel.trigger( 'click' );
			}
		} );
	} );

	// Delete device button handler.
	$( '#mls-known-devices-table' ).on( 'click', '.mls-delete-device', function( e ) {
		e.preventDefault();

		var $button = $( this );

		if ( ! confirm( mlsKnownDevices.deleteConfirm ) ) {
			return;
		}

		$button.prop( 'disabled', true );
		$.post( mlsKnownDevices.ajaxUrl, {
			action:       'mls_delete_known_device',
			nonce:        mlsKnownDevices.nonce,
			user_id:      $button.data( 'user-id' ),
			device_index: $button.data( 'device-index' )
		}, function( response ) {
			if ( response.success ) {
				$button.closest( '.mls-known-device-row' ).fadeOut( 300, function() {
					$( this ).remove();
					// Re-index remaining rows.
					$( '#mls-known-devices-table .mls-known-device-row' ).each( function( i ) {
						$( this ).attr( 'data-device-index', i );
						$( this ).find( '.mls-rename-device, .mls-delete-device' ).attr( 'data-device-index', i ).data( 'device-index', i );
					} );
					// Show empty message if no devices left.
					if ( ! $( '#mls-known-devices-table .mls-known-device-row' ).length ) {
						$( '#mls-known-devices-table tbody' ).html(
							'<tr class="mls-no-devices-row"><td><p>' + mlsKnownDevices.noDevices + '</p></td></tr>'
						);
					}
				} );
			} else {
				alert( response.data.message );
				$button.prop( 'disabled', false );
			}
		} ).fail( function() {
			$button.prop( 'disabled', false );
		} );
	} );

} );
