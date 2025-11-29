<?php
/**
 * Tests for the schema extractor working with bundled fixtures.
 */

use Documentate\DocType\SchemaConverter;
use Documentate\DocType\SchemaExtractor;

class SchemaExtractorTest extends WP_UnitTestCase {

	/**
	 * Ensure the demo ODT fixture is parsed with all expected fields and metadata.
	 */
	public function test_demo_fixture_schema_parsed_correctly() {
		$extractor = new SchemaExtractor();
		$schema    = $extractor->extract( dirname( __FILE__, 4 ) . '/fixtures/demo-wp-documentate.odt' );

		$this->assertNotWPError( $schema, 'Expected a valid schema when parsing the demo ODT template.' );
		$this->assertIsArray( $schema );
		$this->assertSame( 2, $schema['version'], 'Schema version must be 2.' );
		$this->assertSame( 'odt', $schema['meta']['template_type'], 'Detected template type must be odt.' );

		$fields = $this->index_fields( $schema['fields'] );

		$this->assertArrayHasKey( 'nombrecompleto', $fields, 'Full name field must exist.' );
		$this->assertSame( 'text', $fields['nombrecompleto']['type'] );
		$this->assertSame( 'Tu nombre y apellidos', $fields['nombrecompleto']['placeholder'] );
		$this->assertSame( '120', $fields['nombrecompleto']['length'] );

		$this->assertArrayHasKey( 'email', $fields, 'Email field must exist.' );
		$this->assertSame( 'email', $fields['email']['type'] );
		$this->assertSame(
			'Enter a valid email (user@domain.tld)',
			$fields['email']['patternmsg']
		);

		$this->assertArrayHasKey( 'telfono', $fields, 'Phone field must exist.' );
		$this->assertSame( '^[+]?[1-9][0-9]{1,14}$', $fields['telfono']['pattern'] );
		$this->assertSame( 'Formato de teléfono no válido', $fields['telfono']['patternmsg'] );

		$this->assertArrayHasKey( 'unidades', $fields, 'Units field must exist.' );
		$this->assertSame( 'number', $fields['unidades']['type'] );
		$this->assertSame( '0', $fields['unidades']['minvalue'] );
		$this->assertSame( '20', $fields['unidades']['maxvalue'] );

		$this->assertArrayHasKey( 'observaciones', $fields, 'Observations field must exist.' );
		$this->assertSame( 'textarea', $fields['observaciones']['type'] );

		$this->assertArrayHasKey( 'web', $fields, 'Web field must exist.' );
		$this->assertSame( 'url', $fields['web']['type'] );

		$this->assertArrayHasKey( 'datelimit', $fields, 'Date limit field must exist.' );
		$this->assertSame( 'date', $fields['datelimit']['type'] );
		$this->assertSame( '2025-01-01', $fields['datelimit']['minvalue'] );
		$this->assertSame( '2030-12-31', $fields['datelimit']['maxvalue'] );

		$repeaters = $this->index_repeaters( $schema['repeaters'] );
		$this->assertArrayHasKey( 'items', $repeaters, 'Repeater block items must exist.' );
		$this->assertArrayHasKey( 'title', $repeaters['items'], 'Item title field must exist.' );
		$this->assertSame( 'text', $repeaters['items']['title']['type'] );
		$this->assertArrayHasKey( 'content', $repeaters['items'], 'Item HTML field must exist.' );
		$this->assertSame( 'html', $repeaters['items']['content']['type'] );

		$legacy = SchemaConverter::to_legacy( $schema );
		$this->assertIsArray( $legacy, 'Legacy conversion must return an array.' );
		$this->assertNotEmpty( $legacy );
		$legacy_items = null;
		foreach ( $legacy as $entry ) {
			if ( isset( $entry['slug'] ) && 'items' === $entry['slug'] ) {
				$legacy_items = $entry;
				break;
			}
		}
		$this->assertNotNull( $legacy_items, 'Repeater block must be preserved in legacy conversion.' );
		$this->assertArrayHasKey( 'item_schema', $legacy_items );
		$this->assertArrayHasKey( 'content', $legacy_items['item_schema'] );
		$this->assertSame( 'rich', $legacy_items['item_schema']['content']['type'] );
	}

