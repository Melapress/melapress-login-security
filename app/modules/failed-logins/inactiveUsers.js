/**
 * Locked/Inactive Users management - vanilla JS.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		initUnlockButtons();
		initRefreshLockStatus();
	} );

	/**
	 * Initializes click handlers for individual unlock buttons.
	 */
	function initUnlockButtons() {
		document.querySelectorAll( '.unlock-inactive-user-button' ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				var userId = this.value;
				var isBlockedUser = this.getAttribute( 'data-is-blocked-user' );

				this.textContent = inactiveUsersStrings.resettingUser;
				this.disabled = true;

				unlockSingleUser( userId, isBlockedUser, this );
			} );
		} );
	}

	/**
	 * Initializes the "Refresh users lock status" button.
	 */
	function initRefreshLockStatus() {
		var refreshButton = document.getElementById( 'mls_refresh_lock_status' );
		if ( ! refreshButton ) {
			return;
		}

		refreshButton.addEventListener( 'click', function () {
			this.disabled = true;
			this.textContent = inactiveUsersStrings.refreshing;
			refreshLockStatus( this );
		} );
	}

	/**
	 * Sends AJAX request to unlock a single user.
	 *
	 * @param {string}      userId        The user ID to unlock.
	 * @param {string}      isBlockedUser Whether the user is blocked ('true'/'false').
	 * @param {HTMLElement} button        The button element that was clicked.
	 */
	function unlockSingleUser( userId, isBlockedUser, button ) {
		var formData = new FormData();
		formData.append( 'action', 'mls_unlock_inactive_user' );
		formData.append( 'user', userId );
		formData.append( 'unblocking_user', isBlockedUser );
		formData.append( '_wpnonce', inactiveUsersStrings.nonce );

		fetch( ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( data ) {
				if ( data.success && data.data.reset_time ) {
					button.textContent = inactiveUsersStrings.resetDone;
					button.disabled = true;

					var row = button.closest( 'tr' );
					if ( row ) {
						row.style.transition = 'opacity 0.3s ease';
						row.style.opacity = '0';
						setTimeout( function () {
							row.remove();
							checkEmptyTable();
						}, 300 );
					}
				}
			} )
			.catch( function () {
				button.textContent = inactiveUsersStrings.resetDone;
				button.disabled = false;
			} );
	}

	/**
	 * Checks if the table is empty and shows a "no users" message.
	 */
	function checkEmptyTable() {
		var tableBody = document.getElementById( 'the-list' );
		if ( ! tableBody ) {
			return;
		}

		var remainingRows = tableBody.querySelectorAll( 'tr:not(.no-items)' );
		if ( remainingRows.length === 0 ) {
			var emptyRow = document.createElement( 'tr' );
			emptyRow.className = 'no-items';

			var emptyCell = document.createElement( 'td' );
			emptyCell.className = 'colspanchange';
			emptyCell.setAttribute( 'colspan', '7' );
			emptyCell.textContent = inactiveUsersStrings.noUsers;

			emptyRow.appendChild( emptyCell );
			tableBody.appendChild( emptyRow );
		}
	}

	/**
	 * Sends AJAX request to refresh the lock status of listed accounts.
	 *
	 * @param {HTMLElement} button The button element that was clicked.
	 */
	function refreshLockStatus( button ) {
		var formData = new FormData();
		formData.append( 'action', 'mls_refresh_lock_status' );
		formData.append( '_wpnonce', button.getAttribute( 'data-nonce' ) );

		fetch( ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( data ) {
				if ( data.success && data.data.dispatched ) {
					button.textContent = inactiveUsersStrings.buttonReloading;
					// Reload so the list reflects what the refresh released.
					window.location.reload();
				} else {
					button.disabled = false;
				}
			} )
			.catch( function () {
				button.disabled = false;
			} );
	}
} )();
