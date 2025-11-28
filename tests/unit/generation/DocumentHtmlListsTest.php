<?php
/**
 * Tests for HTML list rendering in document generation.
 *
 * Validates that HTML lists (ul, ol) are correctly converted to native
 * list structures in ODT (ODF) and DOCX (OOXML) formats.
 *
 * @package Documentate
 */

/**
 * Class DocumentHtmlListsTest
 */
class DocumentHtmlListsTest extends Documentate_Generation_Test_Base {

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
	// ODT List Tests
	// =========================================================================

	/**
	 * Test simple unordered list in ODT.
	 */
	public function test_odt_simple_unordered_list() {
		$html = '<ul><li>Item One</li><li>Item Two</li><li>Item Three</li></ul>';

		$type_data = $this->create_doc_type_with_template( 'minimal-list.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'contenido' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );

		$this->assertIsString( $path, 'ODT generation should return a path.' );
		$this->assertFileExists( $path, 'Generated ODT file should exist.' );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml, 'ODT content.xml should be extractable.' );

		// Verify list items are present.
		$this->assertStringContainsString( 'Item One', $xml, 'First item should be present.' );
		$this->assertStringContainsString( 'Item Two', $xml, 'Second item should be present.' );
		$this->assertStringContainsString( 'Item Three', $xml, 'Third item should be present.' );

		// Verify no raw HTML remains.
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test simple ordered list in ODT.
	 */
	public function test_odt_simple_ordered_list() {
		$html = '<ol><li>First</li><li>Second</li><li>Third</li></ol>';

		$type_data = $this->create_doc_type_with_template( 'minimal-list.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'contenido' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'First', $xml, 'First item should be present.' );
		$this->assertStringContainsString( 'Second', $xml, 'Second item should be present.' );
		$this->assertStringContainsString( 'Third', $xml, 'Third item should be present.' );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test nested 2-level list in ODT.
	 */
	public function test_odt_nested_2_level_list() {
		$html = '<ul><li>Parent 1<ul><li>Child 1.1</li><li>Child 1.2</li></ul></li><li>Parent 2</li></ul>';

		$type_data = $this->create_doc_type_with_template( 'minimal-list.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'contenido' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Parent 1', $xml, 'Parent item should be present.' );
		$this->assertStringContainsString( 'Child 1.1', $xml, 'First child should be present.' );
		$this->assertStringContainsString( 'Child 1.2', $xml, 'Second child should be present.' );
		$this->assertStringContainsString( 'Parent 2', $xml, 'Second parent should be present.' );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test deeply nested 4-level list in ODT.
	 */
	public function test_odt_deeply_nested_4_level_list() {
		$html = '<ul>' .
				'<li>Level 1' .
					'<ul><li>Level 2' .
						'<ul><li>Level 3' .
							'<ul><li>Level 4</li></ul>' .
						'</li></ul>' .
					'</li></ul>' .
				'</li>' .
				'</ul>';

		$type_data = $this->create_doc_type_with_template( 'minimal-list.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'contenido' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );

		$this->assertIsString( $path, 'Deeply nested list should not crash generation.' );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Level 1', $xml );
		$this->assertStringContainsString( 'Level 2', $xml );
		$this->assertStringContainsString( 'Level 3', $xml );
		$this->assertStringContainsString( 'Level 4', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test mixed ol/ul nesting in ODT.
	 */
	public function test_odt_mixed_ol_ul_nesting() {
		$html = '<ol>' .
				'<li>Numbered 1' .
					'<ul><li>Bullet A</li><li>Bullet B</li></ul>' .
				'</li>' .
				'<li>Numbered 2' .
					'<ol><li>Sub-numbered 2.1</li></ol>' .
				'</li>' .
				'</ol>';

		$type_data = $this->create_doc_type_with_template( 'minimal-list.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'contenido' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Numbered 1', $xml );
		$this->assertStringContainsString( 'Bullet A', $xml );
		$this->assertStringContainsString( 'Bullet B', $xml );
		$this->assertStringContainsString( 'Numbered 2', $xml );
		$this->assertStringContainsString( 'Sub-numbered 2.1', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test list with formatted items (bold, italic) in ODT.
	 */
	public function test_odt_list_with_formatted_items() {
		$html = '<ul>' .
				'<li><strong>Bold Item</strong></li>' .
				'<li><em>Italic Item</em></li>' .
				'<li><u>Underlined Item</u></li>' .
				'<li><strong><em>Bold and Italic</em></strong></li>' .
				'</ul>';

		$type_data = $this->create_doc_type_with_template( 'minimal-list.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'contenido' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Bold Item', $xml );
		$this->assertStringContainsString( 'Italic Item', $xml );
		$this->assertStringContainsString( 'Underlined Item', $xml );
		$this->assertStringContainsString( 'Bold and Italic', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test list inside table cell in ODT.
	 */
	public function test_odt_list_inside_table_cell() {
		$html = '<table><tr><td><ul><li>List in cell 1</li><li>List in cell 2</li></ul></td></tr></table>';

		$type_data = $this->create_doc_type_with_template( 'minimal-list.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'contenido' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );

		$this->assertIsString( $path, 'List in table cell should not crash.' );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'List in cell 1', $xml );
		$this->assertStringContainsString( 'List in cell 2', $xml );
		$this->assertStringContainsString( 'table:table', $xml, 'Table should be present.' );
	}

	/**
	 * Test empty list is handled gracefully in ODT.
	 */
	public function test_odt_empty_list_handled() {
		$html = '<ul></ul>';

		$type_data = $this->create_doc_type_with_template( 'minimal-list.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'contenido' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );

		$this->assertIsString( $path, 'Empty list should not crash generation.' );
		$this->assertFileExists( $path );
	}

	// =========================================================================
	// DOCX List Tests
	// =========================================================================

	/**
	 * Test simple unordered list in DOCX.
	 */
	public function test_docx_simple_unordered_list() {
		$html = '<ul><li>Item One</li><li>Item Two</li><li>Item Three</li></ul>';

		$type_data = $this->create_doc_type_with_template( 'minimal-list.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'contenido' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );

		$this->assertIsString( $path, 'DOCX generation should return a path.' );
		$this->assertFileExists( $path, 'Generated DOCX file should exist.' );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml, 'DOCX document.xml should be extractable.' );

		// Verify list items are present.
		$this->assertStringContainsString( 'Item One', $xml, 'First item should be present.' );
		$this->assertStringContainsString( 'Item Two', $xml, 'Second item should be present.' );
		$this->assertStringContainsString( 'Item Three', $xml, 'Third item should be present.' );

		// Verify no raw HTML remains.
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test simple ordered list in DOCX.
	 */
	public function test_docx_simple_ordered_list() {
		$html = '<ol><li>First</li><li>Second</li><li>Third</li></ol>';

		$type_data = $this->create_doc_type_with_template( 'minimal-list.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'contenido' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'First', $xml, 'First item should be present.' );
		$this->assertStringContainsString( 'Second', $xml, 'Second item should be present.' );
		$this->assertStringContainsString( 'Third', $xml, 'Third item should be present.' );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test nested 2-level list in DOCX.
	 */
	public function test_docx_nested_2_level_list() {
		$html = '<ul><li>Parent 1<ul><li>Child 1.1</li><li>Child 1.2</li></ul></li><li>Parent 2</li></ul>';

		$type_data = $this->create_doc_type_with_template( 'minimal-list.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'contenido' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Parent 1', $xml, 'Parent item should be present.' );
		$this->assertStringContainsString( 'Child 1.1', $xml, 'First child should be present.' );
		$this->assertStringContainsString( 'Child 1.2', $xml, 'Second child should be present.' );
		$this->assertStringContainsString( 'Parent 2', $xml, 'Second parent should be present.' );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test deeply nested 4-level list in DOCX.
	 */
	public function test_docx_deeply_nested_4_level_list() {
		$html = '<ul>' .
				'<li>Level 1' .
					'<ul><li>Level 2' .
						'<ul><li>Level 3' .
							'<ul><li>Level 4</li></ul>' .
						'</li></ul>' .
					'</li></ul>' .
				'</li>' .
				'</ul>';

		$type_data = $this->create_doc_type_with_template( 'minimal-list.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'contenido' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );

		$this->assertIsString( $path, 'Deeply nested list should not crash generation.' );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Level 1', $xml );
		$this->assertStringContainsString( 'Level 2', $xml );
		$this->assertStringContainsString( 'Level 3', $xml );
		$this->assertStringContainsString( 'Level 4', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test mixed ol/ul nesting in DOCX.
	 */
	public function test_docx_mixed_ol_ul_nesting() {
		$html = '<ol>' .
				'<li>Numbered 1' .
					'<ul><li>Bullet A</li><li>Bullet B</li></ul>' .
				'</li>' .
				'<li>Numbered 2' .
					'<ol><li>Sub-numbered 2.1</li></ol>' .
				'</li>' .
				'</ol>';

		$type_data = $this->create_doc_type_with_template( 'minimal-list.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'contenido' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Numbered 1', $xml );
		$this->assertStringContainsString( 'Bullet A', $xml );
		$this->assertStringContainsString( 'Bullet B', $xml );
		$this->assertStringContainsString( 'Numbered 2', $xml );
		$this->assertStringContainsString( 'Sub-numbered 2.1', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test list with formatted items (bold, italic) in DOCX.
	 */
	public function test_docx_list_with_formatted_items() {
		$html = '<ul>' .
				'<li><strong>Bold Item</strong></li>' .
				'<li><em>Italic Item</em></li>' .
				'<li><u>Underlined Item</u></li>' .
				'<li><strong><em>Bold and Italic</em></strong></li>' .
				'</ul>';

		$type_data = $this->create_doc_type_with_template( 'minimal-list.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'contenido' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Bold Item', $xml );
		$this->assertStringContainsString( 'Italic Item', $xml );
		$this->assertStringContainsString( 'Underlined Item', $xml );
		$this->assertStringContainsString( 'Bold and Italic', $xml );
		$this->assertStringContainsString( '<w:b', $xml, 'Bold formatting should be present.' );
		$this->assertStringContainsString( '<w:i', $xml, 'Italic formatting should be present.' );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test list inside table cell in DOCX.
	 */
	public function test_docx_list_inside_table_cell() {
		$html = '<table><tr><td><ul><li>List in cell 1</li><li>List in cell 2</li></ul></td></tr></table>';

		$type_data = $this->create_doc_type_with_template( 'minimal-list.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'contenido' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );

		$this->assertIsString( $path, 'List in table cell should not crash.' );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'List in cell 1', $xml );
		$this->assertStringContainsString( 'List in cell 2', $xml );
		$this->assertStringContainsString( 'w:tbl', $xml, 'Table should be present.' );
	}

	/**
	 * Test empty list is handled gracefully in DOCX.
	 */
	public function test_docx_empty_list_handled() {
		$html = '<ul></ul>';

		$type_data = $this->create_doc_type_with_template( 'minimal-list.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'contenido' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );

		$this->assertIsString( $path, 'Empty list should not crash generation.' );
		$this->assertFileExists( $path );
	}
}
