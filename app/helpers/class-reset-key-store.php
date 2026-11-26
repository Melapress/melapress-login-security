<?php
/**
 * Encrypted storage for the password reset keys the plugin keeps in user meta.
 *
 * @package MLS
 * @since 2.4.4
 */

declare(strict_types=1);

namespace MLS;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\MLS\Reset_Key_Store' ) ) {

	/**
	 * Stores and reads password reset keys in user meta.
	 *
	 * WordPress keeps only a hash of a reset key, so reading the database alone
	 * cannot reset a password. The plugin needs the key itself to build reset
	 * links later (on login, or in an email), so it is stored encrypted with a
	 * key derived from the site salts. A database read (a leaked backup, a user
	 * meta export, SQL injection elsewhere) then yields ciphertext, not a key.
	 *
	 * The salts only protect the key when at least one of them comes from
	 * wp-config.php. When they all come from the database, a database read
	 * could decrypt the key, so no key is stored at all, only a marker.
	 *
	 * Values stored before 2.4.4 have no prefix and are read as they are, so
	 * reset links already sent keep working.
	 *
	 * @since 2.4.4
	 */
	class Reset_Key_Store {

		/**
		 * Marks a stored value as encrypted.
		 *
		 * @var string
		 *
		 * @since 2.4.4
		 */
		const CIPHER_PREFIX = 'mlsrk1:';

		/**
		 * Stored when a key can't be encrypted, or there is no secret outside the database.
		 *
		 * Keeps the reset pending without exposing a key. It reads back as an
		 * empty key, so a fresh one is issued when a reset link is needed.
		 *
		 * @var string
		 *
		 * @since 2.4.4
		 */
		const PENDING_MARKER = self::CIPHER_PREFIX . 'pending';

		/**
		 * Cached result of has_config_secret().
		 *
		 * @var bool|null
		 *
		 * @since 2.4.4
		 */
		private static $has_config_secret = null;

		/**
		 * Encrypts a reset key and saves it to user meta.
		 *
		 * @param int    $user_id  - User ID.
		 * @param string $meta_key - Meta key to save to.
		 * @param string $key      - Raw reset key.
		 *
		 * @return bool $stored - True when the meta was written.
		 *
		 * @since 2.4.4
		 */
		public static function store( $user_id, $meta_key, $key ): bool {
			$value = self::encrypt( (string) $key );

			/**
			 * No secret outside the database (see has_config_secret()), or neither
			 * sodium nor AES-GCM is available. Never store the key in clear text;
			 * store a marker instead, so the reset stays pending and a fresh key
			 * is issued when a link is needed.
			 */
			if ( false === $value ) {
				$value = self::PENDING_MARKER;
			}

			return false !== \update_user_meta( (int) $user_id, $meta_key, $value );
		}

		/**
		 * Reads a reset key from user meta.
		 *
		 * @param int    $user_id  - User ID.
		 * @param string $meta_key - Meta key to read.
		 *
		 * @return string $key - Raw reset key, or empty string when there is none or it can't be read.
		 *
		 * @since 2.4.4
		 */
		public static function get( $user_id, $meta_key ): string {
			$stored = \get_user_meta( (int) $user_id, $meta_key, true );

			if ( empty( $stored ) || ! is_scalar( $stored ) ) {
				return '';
			}

			return self::decrypt( (string) $stored );
		}

		/**
		 * Encrypts a reset key for storage.
		 *
		 * @param string $key - Raw reset key.
		 *
		 * @return string|false $value - Prefixed ciphertext, or false when it can't be encrypted or there is no secret outside the database.
		 *
		 * @since 2.4.4
		 */
		public static function encrypt( $key ) {
			if ( ! self::has_config_secret() ) {
				return false;
			}

			$key        = (string) $key;
			$cipher_key = self::cipher_key();

			if ( function_exists( 'sodium_crypto_secretbox' ) ) {
				$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

				return self::CIPHER_PREFIX . base64_encode( $nonce . sodium_crypto_secretbox( $key, $nonce, $cipher_key ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			}

			if ( function_exists( 'openssl_encrypt' ) && in_array( 'aes-256-gcm', openssl_get_cipher_methods(), true ) ) {
				$iv     = random_bytes( 12 );
				$tag    = '';
				$cipher = openssl_encrypt( $key, 'aes-256-gcm', $cipher_key, OPENSSL_RAW_DATA, $iv, $tag );

				if ( false !== $cipher ) {
					return self::CIPHER_PREFIX . base64_encode( $iv . $tag . $cipher ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				}
			}

			return false;
		}

		/**
		 * Decrypts a stored reset key.
		 *
		 * Anything without the prefix is a value stored before 2.4.4 and is
		 * returned as it is.
		 *
		 * @param string $stored - Stored meta value.
		 *
		 * @return string $key - Raw reset key, or empty string when it can't be decrypted.
		 *
		 * @since 2.4.4
		 */
		public static function decrypt( $stored ): string {
			$stored = (string) $stored;

			if ( '' === $stored || 0 !== strpos( $stored, self::CIPHER_PREFIX ) ) {
				return $stored;
			}

			$payload = base64_decode( substr( $stored, strlen( self::CIPHER_PREFIX ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

			if ( false === $payload ) {
				return '';
			}

			$cipher_key = self::cipher_key();

			if ( function_exists( 'sodium_crypto_secretbox_open' ) && strlen( $payload ) > SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
				$nonce  = substr( $payload, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
				$cipher = substr( $payload, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
				$plain  = sodium_crypto_secretbox_open( $cipher, $nonce, $cipher_key );

				if ( false !== $plain ) {
					return $plain;
				}
			}

			if ( function_exists( 'openssl_decrypt' ) && strlen( $payload ) > 28 ) {
				$iv     = substr( $payload, 0, 12 );
				$tag    = substr( $payload, 12, 16 );
				$cipher = substr( $payload, 28 );
				$plain  = openssl_decrypt( $cipher, 'aes-256-gcm', $cipher_key, OPENSSL_RAW_DATA, $iv, $tag );

				if ( false !== $plain ) {
					return $plain;
				}
			}

			return '';
		}

		/**
		 * Checks that the encryption key has a secret from wp-config.php.
		 *
		 * Mirrors how wp_salt( 'secure_auth' ) picks its values: a constant is
		 * used when it's defined, not empty, not the default phrase and not
		 * repeated in another salt constant; otherwise WordPress falls back to
		 * a value stored in the database. At least one of SECRET_KEY,
		 * SECURE_AUTH_KEY and SECURE_AUTH_SALT has to be used, or a database
		 * read would be enough to decrypt the stored keys.
		 *
		 * @return bool $has_secret - True when at least one salt comes from wp-config.php.
		 *
		 * @since 2.4.4
		 */
		public static function has_config_secret(): bool {
			if ( null !== self::$has_config_secret ) {
				return self::$has_config_secret;
			}

			$constants = array();

			foreach ( array( 'AUTH', 'SECURE_AUTH', 'LOGGED_IN', 'NONCE', 'SECRET' ) as $first ) {
				foreach ( array( 'KEY', 'SALT' ) as $second ) {
					$constant = $first . '_' . $second;

					if ( defined( $constant ) ) {
						$constants[ $constant ] = constant( $constant );
					}
				}
			}

			self::$has_config_secret = self::secret_in_constants( $constants );

			return self::$has_config_secret;
		}

		/**
		 * Checks a set of salt constants for a secret wp_salt( 'secure_auth' ) would use.
		 *
		 * Kept apart from has_config_secret() so the rule can be checked with any
		 * set of values, since constants can't be changed once defined.
		 *
		 * @param array $constants - Defined salt constants, name => value.
		 *
		 * @return bool $has_secret - True when SECRET_KEY, SECURE_AUTH_KEY or SECURE_AUTH_SALT would be used.
		 *
		 * @since 2.4.4
		 */
		public static function secret_in_constants( array $constants ): bool {
			$counts = array();

			foreach ( $constants as $value ) {
				$value = (string) $value;

				if ( ! isset( $counts[ $value ] ) ) {
					$counts[ $value ] = 0;
				}

				++$counts[ $value ];
			}

			$defaults = array( 'put your unique phrase here', \__( 'put your unique phrase here' ) ); // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- Same check as core's wp_salt().

			foreach ( array( 'SECRET_KEY', 'SECURE_AUTH_KEY', 'SECURE_AUTH_SALT' ) as $constant ) {
				if ( ! isset( $constants[ $constant ] ) ) {
					continue;
				}

				$value = (string) $constants[ $constant ];

				// Falsey values are ignored by wp_salt() too, e.g. '0'.
				if ( empty( $value ) || in_array( $value, $defaults, true ) || 1 < $counts[ $value ] ) {
					continue;
				}

				return true;
			}

			return false;
		}

		/**
		 * Returns the encryption key, derived from the site salts.
		 *
		 * The salts live in wp-config.php, not in the database, so a database
		 * read alone can't decrypt the stored keys.
		 *
		 * @return string $cipher_key - 32 raw bytes.
		 *
		 * @since 2.4.4
		 */
		private static function cipher_key(): string {
			return hash_hmac( 'sha256', 'mls-password-reset-key', \wp_salt( 'secure_auth' ), true );
		}
	}
}
