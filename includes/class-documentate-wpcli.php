<?php
/**
 * WP-CLI commands for the Documentate plugin.
 *
 * @package Documentate
 * @subpackage Documentate/includes
 */

/**
 * Custom WP-CLI commands for Documentate Plugin.
 */
class Documentate_WPCLI {

	/**
	 * Say hello.
	 *
	 * ## OPTIONS
	 *
	 * [--name=<name>]
	 * : The name to greet.
	 *
	 * ## EXAMPLES
	 *
	 *     wp documentate greet --name=Freddy
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function greet( $args, $assoc_args ) {
		$name = $assoc_args['name'] ?? 'World';
		$this->cli_success( "Hello, $name!" );
	}

	/**
	 * Create sample data for Documentate Plugin.
	 *
	 * This command creates 10 labels, 5 boards and 10 tasks per board.
	 *
	 * ## EXAMPLES
	 *
	 *     wp documentate create_sample_data
	 */
	public function create_sample_data() {
		// Check if we're running on a development version.
		if ( defined( 'DOCUMENTATE_VERSION' ) && DOCUMENTATE_VERSION !== '0.0.0' ) {
			/* translators: %s: plugin version number. */
			$this->cli_warning( sprintf( __( 'You are adding sample data to a non-development version of Documentate (v%s)', 'documentate' ), DOCUMENTATE_VERSION ) );
			$this->cli_confirm( __( 'Do you want to continue?', 'documentate' ) );
		}

		$this->cli_log( __( 'Starting sample data creation...', 'documentate' ) );

		$demo_data = new Documentate_Demo_Data();
		$demo_data->create_sample_data();

		$this->cli_success( __( 'Sample data created successfully!', 'documentate' ) );
	}

	/**
	 * Output a success message. Can be overridden in tests.
	 *
	 * @param string $message Message to display.
	 */
	protected function cli_success( $message ) {
		if ( class_exists( 'WP_CLI' ) ) {
			WP_CLI::success( $message );
		}
	}

	/**
	 * Output a warning message. Can be overridden in tests.
	 *
	 * @param string $message Message to display.
	 */
	protected function cli_warning( $message ) {
		if ( class_exists( 'WP_CLI' ) ) {
			WP_CLI::warning( $message );
		}
	}

	/**
	 * Output a log message. Can be overridden in tests.
	 *
	 * @param string $message Message to display.
	 */
	protected function cli_log( $message ) {
		if ( class_exists( 'WP_CLI' ) ) {
			WP_CLI::log( $message );
		}
	}

	/**
	 * Ask for confirmation. Can be overridden in tests.
	 *
	 * @param string $message Message to display.
	 */
	protected function cli_confirm( $message ) {
		if ( class_exists( 'WP_CLI' ) ) {
			WP_CLI::confirm( $message );
		}
	}
}

// Register the main command only when WP_CLI is available.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'documentate', 'Documentate_WPCLI' );
}
