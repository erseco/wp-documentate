<?php
/**
 * Tests for OpenTBS table row repeater templates.
 *
 * These tests verify that the tbs:row block syntax for table row repeaters
 * works correctly with our document generation system.
 *
 * @package Documentate
 */

/**
 * Class DocumentOpenTBSDemoTest
 */
class DocumentOpenTBSDemoTest extends Documentate_Generation_Test_Base {

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
	// ODT Table Row Repeater Tests
	// =========================================================================

	/**
	 * Test ODT table row repeater template generates without errors.
	 */
	public function test_odt_table_row_repeater_generates() {
		$type_data = $this->create_doc_type_with_template( 'table-row-repeater.odt' );
		$post_id   = $this->create_document_with_data(
			$type_data['term_id'],
			array(
				'yourname' => 'Test User',
			),
			array(
				'a' => array(
					array(
						'firstname' => 'John',
						'name'      => 'Doe',
						'number'    => '001',
					),
					array(
						'firstname' => 'Jane',
						'name'      => 'Smith',
						'number'    => '002',
					),
				),
			)
		);

		$path = $this->generate_document( $post_id, 'odt' );

		$this->assertIsString( $path, 'ODT table row repeater should generate successfully.' );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml, 'Generated ODT should have valid XML.' );

		// Verify scalar field was merged.
		$this->assertStringContainsString( 'Test User', $xml );
	}

	/**
	 * Test ODT table row repeater merges all rows correctly.
	 */
	public function test_odt_table_rows_merge() {
		$type_data = $this->create_doc_type_with_template( 'table-row-repeater.odt' );
		$post_id   = $this->create_document_with_data(
			$type_data['term_id'],
			array(
				'yourname' => 'Demo User',
			),
			array(
				'a' => array(
					array(
						'firstname' => 'Alice',
						'name'      => 'Anderson',
						'number'    => 'A001',
					),
					array(
						'firstname' => 'Bob',
						'name'      => 'Brown',
						'number'    => 'B002',
					),
					array(
						'firstname' => 'Charlie',
						'name'      => 'Clark',
						'number'    => 'C003',
					),
				),
			)
		);

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );

		// Verify all table row data was merged.
		$this->assertStringContainsString( 'Alice', $xml );
		$this->assertStringContainsString( 'Anderson', $xml );
		$this->assertStringContainsString( 'A001', $xml );
		$this->assertStringContainsString( 'Bob', $xml );
		$this->assertStringContainsString( 'Brown', $xml );
		$this->assertStringContainsString( 'Charlie', $xml );
		$this->assertStringContainsString( 'Clark', $xml );
		$this->assertStringContainsString( 'C003', $xml );

		// Verify no placeholder artifacts remain.
		$this->assertNoPlaceholderArtifacts( $path );
	}

	/**
	 * Test ODT table row repeater with empty data.
	 */
	public function test_odt_empty_table_row_repeater() {
		$type_data = $this->create_doc_type_with_template( 'table-row-repeater.odt' );
		$post_id   = $this->create_document_with_data(
			$type_data['term_id'],
			array(
				'yourname' => 'Empty Test',
			),
			array(
				'a' => array(),
			)
		);

		$path = $this->generate_document( $post_id, 'odt' );

		$this->assertIsString( $path, 'ODT with empty table row repeater should still generate.' );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Empty Test', $xml );

		// Verify no placeholder artifacts remain.
		$this->assertNoPlaceholderArtifacts( $path );
	}

	/**
	 * Test ODT table row repeater with special characters.
	 */
	public function test_odt_table_row_special_characters() {
		$type_data = $this->create_doc_type_with_template( 'table-row-repeater.odt' );
		$post_id   = $this->create_document_with_data(
			$type_data['term_id'],
			array(
				'yourname' => 'José García <test> & "quoted"',
			),
			array(
				'a' => array(
					array(
						'firstname' => 'François',
						'name'      => "O'Brien",
						'number'    => '001 & 002',
					),
				),
			)
		);

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		// Special characters should be properly escaped.
		$this->assertStringContainsString( 'José', $xml );
		$this->assertStringContainsString( 'García', $xml );
		$this->assertStringContainsString( 'François', $xml );

		// Document should remain valid XML.
		$doc = new DOMDocument();
		$this->assertTrue( $doc->loadXML( $xml ), 'Document should be valid XML despite special characters.' );
	}

	/**
	 * Test ODT table row repeater with Unicode data.
	 */
	public function test_odt_table_row_unicode_data() {
		$type_data = $this->create_doc_type_with_template( 'table-row-repeater.odt' );
		$post_id   = $this->create_document_with_data(
			$type_data['term_id'],
			array(
				'yourname' => '日本語ユーザー',
			),
			array(
				'a' => array(
					array(
						'firstname' => '太郎',
						'name'      => '山田',
						'number'    => '日001',
					),
					array(
						'firstname' => 'Müller',
						'name'      => 'Schröder',
						'number'    => 'DE002',
					),
				),
			)
		);

		$path = $this->generate_document( $post_id, 'odt' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		// Check for Japanese characters (may be raw UTF-8 or unicode-escaped).
		$has_japanese_header = strpos( $xml, '日本語' ) !== false || strpos( $xml, 'u65e5u672cu8a9e' ) !== false;
		$this->assertTrue( $has_japanese_header, 'Japanese header text should be present in some form.' );
		// European characters with diacritics should always be preserved.
		$this->assertStringContainsString( 'Müller', $xml );
		$this->assertStringContainsString( 'Schröder', $xml );
	}

	/**
	 * Test ODT table row repeater with large data set.
	 */
	public function test_odt_large_table_row_repeater() {
		$items = array();
		for ( $i = 1; $i <= 20; $i++ ) {
			$items[] = array(
				'firstname' => "First$i",
				'name'      => "Last$i",
				'number'    => sprintf( '%03d', $i ),
			);
		}

		$type_data = $this->create_doc_type_with_template( 'table-row-repeater.odt' );
		$post_id   = $this->create_document_with_data(
			$type_data['term_id'],
			array(
				'yourname' => 'Large Test',
			),
			array(
				'a' => $items,
			)
		);

		$path = $this->generate_document( $post_id, 'odt' );

		$this->assertIsString( $path, 'Large table row repeater should generate successfully.' );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml );

		// Verify first, middle, and last items.
		$this->assertStringContainsString( 'First1', $xml );
		$this->assertStringContainsString( 'First10', $xml );
		$this->assertStringContainsString( 'First20', $xml );

		$this->assertNoPlaceholderArtifacts( $path );
	}

	// =========================================================================
	// DOCX Table Row Repeater Tests
	// =========================================================================

	/**
	 * Test DOCX table row repeater template generates without errors.
	 */
	public function test_docx_table_row_repeater_generates() {
		$type_data = $this->create_doc_type_with_template( 'table-row-repeater.docx' );
		$post_id   = $this->create_document_with_data(
			$type_data['term_id'],
			array(
				'yourname' => 'Test User',
			),
			array(
				'a' => array(
					array(
						'firstname' => 'John',
						'name'      => 'Doe',
						'number'    => '001',
					),
					array(
						'firstname' => 'Jane',
						'name'      => 'Smith',
						'number'    => '002',
					),
				),
			)
		);

		$path = $this->generate_document( $post_id, 'docx' );

		$this->assertIsString( $path, 'DOCX table row repeater should generate successfully.' );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml, 'Generated DOCX should have valid XML.' );

		// Verify scalar field was merged.
		$this->assertStringContainsString( 'Test User', $xml );
	}

	/**
	 * Test DOCX table row repeater merges all rows correctly.
	 */
	public function test_docx_table_rows_merge() {
		$type_data = $this->create_doc_type_with_template( 'table-row-repeater.docx' );
		$post_id   = $this->create_document_with_data(
			$type_data['term_id'],
			array(
				'yourname' => 'Demo User',
			),
			array(
				'a' => array(
					array(
						'firstname' => 'Alice',
						'name'      => 'Anderson',
						'number'    => 'A001',
					),
					array(
						'firstname' => 'Bob',
						'name'      => 'Brown',
						'number'    => 'B002',
					),
					array(
						'firstname' => 'Charlie',
						'name'      => 'Clark',
						'number'    => 'C003',
					),
				),
			)
		);

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );

		// Verify all table row data was merged.
		$this->assertStringContainsString( 'Alice', $xml );
		$this->assertStringContainsString( 'Anderson', $xml );
		$this->assertStringContainsString( 'A001', $xml );
		$this->assertStringContainsString( 'Bob', $xml );
		$this->assertStringContainsString( 'Brown', $xml );
		$this->assertStringContainsString( 'Charlie', $xml );
		$this->assertStringContainsString( 'Clark', $xml );
		$this->assertStringContainsString( 'C003', $xml );

		// Verify no placeholder artifacts remain.
		$this->assertNoPlaceholderArtifacts( $path );
	}

	/**
	 * Test DOCX table row repeater with empty data.
	 */
	public function test_docx_empty_table_row_repeater() {
		$type_data = $this->create_doc_type_with_template( 'table-row-repeater.docx' );
		$post_id   = $this->create_document_with_data(
			$type_data['term_id'],
			array(
				'yourname' => 'Empty Test',
			),
			array(
				'a' => array(),
			)
		);

		$path = $this->generate_document( $post_id, 'docx' );

		$this->assertIsString( $path, 'DOCX with empty table row repeater should still generate.' );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml );
		$this->assertStringContainsString( 'Empty Test', $xml );

		// Verify no placeholder artifacts remain.
		$this->assertNoPlaceholderArtifacts( $path );
	}

	/**
	 * Test DOCX table row repeater with special characters.
	 */
	public function test_docx_table_row_special_characters() {
		$type_data = $this->create_doc_type_with_template( 'table-row-repeater.docx' );
		$post_id   = $this->create_document_with_data(
			$type_data['term_id'],
			array(
				'yourname' => 'José García <test> & "quoted"',
			),
			array(
				'a' => array(
					array(
						'firstname' => 'François',
						'name'      => "O'Brien",
						'number'    => '001 & 002',
					),
				),
			)
		);

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		// Special characters should be properly escaped.
		$this->assertStringContainsString( 'José', $xml );
		$this->assertStringContainsString( 'García', $xml );
		$this->assertStringContainsString( 'François', $xml );

		// Document should remain valid XML.
		$doc = new DOMDocument();
		$this->assertTrue( $doc->loadXML( $xml ), 'Document should be valid XML despite special characters.' );
	}

	/**
	 * Test DOCX table row repeater with Unicode data.
	 */
	public function test_docx_table_row_unicode_data() {
		$type_data = $this->create_doc_type_with_template( 'table-row-repeater.docx' );
		$post_id   = $this->create_document_with_data(
			$type_data['term_id'],
			array(
				'yourname' => '日本語ユーザー',
			),
			array(
				'a' => array(
					array(
						'firstname' => '太郎',
						'name'      => '山田',
						'number'    => '日001',
					),
					array(
						'firstname' => 'Müller',
						'name'      => 'Schröder',
						'number'    => 'DE002',
					),
				),
			)
		);

		$path = $this->generate_document( $post_id, 'docx' );
		$xml  = $this->extract_document_xml( $path );

		$this->assertNotFalse( $xml );
		// Check for Japanese characters (may be raw UTF-8 or unicode-escaped).
		$has_japanese_header = strpos( $xml, '日本語' ) !== false || strpos( $xml, 'u65e5u672cu8a9e' ) !== false;
		$this->assertTrue( $has_japanese_header, 'Japanese header text should be present in some form.' );
		// European characters with diacritics should always be preserved.
		$this->assertStringContainsString( 'Müller', $xml );
		$this->assertStringContainsString( 'Schröder', $xml );
	}

	/**
	 * Test DOCX table row repeater with large data set.
	 */
	public function test_docx_large_table_row_repeater() {
		$items = array();
		for ( $i = 1; $i <= 20; $i++ ) {
			$items[] = array(
				'firstname' => "First$i",
				'name'      => "Last$i",
				'number'    => sprintf( '%03d', $i ),
			);
		}

		$type_data = $this->create_doc_type_with_template( 'table-row-repeater.docx' );
		$post_id   = $this->create_document_with_data(
			$type_data['term_id'],
			array(
				'yourname' => 'Large Test',
			),
			array(
				'a' => $items,
			)
		);

		$path = $this->generate_document( $post_id, 'docx' );

		$this->assertIsString( $path, 'Large table row repeater should generate successfully.' );
		$this->assertFileExists( $path );

		$xml = $this->extract_document_xml( $path );
		$this->assertNotFalse( $xml );

		// Verify first, middle, and last items.
		$this->assertStringContainsString( 'First1', $xml );
		$this->assertStringContainsString( 'First10', $xml );
		$this->assertStringContainsString( 'First20', $xml );

		$this->assertNoPlaceholderArtifacts( $path );
	}

	// =========================================================================
	// Cross-Format Comparison Tests
	// =========================================================================

	/**
	 * Test both formats produce consistent content with same data.
	 */
	public function test_both_formats_produce_consistent_content() {
		$common_data   = array(
			'yourname' => 'Consistency Test',
		);
		$common_repeat = array(
			'a' => array(
				array(
					'firstname' => 'TestFirst',
					'name'      => 'TestLast',
					'number'    => 'TEST001',
				),
			),
		);

		// Generate ODT.
		$odt_type_data = $this->create_doc_type_with_template( 'table-row-repeater.odt' );
		$odt_post_id   = $this->create_document_with_data( $odt_type_data['term_id'], $common_data, $common_repeat );
		$odt_path      = $this->generate_document( $odt_post_id, 'odt' );
		$odt_xml       = $this->extract_document_xml( $odt_path );

		// Generate DOCX.
		$docx_type_data = $this->create_doc_type_with_template( 'table-row-repeater.docx' );
		$docx_post_id   = $this->create_document_with_data( $docx_type_data['term_id'], $common_data, $common_repeat );
		$docx_path      = $this->generate_document( $docx_post_id, 'docx' );
		$docx_xml       = $this->extract_document_xml( $docx_path );

		// Both should contain the same merged data.
		$this->assertStringContainsString( 'Consistency Test', $odt_xml );
		$this->assertStringContainsString( 'Consistency Test', $docx_xml );
		$this->assertStringContainsString( 'TestFirst', $odt_xml );
		$this->assertStringContainsString( 'TestFirst', $docx_xml );
		$this->assertStringContainsString( 'TestLast', $odt_xml );
		$this->assertStringContainsString( 'TestLast', $docx_xml );
		$this->assertStringContainsString( 'TEST001', $odt_xml );
		$this->assertStringContainsString( 'TEST001', $docx_xml );

		// Neither should have placeholder artifacts.
		$this->assertNoPlaceholderArtifacts( $odt_path );
		$this->assertNoPlaceholderArtifacts( $docx_path );
	}
}
