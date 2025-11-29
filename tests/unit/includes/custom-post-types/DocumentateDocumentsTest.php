<?php
/**
 * Tests for Documentate_Documents class.
 *
 * @package Documentate
 */

use Documentate\DocType\SchemaStorage;

/**
 * @covers Documentate_Documents
 */
class DocumentateDocumentsTest extends Documentate_Test_Base {

	/**
	 * Documents instance.
	 *
	 * @var Documentate_Documents
	 */
	private $documents;

	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	private $admin_user_id;

	/**
	 * Set up test fixtures.
	 */
	public function set_up() {
		parent::set_up();

		$this->admin_user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->admin_user_id );

		set_current_screen( 'edit-documentate_document' );

		$this->documents = new Documentate_Documents();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tear_down() {
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * Test constructor registers hooks.
	 */
	public function test_constructor_registers_hooks() {
		$this->assertNotFalse( has_action( 'init', array( $this->documents, 'register_post_type' ) ) );
		$this->assertNotFalse( has_action( 'init', array( $this->documents, 'register_taxonomies' ) ) );
		$this->assertNotFalse( has_filter( 'use_block_editor_for_post_type', array( $this->documents, 'disable_gutenberg' ) ) );
		$this->assertNotFalse( has_action( 'add_meta_boxes', array( $this->documents, 'register_meta_boxes' ) ) );
		$this->assertNotFalse( has_action( 'save_post_documentate_document', array( $this->documents, 'save_meta_boxes' ) ) );
		$this->assertNotFalse( has_action( 'wp_save_post_revision', array( $this->documents, 'copy_meta_to_revision' ) ) );
		$this->assertNotFalse( has_action( 'wp_restore_post_revision', array( $this->documents, 'restore_meta_from_revision' ) ) );
		$this->assertNotFalse( has_filter( 'wp_revisions_to_keep', array( $this->documents, 'limit_revisions_for_cpt' ) ) );
		$this->assertNotFalse( has_action( 'set_object_terms', array( $this->documents, 'enforce_locked_doc_type' ) ) );
	}

	/**
	 * Test register_post_type creates the CPT.
	 */
	public function test_register_post_type_creates_cpt() {
		// The CPT should already be registered via constructor hooks.
		$this->assertTrue( post_type_exists( 'documentate_document' ) );
	}

	/**
	 * Test register_taxonomies creates document type taxonomy.
	 */
	public function test_register_taxonomies_creates_taxonomy() {
		$this->assertTrue( taxonomy_exists( 'documentate_doc_type' ) );
	}

	/**
	 * Test disable_gutenberg returns false for documents.
	 */
	public function test_disable_gutenberg_for_documents() {
		$result = $this->documents->disable_gutenberg( true, 'documentate_document' );
		$this->assertFalse( $result );
	}

	/**
	 * Test disable_gutenberg returns original value for other post types.
	 */
	public function test_disable_gutenberg_allows_other_types() {
		$result = $this->documents->disable_gutenberg( true, 'post' );
		$this->assertTrue( $result );
	}

	/**
	 * Test limit_revisions_for_cpt returns 15 for documents.
	 */
	public function test_limit_revisions_for_documents() {
		$post = $this->factory->post->create_and_get( array( 'post_type' => 'documentate_document' ) );
		$result = $this->documents->limit_revisions_for_cpt( 10, $post );
		$this->assertSame( 15, $result );
	}

	/**
	 * Test limit_revisions_for_cpt returns original value for other types.
	 */
	public function test_limit_revisions_unchanged_for_other_types() {
		$post = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );
		$result = $this->documents->limit_revisions_for_cpt( 10, $post );
		$this->assertSame( 10, $result );
	}

	/**
	 * Test force_revision_on_meta returns true for documents.
	 */
	public function test_force_revision_on_meta_for_documents() {
		$post = $this->factory->post->create_and_get( array( 'post_type' => 'documentate_document' ) );
		$result = $this->documents->force_revision_on_meta( false, null, $post );
		$this->assertTrue( $result );
	}

