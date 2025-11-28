<?php
/**
 * Helper class for asserting XML content in generated documents.
 *
 * Provides specialized assertions for DOCX (WordprocessingML) and ODT (ODF)
 * document formats.
 *
 * @package Documentate
 */

/**
 * Class Document_Xml_Asserter
 */
class Document_Xml_Asserter {

	/**
	 * WordprocessingML namespace.
	 */
	const WORD_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

	/**
	 * ODF text namespace.
	 */
	const ODF_TEXT_NS = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';

	/**
	 * ODF table namespace.
	 */
	const ODF_TABLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';

	/**
	 * ODF style namespace.
	 */
	const ODF_STYLE_NS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';

	/**
	 * Parse XML string into DOMDocument.
	 *
	 * @param string $xml XML content.
	 * @return DOMDocument Parsed document.
	 */
	public function parse( $xml ) {
		libxml_use_internal_errors( true );
		$dom = new DOMDocument();
		$dom->loadXML( $xml );
		libxml_clear_errors();
		return $dom;
	}

	/**
	 * Create XPath for DOCX document.
	 *
	 * @param DOMDocument $dom DOM document.
	 * @return DOMXPath XPath instance with namespaces.
	 */
	public function createDocxXPath( DOMDocument $dom ) {
		$xpath = new DOMXPath( $dom );
		$xpath->registerNamespace( 'w', self::WORD_NS );
		$xpath->registerNamespace( 'r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships' );
		$xpath->registerNamespace( 'wp', 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing' );
		return $xpath;
	}

	/**
	 * Create XPath for ODT document.
	 *
	 * @param DOMDocument $dom DOM document.
	 * @return DOMXPath XPath instance with namespaces.
	 */
	public function createOdtXPath( DOMDocument $dom ) {
		$xpath = new DOMXPath( $dom );
		$xpath->registerNamespace( 'text', self::ODF_TEXT_NS );
		$xpath->registerNamespace( 'table', self::ODF_TABLE_NS );
		$xpath->registerNamespace( 'office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0' );
		$xpath->registerNamespace( 'style', self::ODF_STYLE_NS );
		$xpath->registerNamespace( 'fo', 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0' );
		$xpath->registerNamespace( 'xlink', 'http://www.w3.org/1999/xlink' );
		return $xpath;
	}

	// =========================================================================
	// DOCX Assertions
	// =========================================================================

	/**
	 * Assert DOCX contains text.
	 *
	 * @param DOMXPath $xpath   XPath instance.
	 * @param string   $text    Expected text.
	 * @param string   $message Optional assertion message.
	 */
	public function assertDocxContainsText( DOMXPath $xpath, $text, $message = '' ) {
		$escaped = addslashes( $text );
		$nodes   = $xpath->query( "//w:t[contains(text(), '$escaped')]" );
		PHPUnit\Framework\Assert::assertGreaterThan(
			0,
			$nodes->length,
			$message ?: "DOCX should contain text: $text"
		);
	}

	/**
	 * Assert DOCX text is bold.
	 *
	 * @param DOMXPath $xpath   XPath instance.
	 * @param string   $text    Text to check.
	 * @param string   $message Optional assertion message.
	 */
	public function assertDocxTextIsBold( DOMXPath $xpath, $text, $message = '' ) {
		$escaped = addslashes( $text );
		$runs    = $xpath->query( "//w:r[w:t[contains(text(), '$escaped')]]" );
		PHPUnit\Framework\Assert::assertGreaterThan( 0, $runs->length, "Text '$text' should exist in DOCX." );

		$bold_found = false;
		foreach ( $runs as $run ) {
			$bold_nodes = $xpath->query( './/w:rPr/w:b', $run );
			if ( $bold_nodes->length > 0 ) {
				$bold_found = true;
				break;
			}
		}
		PHPUnit\Framework\Assert::assertTrue( $bold_found, $message ?: "Text '$text' should be bold in DOCX." );
	}

	/**
	 * Assert DOCX text is italic.
	 *
	 * @param DOMXPath $xpath   XPath instance.
	 * @param string   $text    Text to check.
	 * @param string   $message Optional assertion message.
	 */
	public function assertDocxTextIsItalic( DOMXPath $xpath, $text, $message = '' ) {
		$escaped = addslashes( $text );
		$runs    = $xpath->query( "//w:r[w:t[contains(text(), '$escaped')]]" );
		PHPUnit\Framework\Assert::assertGreaterThan( 0, $runs->length, "Text '$text' should exist in DOCX." );

		$italic_found = false;
		foreach ( $runs as $run ) {
			$italic_nodes = $xpath->query( './/w:rPr/w:i', $run );
			if ( $italic_nodes->length > 0 ) {
				$italic_found = true;
				break;
			}
		}
		PHPUnit\Framework\Assert::assertTrue( $italic_found, $message ?: "Text '$text' should be italic in DOCX." );
	}

	/**
	 * Assert DOCX text is underlined.
	 *
	 * @param DOMXPath $xpath   XPath instance.
	 * @param string   $text    Text to check.
	 * @param string   $message Optional assertion message.
	 */
	public function assertDocxTextIsUnderlined( DOMXPath $xpath, $text, $message = '' ) {
		$escaped = addslashes( $text );
		$runs    = $xpath->query( "//w:r[w:t[contains(text(), '$escaped')]]" );
		PHPUnit\Framework\Assert::assertGreaterThan( 0, $runs->length, "Text '$text' should exist in DOCX." );

		$underline_found = false;
		foreach ( $runs as $run ) {
			$underline_nodes = $xpath->query( './/w:rPr/w:u', $run );
			if ( $underline_nodes->length > 0 ) {
				$underline_found = true;
				break;
			}
		}
		PHPUnit\Framework\Assert::assertTrue( $underline_found, $message ?: "Text '$text' should be underlined in DOCX." );
	}

	/**
	 * Assert DOCX contains a table with specific dimensions.
	 *
	 * @param DOMXPath $xpath   XPath instance.
	 * @param int      $rows    Expected number of rows.
	 * @param int      $cols    Expected number of columns (in first row).
	 * @param string   $message Optional assertion message.
	 */
	public function assertDocxTableExists( DOMXPath $xpath, $rows, $cols, $message = '' ) {
		$tables = $xpath->query( '//w:tbl' );
		PHPUnit\Framework\Assert::assertGreaterThan( 0, $tables->length, 'DOCX should contain at least one table.' );

		$table     = $tables->item( 0 );
		$row_nodes = $xpath->query( './/w:tr', $table );
		PHPUnit\Framework\Assert::assertSame( $rows, $row_nodes->length, $message ?: "Table should have $rows rows." );

		if ( $row_nodes->length > 0 ) {
			$first_row  = $row_nodes->item( 0 );
			$cell_nodes = $xpath->query( './/w:tc', $first_row );
			PHPUnit\Framework\Assert::assertSame( $cols, $cell_nodes->length, $message ?: "Table should have $cols columns." );
		}
	}

	/**
	 * Assert DOCX table count.
	 *
	 * @param DOMXPath $xpath   XPath instance.
	 * @param int      $count   Expected number of tables.
	 * @param string   $message Optional assertion message.
	 */
	public function assertDocxTableCount( DOMXPath $xpath, $count, $message = '' ) {
		$tables = $xpath->query( '//w:tbl' );
		PHPUnit\Framework\Assert::assertSame( $count, $tables->length, $message ?: "DOCX should contain $count table(s)." );
	}

	/**
	 * Assert DOCX table has borders.
	 *
	 * @param DOMXPath $xpath   XPath instance.
	 * @param string   $message Optional assertion message.
	 */
	public function assertDocxTableHasBorders( DOMXPath $xpath, $message = '' ) {
		$borders = $xpath->query( '//w:tbl/w:tblPr/w:tblBorders' );
		PHPUnit\Framework\Assert::assertGreaterThan( 0, $borders->length, $message ?: 'Table should have borders.' );
	}

	/**
	 * Assert DOCX contains a hyperlink with specific text.
	 *
	 * @param DOMXPath $xpath   XPath instance.
	 * @param string   $text    Link text.
	 * @param string   $message Optional assertion message.
	 */
	public function assertDocxHyperlinkExists( DOMXPath $xpath, $text, $message = '' ) {
		$escaped    = addslashes( $text );
		$hyperlinks = $xpath->query( "//w:hyperlink[.//w:t[contains(text(), '$escaped')]]" );
		PHPUnit\Framework\Assert::assertGreaterThan(
			0,
			$hyperlinks->length,
			$message ?: "DOCX should contain hyperlink with text: $text"
		);
	}

	// =========================================================================
	// ODT Assertions
	// =========================================================================

	/**
	 * Assert ODT contains text.
	 *
	 * @param DOMXPath $xpath   XPath instance.
	 * @param string   $text    Expected text.
	 * @param string   $message Optional assertion message.
	 */
	public function assertOdtContainsText( DOMXPath $xpath, $text, $message = '' ) {
		$escaped = addslashes( $text );
		$nodes   = $xpath->query( "//*[contains(text(), '$escaped')]" );
		PHPUnit\Framework\Assert::assertGreaterThan(
			0,
			$nodes->length,
			$message ?: "ODT should contain text: $text"
		);
	}

	/**
	 * Assert ODT contains a table with specific dimensions.
	 *
	 * @param DOMXPath $xpath   XPath instance.
	 * @param int      $rows    Expected number of rows.
	 * @param int      $cols    Expected number of columns (in first row).
	 * @param string   $message Optional assertion message.
	 */
	public function assertOdtTableExists( DOMXPath $xpath, $rows, $cols, $message = '' ) {
		$tables = $xpath->query( '//table:table' );
		PHPUnit\Framework\Assert::assertGreaterThan( 0, $tables->length, 'ODT should contain at least one table.' );

		$table     = $tables->item( 0 );
		$row_nodes = $xpath->query( './/table:table-row', $table );
		PHPUnit\Framework\Assert::assertSame( $rows, $row_nodes->length, $message ?: "Table should have $rows rows." );

		if ( $row_nodes->length > 0 ) {
			$first_row  = $row_nodes->item( 0 );
			$cell_nodes = $xpath->query( './/table:table-cell', $first_row );
			PHPUnit\Framework\Assert::assertSame( $cols, $cell_nodes->length, $message ?: "Table should have $cols columns." );
		}
	}

	/**
	 * Assert ODT table count.
	 *
	 * @param DOMXPath $xpath   XPath instance.
	 * @param int      $count   Expected number of tables.
	 * @param string   $message Optional assertion message.
	 */
	public function assertOdtTableCount( DOMXPath $xpath, $count, $message = '' ) {
		$tables = $xpath->query( '//table:table' );
		PHPUnit\Framework\Assert::assertSame( $count, $tables->length, $message ?: "ODT should contain $count table(s)." );
	}

	/**
	 * Assert ODT table has borders (via style reference).
	 *
	 * @param DOMXPath $xpath   XPath instance.
	 * @param string   $message Optional assertion message.
	 */
	public function assertOdtTableHasBorders( DOMXPath $xpath, $message = '' ) {
		// Check for DocumentateRichTable style or fo:border properties.
		$styles = $xpath->query( "//style:style[@style:name='DocumentateRichTable']" );
		if ( $styles->length > 0 ) {
			PHPUnit\Framework\Assert::assertTrue( true );
			return;
		}

		// Alternative: check for border properties on table cells.
		$borders = $xpath->query( "//style:table-cell-properties[contains(@fo:border, 'solid')]" );
		PHPUnit\Framework\Assert::assertGreaterThan(
			0,
			$borders->length,
			$message ?: 'ODT table should have border styles.'
		);
	}

	/**
	 * Assert ODT contains hyperlink.
	 *
	 * @param DOMXPath $xpath   XPath instance.
	 * @param string   $text    Link text.
	 * @param string   $message Optional assertion message.
	 */
	public function assertOdtHyperlinkExists( DOMXPath $xpath, $text, $message = '' ) {
		$escaped = addslashes( $text );
		$links   = $xpath->query( "//text:a[contains(text(), '$escaped')]" );
		PHPUnit\Framework\Assert::assertGreaterThan(
			0,
			$links->length,
			$message ?: "ODT should contain hyperlink with text: $text"
		);
	}

	/**
	 * Assert ODT list item count.
	 *
	 * @param DOMXPath $xpath   XPath instance.
	 * @param int      $count   Expected number of list items.
	 * @param string   $message Optional assertion message.
	 */
	public function assertOdtListItemCount( DOMXPath $xpath, $count, $message = '' ) {
		// ODT lists converted to paragraphs with bullet prefix.
		$bullets = $xpath->query( "//*[contains(text(), '\xE2\x80\xA2')]" );
		PHPUnit\Framework\Assert::assertGreaterThanOrEqual(
			$count,
			$bullets->length,
			$message ?: "ODT should contain at least $count list bullet items."
		);
	}

	/**
	 * Assert ODT text has specific style.
	 *
	 * @param DOMXPath $xpath      XPath instance.
	 * @param string   $text       Text to find.
	 * @param string   $style_name Style name to check.
	 * @param string   $message    Optional assertion message.
	 */
	public function assertOdtTextHasStyle( DOMXPath $xpath, $text, $style_name, $message = '' ) {
		$escaped = addslashes( $text );
		$spans   = $xpath->query( "//text:span[@text:style-name='$style_name'][contains(text(), '$escaped')]" );
		PHPUnit\Framework\Assert::assertGreaterThan(
			0,
			$spans->length,
			$message ?: "Text '$text' should have style '$style_name' in ODT."
		);
	}

	// =========================================================================
	// Generic Assertions
	// =========================================================================

	/**
	 * Assert no unresolved placeholders remain.
	 *
	 * @param string $xml     XML content.
	 * @param string $message Optional assertion message.
	 */
	public function assertNoPlaceholderArtifacts( $xml, $message = '' ) {
		PHPUnit\Framework\Assert::assertStringNotContainsString(
			'[',
			$xml,
			$message ?: 'No unresolved placeholders should remain.'
		);
	}

	/**
	 * Assert no "Array" artifacts remain.
	 *
	 * @param string $xml     XML content.
	 * @param string $message Optional assertion message.
	 */
	public function assertNoArrayArtifacts( $xml, $message = '' ) {
		PHPUnit\Framework\Assert::assertStringNotContainsString(
			'>Array<',
			$xml,
			$message ?: 'No "Array" literal should appear in document.'
		);
	}

	/**
	 * Assert no raw HTML tags remain.
	 *
	 * @param string $xml     XML content.
	 * @param string $message Optional assertion message.
	 */
	public function assertNoRawHtmlTags( $xml, $message = '' ) {
		$html_tags = array(
			'<strong>',
			'</strong>',
			'<em>',
			'</em>',
			'<table>',
			'</table>',
			'<tr>',
			'</tr>',
			'<td>',
			'</td>',
			'<th>',
			'</th>',
			'<ul>',
			'</ul>',
			'<ol>',
			'</ol>',
			'<li>',
			'</li>',
			'<br>',
			'<br/>',
			'<br />',
		);

		foreach ( $html_tags as $tag ) {
			PHPUnit\Framework\Assert::assertStringNotContainsString(
				$tag,
				$xml,
				$message ?: "No raw HTML '$tag' should remain in document."
			);
		}
	}

	/**
	 * Assert XML is well-formed.
	 *
	 * @param string $xml     XML content.
	 * @param string $message Optional assertion message.
	 */
	public function assertXmlWellFormed( $xml, $message = '' ) {
		libxml_use_internal_errors( true );
		$dom    = new DOMDocument();
		$loaded = $dom->loadXML( $xml );
		$errors = libxml_get_errors();
		libxml_clear_errors();

		PHPUnit\Framework\Assert::assertTrue( $loaded, $message ?: 'XML should be loadable.' );
		PHPUnit\Framework\Assert::assertEmpty( $errors, $message ?: 'XML should have no parse errors.' );
	}
}
