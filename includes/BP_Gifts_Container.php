<?php
/**
 * Simple Dependency Injection Container
 *
 * Provides basic dependency injection and service management.
 *
 * @package BP_Gifts
 * @since   2.1.0
 * @author  SuitePlugins
 * @license GPL-2.0-or-later
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BP_Gifts_Container
 *
 * Simple dependency injection container for managing services.
 *
 * @since 2.1.0
 */
class BP_Gifts_Container {

	/**
	 * Container instance.
	 *
	 * @since 2.1.0
	 * @var   BP_Gifts_Container|null
	 */
	private static $instance = null;

	/**
	 * Registered services.
	 *
	 * @since 2.1.0
	 * @var   array
	 */
	private $services = array();

	/**
	 * Service instances.
	 *
	 * @since 2.1.0
	 * @var   array
	 */
	private $instances = array();

	/**
	 * Get container instance.
	 *
	 * @since 2.1.0
	 * @return BP_Gifts_Container
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register a service.
	 *
	 * @since 2.1.0
	 * @param string   $name    Service name.
	 * @param callable $factory Service factory function.
	 * @param bool     $shared  Whether to share instances (singleton).
	 * @return void
	 */
	public function register( string $name, callable $factory, bool $shared = true ) {
		$this->services[ $name ] = array(
			'factory' => $factory,
			'shared'  => $shared,
		);
	}

	/**
	 * Get a service instance.
	 *
	 * @since 2.1.0
	 * @param string $name Service name.
	 * @return mixed Service instance.
	 * @throws InvalidArgumentException If service not found.
	 */
	public function get( string $name ) {
		if ( ! isset( $this->services[ $name ] ) ) {
			throw new InvalidArgumentException( 
				sprintf( 
					/* translators: %s: Service name */
					esc_html__( 'Service "%s" not found in container.', 'bp-gifts' ), 
					$name 
				) 
			);
		}

		$service = $this->services[ $name ];

		// Return cached instance if shared
		if ( $service['shared'] && isset( $this->instances[ $name ] ) ) {
			return $this->instances[ $name ];
		}

		// Create new instance
		$instance = call_user_func( $service['factory'], $this );

		// Cache if shared
		if ( $service['shared'] ) {
			$this->instances[ $name ] = $instance;
		}

		return $instance;
	}

	/**
	 * Check if service is registered.
	 *
	 * @since 2.1.0
	 * @param string $name Service name.
	 * @return bool True if registered, false otherwise.
	 */
	public function has( string $name ) {
		return isset( $this->services[ $name ] );
	}

	/**
	 * Remove a service.
	 *
	 * @since 2.1.0
	 * @param string $name Service name.
	 * @return void
	 */
	public function remove( string $name ) {
		unset( $this->services[ $name ], $this->instances[ $name ] );
	}

	/**
	 * Get all registered service names.
	 *
	 * @since 2.1.0
	 * @return array Service names.
	 */
	public function get_service_names() {
		return array_keys( $this->services );
	}
}