	/**
	 * Ensure the demo DOCX fixture is parsed with all expected fields and metadata.
	 */
	public function test_demo_docx_fixture_schema_parsed_correctly() {
		$extractor = new SchemaExtractor();
		$schema    = $extractor->extract( dirname( __FILE__, 4 ) . '/fixtures/demo-wp-documentate.docx' );

		$this->assertNotWPError( $schema, 'Expected a valid schema when parsing the demo DOCX template.' );
		$this->assertIsArray( $schema );
		$this->assertSame( 2, $schema['version'], 'Schema version must be 2.' );
		$this->assertSame( 'docx', $schema['meta']['template_type'], 'Detected template type must be docx.' );

		$fields = $this->index_fields( $schema['fields'] );

		$this->assertArrayHasKey( 'nombrecompleto', $fields, 'Full name field must exist.' );
		$this->assertSame( 'text', $fields['nombrecompleto']['type'] );
		$this->assertSame( 'Tu nombre y apellidos', $fields['nombrecompleto']['placeholder'] );
		$this->assertSame( '120', $fields['nombrecompleto']['length'] );

		$this->assertArrayHasKey( 'email', $fields, 'Email field must exist.' );
		$this->assertSame( 'email', $fields['email']['type'] );
		$this->assertSame(
			'Enter a valid email (user@domain.tld)',
			$fields['email']['patternmsg']
		);

		$this->assertArrayHasKey( 'telfono', $fields, 'Phone field must exist.' );
		$this->assertSame( '^[+]?[1-9][0-9]{1,14}$', $fields['telfono']['pattern'] );
		$this->assertSame( 'Formato de teléfono no válido', $fields['telfono']['patternmsg'] );

		$this->assertArrayHasKey( 'unidades', $fields, 'Units field must exist.' );
		$this->assertSame( 'number', $fields['unidades']['type'] );
		$this->assertSame( '0', $fields['unidades']['minvalue'] );
		$this->assertSame( '20', $fields['unidades']['maxvalue'] );

		$this->assertArrayHasKey( 'observaciones', $fields, 'Observations field must exist.' );
		$this->assertSame( 'textarea', $fields['observaciones']['type'] );

		$this->assertArrayHasKey( 'web', $fields, 'Web field must exist.' );
		$this->assertSame( 'url', $fields['web']['type'] );

		$this->assertArrayHasKey( 'datelimit', $fields, 'Date limit field must exist.' );
		$this->assertSame( 'date', $fields['datelimit']['type'] );
		$this->assertSame( '2025-01-01', $fields['datelimit']['minvalue'] );
		$this->assertSame( '2030-12-31', $fields['datelimit']['maxvalue'] );

		$repeaters = $this->index_repeaters( $schema['repeaters'] );
		$this->assertArrayHasKey( 'items', $repeaters, 'Repeater block items must exist.' );
		$this->assertArrayHasKey( 'title', $repeaters['items'], 'Item title field must exist.' );
		$this->assertSame( 'text', $repeaters['items']['title']['type'] );
		$this->assertArrayHasKey( 'content', $repeaters['items'], 'Item HTML field must exist.' );
		$this->assertSame( 'html', $repeaters['items']['content']['type'] );

		$legacy = SchemaConverter::to_legacy( $schema );
		$this->assertIsArray( $legacy, 'Legacy conversion must return an array.' );
		$this->assertNotEmpty( $legacy );
		$legacy_items = null;
		foreach ( $legacy as $entry ) {
			if ( isset( $entry['slug'] ) && 'items' === $entry['slug'] ) {
				$legacy_items = $entry;
				break;
			}
		}
		$this->assertNotNull( $legacy_items, 'Repeater block must be preserved in legacy conversion.' );
		$this->assertArrayHasKey( 'item_schema', $legacy_items );
		$this->assertArrayHasKey( 'content', $legacy_items['item_schema'] );
		$this->assertSame( 'rich', $legacy_items['item_schema']['content']['type'] );
	}

	/**
	 * Index fields by slug.
	 *
	 * @param array $fields Schema fields.
	 * @return array<string,array>
	 */
	private function index_fields( $fields ) {
		$indexed = array();
		foreach ( $fields as $field ) {
			if ( is_array( $field ) && isset( $field['slug'] ) ) {
				$indexed[ $field['slug'] ] = $field;
			}
		}
		return $indexed;
	}

	/**
	 * Index repeater item schemas by slug.
	 *
	 * @param array $repeaters Schema repeaters.
	 * @return array<string,array<string,array>>
	 */
	private function index_repeaters( $repeaters ) {
		$indexed = array();
		foreach ( $repeaters as $repeater ) {
			if ( ! is_array( $repeater ) || empty( $repeater['slug'] ) || empty( $repeater['fields'] ) ) {
				continue;
			}
			$items = array();
			foreach ( $repeater['fields'] as $field ) {
				if ( is_array( $field ) && isset( $field['slug'] ) ) {
					$items[ $field['slug'] ] = $field;
				}
			}
			$indexed[ $repeater['slug'] ] = $items;
		}
		return $indexed;
	}
}
