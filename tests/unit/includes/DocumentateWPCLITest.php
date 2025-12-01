<?php
/**
 * Tests for Documentate_WPCLI class.
 *
 * @package Documentate
 */

// Load the demo data class if not already loaded.
if ( ! class_exists( 'Documentate_Demo_Data' ) ) {
	require_once plugin_dir_path( DOCUMENTATE_PLUGIN_FILE ) . 'includes/class-documentate-demo-data.php';
}

// Load the WPCLI class if not already loaded.
if ( ! class_exists( 'Documentate_WPCLI' ) ) {
	require_once plugin_dir_path( DOCUMENTATE_PLUGIN_FILE ) . 'includes/class-documentate-wpcli.php';
}

/**
 * Testable subclass that captures CLI messages.
 */
class Documentate_WPCLI_Testable extends Documentate_WPCLI {

	/**
	 * Messages captured during tests.
	 *
	 * @var array
	 */
	public $messages = array();

	/**
	 * Output a success message.
	 *
	 * @param string $message Message to display.
	 */
	protected function cli_success( $message ) {
		$this->messages[] = array(
			'type'    => 'success',
			'message' => $message,
		);
	}

	/**
	 * Output a warning message.
	 *
	 * @param string $message Message to display.
	 */
	protected function cli_warning( $message ) {
		$this->messages[] = array(
			'type'    => 'warning',
			'message' => $message,
		);
	}

	/**
	 * Output a log message.
	 *
	 * @param string $message Message to display.
	 */
	protected function cli_log( $message ) {
		$this->messages[] = array(
			'type'    => 'log',
			'message' => $message,
		);
	}

	/**
	 * Ask for confirmation.
	 *
	 * @param string $message Message to display.
	 */
	protected function cli_confirm( $message ) {
		$this->messages[] = array(
			'type'    => 'confirm',
			'message' => $message,
		);
	}

	/**
	 * Reset messages for testing.
	 */
	public function reset() {
		$this->messages = array();
	}
}

/**
 * @covers Documentate_WPCLI
 */
class DocumentateWPCLITest extends WP_UnitTestCase {

	/**
	 * WPCLI instance.
	 *
	 * @var Documentate_WPCLI_Testable
	 */
	private $wpcli;

	/**
	 * Set up test fixtures.
	 */
	public function set_up() {
		parent::set_up();
		$this->wpcli = new Documentate_WPCLI_Testable();
		$this->wpcli->reset();
	}

	/**
	 * Test WPCLI source file exists.
	 */
	public function test_wpcli_file_exists() {
		$file = plugin_dir_path( DOCUMENTATE_PLUGIN_FILE ) . 'includes/class-documentate-wpcli.php';
		$this->assertFileExists( $file );
	}

	/**
	 * Test WPCLI class structure in source file.
	 */
	public function test_wpcli_class_structure() {
		$this->assertTrue( class_exists( 'Documentate_WPCLI' ) );
	}

	/**
	 * Test greet method exists and is public.
	 */
	public function test_greet_method_exists() {
		$this->assertTrue( method_exists( $this->wpcli, 'greet' ) );

		$reflection = new ReflectionMethod( $this->wpcli, 'greet' );
		$this->assertTrue( $reflection->isPublic() );
	}

	/**
	 * Test greet method has correct parameters.
	 */
	public function test_greet_method_parameters() {
		$reflection = new ReflectionMethod( $this->wpcli, 'greet' );
		$params     = $reflection->getParameters();

		$this->assertCount( 2, $params );
		$this->assertSame( 'args', $params[0]->getName() );
		$this->assertSame( 'assoc_args', $params[1]->getName() );
	}

	/**
	 * Test create_sample_data method exists and is public.
	 */
	public function test_create_sample_data_method_exists() {
		$this->assertTrue( method_exists( $this->wpcli, 'create_sample_data' ) );

		$reflection = new ReflectionMethod( $this->wpcli, 'create_sample_data' );
		$this->assertTrue( $reflection->isPublic() );
	}

	/**
	 * Test create_sample_data method has no required parameters.
	 */
	public function test_create_sample_data_no_parameters() {
		$reflection = new ReflectionMethod( $this->wpcli, 'create_sample_data' );
		$this->assertSame( 0, $reflection->getNumberOfRequiredParameters() );
	}

	/**
	 * Test greet with default name outputs success.
	 */
	public function test_greet_default_name() {
		$this->wpcli->greet( array(), array() );

		$this->assertCount( 1, $this->wpcli->messages );
		$this->assertSame( 'success', $this->wpcli->messages[0]['type'] );
		$this->assertStringContainsString( 'World', $this->wpcli->messages[0]['message'] );
	}

