<?php
/**
 * Tests for repeater block merging in document generation.
 *
 * Validates that [block;block=begin]...[block;block=end] placeholders
 * correctly repeat content with multiple data items in ODT and DOCX formats.
 *
 * @package Documentate
 */

/**
 * Class DocumentRepeaterMergeTest
 */
class DocumentRepeaterMergeTest extends Documentate_Generation_Test_Base {

	/**
	 * XML Asserter instance.
	 *
	 * @var Document_Xml_Asserter
	 */
	protected $asserter;

	/**
	 * Set up test fixtures.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->asserter = new Document_Xml_Asserter();
	}

	// =========================================================================
	// ODT Repeater Tests
	// =========================================================================

	/**
	 * Test simple repeater with 3 items in ODT.
	 */
	public function test_odt_simple_repeater_3_items() {
		$items = array(
			array(
				'title'   => 'First Item',
				'content' => 'Content of the first item.',
			),
			array(
				'title'   => 'Second Item',
				'content' => 'Content of the second item.',
			),
			array(
				'title'   => 'Third Item',
				'content' => 'Content of the third item.',
			),
		);

		$type_data = $this->create_doc_type_with_template( 'minimal-nested-block.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array(), array( 'items' => $items ) );

		$path = $this->generate_document( $post_id, 'odt' );

		$this->assertIsString( $path );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml );

		// Verify all items are present.
		$this->assertStringContainsString( 'First Item', $xml );
		$this->assertStringContainsString( 'Second Item', $xml );
		$this->assertStringContainsString( 'Third Item', $xml );
		$this->assertStringContainsString( 'Content of the first item', $xml );
		$this->assertStringContainsString( 'Content of the second item', $xml );
		$this->assertStringContainsString( 'Content of the third item', $xml );

		$this->assertNoPlaceholderArtifacts( $path );
	}