	/**
	 * Test force_revision_on_meta returns original value for other types.
	 */
	public function test_force_revision_on_meta_unchanged_for_other_types() {
		$post = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );
		$result = $this->documents->force_revision_on_meta( false, null, $post );
		$this->assertFalse( $result );
	}

	/**
	 * Test enforce_locked_doc_type ignores other taxonomies.
	 */
	public function test_enforce_locked_doc_type_ignores_other_taxonomies() {
		$post = $this->factory->post->create_and_get( array( 'post_type' => 'documentate_document' ) );

		// Should not throw or modify anything for category taxonomy.
		$this->documents->enforce_locked_doc_type( $post->ID, array(), array(), 'category', false, array() );

		$this->assertTrue( true ); // Verify no errors occurred.
	}

	/**
	 * Test enforce_locked_doc_type ignores non-document posts.
	 */
	public function test_enforce_locked_doc_type_ignores_other_post_types() {
		$post = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );

		// Should not modify anything.
		$this->documents->enforce_locked_doc_type( $post->ID, array(), array(), 'documentate_doc_type', false, array() );

		$this->assertTrue( true );
	}

	/**
	 * Test enforce_locked_doc_type locks type on first assignment.
	 */
	public function test_enforce_locked_doc_type_locks_on_first_assignment() {
		$post = $this->factory->post->create_and_get( array( 'post_type' => 'documentate_document' ) );
		$term = wp_insert_term( 'Test Type Lock', 'documentate_doc_type' );
		$term_id = $term['term_id'];

		// Assign term.
		wp_set_post_terms( $post->ID, array( $term_id ), 'documentate_doc_type' );

		// Trigger the lock.
		$this->documents->enforce_locked_doc_type( $post->ID, array( $term_id ), array(), 'documentate_doc_type', false, array() );

		$locked = get_post_meta( $post->ID, 'documentate_locked_doc_type', true );
		$this->assertSame( $term_id, intval( $locked ) );
	}

	/**
	 * Test register_meta_boxes adds metaboxes.
	 */
	public function test_register_meta_boxes_adds_boxes() {
		global $wp_meta_boxes;

		$this->documents->register_meta_boxes();

		$this->assertArrayHasKey( 'documentate_document', $wp_meta_boxes );
		$this->assertArrayHasKey( 'side', $wp_meta_boxes['documentate_document'] );
		$this->assertArrayHasKey( 'documentate_doc_type', $wp_meta_boxes['documentate_document']['side']['high'] );
		$this->assertArrayHasKey( 'documentate_sections', $wp_meta_boxes['documentate_document']['normal']['default'] );
	}

	/**
	 * Test render_type_metabox with no types shows message.
	 */
	public function test_render_type_metabox_no_types_message() {
		// Delete all existing terms.
		$terms = get_terms( array( 'taxonomy' => 'documentate_doc_type', 'hide_empty' => false, 'fields' => 'ids' ) );
		foreach ( $terms as $term_id ) {
			wp_delete_term( $term_id, 'documentate_doc_type' );
		}

		$post = $this->factory->post->create_and_get( array( 'post_type' => 'documentate_document' ) );

		ob_start();
		$this->documents->render_type_metabox( $post );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'No document types defined', $output );
	}

	/**
	 * Test render_type_metabox shows select dropdown for new document.
	 */
	public function test_render_type_metabox_shows_select_for_new() {
		wp_insert_term( 'Type Select Test', 'documentate_doc_type' );

		$post = $this->factory->post->create_and_get(
			array(
				'post_type'   => 'documentate_document',
				'post_status' => 'auto-draft',
			)
		);

		ob_start();
		$this->documents->render_type_metabox( $post );
		$output = ob_get_clean();

		$this->assertStringContainsString( '<select', $output );
		$this->assertStringContainsString( 'Type Select Test', $output );
	}

	/**
	 * Test render_type_metabox shows locked type for published document.
	 */
	public function test_render_type_metabox_shows_locked_for_published() {
		$term = wp_insert_term( 'Locked Type Test', 'documentate_doc_type' );
		$term_id = $term['term_id'];

		$post = $this->factory->post->create_and_get(
			array(
				'post_type'   => 'documentate_document',
				'post_status' => 'publish',
			)
		);
		wp_set_post_terms( $post->ID, array( $term_id ), 'documentate_doc_type' );

		ob_start();
		$this->documents->render_type_metabox( $post );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Selected type:', $output );
		$this->assertStringContainsString( 'Locked Type Test', $output );
		$this->assertStringContainsString( 'type="hidden"', $output );
	}

	/**
	 * Test render_sections_metabox without schema shows description.
	 */
	public function test_render_sections_metabox_no_schema() {
		$post = $this->factory->post->create_and_get( array( 'post_type' => 'documentate_document' ) );

		ob_start();
		$this->documents->render_sections_metabox( $post );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Configure a document type with fields', $output );
	}

	/**
	 * Test render_sections_metabox with schema shows fields.
	 */
	public function test_render_sections_metabox_with_schema() {
		$term = wp_insert_term( 'Schema Type Test', 'documentate_doc_type' );
		$term_id = $term['term_id'];

		$storage = new SchemaStorage();
		$storage->save_schema(
			$term_id,
			array(
				'version'   => 2,
				'fields'    => array(
					array(
						'name' => 'Test Field',
						'slug' => 'test_field',
						'type' => 'text',
					),
				),
				'repeaters' => array(),
			)
		);

		$post = $this->factory->post->create_and_get( array( 'post_type' => 'documentate_document' ) );
		wp_set_post_terms( $post->ID, array( $term_id ), 'documentate_doc_type' );

		ob_start();
		$this->documents->render_sections_metabox( $post );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Test Field', $output );
	}

	/**
	 * Test copy_meta_to_revision ignores non-document posts.
	 */
	public function test_copy_meta_to_revision_ignores_other_types() {
		$post = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );

		// Should not throw.
		$this->documents->copy_meta_to_revision( $post->ID, 0 );

		$this->assertTrue( true );
	}

	/**
	 * Test copy_meta_to_revision copies meta to revision.
	 */
	public function test_copy_meta_to_revision_copies_meta() {
		$post = $this->factory->post->create_and_get( array( 'post_type' => 'documentate_document' ) );
		update_post_meta( $post->ID, 'documentate_field_test', 'Test Value' );

		// Create a revision post.
		$revision_id = wp_insert_post(
			array(
				'post_type'   => 'revision',
				'post_status' => 'inherit',
				'post_parent' => $post->ID,
			)
		);

		$this->documents->copy_meta_to_revision( $post->ID, $revision_id );

		$copied = get_metadata( 'post', $revision_id, 'documentate_field_test', true );
		$this->assertSame( 'Test Value', $copied );
	}

	/**
	 * Test restore_meta_from_revision ignores non-document posts.
	 */
	public function test_restore_meta_from_revision_ignores_other_types() {
		$post = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );

		$this->documents->restore_meta_from_revision( $post->ID, 0 );

		$this->assertTrue( true );
	}

	/**
	 * Test restore_meta_from_revision restores meta.
	 */
	public function test_restore_meta_from_revision_restores_meta() {
		$term = wp_insert_term( 'Restore Type', 'documentate_doc_type' );
		$term_id = $term['term_id'];

		$storage = new SchemaStorage();
		$storage->save_schema(
			$term_id,
			array(
				'version'   => 2,
				'fields'    => array(
					array(
						'name' => 'Restore Field',
						'slug' => 'restore_field',
						'type' => 'text',
					),
				),
				'repeaters' => array(),
			)
		);

		$post = $this->factory->post->create_and_get( array( 'post_type' => 'documentate_document' ) );
		wp_set_post_terms( $post->ID, array( $term_id ), 'documentate_doc_type' );
		update_post_meta( $post->ID, 'documentate_field_restore_field', 'Current Value' );

		// Create a revision with different value.
		$revision_id = wp_insert_post(
			array(
				'post_type'   => 'revision',
				'post_status' => 'inherit',
				'post_parent' => $post->ID,
			)
		);
		add_metadata( 'post', $revision_id, 'documentate_field_restore_field', 'Revision Value', true );

		$this->documents->restore_meta_from_revision( $post->ID, $revision_id );

		$restored = get_post_meta( $post->ID, 'documentate_field_restore_field', true );
		$this->assertSame( 'Revision Value', $restored );
	}

	/**
	 * Test hide_submit_box_controls outputs CSS.
	 */
	public function test_hide_submit_box_controls_outputs_css() {
		$screen = WP_Screen::get( 'documentate_document' );
		$screen->post_type = 'documentate_document';
		$GLOBALS['current_screen'] = $screen;

		ob_start();
		$this->documents->hide_submit_box_controls();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<style', $output );
		$this->assertStringContainsString( 'documentate-document-submitbox-controls', $output );
		$this->assertStringContainsString( 'display:none', $output );
	}

	/**
	 * Test hide_submit_box_controls does nothing for other screens.
	 */
	public function test_hide_submit_box_controls_ignores_other_screens() {
		$screen = WP_Screen::get( 'post' );
		$screen->post_type = 'post';
		$GLOBALS['current_screen'] = $screen;

		ob_start();
		$this->documents->hide_submit_box_controls();
		$output = ob_get_clean();

		$this->assertEmpty( $output );
	}

	/**
	 * Test add_revision_fields returns fields.
	 */
	public function test_add_revision_fields() {
		$post = $this->factory->post->create_and_get( array( 'post_type' => 'documentate_document' ) );

		$fields = array( 'post_title' => 'Title', 'post_content' => 'Content' );
		$result = $this->documents->add_revision_fields( $fields, $post );

		$this->assertSame( $fields, $result );
	}

	/**
	 * Test revision_field_value returns empty for invalid revision.
	 */
	public function test_revision_field_value_returns_empty_for_invalid() {
		// No valid revision passed.
		$result = $this->documents->revision_field_value( '', null );

		$this->assertEmpty( $result );
	}

	/**
	 * Test filter_post_data_compose_content ignores non-document posts.
	 */
	public function test_filter_post_data_compose_content_ignores_other_types() {
		$data = array( 'post_type' => 'post' );
		$postarr = array( 'ID' => 1 );

		$result = $this->documents->filter_post_data_compose_content( $data, $postarr );

		$this->assertSame( $data, $result );
	}

	/**
	 * Test parse_structured_content with empty content.
	 */
	public function test_parse_structured_content_empty() {
		$result = Documentate_Documents::parse_structured_content( '' );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test parse_structured_content with valid content.
	 */
	public function test_parse_structured_content_valid() {
		// The format uses HTML comments: <!-- documentate-field slug="..." type="..." -->value<!-- /documentate-field -->
		$content = '<!-- documentate-field slug="test_field" type="text" -->Test Value<!-- /documentate-field -->';

		$result = Documentate_Documents::parse_structured_content( $content );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'test_field', $result );
		$this->assertSame( 'text', $result['test_field']['type'] );
		$this->assertSame( 'Test Value', $result['test_field']['value'] );
	}

	/**
	 * Test ARRAY_FIELD_MAX_ITEMS constant exists.
	 */
	public function test_array_field_max_items_constant() {
		$this->assertSame( 20, Documentate_Documents::ARRAY_FIELD_MAX_ITEMS );
	}

	/**
	 * Test get_dynamic_fields_schema_for_post via reflection.
	 */
	public function test_get_dynamic_fields_schema_for_post_no_type() {
		$post = $this->factory->post->create_and_get( array( 'post_type' => 'documentate_document' ) );

		$reflection = new ReflectionClass( $this->documents );
		$method = $reflection->getMethod( 'get_dynamic_fields_schema_for_post' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->documents, $post->ID );

		$this->assertEmpty( $result );
	}

	/**
	 * Test get_dynamic_fields_schema_for_post with type and schema.
	 */
	public function test_get_dynamic_fields_schema_for_post_with_schema() {
		$term = wp_insert_term( 'Dynamic Schema Type', 'documentate_doc_type' );
		$term_id = $term['term_id'];

		$storage = new SchemaStorage();
		$storage->save_schema(
			$term_id,
			array(
				'version'   => 2,
				'fields'    => array(
					array(
						'name' => 'Dynamic Field',
						'slug' => 'dynamic_field',
						'type' => 'text',
					),
				),
				'repeaters' => array(),
			)
		);

		$post = $this->factory->post->create_and_get( array( 'post_type' => 'documentate_document' ) );
		wp_set_post_terms( $post->ID, array( $term_id ), 'documentate_doc_type' );

		$reflection = new ReflectionClass( $this->documents );
		$method = $reflection->getMethod( 'get_dynamic_fields_schema_for_post' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->documents, $post->ID );

		$this->assertNotEmpty( $result );
		$this->assertSame( 'dynamic_field', $result[0]['slug'] );
	}

	/**
	 * Test normalize_html_for_diff via reflection.
	 */
	public function test_normalize_html_for_diff() {
		$reflection = new ReflectionClass( $this->documents );
		$method = $reflection->getMethod( 'normalize_html_for_diff' );
		$method->setAccessible( true );

		$html = '<p>First</p><p>Second</p><div>Third</div>';
		$result = $method->invoke( $this->documents, $html );

		$this->assertStringContainsString( 'First', $result );
		$this->assertStringContainsString( 'Second', $result );
		$this->assertStringNotContainsString( '<p>', $result );
	}

	/**
	 * Test normalize_html_for_diff with empty string.
	 */
	public function test_normalize_html_for_diff_empty() {
		$reflection = new ReflectionClass( $this->documents );
		$method = $reflection->getMethod( 'normalize_html_for_diff' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->documents, '' );

		$this->assertSame( '', $result );
	}

	/**
	 * Test is_collaborative_editing_enabled via reflection.
	 */
	public function test_is_collaborative_editing_enabled_false() {
		delete_option( 'documentate_settings' );

		$reflection = new ReflectionClass( $this->documents );
		$method = $reflection->getMethod( 'is_collaborative_editing_enabled' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->documents );

		$this->assertFalse( $result );
	}

	/**
	 * Test is_collaborative_editing_enabled true.
	 */
	public function test_is_collaborative_editing_enabled_true() {
		update_option( 'documentate_settings', array( 'collaborative_enabled' => '1' ) );

		$reflection = new ReflectionClass( $this->documents );
		$method = $reflection->getMethod( 'is_collaborative_editing_enabled' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->documents );

		$this->assertTrue( $result );
	}

	/**
	 * Test get_meta_fields_for_post via reflection.
	 */
	public function test_get_meta_fields_for_post_empty() {
		$post = $this->factory->post->create_and_get( array( 'post_type' => 'documentate_document' ) );

		$reflection = new ReflectionClass( $this->documents );
		$method = $reflection->getMethod( 'get_meta_fields_for_post' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->documents, $post->ID );

		$this->assertIsArray( $result );
	}

	/**
	 * Test get_meta_fields_for_post with existing meta.
	 */
	public function test_get_meta_fields_for_post_with_meta() {
		$post = $this->factory->post->create_and_get( array( 'post_type' => 'documentate_document' ) );
		update_post_meta( $post->ID, 'documentate_field_custom', 'value' );

		$reflection = new ReflectionClass( $this->documents );
		$method = $reflection->getMethod( 'get_meta_fields_for_post' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->documents, $post->ID );

		$this->assertContains( 'documentate_field_custom', $result );
	}
}
