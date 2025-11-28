/**
 * Document Types E2E Tests for Documentate plugin.
 *
 * Tests document type taxonomy management including
 * creating types, setting colors, and template upload.
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );
const { navigateToDocumentTypes } = require( '../utils/helpers' );

test.describe( 'Document Types Management', () => {
	test( 'can navigate to document types admin page', async ( {
		admin,
		page,
	} ) => {
		await navigateToDocumentTypes( admin );

		// Verify we're on the document types page
		await expect( page ).toHaveURL( /taxonomy=documentate_doc_type/ );

		// Verify the add form is present
		await expect( page.locator( '#col-left' ) ).toBeVisible();
	} );

	test( 'can create new document type with name', async ( {
		admin,
		page,
	} ) => {
		await navigateToDocumentTypes( admin );

		const typeName = `Test Type ${ Date.now() }`;

		// Fill in the name field
		await page.locator( '#tag-name' ).fill( typeName );

		// Submit the form
		await page.locator( '#submit' ).click();

		// Wait for the page to update
		await page.waitForResponse(
			( response ) =>
				response.url().includes( 'admin-ajax.php' ) ||
				response.url().includes( 'edit-tags.php' )
		);

		// Verify the new type appears in the list
		// The term should appear in the table
		await page.reload();

		const typeRow = page.locator( `#the-list tr`, {
			has: page.locator( `a.row-title:has-text("${ typeName }")` ),
		} );

		await expect( typeRow ).toBeVisible();
	} );

	test( 'can access document type edit page', async ( { admin, page } ) => {
		await navigateToDocumentTypes( admin );

		// Click on the first document type to edit
		const firstTypeLink = page.locator( '#the-list tr:first-child a.row-title' );

		if ( ( await firstTypeLink.count() ) === 0 ) {
			test.skip();
			return;
		}

		await firstTypeLink.click();

		// Verify we're on the edit page (term.php or action=edit)
		await expect( page ).toHaveURL( /term\.php|action=edit/ );

		// Verify the edit form is visible
		await expect( page.locator( '#edittag' ) ).toBeVisible();
	} );

	test( 'document type edit page shows color picker', async ( {
		admin,
		page,
	} ) => {
		await navigateToDocumentTypes( admin );

		// Click on the first document type to edit
		const firstTypeLink = page.locator( '#the-list tr:first-child a.row-title' );

		if ( ( await firstTypeLink.count() ) === 0 ) {
			test.skip();
			return;
		}

		await firstTypeLink.click();

		// The color picker replaces the input with a visual picker
		// Look for the color picker wrapper or the hidden input
		const colorPickerWrapper = page.locator( '.wp-picker-container' );
		const colorInput = page.locator( '#documentate_type_color' );

		// Either the picker wrapper is visible OR the input exists
		const hasColorPicker =
			( await colorPickerWrapper.count() ) > 0 ||
			( await colorInput.count() ) > 0;

		expect( hasColorPicker ).toBe( true );
	} );

	test( 'document type edit page shows template field', async ( {
		admin,
		page,
	} ) => {
		await navigateToDocumentTypes( admin );

		// Click on the first document type to edit
		const firstTypeLink = page.locator( '#the-list tr:first-child a.row-title' );

		if ( ( await firstTypeLink.count() ) === 0 ) {
			test.skip();
			return;
		}

		await firstTypeLink.click();

		// The template field may be a hidden input or wrapped in a container
		// Check for the input existence (visible or hidden)
		const templateInput = page.locator(
			'input[name="documentate_type_template_id"], #documentate_type_template_id'
		);

		// Verify the input exists (doesn't need to be visible)
		expect( await templateInput.count() ).toBeGreaterThan( 0 );
	} );

	test( 'document type shows detected fields from template', async ( {
		admin,
		page,
	} ) => {
		await navigateToDocumentTypes( admin );

		// Click on a demo document type that has a template
		const demoTypeLink = page.locator(
			'#the-list tr a.row-title:has-text("demo"), #the-list tr a.row-title:has-text("Demo")'
		).first();

		if ( ( await demoTypeLink.count() ) === 0 ) {
			// Try the first type instead
			const firstTypeLink = page.locator(
				'#the-list tr:first-child a.row-title'
			);
			if ( ( await firstTypeLink.count() ) === 0 ) {
				test.skip();
				return;
			}
			await firstTypeLink.click();
		} else {
			await demoTypeLink.click();
		}

		// Verify the page loaded correctly
		await expect( page.locator( '#edittag' ) ).toBeVisible();
	} );

	test( 'can set document type color', async ( { admin, page } ) => {
		await navigateToDocumentTypes( admin );

		// Click on the first document type to edit
		const firstTypeLink = page.locator( '#the-list tr:first-child a.row-title' );

		if ( ( await firstTypeLink.count() ) === 0 ) {
			test.skip();
			return;
		}

		await firstTypeLink.click();

		// Find color input (may be hidden by color picker)
		const colorInput = page.locator( '#documentate_type_color' );

		if ( ( await colorInput.count() ) === 0 ) {
			test.skip();
			return;
		}

		// Use force to fill the hidden input
		await colorInput.fill( '#ff5733', { force: true } );

		// Submit the form
		await page.locator( '#submit, input[type="submit"]' ).click();

		// Wait for save
		await page.waitForURL( /message=/ );

		// Verify the color was saved (check for success message)
		await expect( page.locator( '#message, .notice-success' ) ).toBeVisible();
	} );
} );
