<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName

namespace PPMWP;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extension loader class to handle registration of premium extensions.
 *
 * @package PPMWP
 * @since 1.0.0
 * @author Martin Krcho <martin@wpwhitesecurity.com>
 */
class ExtensionLoader {

	/**
	 * Array of loaded extensions.
	 *
	 * @var array
	 */
	private $extensions = array();

	/**
	 * Callback for license check/
	 *
	 * @var callable
	 */
	private $license_check_callback;

	/**
	 * List of feature aliases used in the config file.
	 *
	 * @var arrau
	 */
	private $feature_aliases;

	/**
	 * Path to the config file
	 *
	 * @var string
	 *
	 * @since 7.0.0
	 */
	private static $config_file_path = PPM_WP_PATH . 'app' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'config.php';

	/**
	 * String with the checksum
	 *
	 * @var string
	 *
	 * @since 7.0.0
	 */
	private static $checksum = null;

	/**
	 * ExtensionLoader constructor.
	 *
	 * @param callable $license_check_callback Callback function for extension license check.
	 * @param array    $feature_aliases - name of feature.
	 */
	public function __construct( callable $license_check_callback, $feature_aliases ) {
		$this->license_check_callback = $license_check_callback;
		$this->feature_aliases        = $feature_aliases;
	}

	/**
	 * Registers an extension with the extension loader.
	 *
	 * @param string $slug Extension slug.
	 * @param array  $plans List of Freemius plans the extension is allowed for.
	 */
	public function register_extension( string $slug, array $plans ) {
		if ( array_key_exists( $slug, $this->extensions ) ) {
			return;
		}

		$this->extensions[ $slug ] = $plans;
	}

	/**
	 * Bootstraps all extensions by loading them one by one.
	 */
	public function bootstrap() {
		if ( file_exists( self::$config_file_path ) ) {
			$extensions = require_once self::$config_file_path;
			if ( is_array( $extensions ) && ! empty( $extensions ) ) {
				foreach ( $extensions as $alias => $plans ) {
					if ( array_key_exists( $alias, $this->feature_aliases ) ) {
						$slug = $this->feature_aliases[ $alias ];
						if ( $this->load_extension( $slug, $plans ) ) {
							$this->register_extension( $slug, $plans );
						}
					}
				}
			}
		}
	}

	/**
	 * Extracts the checksum for the config file
	 *
	 * @return string|bool
	 *
	 * @since 7.0.0
	 */
	public static function stored_cache_checksum() {
		if ( null === self::$checksum ) {
			self::$checksum = get_transient( 'mls_config_file_hash' );

			if ( false === self::$checksum ) {
				if ( ! file_exists( self::$config_file_path ) ) {
					self::$checksum = false;
				} else {
					self::$checksum = md5(
						str_replace(
							array( "\r\n", "\n", "\r" ),
							'',
							file_get_contents( self::$config_file_path )
						)
					);
				}
			}

			set_transient( 'mls_config_file_hash', self::$checksum, WEEK_IN_SECONDS );
		}
		return self::$checksum;
	}

	/**
	 * Loads single extension only if the extension main file is available and the extension is allowed for user's
	 * Freemius license.
	 *
	 * @param string $slug Extension slug.
	 * @param array  $plans List of Freemius plans the extension is allowed for.
	 *
	 * @return bool True is extension was loaded. Otherwise false.
	 */
	private function load_extension( $slug, $plans ): bool {

		// check if the file exists first.
		$possible_paths = array(
			'third-party'    => PPM_WP_PATH . 'app' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'third-party' . DIRECTORY_SEPARATOR . 'third-parties-boot.php',
			'email-settings' => PPM_WP_PATH . 'app' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'email-settings' . DIRECTORY_SEPARATOR . 'email-settings-boot.php',
			'inactive-users' => PPM_WP_PATH . 'app' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'inactive-users' . DIRECTORY_SEPARATOR . 'inactive-users-boot.php',
			'failed-logins'  => PPM_WP_PATH . 'app' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'failed-logins' . DIRECTORY_SEPARATOR . 'failed-logins-boot.php',
		);
		
		$main_extension_file_path = isset( $possible_paths[ $slug ] ) ? $possible_paths[ $slug ] : false;

		if ( $main_extension_file_path && file_exists( $main_extension_file_path ) ) {
			// check the licensing.
			if ( is_callable( $this->license_check_callback ) ) {
				if ( call_user_func( $this->license_check_callback, $slug, $plans ) ) {
					require_once $main_extension_file_path;
					return true;
				}
			}
		}

		return false;
	}
}
