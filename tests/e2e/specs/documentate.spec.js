/**
 * WordPress E2E Test for Documentate plugin.
 *
 * @see https://developer.wordpress.org/block-editor/contributors/code/testing-overview/e2e/
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

test.describe( 'Documentate Plugin', () => {
	test( 'plugin is active', async ( { admin, page } ) => {
		// Navigate to plugins page.
		await admin.visitAdminPage( 'plugins.php' );

		// Look for the plugin by searching for its name in the table.
		const pluginRow = page.locator( 'tr', {
			has: page.locator( 'td', { hasText: 'Documentate' } ),
		} );
		await expect( pluginRow ).toBeVisible();

		// Check it has the "Deactivate" link (meaning it's active).
		const deactivateLink = pluginRow.locator(
			'span.deactivate a, a[href*="action=deactivate"]'
		);
		await expect( deactivateLink ).toBeVisible();
	} );

	test( 'can navigate to documents list', async ( { admin, page } ) => {
		// Navigate to the documents list page.
		await admin.visitAdminPage(
			'edit.php',
			'post_type=documentate_document'
		);

		// Verify we're on the correct page by checking the URL.
		await expect( page ).toHaveURL( /post_type=documentate_document/ );

		// Verify the page loaded correctly (has the posts table or empty message).
		const pageContent = page.locator( '#posts-filter, .no-items' );
		await expect( pageContent ).toBeVisible();
	} );

	test( 'can access add new document page', async ( { admin, page } ) => {
		// Navigate to add new document page.
		await admin.visitAdminPage(
			'post-new.php',
			'post_type=documentate_document'
		);

		// Verify we're on the correct page by checking the URL.
		await expect( page ).toHaveURL( /post_type=documentate_document/ );

		// Verify the editor form is present (works for both classic and block editor).
		const editorForm = page.locator( '#post, .editor-styles-wrapper' );
		await expect( editorForm ).toBeVisible();
	} );

	test( 'can navigate to document types taxonomy', async ( {
		admin,
		page,
	} ) => {
		// Navigate to document types taxonomy page.
		await admin.visitAdminPage(
			'edit-tags.php',
			'taxonomy=documentate_doc_type&post_type=documentate_document'
		);

		// Verify we're on the correct page by checking the URL.
		await expect( page ).toHaveURL( /taxonomy=documentate_doc_type/ );

		// Verify the taxonomy page layout is present.
		const taxonomyPage = page.locator( '#col-left' );
		await expect( taxonomyPage ).toBeVisible();
	} );
} );