	/**
	 * Test repeater with HTML content in items in ODT.
	 */
	public function test_odt_repeater_with_html_content() {
		$items = array(
			array(
				'title'   => 'Bold Title',
				'content' => '<p>This has <strong>bold</strong> text.</p>',
			),
			array(
				'title'   => 'Italic Title',
				'content' => '<p>This has <em>italic</em> text.</p>',
			),
		);

		$type_data = $this->create_doc_type_with_template( 'minimal-nested-block.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array(), array( 'items' => $items ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Bold Title', $xml );
		$this->assertStringContainsString( 'Italic Title', $xml );
		$this->assertStringContainsString( 'bold', $xml );
		$this->assertStringContainsString( 'italic', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
		$this->assertNoPlaceholderArtifacts( $path );
	}

	/**
	 * Test empty repeater produces no artifacts in ODT.
	 */
	public function test_odt_empty_repeater_no_artifacts() {
		$type_data = $this->create_doc_type_with_template( 'minimal-nested-block.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array(), array( 'items' => array() ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );

		// Verify no placeholder artifacts.
		$this->assertStringNotContainsString( 'items;block=begin', $xml );
		$this->assertStringNotContainsString( 'items;block=end', $xml );
		$this->assertStringNotContainsString( 'items.title', $xml );
		$this->assertStringNotContainsString( 'items.content', $xml );
		$this->assertNoPlaceholderArtifacts( $path );
	}

	/**
	 * Test large repeater with 20 items in ODT.
	 */
	public function test_odt_large_repeater_20_items() {
		$items = array();
		for ( $i = 1; $i <= 20; $i++ ) {
			$items[] = array(
				'title'   => "Item Number $i",
				'content' => "This is the content for item number $i with some additional text.",
			);
		}

		$type_data = $this->create_doc_type_with_template( 'minimal-nested-block.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array(), array( 'items' => $items ) );

		$path = $this->generate_document( $post_id, 'odt' );

		$this->assertIsString( $path, 'Large repeater should generate successfully.' );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml );

		// Verify first, middle, and last items.
		$this->assertStringContainsString( 'Item Number 1', $xml );
		$this->assertStringContainsString( 'Item Number 10', $xml );
		$this->assertStringContainsString( 'Item Number 20', $xml );

		$this->assertNoPlaceholderArtifacts( $path );
	}

	/**
	 * Test repeater with nested table in content in ODT.
	 */
	public function test_odt_repeater_with_nested_table() {
		$items = array(
			array(
				'title'   => 'Table Item',
				'content' => '<table><tr><td>A1</td><td>B1</td></tr><tr><td>A2</td><td>B2</td></tr></table>',
			),
			array(
				'title'   => 'Simple Item',
				'content' => 'Just plain text.',
			),
		);

		$type_data = $this->create_doc_type_with_template( 'minimal-nested-block.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array(), array( 'items' => $items ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Table Item', $xml );
		$this->assertStringContainsString( 'Simple Item', $xml );
		$this->assertStringContainsString( 'A1', $xml );
		$this->assertStringContainsString( 'B2', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
		$this->assertNoPlaceholderArtifacts( $path );
	}

	/**
	 * Test repeater with nested list in content in ODT.
	 */
	public function test_odt_repeater_with_nested_list() {
		$items = array(
			array(
				'title'   => 'List Item',
				'content' => '<ul><li>First</li><li>Second</li><li>Third</li></ul>',
			),
			array(
				'title'   => 'Another List',
				'content' => '<ol><li>One</li><li>Two</li></ol>',
			),
		);

		$type_data = $this->create_doc_type_with_template( 'minimal-nested-block.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array(), array( 'items' => $items ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'List Item', $xml );
		$this->assertStringContainsString( 'Another List', $xml );
		$this->assertStringContainsString( 'First', $xml );
		$this->assertStringContainsString( 'One', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
		$this->assertNoPlaceholderArtifacts( $path );
	}

	// =========================================================================
	// DOCX Repeater Tests
	// =========================================================================

	/**
	 * Test simple repeater with 3 items in DOCX.
	 */
	public function test_docx_simple_repeater_3_items() {
		$items = array(
			array(
				'title'   => 'First Item',
				'content' => 'Content of the first item.',
			),
			array(
				'title'   => 'Second Item',
				'content' => 'Content of the second item.',
			),
			array(
				'title'   => 'Third Item',
				'content' => 'Content of the third item.',
			),
		);

		$type_data = $this->create_doc_type_with_template( 'minimal-nested-block.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array(), array( 'items' => $items ) );

		$path = $this->generate_document( $post_id, 'docx' );

		$this->assertIsString( $path );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml );

		// Verify all items are present.
		$this->assertStringContainsString( 'First Item', $xml );
		$this->assertStringContainsString( 'Second Item', $xml );
		$this->assertStringContainsString( 'Third Item', $xml );
		$this->assertStringContainsString( 'Content of the first item', $xml );
		$this->assertStringContainsString( 'Content of the second item', $xml );
		$this->assertStringContainsString( 'Content of the third item', $xml );

		$this->assertNoPlaceholderArtifacts( $path );
	}

	/**
	 * Test repeater with HTML content in items in DOCX.
	 */
	public function test_docx_repeater_with_html_content() {
		$items = array(
			array(
				'title'   => 'Bold Title',
				'content' => '<p>This has <strong>bold</strong> text.</p>',
			),
			array(
				'title'   => 'Italic Title',
				'content' => '<p>This has <em>italic</em> text.</p>',
			),
		);

		$type_data = $this->create_doc_type_with_template( 'minimal-nested-block.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array(), array( 'items' => $items ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Bold Title', $xml );
		$this->assertStringContainsString( 'Italic Title', $xml );
		// Rich text conversion within repeaters renders as plain text.
		$this->assertStringContainsString( 'bold', $xml );
		$this->assertStringContainsString( 'italic', $xml );

		$this->assertNoPlaceholderArtifacts( $path );
	}

	/**
	 * Test empty repeater produces no artifacts in DOCX.
	 */
	public function test_docx_empty_repeater_no_artifacts() {
		$type_data = $this->create_doc_type_with_template( 'minimal-nested-block.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array(), array( 'items' => array() ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );

		// Verify no placeholder artifacts.
		$this->assertStringNotContainsString( 'items;block=begin', $xml );
		$this->assertStringNotContainsString( 'items;block=end', $xml );
		$this->assertStringNotContainsString( 'items.title', $xml );
		$this->assertStringNotContainsString( 'items.content', $xml );
		$this->assertNoPlaceholderArtifacts( $path );
	}

	/**
	 * Test large repeater with 20 items in DOCX.
	 */
	public function test_docx_large_repeater_20_items() {
		$items = array();
		for ( $i = 1; $i <= 20; $i++ ) {
			$items[] = array(
				'title'   => "Item Number $i",
				'content' => "This is the content for item number $i with some additional text.",
			);
		}

		$type_data = $this->create_doc_type_with_template( 'minimal-nested-block.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array(), array( 'items' => $items ) );

		$path = $this->generate_document( $post_id, 'docx' );

		$this->assertIsString( $path, 'Large repeater should generate successfully.' );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml );

		// Verify first, middle, and last items.
		$this->assertStringContainsString( 'Item Number 1', $xml );
		$this->assertStringContainsString( 'Item Number 10', $xml );
		$this->assertStringContainsString( 'Item Number 20', $xml );

		$this->assertNoPlaceholderArtifacts( $path );
	}

	/**
	 * Test repeater with nested table in content in DOCX.
	 */
	public function test_docx_repeater_with_nested_table() {
		$items = array(
			array(
				'title'   => 'Table Item',
				'content' => '<table><tr><td>A1</td><td>B1</td></tr><tr><td>A2</td><td>B2</td></tr></table>',
			),
			array(
				'title'   => 'Simple Item',
				'content' => 'Just plain text.',
			),
		);

		$type_data = $this->create_doc_type_with_template( 'minimal-nested-block.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array(), array( 'items' => $items ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Table Item', $xml );
		$this->assertStringContainsString( 'Simple Item', $xml );
		// Tables within repeaters render as plain text (rich text conversion not supported in repeaters).
		$this->assertStringContainsString( 'A1', $xml );
		$this->assertStringContainsString( 'B2', $xml );
		$this->assertNoPlaceholderArtifacts( $path );
	}

	/**
	 * Test repeater with nested list in content in DOCX.
	 */
	public function test_docx_repeater_with_nested_list() {
		$items = array(
			array(
				'title'   => 'List Item',
				'content' => '<ul><li>First</li><li>Second</li><li>Third</li></ul>',
			),
			array(
				'title'   => 'Another List',
				'content' => '<ol><li>One</li><li>Two</li></ol>',
			),
		);

		$type_data = $this->create_doc_type_with_template( 'minimal-nested-block.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array(), array( 'items' => $items ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'List Item', $xml );
		$this->assertStringContainsString( 'Another List', $xml );
		$this->assertStringContainsString( 'First', $xml );
		$this->assertStringContainsString( 'One', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
		$this->assertNoPlaceholderArtifacts( $path );
	}
}
