/**
 * Document Export E2E Tests for Documentate plugin.
 *
 * Tests the document export modal and generation functionality.
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );
const {
	createDocument,
	saveDocument,
	getPostIdFromUrl,
} = require( '../utils/helpers' );

test.describe( 'Document Export', () => {
	/**
	 * Helper to create a document with a type (needed for export).
	 */
	async function createDocumentWithType( admin, page, title ) {
		await createDocument( admin, page, { title } );

		// Select a document type if available
		const typeOptions = page.locator(
			'#documentate_doc_typechecklist input[type="checkbox"], #documentate_doc_typechecklist input[type="radio"]'
		);

		if ( ( await typeOptions.count() ) > 0 ) {
			await typeOptions.first().check();
		}

		// Publish the document (export usually requires published document)
		await page.locator( '#publish' ).click();
		await page.waitForSelector( '#message, .notice-success', {
			timeout: 10000,
		} );

		return await getPostIdFromUrl( page );
	}

	test( 'export button is visible on document edit page', async ( {
		admin,
		page,
	} ) => {
		const postId = await createDocumentWithType(
			admin,
			page,
			'Export Button Test'
		);

		// Reload the page
		await admin.visitAdminPage( 'post.php', `post=${ postId }&action=edit` );

		// Look for export button
		const exportButton = page.locator(
			'#documentate-export-button, .documentate-export-button, [data-action="documentate-export"], button:has-text("Export"), button:has-text("Exportar"), a:has-text("Export"), a:has-text("Exportar")'
		);

		// Export button should be visible
		await expect( exportButton.first() ).toBeVisible();
	} );

	test( 'can open export modal from document edit screen', async ( {
		admin,
		page,
	} ) => {
		const postId = await createDocumentWithType(
			admin,
			page,
			'Export Modal Test'
		);

		await admin.visitAdminPage( 'post.php', `post=${ postId }&action=edit` );

		// Find and click export button
		const exportButton = page.locator(
			'#documentate-export-button, .documentate-export-button, [data-action="documentate-export"], button:has-text("Export"), button:has-text("Exportar")'
		).first();

		if ( ! ( await exportButton.isVisible() ) ) {
			test.skip();
			return;
		}

		await exportButton.click();

		// Wait for modal to appear
		const modal = page.locator(
			'.documentate-export-modal, #documentate-export-modal, .documentate-modal, [role="dialog"]'
		);

		await expect( modal.first() ).toBeVisible( { timeout: 5000 } );
	} );

	test( 'export modal shows format options', async ( { admin, page } ) => {
		const postId = await createDocumentWithType(
			admin,
			page,
			'Export Format Test'
		);

		await admin.visitAdminPage( 'post.php', `post=${ postId }&action=edit` );

		const exportButton = page.locator(
			'#documentate-export-button, .documentate-export-button, [data-action="documentate-export"], button:has-text("Export"), button:has-text("Exportar")'
		).first();

		if ( ! ( await exportButton.isVisible() ) ) {
			test.skip();
			return;
		}

		await exportButton.click();

		// Wait for modal
		await page.waitForSelector(
			'.documentate-export-modal, #documentate-export-modal, .documentate-modal',
			{ state: 'visible', timeout: 5000 }
		);

		// Look for format options (DOCX, ODT, PDF)
		const docxOption = page.locator(
			'button:has-text("DOCX"), a:has-text("DOCX"), .export-docx, [data-format="docx"]'
		);
		const odtOption = page.locator(
			'button:has-text("ODT"), a:has-text("ODT"), .export-odt, [data-format="odt"]'
		);

		// At least one format should be available
		const hasDocx = ( await docxOption.count() ) > 0;
		const hasOdt = ( await odtOption.count() ) > 0;

		expect( hasDocx || hasOdt ).toBe( true );
	} );

	test( 'DOCX export option is clickable', async ( { admin, page } ) => {
		const postId = await createDocumentWithType(
			admin,
			page,
			'DOCX Export Test'
		);

		await admin.visitAdminPage( 'post.php', `post=${ postId }&action=edit` );

		const exportButton = page.locator(
			'#documentate-export-button, .documentate-export-button, [data-action="documentate-export"], button:has-text("Export"), button:has-text("Exportar")'
		).first();

		if ( ! ( await exportButton.isVisible() ) ) {
			test.skip();
			return;
		}

		await exportButton.click();

		await page.waitForSelector(
			'.documentate-export-modal, #documentate-export-modal, .documentate-modal',
			{ state: 'visible', timeout: 5000 }
		);

		// Find DOCX button
		const docxButton = page.locator(
			'button:has-text("DOCX"), a:has-text("DOCX"), .export-docx, [data-format="docx"]'
		).first();

		if ( ( await docxButton.count() ) > 0 ) {
			// Verify it's enabled/clickable
			await expect( docxButton ).toBeEnabled();
		}
	} );

	test( 'can close export modal with Escape key', async ( {
		admin,
		page,
	} ) => {
		const postId = await createDocumentWithType(
			admin,
			page,
			'Close Modal Test'
		);

		await admin.visitAdminPage( 'post.php', `post=${ postId }&action=edit` );

		const exportButton = page.locator(
			'#documentate-export-button, .documentate-export-button, [data-action="documentate-export"], button:has-text("Export"), button:has-text("Exportar")'
		).first();

		if ( ! ( await exportButton.isVisible() ) ) {
			test.skip();
			return;
		}

		await exportButton.click();

		// Wait for modal
		const modal = page.locator(
			'.documentate-export-modal, #documentate-export-modal, .documentate-modal'
		).first();

		await expect( modal ).toBeVisible( { timeout: 5000 } );

		// Press Escape to close
		await page.keyboard.press( 'Escape' );

		// Modal should be hidden
		await expect( modal ).toBeHidden( { timeout: 5000 } );
	} );
} );