	/**
	 * Test greet with custom name.
	 */
	public function test_greet_custom_name() {
		$this->wpcli->greet( array(), array( 'name' => 'TestUser' ) );

		$this->assertCount( 1, $this->wpcli->messages );
		$this->assertSame( 'success', $this->wpcli->messages[0]['type'] );
		$this->assertStringContainsString( 'TestUser', $this->wpcli->messages[0]['message'] );
	}

	/**
	 * Test create_sample_data creates data successfully.
	 */
	public function test_create_sample_data_creates_data() {
		$this->wpcli->create_sample_data();

		// Should have log and success messages.
		$types = array_column( $this->wpcli->messages, 'type' );
		$this->assertContains( 'log', $types );
		$this->assertContains( 'success', $types );
	}

	/**
	 * Test greet message format.
	 */
	public function test_greet_message_format() {
		$this->wpcli->greet( array(), array( 'name' => 'Alice' ) );

		$this->assertSame( 'Hello, Alice!', $this->wpcli->messages[0]['message'] );
	}

	/**
	 * Test CLI helper methods exist.
	 */
	public function test_cli_helper_methods_exist() {
		$reflection = new ReflectionClass( 'Documentate_WPCLI' );

		$this->assertTrue( $reflection->hasMethod( 'cli_success' ) );
		$this->assertTrue( $reflection->hasMethod( 'cli_warning' ) );
		$this->assertTrue( $reflection->hasMethod( 'cli_log' ) );
		$this->assertTrue( $reflection->hasMethod( 'cli_confirm' ) );
	}

	/**
	 * Test CLI helper methods are protected.
	 */
	public function test_cli_helper_methods_are_protected() {
		$methods = array( 'cli_success', 'cli_warning', 'cli_log', 'cli_confirm' );

		foreach ( $methods as $method ) {
			$reflection = new ReflectionMethod( 'Documentate_WPCLI', $method );
			$this->assertTrue( $reflection->isProtected(), "$method should be protected" );
		}
	}

	/**
	 * Test create_sample_data log message content.
	 */
	public function test_create_sample_data_log_message() {
		$this->wpcli->create_sample_data();

		$log_messages = array_filter(
			$this->wpcli->messages,
			function ( $m ) {
				return 'log' === $m['type'];
			}
		);

		$this->assertNotEmpty( $log_messages );
		$log_message = reset( $log_messages );
		$this->assertStringContainsString( 'sample data', strtolower( $log_message['message'] ) );
	}

	/**
	 * Test create_sample_data success message content.
	 */
	public function test_create_sample_data_success_message() {
		$this->wpcli->create_sample_data();

		$success_messages = array_filter(
			$this->wpcli->messages,
			function ( $m ) {
				return 'success' === $m['type'];
			}
		);

		$this->assertNotEmpty( $success_messages );
		$success_message = reset( $success_messages );
		$this->assertStringContainsString( 'successfully', strtolower( $success_message['message'] ) );
	}

	/**
	 * Test base cli methods are protected.
	 */
	public function test_base_cli_methods_are_protected() {
		$methods = array( 'cli_success', 'cli_warning', 'cli_log', 'cli_confirm' );

		foreach ( $methods as $method_name ) {
			$ref    = new ReflectionMethod( 'Documentate_WPCLI', $method_name );
			$this->assertTrue( $ref->isProtected(), "$method_name should be protected" );
		}
	}

	/**
	 * Test greet uses cli_success internally.
	 */
	public function test_greet_uses_cli_success() {
		$this->wpcli->greet( array(), array( 'name' => 'Tester' ) );

		$this->assertCount( 1, $this->wpcli->messages );
		$this->assertSame( 'success', $this->wpcli->messages[0]['type'] );
	}

	/**
	 * Test greet with empty name uses default.
	 */
	public function test_greet_with_empty_name() {
		$this->wpcli->greet( array(), array( 'name' => '' ) );

		// Empty string is still used, not default.
		$this->assertSame( 'Hello, !', $this->wpcli->messages[0]['message'] );
	}

	/**
	 * Test greet with special characters in name.
	 */
	public function test_greet_with_special_characters() {
		$this->wpcli->greet( array(), array( 'name' => '<script>alert("xss")</script>' ) );

		// Name should be used as-is (WP_CLI would escape it).
		$this->assertStringContainsString( '<script>', $this->wpcli->messages[0]['message'] );
	}
}
