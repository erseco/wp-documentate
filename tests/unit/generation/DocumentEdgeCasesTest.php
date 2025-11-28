<?php
/**
 * Tests for edge cases in document generation.
 *
 * Validates handling of special characters, Unicode, empty values,
 * malformed HTML, and other edge cases in ODT and DOCX formats.
 *
 * @package Documentate
 */

/**
 * Class DocumentEdgeCasesTest
 */
class DocumentEdgeCasesTest extends Documentate_Generation_Test_Base {

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
	// ODT Edge Cases
	// =========================================================================

	/**
	 * Test special XML characters are escaped in ODT.
	 */
	public function test_odt_special_xml_characters_escaped() {
		$html = '<p>Special chars: &lt; &gt; &amp; &quot; &apos;</p>' .
				'<p>Raw: < > & " \'</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );

		$this->assertIsString( $path );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml, 'XML should be valid despite special characters.' );

		// Document should still be valid XML.
		$doc = new DOMDocument();
		$this->assertTrue( $doc->loadXML( $xml ), 'Document XML should be parseable.' );
	}

	/**
	 * Test Unicode characters in ODT.
	 */
	public function test_odt_unicode_characters() {
		$html = '<p>Unicode test: ' .
				'Spanish: ñ á é í ó ú ü ' .
				'French: é è ê ë ç ' .
				'German: ä ö ü ß ' .
				'Greek: α β γ δ ' .
				'Cyrillic: а б в г ' .
				'Chinese: 中文测试 ' .
				'Japanese: 日本語 ' .
				'Emoji: 😀 🎉 ✓</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'ñ', $xml );
		$this->assertStringContainsString( 'α', $xml );
		$this->assertStringContainsString( '中文', $xml );
	}

	/**
	 * Test very long text content in ODT.
	 */
	public function test_odt_very_long_text() {
		$long_text = str_repeat( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 500 );
		$html      = '<p>' . $long_text . '</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );

		$this->assertIsString( $path, 'Long text should generate successfully.' );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Lorem ipsum', $xml );
	}

	/**
	 * Test empty field values in ODT.
	 */
	public function test_odt_empty_field_values() {
		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data(
			$type_data['term_id'],
			array(
				'name'  => '',
				'email' => '',
				'body'  => '',
			)
		);

		$path = $this->generate_document( $post_id, 'odt' );

		$this->assertIsString( $path );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml );

		// Placeholders should not remain.
		$this->assertStringNotContainsString( '[name]', $xml );
		$this->assertStringNotContainsString( '[email]', $xml );
		$this->assertStringNotContainsString( '[body]', $xml );
	}

	/**
	 * Test malformed HTML handling in ODT.
	 */
	public function test_odt_malformed_html_handling() {
		// Unclosed tags, nested incorrectly, etc.
		$html = '<p>Unclosed paragraph' .
				'<strong>Unclosed bold' .
				'<em>Nested wrong</strong></em>' .
				'<div><span>Mixed up</div></span>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );

		// Should still generate a document, even if HTML is malformed.
		$this->assertIsString( $path, 'Malformed HTML should not crash generation.' );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml, 'Output should still be valid XML.' );
	}

	/**
	 * Test HTML entities handling in ODT.
	 */
	public function test_odt_html_entities() {
		$html = '<p>&copy; 2024 Company &reg; &trade;</p>' .
				'<p>&nbsp; &mdash; &ndash; &hellip;</p>' .
				'<p>&euro; &pound; &yen;</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		// Entities should be converted to their character equivalents.
		$this->assertStringContainsString( '2024', $xml );
	}

	/**
	 * Test whitespace preservation in ODT.
	 */
	public function test_odt_whitespace_handling() {
		$html = "<p>Multiple   spaces   here</p>\n" .
				"<p>Line\nbreaks\nhere</p>\n" .
				"<p>Tab\there</p>";

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );

		$this->assertIsString( $path );
		$this->assertFileExists( $path );
	}

	// =========================================================================
	// DOCX Edge Cases
	// =========================================================================

	/**
	 * Test special XML characters are escaped in DOCX.
	 */
	public function test_docx_special_xml_characters_escaped() {
		$html = '<p>Special chars: &lt; &gt; &amp; &quot; &apos;</p>' .
				'<p>Raw: < > & " \'</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );

		$this->assertIsString( $path );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml, 'XML should be valid despite special characters.' );

		// Document should still be valid XML.
		$doc = new DOMDocument();
		$this->assertTrue( $doc->loadXML( $xml ), 'Document XML should be parseable.' );
	}

	/**
	 * Test Unicode characters in DOCX.
	 */
	public function test_docx_unicode_characters() {
		$html = '<p>Unicode test: ' .
				'Spanish: ñ á é í ó ú ü ' .
				'French: é è ê ë ç ' .
				'German: ä ö ü ß ' .
				'Greek: α β γ δ ' .
				'Cyrillic: а б в г ' .
				'Chinese: 中文测试 ' .
				'Japanese: 日本語 ' .
				'Emoji: 😀 🎉 ✓</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'ñ', $xml );
		$this->assertStringContainsString( 'α', $xml );
		$this->assertStringContainsString( '中文', $xml );
	}

	/**
	 * Test very long text content in DOCX.
	 */
	public function test_docx_very_long_text() {
		$long_text = str_repeat( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 500 );
		$html      = '<p>' . $long_text . '</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );

		$this->assertIsString( $path, 'Long text should generate successfully.' );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Lorem ipsum', $xml );
	}

	/**
	 * Test empty field values in DOCX.
	 */
	public function test_docx_empty_field_values() {
		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data(
			$type_data['term_id'],
			array(
				'name'  => '',
				'email' => '',
				'body'  => '',
			)
		);

		$path = $this->generate_document( $post_id, 'docx' );

		$this->assertIsString( $path );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml );

		// Placeholders should not remain.
		$this->assertStringNotContainsString( '[name]', $xml );
		$this->assertStringNotContainsString( '[email]', $xml );
		$this->assertStringNotContainsString( '[body]', $xml );
	}

	/**
	 * Test malformed HTML handling in DOCX.
	 */
	public function test_docx_malformed_html_handling() {
		// Unclosed tags, nested incorrectly, etc.
		$html = '<p>Unclosed paragraph' .
				'<strong>Unclosed bold' .
				'<em>Nested wrong</strong></em>' .
				'<div><span>Mixed up</div></span>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );

		// Should still generate a document, even if HTML is malformed.
		$this->assertIsString( $path, 'Malformed HTML should not crash generation.' );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml, 'Output should still be valid XML.' );
	}

	/**
	 * Test HTML entities handling in DOCX.
	 */
	public function test_docx_html_entities() {
		$html = '<p>&copy; 2024 Company &reg; &trade;</p>' .
				'<p>&nbsp; &mdash; &ndash; &hellip;</p>' .
				'<p>&euro; &pound; &yen;</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		// Entities should be converted to their character equivalents.
		$this->assertStringContainsString( '2024', $xml );
	}

	/**
	 * Test whitespace preservation in DOCX.
	 */
	public function test_docx_whitespace_handling() {
		$html = "<p>Multiple   spaces   here</p>\n" .
				"<p>Line\nbreaks\nhere</p>\n" .
				"<p>Tab\there</p>";

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );

		$this->assertIsString( $path );
		$this->assertFileExists( $path );
	}

	// =========================================================================
	// Additional Edge Cases - Deeply Nested Structures
	// =========================================================================

	/**
	 * Test deeply nested list structures in ODT.
	 */
	public function test_odt_deeply_nested_lists() {
		$html = '<ul>' .
				'<li>Level 1' .
					'<ul><li>Level 2' .
						'<ul><li>Level 3' .
							'<ul><li>Level 4' .
								'<ul><li>Level 5</li></ul>' .
							'</li></ul>' .
						'</li></ul>' .
					'</li></ul>' .
				'</li>' .
				'</ul>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Level 1', $xml );
		$this->assertStringContainsString( 'Level 5', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test deeply nested list structures in DOCX.
	 */
	public function test_docx_deeply_nested_lists() {
		$html = '<ul>' .
				'<li>Level 1' .
					'<ul><li>Level 2' .
						'<ul><li>Level 3' .
							'<ul><li>Level 4' .
								'<ul><li>Level 5</li></ul>' .
							'</li></ul>' .
						'</li></ul>' .
					'</li></ul>' .
				'</li>' .
				'</ul>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Level 1', $xml );
		$this->assertStringContainsString( 'Level 5', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test deeply nested formatting in ODT.
	 */
	public function test_odt_deeply_nested_formatting() {
		$html = '<p><strong><em><u><strong><em>Deeply nested formatting</em></strong></u></em></strong></p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Deeply nested formatting', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test deeply nested formatting in DOCX.
	 */
	public function test_docx_deeply_nested_formatting() {
		$html = '<p><strong><em><u><strong><em>Deeply nested formatting</em></strong></u></em></strong></p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Deeply nested formatting', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	// =========================================================================
	// Additional Edge Cases - XSS and Sanitization
	// =========================================================================

	/**
	 * Test XSS attempts are sanitized in ODT.
	 */
	public function test_odt_xss_sanitization() {
		$html = '<p>Normal text</p>' .
				'<script>alert("XSS")</script>' .
				'<p onclick="alert(1)">Click me</p>' .
				'<img src="x" onerror="alert(1)">' .
				'<a href="javascript:alert(1)">Link</a>' .
				'<style>body { display: none; }</style>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Normal text', $xml );
		// Script and dangerous content should be stripped.
		$this->assertStringNotContainsString( '<script', $xml );
		$this->assertStringNotContainsString( 'onclick', $xml );
		$this->assertStringNotContainsString( 'onerror', $xml );
		$this->assertStringNotContainsString( 'javascript:', $xml );
	}

	/**
	 * Test XSS attempts are sanitized in DOCX.
	 */
	public function test_docx_xss_sanitization() {
		$html = '<p>Normal text</p>' .
				'<script>alert("XSS")</script>' .
				'<p onclick="alert(1)">Click me</p>' .
				'<img src="x" onerror="alert(1)">' .
				'<a href="javascript:alert(1)">Link</a>' .
				'<style>body { display: none; }</style>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Normal text', $xml );
		// Script and dangerous content should be stripped.
		$this->assertStringNotContainsString( '<script', $xml );
		$this->assertStringNotContainsString( 'onclick', $xml );
		$this->assertStringNotContainsString( 'onerror', $xml );
		$this->assertStringNotContainsString( 'javascript:', $xml );
	}

	// =========================================================================
	// Additional Edge Cases - Special Content
	// =========================================================================

	/**
	 * Test RTL (right-to-left) text in ODT.
	 */
	public function test_odt_rtl_text() {
		$html = '<p>English text</p>' .
				'<p>Hebrew: שלום עולם</p>' .
				'<p>Arabic: مرحبا بالعالم</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'English text', $xml );
		$this->assertStringContainsString( 'שלום', $xml );
		$this->assertStringContainsString( 'مرحبا', $xml );
	}

	/**
	 * Test RTL (right-to-left) text in DOCX.
	 */
	public function test_docx_rtl_text() {
		$html = '<p>English text</p>' .
				'<p>Hebrew: שלום עולם</p>' .
				'<p>Arabic: مرحبا بالعالم</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'English text', $xml );
		$this->assertStringContainsString( 'שלום', $xml );
		$this->assertStringContainsString( 'مرحبا', $xml );
	}

	/**
	 * Test mathematical and technical symbols in ODT.
	 */
	public function test_odt_mathematical_symbols() {
		$html = '<p>Math: ∑ ∏ ∫ ∂ √ ∞ ≈ ≠ ≤ ≥ ± × ÷</p>' .
				'<p>Greek: α β γ δ ε ζ η θ λ μ π σ φ ω</p>' .
				'<p>Arrows: ← → ↑ ↓ ↔ ⇐ ⇒</p>' .
				'<p>Fractions: ½ ¼ ¾ ⅓ ⅔</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( '∑', $xml );
		$this->assertStringContainsString( '∞', $xml );
		$this->assertStringContainsString( '½', $xml );
	}

	/**
	 * Test mathematical and technical symbols in DOCX.
	 */
	public function test_docx_mathematical_symbols() {
		$html = '<p>Math: ∑ ∏ ∫ ∂ √ ∞ ≈ ≠ ≤ ≥ ± × ÷</p>' .
				'<p>Greek: α β γ δ ε ζ η θ λ μ π σ φ ω</p>' .
				'<p>Arrows: ← → ↑ ↓ ↔ ⇐ ⇒</p>' .
				'<p>Fractions: ½ ¼ ¾ ⅓ ⅔</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( '∑', $xml );
		$this->assertStringContainsString( '∞', $xml );
		$this->assertStringContainsString( '½', $xml );
	}

	// =========================================================================
	// Additional Edge Cases - Empty and Minimal Content
	// =========================================================================

	/**
	 * Test empty HTML tags in ODT.
	 */
	public function test_odt_empty_html_tags() {
		$html = '<p></p>' .
				'<ul></ul>' .
				'<table></table>' .
				'<strong></strong>' .
				'<em></em>' .
				'<p>Actual content</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Actual content', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test empty HTML tags in DOCX.
	 */
	public function test_docx_empty_html_tags() {
		$html = '<p></p>' .
				'<ul></ul>' .
				'<table></table>' .
				'<strong></strong>' .
				'<em></em>' .
				'<p>Actual content</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Actual content', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test single character content in ODT.
	 */
	public function test_odt_single_character_content() {
		$html = '<p>X-single</p>' .
				'<ul><li>Y-bullet</li></ul>' .
				'<table><tr><td>Z-cell</td></tr></table>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		// All single character identifiers should be present.
		$this->assertStringContainsString( 'X-single', $xml );
		$this->assertStringContainsString( 'Y-bullet', $xml );
		$this->assertStringContainsString( 'Z-cell', $xml );
	}

	/**
	 * Test single character content in DOCX.
	 */
	public function test_docx_single_character_content() {
		$html = '<p>X-single</p>' .
				'<ul><li>Y-bullet</li></ul>' .
				'<table><tr><td>Z-cell</td></tr></table>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		// All single character identifiers should be present.
		$this->assertStringContainsString( 'X-single', $xml );
		$this->assertStringContainsString( 'Y-bullet', $xml );
		$this->assertStringContainsString( 'Z-cell', $xml );
	}

	// =========================================================================
	// Additional Edge Cases - Boundary Conditions
	// =========================================================================

	/**
	 * Test content starting with special characters in ODT.
	 */
	public function test_odt_content_starting_with_special_chars() {
		$html = '<p>!@#$%^&*() Starting with special chars</p>' .
				'<p>12345 Starting with numbers</p>' .
				'<p>   Starting with spaces</p>' .
				'<p>' . "\n" . 'Starting with newline</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Starting with special chars', $xml );
		$this->assertStringContainsString( 'Starting with numbers', $xml );
	}

	/**
	 * Test content starting with special characters in DOCX.
	 */
	public function test_docx_content_starting_with_special_chars() {
		$html = '<p>!@#$%^&*() Starting with special chars</p>' .
				'<p>12345 Starting with numbers</p>' .
				'<p>   Starting with spaces</p>' .
				'<p>' . "\n" . 'Starting with newline</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Starting with special chars', $xml );
		$this->assertStringContainsString( 'Starting with numbers', $xml );
	}

	/**
	 * Test mixed list types alternating in ODT.
	 */
	public function test_odt_alternating_list_types() {
		$html = '<ol><li>First ordered</li></ol>' .
				'<ul><li>First unordered</li></ul>' .
				'<ol><li>Second ordered</li></ol>' .
				'<ul><li>Second unordered</li></ul>' .
				'<ol><li>Third ordered</li></ol>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'First ordered', $xml );
		$this->assertStringContainsString( 'First unordered', $xml );
		$this->assertStringContainsString( 'Third ordered', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test mixed list types alternating in DOCX.
	 */
	public function test_docx_alternating_list_types() {
		$html = '<ol><li>First ordered</li></ol>' .
				'<ul><li>First unordered</li></ul>' .
				'<ol><li>Second ordered</li></ol>' .
				'<ul><li>Second unordered</li></ul>' .
				'<ol><li>Third ordered</li></ol>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'First ordered', $xml );
		$this->assertStringContainsString( 'First unordered', $xml );
		$this->assertStringContainsString( 'Third ordered', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test table with single cell in ODT.
	 *
	 * Single-cell tables may be simplified to plain text, which is acceptable.
	 */
	public function test_odt_single_cell_table() {
		$html = '<table><tr><td>Only cell</td></tr></table>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		// Content must be preserved (table structure may be simplified).
		$this->assertStringContainsString( 'Only cell', $xml );
	}

	/**
	 * Test table with single cell in DOCX.
	 *
	 * Single-cell tables may be simplified to plain text, which is acceptable.
	 */
	public function test_docx_single_cell_table() {
		$html = '<table><tr><td>Only cell</td></tr></table>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		// Content must be preserved (table structure may be simplified).
		$this->assertStringContainsString( 'Only cell', $xml );
	}

	/**
	 * Test list with single item in ODT.
	 */
	public function test_odt_single_item_list() {
		$html = '<ul><li>Only item</li></ul>' .
				'<ol><li>Only numbered</li></ol>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Only item', $xml );
		$this->assertStringContainsString( 'Only numbered', $xml );
	}

	/**
	 * Test list with single item in DOCX.
	 */
	public function test_docx_single_item_list() {
		$html = '<ul><li>Only item</li></ul>' .
				'<ol><li>Only numbered</li></ol>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Only item', $xml );
		$this->assertStringContainsString( 'Only numbered', $xml );
	}

	// =========================================================================
	// Additional Edge Cases - Complex Combinations
	// =========================================================================

	/**
	 * Test table inside list in ODT.
	 */
	public function test_odt_table_inside_list() {
		$html = '<ul>' .
				'<li>Item with table:' .
					'<table><tr><td>Cell A</td><td>Cell B</td></tr></table>' .
				'</li>' .
				'<li>Normal item</li>' .
				'</ul>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Item with table', $xml );
		$this->assertStringContainsString( 'Cell A', $xml );
		$this->assertStringContainsString( 'Normal item', $xml );
	}

	/**
	 * Test table inside list in DOCX.
	 */
	public function test_docx_table_inside_list() {
		$html = '<ul>' .
				'<li>Item with table:' .
					'<table><tr><td>Cell A</td><td>Cell B</td></tr></table>' .
				'</li>' .
				'<li>Normal item</li>' .
				'</ul>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Item with table', $xml );
		$this->assertStringContainsString( 'Cell A', $xml );
		$this->assertStringContainsString( 'Normal item', $xml );
	}

	/**
	 * Test multiple links in paragraph in ODT.
	 */
	public function test_odt_multiple_links_in_paragraph() {
		$html = '<p>Visit <a href="https://example.com">Example</a>, ' .
				'<a href="https://test.org">Test</a>, and ' .
				'<a href="https://demo.net">Demo</a> sites.</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Example', $xml );
		$this->assertStringContainsString( 'Test', $xml );
		$this->assertStringContainsString( 'Demo', $xml );
		$this->assertStringContainsString( 'example.com', $xml );
	}

	/**
	 * Test multiple links in paragraph in DOCX.
	 */
	public function test_docx_multiple_links_in_paragraph() {
		$html = '<p>Visit <a href="https://example.com">Example</a>, ' .
				'<a href="https://test.org">Test</a>, and ' .
				'<a href="https://demo.net">Demo</a> sites.</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Example', $xml );
		$this->assertStringContainsString( 'Test', $xml );
		$this->assertStringContainsString( 'Demo', $xml );
	}

	/**
	 * Test content with only whitespace between tags in ODT.
	 */
	public function test_odt_whitespace_only_between_tags() {
		$html = '<p>First</p>     <p>Second</p>' . "\n\n\n" . '<p>Third</p>' . "\t\t" . '<p>Fourth</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'First', $xml );
		$this->assertStringContainsString( 'Second', $xml );
		$this->assertStringContainsString( 'Third', $xml );
		$this->assertStringContainsString( 'Fourth', $xml );
	}

	/**
	 * Test content with only whitespace between tags in DOCX.
	 */
	public function test_docx_whitespace_only_between_tags() {
		$html = '<p>First</p>     <p>Second</p>' . "\n\n\n" . '<p>Third</p>' . "\t\t" . '<p>Fourth</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'First', $xml );
		$this->assertStringContainsString( 'Second', $xml );
		$this->assertStringContainsString( 'Third', $xml );
		$this->assertStringContainsString( 'Fourth', $xml );
	}

	/**
	 * Test numeric HTML entities in ODT.
	 */
	public function test_odt_numeric_html_entities() {
		$html = '<p>Numeric entities: &#169; &#174; &#8364; &#x00A9; &#x00AE;</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Numeric entities', $xml );
	}

	/**
	 * Test numeric HTML entities in DOCX.
	 */
	public function test_docx_numeric_html_entities() {
		$html = '<p>Numeric entities: &#169; &#174; &#8364; &#x00A9; &#x00AE;</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Numeric entities', $xml );
	}

	/**
	 * Test zero-width characters in ODT.
	 */
	public function test_odt_zero_width_characters() {
		// Zero-width space, zero-width non-joiner, zero-width joiner.
		$html = '<p>Hidden' . "\u{200B}" . 'chars' . "\u{200C}" . 'here' . "\u{200D}" . 'test</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		// The text should still be readable (zero-width chars are invisible).
		$this->assertStringContainsString( 'Hidden', $xml );
		$this->assertStringContainsString( 'test', $xml );
	}

	/**
	 * Test zero-width characters in DOCX.
	 */
	public function test_docx_zero_width_characters() {
		// Zero-width space, zero-width non-joiner, zero-width joiner.
		$html = '<p>Hidden' . "\u{200B}" . 'chars' . "\u{200C}" . 'here' . "\u{200D}" . 'test</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		// The text should still be readable (zero-width chars are invisible).
		$this->assertStringContainsString( 'Hidden', $xml );
		$this->assertStringContainsString( 'test', $xml );
	}

	// =========================================================================
	// OpenTBS-Specific Edge Cases - Typographic Characters
	// =========================================================================

	/**
	 * Test typographic apostrophes in ODT.
	 *
	 * OpenTBS converts typographic apostrophes (') to regular quotes (').
	 */
	public function test_odt_typographic_apostrophes() {
		// Using both regular and typographic apostrophes.
		$html = "<p>It's working with regular apostrophe</p>" .
				"<p>It's working with typographic apostrophe</p>" .
				"<p>Don't, won't, can't - contractions</p>";

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'working', $xml );
		$this->assertStringContainsString( 'contractions', $xml );
	}

	/**
	 * Test typographic apostrophes in DOCX.
	 */
	public function test_docx_typographic_apostrophes() {
		$html = "<p>It's working with regular apostrophe</p>" .
				"<p>It's working with typographic apostrophe</p>" .
				"<p>Don't, won't, can't - contractions</p>";

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'working', $xml );
		$this->assertStringContainsString( 'contractions', $xml );
	}

	/**
	 * Test typographic quotes in ODT.
	 */
	public function test_odt_typographic_quotes() {
		// Both straight and curly quotes.
		$html = '<p>"Straight quotes" and "curly quotes"</p>' .
				"<p>'Single straight' and 'single curly'</p>" .
				'<p>«Guillemets» and „German quotes"</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Straight quotes', $xml );
		$this->assertStringContainsString( 'curly quotes', $xml );
		$this->assertStringContainsString( 'Guillemets', $xml );
	}

	/**
	 * Test typographic quotes in DOCX.
	 */
	public function test_docx_typographic_quotes() {
		$html = '<p>"Straight quotes" and "curly quotes"</p>' .
				"<p>'Single straight' and 'single curly'</p>" .
				'<p>«Guillemets» and „German quotes"</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Straight quotes', $xml );
		$this->assertStringContainsString( 'curly quotes', $xml );
		$this->assertStringContainsString( 'Guillemets', $xml );
	}

	/**
	 * Test dashes and ellipsis in ODT.
	 */
	public function test_odt_dashes_and_ellipsis() {
		$html = '<p>Em-dash: text—more text</p>' .
				'<p>En-dash: 2020–2024</p>' .
				'<p>Ellipsis: Wait… for it</p>' .
				'<p>Three dots: Wait... for it</p>' .
				'<p>Hyphen: self-service</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Em-dash', $xml );
		$this->assertStringContainsString( 'En-dash', $xml );
		$this->assertStringContainsString( 'Ellipsis', $xml );
		$this->assertStringContainsString( 'self-service', $xml );
	}

	/**
	 * Test dashes and ellipsis in DOCX.
	 */
	public function test_docx_dashes_and_ellipsis() {
		$html = '<p>Em-dash: text—more text</p>' .
				'<p>En-dash: 2020–2024</p>' .
				'<p>Ellipsis: Wait… for it</p>' .
				'<p>Three dots: Wait... for it</p>' .
				'<p>Hyphen: self-service</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Em-dash', $xml );
		$this->assertStringContainsString( 'En-dash', $xml );
		$this->assertStringContainsString( 'Ellipsis', $xml );
		$this->assertStringContainsString( 'self-service', $xml );
	}

	// =========================================================================
	// OpenTBS-Specific Edge Cases - Line Breaks and Spacing
	// =========================================================================

	/**
	 * Test multiple br tags in ODT.
	 */
	public function test_odt_multiple_br_tags() {
		$html = '<p>Line one<br>Line two<br><br>After double break<br><br><br>After triple</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Line one', $xml );
		$this->assertStringContainsString( 'Line two', $xml );
		$this->assertStringContainsString( 'After double break', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test multiple br tags in DOCX.
	 */
	public function test_docx_multiple_br_tags() {
		$html = '<p>Line one<br>Line two<br><br>After double break<br><br><br>After triple</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Line one', $xml );
		$this->assertStringContainsString( 'Line two', $xml );
		$this->assertStringContainsString( 'After double break', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test br tags in table cells in ODT.
	 */
	public function test_odt_br_in_table_cells() {
		$html = '<table>' .
				'<tr><td>Cell with<br>line break</td><td>Normal cell</td></tr>' .
				'<tr><td>Multiple<br><br>breaks</td><td>End</td></tr>' .
				'</table>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Cell with', $xml );
		$this->assertStringContainsString( 'line break', $xml );
		$this->assertStringContainsString( 'Normal cell', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test br tags in table cells in DOCX.
	 */
	public function test_docx_br_in_table_cells() {
		$html = '<table>' .
				'<tr><td>Cell with<br>line break</td><td>Normal cell</td></tr>' .
				'<tr><td>Multiple<br><br>breaks</td><td>End</td></tr>' .
				'</table>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Cell with', $xml );
		$this->assertStringContainsString( 'line break', $xml );
		$this->assertStringContainsString( 'Normal cell', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test non-breaking space handling in ODT.
	 */
	public function test_odt_nbsp_handling() {
		$html = '<p>Text&nbsp;with&nbsp;nbsp&nbsp;spaces</p>' .
				'<p>Multiple&nbsp;&nbsp;&nbsp;nbsp together</p>' .
				'<p>Mixed normal and&nbsp;nbsp</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Text', $xml );
		$this->assertStringContainsString( 'nbsp', $xml );
		$this->assertStringContainsString( 'Mixed', $xml );
	}

	/**
	 * Test non-breaking space handling in DOCX.
	 */
	public function test_docx_nbsp_handling() {
		$html = '<p>Text&nbsp;with&nbsp;nbsp&nbsp;spaces</p>' .
				'<p>Multiple&nbsp;&nbsp;&nbsp;nbsp together</p>' .
				'<p>Mixed normal and&nbsp;nbsp</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Text', $xml );
		$this->assertStringContainsString( 'nbsp', $xml );
		$this->assertStringContainsString( 'Mixed', $xml );
	}

	// =========================================================================
	// OpenTBS-Specific Edge Cases - XML-like Content
	// =========================================================================

	/**
	 * Test angle brackets in text in ODT.
	 */
	public function test_odt_angle_brackets_in_text() {
		$html = '<p>Comparison: 5 &lt; 10 &gt; 2</p>' .
				'<p>Not a tag: &lt;not-a-tag&gt;</p>' .
				'<p>Generic: List&lt;String&gt;</p>' .
				'<p>Arrow: -&gt; and &lt;-</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Comparison', $xml );
		$this->assertStringContainsString( 'Generic', $xml );
		// Document should remain valid XML.
		$doc = new DOMDocument();
		$this->assertTrue( $doc->loadXML( $xml ) );
	}

	/**
	 * Test angle brackets in text in DOCX.
	 */
	public function test_docx_angle_brackets_in_text() {
		$html = '<p>Comparison: 5 &lt; 10 &gt; 2</p>' .
				'<p>Not a tag: &lt;not-a-tag&gt;</p>' .
				'<p>Generic: List&lt;String&gt;</p>' .
				'<p>Arrow: -&gt; and &lt;-</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Comparison', $xml );
		$this->assertStringContainsString( 'Generic', $xml );
		// Document should remain valid XML.
		$doc = new DOMDocument();
		$this->assertTrue( $doc->loadXML( $xml ) );
	}

	/**
	 * Test HTML comments in content in ODT.
	 *
	 * HTML comments may be stripped or preserved as escaped text.
	 * Either behavior is acceptable as long as document generates correctly.
	 */
	public function test_odt_html_comments() {
		$html = '<p>Before comment</p>' .
				'<!-- This is an HTML comment -->' .
				'<p>After comment</p>' .
				'<p>Final text</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		// Content around comments should be present.
		$this->assertStringContainsString( 'Before comment', $xml );
		$this->assertStringContainsString( 'After comment', $xml );
		$this->assertStringContainsString( 'Final text', $xml );
		// Document should remain valid XML.
		$doc = new DOMDocument();
		$this->assertTrue( $doc->loadXML( $xml ) );
	}

	/**
	 * Test HTML comments in content in DOCX.
	 *
	 * HTML comments may be stripped or preserved as escaped text.
	 * Either behavior is acceptable as long as document generates correctly.
	 */
	public function test_docx_html_comments() {
		$html = '<p>Before comment</p>' .
				'<!-- This is an HTML comment -->' .
				'<p>After comment</p>' .
				'<p>Final text</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		// Content around comments should be present.
		$this->assertStringContainsString( 'Before comment', $xml );
		$this->assertStringContainsString( 'After comment', $xml );
		$this->assertStringContainsString( 'Final text', $xml );
		// Document should remain valid XML.
		$doc = new DOMDocument();
		$this->assertTrue( $doc->loadXML( $xml ) );
	}

	// =========================================================================
	// OpenTBS-Specific Edge Cases - Long Content
	// =========================================================================

	/**
	 * Test very long URL in ODT.
	 */
	public function test_odt_very_long_url() {
		$long_url = 'https://example.com/path/' . str_repeat( 'segment/', 50 ) . 'end';
		$html     = '<p>Short URL: <a href="https://example.com">Link</a></p>' .
					'<p>Long URL: <a href="' . esc_attr( $long_url ) . '">Very long link</a></p>' .
					'<p>URL as text: ' . esc_html( $long_url ) . '</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Short URL', $xml );
		$this->assertStringContainsString( 'Very long link', $xml );
	}

	/**
	 * Test very long URL in DOCX.
	 */
	public function test_docx_very_long_url() {
		$long_url = 'https://example.com/path/' . str_repeat( 'segment/', 50 ) . 'end';
		$html     = '<p>Short URL: <a href="https://example.com">Link</a></p>' .
					'<p>Long URL: <a href="' . esc_attr( $long_url ) . '">Very long link</a></p>' .
					'<p>URL as text: ' . esc_html( $long_url ) . '</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Short URL', $xml );
		$this->assertStringContainsString( 'Very long link', $xml );
	}

	/**
	 * Test repeated characters in ODT.
	 */
	public function test_odt_repeated_characters() {
		$html = '<p>Repeated a: ' . str_repeat( 'a', 500 ) . '</p>' .
				'<p>Repeated dash: ' . str_repeat( '-', 100 ) . '</p>' .
				'<p>Repeated emoji: ' . str_repeat( '😀', 50 ) . '</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Repeated a', $xml );
		$this->assertStringContainsString( 'Repeated dash', $xml );
	}

	/**
	 * Test repeated characters in DOCX.
	 */
	public function test_docx_repeated_characters() {
		$html = '<p>Repeated a: ' . str_repeat( 'a', 500 ) . '</p>' .
				'<p>Repeated dash: ' . str_repeat( '-', 100 ) . '</p>' .
				'<p>Repeated emoji: ' . str_repeat( '😀', 50 ) . '</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Repeated a', $xml );
		$this->assertStringContainsString( 'Repeated dash', $xml );
	}

	// =========================================================================
	// OpenTBS-Specific Edge Cases - Emojis
	// =========================================================================

	/**
	 * Test basic emojis in ODT.
	 */
	public function test_odt_basic_emojis() {
		$html = '<p>Faces: 😀 😃 😄 😁 😆 😅 🤣 😂</p>' .
				'<p>Hearts: ❤️ 🧡 💛 💚 💙 💜</p>' .
				'<p>Symbols: ✅ ❌ ⭐ 🔥 💡 ⚡</p>' .
				'<p>Flags: 🇺🇸 🇬🇧 🇪🇸 🇫🇷 🇩🇪</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Faces', $xml );
		$this->assertStringContainsString( 'Hearts', $xml );
		$this->assertStringContainsString( 'Symbols', $xml );
	}

	/**
	 * Test basic emojis in DOCX.
	 */
	public function test_docx_basic_emojis() {
		$html = '<p>Faces: 😀 😃 😄 😁 😆 😅 🤣 😂</p>' .
				'<p>Hearts: ❤️ 🧡 💛 💚 💙 💜</p>' .
				'<p>Symbols: ✅ ❌ ⭐ 🔥 💡 ⚡</p>' .
				'<p>Flags: 🇺🇸 🇬🇧 🇪🇸 🇫🇷 🇩🇪</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Faces', $xml );
		$this->assertStringContainsString( 'Hearts', $xml );
		$this->assertStringContainsString( 'Symbols', $xml );
	}

	// =========================================================================
	// OpenTBS-Specific Edge Cases - Bidirectional Text
	// =========================================================================

	/**
	 * Test mixed LTR and RTL in same paragraph in ODT.
	 */
	public function test_odt_mixed_ltr_rtl() {
		$html = '<p>English שלום English again</p>' .
				'<p>Numbers in RTL: שלום 123 עולם</p>' .
				'<p>Punctuation: "שלום" - (עולם)!</p>' .
				'<p>Mixed Arabic: Hello مرحبا World</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'English', $xml );
		$this->assertStringContainsString( 'שלום', $xml );
		$this->assertStringContainsString( 'مرحبا', $xml );
	}

	/**
	 * Test mixed LTR and RTL in same paragraph in DOCX.
	 */
	public function test_docx_mixed_ltr_rtl() {
		$html = '<p>English שלום English again</p>' .
				'<p>Numbers in RTL: שלום 123 עולם</p>' .
				'<p>Punctuation: "שלום" - (עולם)!</p>' .
				'<p>Mixed Arabic: Hello مرحبا World</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'English', $xml );
		$this->assertStringContainsString( 'שלום', $xml );
		$this->assertStringContainsString( 'مرحبا', $xml );
	}

	// =========================================================================
	// OpenTBS-Specific Edge Cases - Control Characters
	// =========================================================================

	/**
	 * Test soft hyphens in ODT.
	 */
	public function test_odt_soft_hyphens() {
		// Soft hyphen: ­ (U+00AD).
		$html = '<p>Hyphen­ation with soft hyphens</p>' .
				'<p>Anti­dis­estab­lish­ment­ari­an­ism</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		// Content should be present (soft hyphens may or may not be preserved).
		$this->assertStringContainsString( 'Hyphen', $xml );
	}

	/**
	 * Test soft hyphens in DOCX.
	 */
	public function test_docx_soft_hyphens() {
		$html = '<p>Hyphen­ation with soft hyphens</p>' .
				'<p>Anti­dis­estab­lish­ment­ari­an­ism</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Hyphen', $xml );
	}

	/**
	 * Test word joiner character in ODT.
	 */
	public function test_odt_word_joiner() {
		// Word joiner: U+2060.
		$html = '<p>Word' . "\u{2060}" . 'joiner test</p>' .
				'<p>Normal word break test</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Word', $xml );
		$this->assertStringContainsString( 'Normal', $xml );
	}

	/**
	 * Test word joiner character in DOCX.
	 */
	public function test_docx_word_joiner() {
		$html = '<p>Word' . "\u{2060}" . 'joiner test</p>' .
				'<p>Normal word break test</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Word', $xml );
		$this->assertStringContainsString( 'Normal', $xml );
	}

	// =========================================================================
	// OpenTBS-Specific Edge Cases - Double Escapes and Entities
	// =========================================================================

	/**
	 * Test double escaped entities in ODT.
	 */
	public function test_odt_double_escaped_entities() {
		$html = '<p>Double amp: &amp;amp;</p>' .
				'<p>Double lt: &amp;lt;</p>' .
				'<p>Double gt: &amp;gt;</p>' .
				'<p>Double nbsp: &amp;nbsp;</p>' .
				'<p>Entity as text: the &amp;copy; entity</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Double amp', $xml );
		$this->assertStringContainsString( 'Entity as text', $xml );
		// Document should remain valid XML.
		$doc = new DOMDocument();
		$this->assertTrue( $doc->loadXML( $xml ) );
	}

	/**
	 * Test double escaped entities in DOCX.
	 */
	public function test_docx_double_escaped_entities() {
		$html = '<p>Double amp: &amp;amp;</p>' .
				'<p>Double lt: &amp;lt;</p>' .
				'<p>Double gt: &amp;gt;</p>' .
				'<p>Double nbsp: &amp;nbsp;</p>' .
				'<p>Entity as text: the &amp;copy; entity</p>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Double amp', $xml );
		$this->assertStringContainsString( 'Entity as text', $xml );
		// Document should remain valid XML.
		$doc = new DOMDocument();
		$this->assertTrue( $doc->loadXML( $xml ) );
	}

	// =========================================================================
	// OpenTBS-Specific Edge Cases - Boundary Structures
	// =========================================================================

	/**
	 * Test table with empty header row in ODT.
	 */
	public function test_odt_table_with_empty_header() {
		$html = '<table>' .
				'<tr><th></th><th></th></tr>' .
				'<tr><td>Data 1</td><td>Data 2</td></tr>' .
				'</table>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Data 1', $xml );
		$this->assertStringContainsString( 'Data 2', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test table with empty header row in DOCX.
	 */
	public function test_docx_table_with_empty_header() {
		$html = '<table>' .
				'<tr><th></th><th></th></tr>' .
				'<tr><td>Data 1</td><td>Data 2</td></tr>' .
				'</table>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Data 1', $xml );
		$this->assertStringContainsString( 'Data 2', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test list with empty items in ODT.
	 */
	public function test_odt_list_with_empty_items() {
		$html = '<ul>' .
				'<li></li>' .
				'<li>Non-empty item</li>' .
				'<li></li>' .
				'<li>Another item</li>' .
				'<li></li>' .
				'</ul>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.odt' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Non-empty item', $xml );
		$this->assertStringContainsString( 'Another item', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}

	/**
	 * Test list with empty items in DOCX.
	 */
	public function test_docx_list_with_empty_items() {
		$html = '<ul>' .
				'<li></li>' .
				'<li>Non-empty item</li>' .
				'<li></li>' .
				'<li>Another item</li>' .
				'<li></li>' .
				'</ul>';

		$type_data = $this->create_doc_type_with_template( 'minimal-scalar.docx' );
		$post_id   = $this->create_document_with_data( $type_data['term_id'], array( 'body' => $html ) );

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Non-empty item', $xml );
		$this->assertStringContainsString( 'Another item', $xml );
		$this->asserter->assertNoRawHtmlTags( $xml );
	}
}
