#!/usr/bin/env php
<?php
/**
 * Generates ODT test templates and converts them to DOCX using LibreOffice.
 *
 * Usage: php generate-templates.php
 * Requirement: LibreOffice installed (soffice in PATH)
 *
 * @package Documentate
 */

echo "=== Documentate Test Template Generator ===\n\n";

/**
 * Template definitions.
 *
 * Each template has a name, placeholders (for reference), and content in ODF XML format.
 */
$templates = array(
	'minimal-scalar'       => array(
		'description'  => 'Simple scalar placeholder substitution',
		'placeholders' => array( '[name]', '[email]', '[body]' ),
		'content'      => '<text:p text:style-name="Standard">Name: [name]</text:p>' .
						  '<text:p text:style-name="Standard">Email: [email]</text:p>' .
						  '<text:p text:style-name="Standard">Body: [body]</text:p>',
	),
	'minimal-table'        => array(
		'description'  => 'HTML table rendering test',
		'placeholders' => array( '[contenido]' ),
		'content'      => '<text:p text:style-name="Standard">Table content:</text:p>' .
						  '<text:p text:style-name="Standard">[contenido]</text:p>',
	),
	'minimal-list'         => array(
		'description'  => 'HTML list rendering test',
		'placeholders' => array( '[contenido]' ),
		'content'      => '<text:p text:style-name="Standard">List content:</text:p>' .
						  '<text:p text:style-name="Standard">[contenido]</text:p>',
	),
	'minimal-nested-block' => array(
		'description'  => 'Repeater block test',
		'placeholders' => array( '[items;block=begin]', '[items.title]', '[items.content]', '[items;block=end]' ),
		'content'      => '<text:p text:style-name="Standard">Items list:</text:p>' .
						  '<text:p text:style-name="Standard">[items;block=begin]</text:p>' .
						  '<text:p text:style-name="Standard">Title: [items.title]</text:p>' .
						  '<text:p text:style-name="Standard">Content: [items.content]</text:p>' .
						  '<text:p text:style-name="Standard">[items;block=end]</text:p>',
	),
	'comprehensive-test'   => array(
		'description'  => 'Complete test with all field types',
		'placeholders' => array( '[name]', '[email]', '[body]', '[items;block=begin/end]' ),
		'content'      => '<text:p text:style-name="Standard">DOCUMENT TEST</text:p>' .
						  '<text:p text:style-name="Standard">Name: [name]</text:p>' .
						  '<text:p text:style-name="Standard">Email: [email]</text:p>' .
						  '<text:p text:style-name="Standard">Body content:</text:p>' .
						  '<text:p text:style-name="Standard">[body]</text:p>' .
						  '<text:p text:style-name="Standard">---</text:p>' .
						  '<text:p text:style-name="Standard">Repeating items:</text:p>' .
						  '<text:p text:style-name="Standard">[items;block=begin]</text:p>' .
						  '<text:p text:style-name="Standard">- [items.title]: [items.content]</text:p>' .
						  '<text:p text:style-name="Standard">[items;block=end]</text:p>',
	),
	'table-row-repeater'   => array(
		'description'  => 'Table row repeater using tbs:row syntax',
		'placeholders' => array( '[yourname]', '[a.firstname;block=tbs:row]', '[a.name]', '[a.number]' ),
		'content'      => '<text:p text:style-name="Standard">Hello [yourname],</text:p>' .
						  '<text:p text:style-name="Standard">Member list:</text:p>' .
						  '<table:table table:name="Members">' .
						  '<table:table-column table:number-columns-repeated="3"/>' .
						  '<table:table-row>' .
						  '<table:table-cell><text:p text:style-name="Standard">First Name</text:p></table:table-cell>' .
						  '<table:table-cell><text:p text:style-name="Standard">Last Name</text:p></table:table-cell>' .
						  '<table:table-cell><text:p text:style-name="Standard">Number</text:p></table:table-cell>' .
						  '</table:table-row>' .
						  '<table:table-row>' .
						  '<table:table-cell><text:p text:style-name="Standard">[a.firstname;block=tbs:row]</text:p></table:table-cell>' .
						  '<table:table-cell><text:p text:style-name="Standard">[a.name]</text:p></table:table-cell>' .
						  '<table:table-cell><text:p text:style-name="Standard">[a.number]</text:p></table:table-cell>' .
						  '</table:table-row>' .
						  '</table:table>',
	),
);

/**
 * Creates an ODT file with given content.
 *
 * @param string $name       Template name.
 * @param string $content    ODF XML content for the body.
 * @param string $output_dir Output directory.
 * @return string Path to created ODT file.
 */
