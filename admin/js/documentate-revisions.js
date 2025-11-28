/**
 * Documentate Revisions Diff Enhancement
 *
 * Replaces raw HTML comment markers in the revisions diff view
 * with styled, human-readable section headers.
 *
 * @package Documentate
 */

( function() {
	'use strict';

	// Bail if localized data is not available.
	if ( typeof window.documentateRevisions === 'undefined' ) {
		return;
	}

	var config = window.documentateRevisions;
	var fieldLabels = config.fieldLabels || {};
	var processed = new WeakSet();

	/**
	 * Escape HTML entities for safe insertion.
	 *
	 * @param {string} str String to escape.
	 * @return {string} Escaped string.
	 */
	function escapeHtml( str ) {
		var div = document.createElement( 'div' );
		div.textContent = str;
		return div.innerHTML;
	}

	/**
	 * Get human-readable label for a field slug.
	 *
	 * @param {string} slug Field slug.
	 * @return {string} Human-readable label.
	 */
	function getFieldLabel( slug ) {
		if ( fieldLabels.hasOwnProperty( slug ) ) {
			return fieldLabels[ slug ];
		}
		// Fallback: convert slug to title case.
		return slug
			.replace( /_/g, ' ' )
			.replace( /\b\w/g, function( char ) {
				return char.toUpperCase();
			} );
	}

	/**
	 * Get icon for field type.
	 *
	 * @param {string} type Field type.
	 * @return {string} Icon character.
	 */
	function getTypeIcon( type ) {
		switch ( type ) {
			case 'rich':
				return '&#xf464;'; // Text editor icon (dashicons).
			case 'textarea':
				return '&#xf478;'; // Text icon.
			case 'single':
				return '&#xf145;'; // Minus icon (single line).
			case 'array':
				return '&#xf163;'; // List icon.
			default:
				return '&#xf12a;'; // Marker icon.
		}
	}

	/**
	 * Create a styled section header for a field.
	 *
	 * @param {string} slug Field slug.
	 * @param {string} type Field type (optional).
	 * @return {string} HTML string for the header.
	 */
	function createSectionHeader( slug, type ) {
		var label = getFieldLabel( slug );
		var typeClass = type ? ' doc-field-header--' + escapeHtml( type ) : '';
		var hint = config.strings.fieldContent || 'Contenido del campo';

		return '<div class="doc-field-header' + typeClass + '">' +
			'<div class="doc-field-header__inner">' +
			'<span class="doc-field-header__icon dashicons">' + getTypeIcon( type ) + '</span>' +
			'<span class="doc-field-header__label">' + escapeHtml( label ) + '</span>' +
			'<span class="doc-field-header__hint">' + escapeHtml( hint ) + '</span>' +
			'</div>' +
			'</div>';
	}

	/**
	 * Create a separator between field sections.
	 *
	 * @return {string} HTML string for the separator.
	 */
	function createSeparator() {
		return '<hr class="doc-field-separator" />';
	}

	/**
	 * Process text content and replace markers with headers.
	 *
	 * @param {string} text Text content to process.
	 * @return {string} Processed text with headers.
	 */
	function processText( text ) {
		// Pattern for opening tag (both escaped and unescaped).
		// Matches: <!-- documentate-field slug="xxx" type="yyy" -->
		// Or escaped: &lt;!-- documentate-field slug="xxx" type="yyy" --&gt;
		var openPattern = /(?:&lt;!--|<!--)\s*documentate-field\s+([^>]*?)(?:--&gt;|-->)/gi;

		// Pattern for closing tag - we'll remove these entirely.
		// Matches: <!-- /documentate-field -->
		// Or escaped: &lt;!-- /documentate-field --&gt;
		var closePattern = /(?:&lt;!--|<!--)\s*\/documentate-field\s*(?:--&gt;|-->)/gi;

		// First, remove all closing tags (they add visual noise).
		text = text.replace( closePattern, function() {
			return createSeparator();
		} );

		// Process opening tags - replace with section headers.
		text = text.replace( openPattern, function( match, attrs ) {
			var slugMatch = /slug="([^"]+)"/.exec( attrs );
			var typeMatch = /type="([^"]+)"/.exec( attrs );

			if ( ! slugMatch ) {
				return match; // Keep original if no slug found.
			}

			var slug = slugMatch[1];
			var type = typeMatch ? typeMatch[1] : '';

			return createSectionHeader( slug, type );
		} );

		// Clean up multiple consecutive separators.
		text = text.replace( /(<hr class="doc-field-separator" \/>[\s\n]*){2,}/gi, '<hr class="doc-field-separator" />' );

		// Remove separator at the very end.
		text = text.replace( /<hr class="doc-field-separator" \/>\s*$/i, '' );

		// Remove separator right before a header.
		text = text.replace( /<hr class="doc-field-separator" \/>\s*(<div class="doc-field-header)/gi, '$1' );

		return text;
	}

	/**
	 * Process a single table cell or content element.
	 *
	 * @param {HTMLElement} element Element to process.
	 */
	function processElement( element ) {
		if ( processed.has( element ) ) {
			return;
		}

		var html = element.innerHTML;

		// Check if content contains documentate-field markers.
		if ( html.indexOf( 'documentate-field' ) === -1 ) {
			processed.add( element );
			return;
		}

		element.innerHTML = processText( html );
		processed.add( element );
	}

	/**
	 * Scan and process all diff table cells.
	 */
	function processDiffTable() {
		// Target cells in the diff table.
		var selectors = [
			'.revisions-diff table.diff td',
			'table.diff td.diff-deletedline',
			'table.diff td.diff-addedline',
			'table.diff td.diff-context',
			'.diff-table td',
			'.revisions-diff .diff-title'
		];

		var cells = document.querySelectorAll( selectors.join( ', ' ) );

		cells.forEach( function( cell ) {
			processElement( cell );
		} );
	}

	/**
	 * Initialize MutationObserver to watch for dynamic content changes.
	 */
	function initObserver() {
		// Find the revisions container.
		var containers = [
			document.querySelector( '.revisions-diff' ),
			document.querySelector( '.wp-revisions-container' ),
			document.querySelector( '#revisions-diff' ),
			document.getElementById( 'wpbody-content' )
		];

		var targetContainer = null;
		for ( var i = 0; i < containers.length; i++ ) {
			if ( containers[i] ) {
				targetContainer = containers[i];
				break;
			}
		}

		if ( ! targetContainer ) {
			// Fallback: observe body if no specific container found.
			targetContainer = document.body;
		}

		var observer = new MutationObserver( function( mutations ) {
			var shouldProcess = false;

			mutations.forEach( function( mutation ) {
				if ( mutation.type === 'childList' && mutation.addedNodes.length > 0 ) {
					mutation.addedNodes.forEach( function( node ) {
						if ( node.nodeType === Node.ELEMENT_NODE ) {
							// Check if added node or its children contain diff content.
							if ( node.classList &&
								( node.classList.contains( 'diff' ) ||
								  node.classList.contains( 'revisions-diff' ) ||
								  node.querySelector( 'table.diff' ) ) ) {
								shouldProcess = true;
							}
							// Also check for table cells directly.
							if ( node.tagName === 'TD' || node.querySelector( 'td' ) ) {
								shouldProcess = true;
							}
						}
					} );
				}
			} );

			if ( shouldProcess ) {
				// Debounce processing.
				clearTimeout( window.documentateRevisionsTimeout );
				window.documentateRevisionsTimeout = setTimeout( processDiffTable, 50 );
			}
		} );

		observer.observe( targetContainer, {
			childList: true,
			subtree: true
		} );
	}

	/**
	 * Initialize the revisions enhancement.
	 */
	function init() {
		// Process existing content.
		processDiffTable();

		// Set up observer for dynamic content.
		initObserver();

		// Also process when slider changes (WordPress revisions slider).
		document.addEventListener( 'click', function( e ) {
			if ( e.target.closest( '.revisions-controls' ) ||
				 e.target.closest( '.revisions-tickmarks' ) ) {
				setTimeout( processDiffTable, 100 );
			}
		} );

		// Listen for revision slider input events.
		var slider = document.querySelector( 'input.revisions-goto' );
		if ( slider ) {
			slider.addEventListener( 'input', function() {
				setTimeout( processDiffTable, 100 );
			} );
			slider.addEventListener( 'change', function() {
				setTimeout( processDiffTable, 100 );
			} );
		}
	}

	// Initialize when DOM is ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	// Also initialize on window load for deferred content.
	window.addEventListener( 'load', function() {
		setTimeout( processDiffTable, 200 );
	} );

} )();
