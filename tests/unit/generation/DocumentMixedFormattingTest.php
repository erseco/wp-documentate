<?php
/**
 * Tests for mixed HTML formatting in document generation.
 *
 * Validates that combined HTML formatting (bold + italic + underline + links)
 * is correctly converted in ODT and DOCX formats.
 *
 * @package Documentate
 */

/**
 * Class DocumentMixedFormattingTest
 */
class DocumentMixedFormattingTest extends Documentate_Generation_Test_Base {

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
	// ODT Mixed Formatting Tests
	// =========================================================================

	/**
	 * Test bold, italic, and underline combined in ODT.
	 */
	public function test_odt_bold_italic_underline_combined() {
		$html = '<p>Normal <strong>bold</strong> <em>italic</em> <u>underline</u> ' .
				'<strong><em>bold-italic</em></strong> ' .
				'<strong><u>bold-underline</u></strong> ' .
				'<em><u>italic-underline</u></em> ' .
				'<strong><em><u>all three</u></em></strong></p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );

		$this->assertIsString( $path );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml );

		// Verify all text is present.
		$this->assertStringContainsString( 'Normal', $xml );
		$this->assertStringContainsString( 'bold', $xml );
		$this->assertStringContainsString( 'italic', $xml );
		$this->assertStringContainsString( 'underline', $xml );
		$this->assertStringContainsString( 'bold-italic', $xml );
		$this->assertStringContainsString( 'bold-underline', $xml );
		$this->assertStringContainsString( 'italic-underline', $xml );
		$this->assertStringContainsString( 'all three', $xml );

