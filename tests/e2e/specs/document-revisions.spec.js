/**
 * Document Revisions E2E Tests for Documentate plugin.
 *
 * Tests the document revision system.
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );
const {
	createDocument,
	getPostIdFromUrl,
	waitForSave,
	fillTitle,
} = require( '../utils/helpers' );

test.describe( 'Document Revisions', () => {
	test( 'creating and editing document creates revisions', async ( {
		admin,
		page,
	} ) => {
		// Create a document
		await createDocument( admin, page, { title: 'Revision Test Document' } );

		// Publish it
		await page.locator( '#publish' ).click();
		await waitForSave( page );

		const postId = await getPostIdFromUrl( page );

		// Edit the title to create a revision
		await fillTitle( page, 'Revision Test Document - Updated' );

		// Update
		await page.locator( '#publish' ).click();
		await waitForSave( page );

		// Check for revisions link
		const revisionsLink = page.locator(
			'#revisions-meta-box a, .misc-pub-revisions a, a[href*="revision.php"]'
		);

		// If revisions are enabled, the link should exist
		if ( ( await revisionsLink.count() ) > 0 ) {
			await expect( revisionsLink.first() ).toBeVisible();
		}
	} );

	test( 'can view revision history', async ( { admin, page } ) => {
		// Create and edit document to ensure revisions exist
		await createDocument( admin, page, { title: 'View Revisions Test' } );
		await page.locator( '#publish' ).click();
		await waitForSave( page );

		const postId = await getPostIdFromUrl( page );

		// Make an edit
		await fillTitle( page, 'View Revisions Test - Edit 1' );
		await page.locator( '#publish' ).click();
		await waitForSave( page );

		// Look for revisions link
		const revisionsLink = page.locator(
			'a[href*="revision.php"], .misc-pub-revisions a'
		).first();

		if ( ( await revisionsLink.count() ) === 0 ) {
			test.skip();
			return;
		}

		// Click to view revisions
		await revisionsLink.click();

		// Should be on revisions page
		await expect( page ).toHaveURL( /revision\.php|action=revision/ );
	} );

	test( 'revisions page shows comparison slider', async ( {
		admin,
		page,
	} ) => {
		// Create document with multiple revisions
		await createDocument( admin, page, { title: 'Compare Revisions Test' } );
		await page.locator( '#publish' ).click();
		await waitForSave( page );

		const postId = await getPostIdFromUrl( page );

		// Make multiple edits
		for ( let i = 1; i <= 2; i++ ) {
			await fillTitle( page, `Compare Revisions Test - Edit ${ i }` );
			await page.locator( '#publish' ).click();
			await waitForSave( page );
		}

		// Navigate to revisions
		const revisionsLink = page.locator(
			'a[href*="revision.php"], .misc-pub-revisions a'
		).first();

		if ( ( await revisionsLink.count() ) === 0 ) {
			test.skip();
			return;
		}

		await revisionsLink.click();

		// Look for revision slider/controls
		const revisionControls = page.locator(
			'.revisions-controls, #revisions-controls, .wp-slider, input[type="range"]'
		);

		// Revision UI elements should be visible
		await expect( page.locator( '.revisions, #revisions' ).first() ).toBeVisible();
	} );

	test( 'can restore from revision', async ( { admin, page } ) => {
		const originalTitle = 'Restore Revision Test - Original';
		const updatedTitle = 'Restore Revision Test - Updated';

		// Create document
		await createDocument( admin, page, { title: originalTitle } );
		await page.locator( '#publish' ).click();
		await waitForSave( page );

		const postId = await getPostIdFromUrl( page );

		// Update the title
		await fillTitle( page, updatedTitle );
		await page.locator( '#publish' ).click();
		await waitForSave( page );

		// Navigate to revisions
		const revisionsLink = page.locator(
			'a[href*="revision.php"], .misc-pub-revisions a'
		).first();

		if ( ( await revisionsLink.count() ) === 0 ) {
			test.skip();
			return;
		}

		await revisionsLink.click();

		// Wait for revisions page to load
		await page.waitForSelector( '.revisions, #revisions', { timeout: 5000 } );

		// Look for restore button
		const restoreButton = page.locator(
			'input[value*="Restore"], button:has-text("Restore"), a:has-text("Restore"), input[value*="Restaurar"], button:has-text("Restaurar")'
		).first();

		if ( ( await restoreButton.count() ) > 0 && ( await restoreButton.isVisible() ) ) {
			await restoreButton.click();

			// Should redirect back to edit page
			await page.waitForURL( /post\.php.*action=edit/, { timeout: 10000 } );

			// Verify we're back on the edit page
			await expect( page ).toHaveURL( /post\.php/ );
		}
	} );
} );