function create_odt( $name, $content, $output_dir ) {
	$odt_path = $output_dir . '/' . $name . '.odt';

	// ODT content.xml with proper namespaces.
	$content_xml = '<?xml version="1.0" encoding="UTF-8"?>
<office:document-content
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
    xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
    xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"
    xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"
    xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"
    xmlns:xlink="http://www.w3.org/1999/xlink"
    xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"
    office:version="1.2">
<office:scripts/>
<office:font-face-decls>
    <style:font-face style:name="Liberation Sans" svg:font-family="&apos;Liberation Sans&apos;" style:font-family-generic="swiss" style:font-pitch="variable"/>
</office:font-face-decls>
<office:automatic-styles>
    <style:style style:name="Standard" style:family="paragraph">
        <style:paragraph-properties fo:margin-top="0cm" fo:margin-bottom="0.212cm"/>
    </style:style>
</office:automatic-styles>
<office:body>
<office:text>
' . $content . '
</office:text>
</office:body>
</office:document-content>';

	// ODT styles.xml (minimal).
	$styles_xml = '<?xml version="1.0" encoding="UTF-8"?>
<office:document-styles
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
    xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
    xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"
    office:version="1.2">
<office:font-face-decls>
    <style:font-face style:name="Liberation Sans" svg:font-family="&apos;Liberation Sans&apos;"/>
</office:font-face-decls>
<office:styles>
    <style:default-style style:family="paragraph">
        <style:paragraph-properties fo:margin-top="0cm" fo:margin-bottom="0.212cm"/>
        <style:text-properties style:font-name="Liberation Sans" fo:font-size="12pt"/>
    </style:default-style>
</office:styles>
<office:automatic-styles/>
<office:master-styles>
    <style:master-page style:name="Standard" style:page-layout-name="pm1"/>
</office:master-styles>
</office:document-styles>';

	// ODT manifest.
	$manifest_xml = '<?xml version="1.0" encoding="UTF-8"?>
<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0" manifest:version="1.2">
    <manifest:file-entry manifest:full-path="/" manifest:version="1.2" manifest:media-type="application/vnd.oasis.opendocument.text"/>
    <manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/>
    <manifest:file-entry manifest:full-path="styles.xml" manifest:media-type="text/xml"/>
    <manifest:file-entry manifest:full-path="meta.xml" manifest:media-type="text/xml"/>
</manifest:manifest>';

	// ODT meta.xml (minimal).
	$meta_xml = '<?xml version="1.0" encoding="UTF-8"?>
<office:document-meta
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    office:version="1.2">
<office:meta>
    <meta:generator>Documentate Test Generator</meta:generator>
    <dc:title>' . htmlspecialchars( $name ) . '</dc:title>
</office:meta>
</office:document-meta>';

	// Create the ZIP archive (ODT is a ZIP file).
	$zip = new ZipArchive();
	if ( true !== $zip->open( $odt_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
		echo "ERROR: Could not create $odt_path\n";
		return '';
	}

	// Add mimetype first (uncompressed, as per ODF spec).
	$zip->addFromString( 'mimetype', 'application/vnd.oasis.opendocument.text' );
	$zip->setCompressionName( 'mimetype', ZipArchive::CM_STORE );

	// Add other files.
	$zip->addFromString( 'content.xml', $content_xml );
	$zip->addFromString( 'styles.xml', $styles_xml );
	$zip->addFromString( 'meta.xml', $meta_xml );
	$zip->addEmptyDir( 'META-INF' );
	$zip->addFromString( 'META-INF/manifest.xml', $manifest_xml );

	$zip->close();

	echo "  Created: $odt_path\n";
	return $odt_path;
}

/**
 * Converts ODT to DOCX using LibreOffice.
 *
 * @param string $odt_path   Path to ODT file.
 * @param string $output_dir Output directory.
 * @return bool True on success.
 */
function convert_to_docx( $odt_path, $output_dir ) {
	if ( empty( $odt_path ) || ! file_exists( $odt_path ) ) {
		return false;
	}

	// Try different LibreOffice binary names.
	$soffice_paths = array(
		'soffice',                                    // Linux/macOS with PATH.
		'/Applications/LibreOffice.app/Contents/MacOS/soffice', // macOS.
		'/usr/bin/soffice',                           // Linux.
		'/usr/bin/libreoffice',                       // Alternative Linux.
	);

	$soffice = '';
	foreach ( $soffice_paths as $path ) {
		$check = shell_exec( "which $path 2>/dev/null" );
		if ( ! empty( trim( $check ?? '' ) ) ) {
			$soffice = $path;
			break;
		}
		if ( file_exists( $path ) ) {
			$soffice = $path;
			break;
		}
	}

	if ( empty( $soffice ) ) {
		echo "  WARNING: LibreOffice not found. DOCX conversion skipped.\n";
		echo "  Install LibreOffice and ensure 'soffice' is in your PATH.\n";
		return false;
	}

	$cmd = sprintf(
		'%s --headless --convert-to docx --outdir %s %s 2>&1',
		escapeshellarg( $soffice ),
		escapeshellarg( $output_dir ),
		escapeshellarg( $odt_path )
	);

	exec( $cmd, $output, $return );

	$docx_path = str_replace( '.odt', '.docx', $odt_path );
	if ( 0 === $return && file_exists( $docx_path ) ) {
		echo "  Converted: $docx_path\n";
		return true;
	}

	echo "  ERROR converting to DOCX: " . implode( "\n", $output ) . "\n";
	return false;
}

// Main execution.
$output_dir = __DIR__;
echo "Output directory: $output_dir\n\n";

$created = 0;
$converted = 0;

foreach ( $templates as $name => $spec ) {
	echo "Template: $name\n";
	echo "  Description: {$spec['description']}\n";
	echo "  Placeholders: " . implode( ', ', $spec['placeholders'] ) . "\n";

	$odt_path = create_odt( $name, $spec['content'], $output_dir );
	if ( ! empty( $odt_path ) ) {
		++$created;
		if ( convert_to_docx( $odt_path, $output_dir ) ) {
			++$converted;
		}
	}
	echo "\n";
}

echo "=== Summary ===\n";
echo "ODT templates created: $created\n";
echo "DOCX templates converted: $converted\n";
echo "\nDone!\n";