		// Verify formatting styles exist.
		$this->assertStringContainsString( 'DocumentateRichBold', $xml, 'Bold style should exist.' );
		$this->assertStringContainsString( 'DocumentateRichItalic', $xml, 'Italic style should exist.' );

		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test formatting inside table cell in ODT.
	 */
	public function test_odt_formatting_inside_table_cell() {
		$html = '<table><tr>' .
				'<td><strong>Bold in cell</strong></td>' .
				'<td><em>Italic in cell</em></td>' .
				'<td><a href="https://example.com">Link in cell</a></td>' .
				'</tr></table>';

		$type_data = $this->create_doc_type_with_template( 'minimal-table.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'contenido' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Bold in cell', $xml );
		$this->assertStringContainsString( 'Italic in cell', $xml );
		$this->assertStringContainsString( 'Link in cell', $xml );
		$this->assertStringContainsString( 'table:table', $xml, 'Table should be present.' );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test formatting inside list item in ODT.
	 */
	public function test_odt_formatting_inside_list_item() {
		$html = '<ul>' .
				'<li><strong>Bold item</strong></li>' .
				'<li><em>Italic item</em></li>' .
				'<li><strong><em>Bold and italic item</em></strong></li>' .
				'<li>Normal with <a href="https://example.com">link</a></li>' .
				'</ul>';

		$type_data = $this->create_doc_type_with_template( 'minimal-list.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'contenido' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Bold item', $xml );
		$this->assertStringContainsString( 'Italic item', $xml );
		$this->assertStringContainsString( 'Bold and italic item', $xml );
		$this->assertStringContainsString( 'link', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test hyperlink with bold text in ODT.
	 */
	public function test_odt_hyperlink_with_bold_text() {
		$html = '<p>Visit <a href="https://example.com"><strong>this important link</strong></a> for more info.</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Visit', $xml );
		$this->assertStringContainsString( 'this important link', $xml );
		$this->assertStringContainsString( 'for more info', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test complex HTML with all features in ODT.
	 */
	public function test_odt_complex_html_with_all_features() {
		$html = '<h2>Document Title</h2>' .
				'<p>Introduction with <strong>bold</strong> and <em>italic</em> text.</p>' .
				'<h3>Section 1</h3>' .
				'<ul>' .
				'<li>Item with <a href="https://example.com">link</a></li>' .
				'<li><strong>Bold item</strong></li>' .
				'</ul>' .
				'<h3>Section 2</h3>' .
				'<table>' .
				'<tr><th>Header A</th><th>Header B</th></tr>' .
				'<tr><td><em>Data 1</em></td><td><strong>Data 2</strong></td></tr>' .
				'</table>' .
				'<p>Conclusion with <strong><em><u>all formatting</u></em></strong>.</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );

		$this->assertIsString( $path, 'Complex HTML should generate successfully.' );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml );

		// Verify all content.
		$this->assertStringContainsString( 'Document Title', $xml );
		$this->assertStringContainsString( 'Introduction', $xml );
		$this->assertStringContainsString( 'Section 1', $xml );
		$this->assertStringContainsString( 'Section 2', $xml );
		$this->assertStringContainsString( 'Header A', $xml );
		$this->assertStringContainsString( 'Header B', $xml );
		$this->assertStringContainsString( 'Data 1', $xml );
		$this->assertStringContainsString( 'Data 2', $xml );
		$this->assertStringContainsString( 'Conclusion', $xml );
		$this->assertStringContainsString( 'all formatting', $xml );

		$this->asserter->assertNoRawHtmlTags( $xml );
		$this->assertNoPlaceholderArtifacts( $path );
	}

	// =========================================================================
	// DOCX Mixed Formatting Tests
	// =========================================================================

	/**
	 * Test bold, italic, and underline combined in DOCX.
	 */
	public function test_docx_bold_italic_underline_combined() {
		$html = '<p>Normal <strong>bold</strong> <em>italic</em> <u>underline</u> ' .
				'<strong><em>bold-italic</em></strong> ' .
				'<strong><u>bold-underline</u></strong> ' .
				'<em><u>italic-underline</u></em> ' .
				'<strong><em><u>all three</u></em></strong></p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );

		$this->assertIsString( $path );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml );

		// Verify all text is present.
		$this->assertStringContainsString( 'Normal', $xml );
		$this->assertStringContainsString( 'bold', $xml );
		$this->assertStringContainsString( 'italic', $xml );
		$this->assertStringContainsString( 'underline', $xml );
		$this->assertStringContainsString( 'bold-italic', $xml );
		$this->assertStringContainsString( 'bold-underline', $xml );
		$this->assertStringContainsString( 'italic-underline', $xml );
		$this->assertStringContainsString( 'all three', $xml );

		// Verify OOXML formatting elements.
		$this->assertStringContainsString( '<w:b', $xml, 'Bold formatting should exist.' );
		$this->assertStringContainsString( '<w:i', $xml, 'Italic formatting should exist.' );
		$this->assertStringContainsString( '<w:u', $xml, 'Underline formatting should exist.' );

		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test formatting inside table cell in DOCX.
	 */
	public function test_docx_formatting_inside_table_cell() {
		$html = '<table><tr>' .
				'<td><strong>Bold in cell</strong></td>' .
				'<td><em>Italic in cell</em></td>' .
				'<td><a href="https://example.com">Link in cell</a></td>' .
				'</tr></table>';

		$type_data = $this->create_doc_type_with_template( 'minimal-table.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'contenido' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Bold in cell', $xml );
		$this->assertStringContainsString( 'Italic in cell', $xml );
		$this->assertStringContainsString( 'Link in cell', $xml );
		$this->assertStringContainsString( 'w:tbl', $xml, 'Table should be present.' );
		$this->assertStringContainsString( '<w:b', $xml, 'Bold formatting should be present.' );
		$this->assertStringContainsString( '<w:i', $xml, 'Italic formatting should be present.' );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test formatting inside list item in DOCX.
	 */
	public function test_docx_formatting_inside_list_item() {
		$html = '<ul>' .
				'<li><strong>Bold item</strong></li>' .
				'<li><em>Italic item</em></li>' .
				'<li><strong><em>Bold and italic item</em></strong></li>' .
				'<li>Normal with <a href="https://example.com">link</a></li>' .
				'</ul>';

		$type_data = $this->create_doc_type_with_template( 'minimal-list.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'contenido' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Bold item', $xml );
		$this->assertStringContainsString( 'Italic item', $xml );
		$this->assertStringContainsString( 'Bold and italic item', $xml );
		$this->assertStringContainsString( 'link', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test hyperlink with bold text in DOCX.
	 */
	public function test_docx_hyperlink_with_bold_text() {
		$html = '<p>Visit <a href="https://example.com"><strong>this important link</strong></a> for more info.</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Visit', $xml );
		$this->assertStringContainsString( 'this important link', $xml );
		$this->assertStringContainsString( 'for more info', $xml );
		$this->assertStringContainsString( 'w:hyperlink', $xml, 'Hyperlink element should be present.' );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test complex HTML with all features in DOCX.
	 */
	public function test_docx_complex_html_with_all_features() {
		$html = '<h2>Document Title</h2>' .
				'<p>Introduction with <strong>bold</strong> and <em>italic</em> text.</p>' .
				'<h3>Section 1</h3>' .
				'<ul>' .
				'<li>Item with <a href="https://example.com">link</a></li>' .
				'<li><strong>Bold item</strong></li>' .
				'</ul>' .
				'<h3>Section 2</h3>' .
				'<table>' .
				'<tr><th>Header A</th><th>Header B</th></tr>' .
				'<tr><td><em>Data 1</em></td><td><strong>Data 2</strong></td></tr>' .
				'</table>' .
				'<p>Conclusion with <strong><em><u>all formatting</u></em></strong>.</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );

		$this->assertIsString( $path, 'Complex HTML should generate successfully.' );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml );

		// Verify all content.
		$this->assertStringContainsString( 'Document Title', $xml );
		$this->assertStringContainsString( 'Introduction', $xml );
		$this->assertStringContainsString( 'Section 1', $xml );
		$this->assertStringContainsString( 'Section 2', $xml );
		$this->assertStringContainsString( 'Header A', $xml );
		$this->assertStringContainsString( 'Header B', $xml );
		$this->assertStringContainsString( 'Data 1', $xml );
		$this->assertStringContainsString( 'Data 2', $xml );
		$this->assertStringContainsString( 'Conclusion', $xml );
		$this->assertStringContainsString( 'all formatting', $xml );

		$this->asserter->assertNoRawHtmlTags( $xml );
		$this->assertNoPlaceholderArtifacts( $path );
	}
}
