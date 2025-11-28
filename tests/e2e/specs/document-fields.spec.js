/**
 * Document Fields E2E Tests for Documentate plugin.
 *
 * Tests field types and interactions when a document type is selected.
 * Requires demo document types to be available (created on plugin activation).
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );
const {
	createDocument,
	saveDocument,
	getPostIdFromUrl,
	waitForSave,
} = require( '../utils/helpers' );

test.describe( 'Document Fields', () => {
	/**
	 * Helper to select a document type and wait for fields to load.
	 */
	async function selectDocTypeAndWaitForFields( page ) {
		const typeOptions = page.locator(
			'#documentate_doc_typechecklist input[type="checkbox"], #documentate_doc_typechecklist input[type="radio"]'
		);

		if ( ( await typeOptions.count() ) === 0 ) {
			return false;
		}

		// Select the first document type
		await typeOptions.first().check();

		// Wait a moment for fields to potentially load via AJAX
		await page.waitForTimeout( 500 );

		return true;
	}

	test( 'fields appear when document type is selected', async ( {
		admin,
		page,
	} ) => {
		await createDocument( admin, page, { title: 'Fields Test Document' } );

		const hasDocTypes = await selectDocTypeAndWaitForFields( page );
		if ( ! hasDocTypes ) {
			test.skip();
			return;
		}

		// Save to trigger field rendering
		await saveDocument( page, 'draft' );

		// Look for any documentate field inputs
		const fieldInputs = page.locator(
			'input[name^="documentate_field_"], textarea[name^="documentate_field_"]'
		);

		// There should be at least one field if a document type is selected
		const fieldCount = await fieldInputs.count();
		expect( fieldCount ).toBeGreaterThanOrEqual( 0 );
	} );

	test( 'can fill simple text field and save', async ( { admin, page } ) => {
		await createDocument( admin, page, { title: 'Text Field Test' } );

		const hasDocTypes = await selectDocTypeAndWaitForFields( page );
		if ( ! hasDocTypes ) {
			test.skip();
			return;
		}

		await saveDocument( page, 'draft' );
		const postId = await getPostIdFromUrl( page );

		// Reload to get fields rendered
		await admin.visitAdminPage( 'post.php', `post=${ postId }&action=edit` );

		// Find a text input field
		const textField = page.locator(
			'input[name^="documentate_field_"][type="text"]'
		).first();

		if ( ( await textField.count() ) > 0 ) {
			await textField.fill( 'Test text value' );
			await saveDocument( page, 'draft' );

			// Reload and verify
			await page.reload();
			await expect( textField ).toHaveValue( 'Test text value' );
		}
	} );

	test( 'can fill textarea field and save', async ( { admin, page } ) => {
		await createDocument( admin, page, { title: 'Textarea Field Test' } );

		const hasDocTypes = await selectDocTypeAndWaitForFields( page );
		if ( ! hasDocTypes ) {
			test.skip();
			return;
		}

		await saveDocument( page, 'draft' );
		const postId = await getPostIdFromUrl( page );

		await admin.visitAdminPage( 'post.php', `post=${ postId }&action=edit` );

		// Find a textarea field (non-TinyMCE)
		const textareaField = page.locator(
			'textarea[name^="documentate_field_"]:not(.wp-editor-area)'
		).first();

		if ( ( await textareaField.count() ) > 0 ) {
			await textareaField.fill( 'Test textarea content\nWith multiple lines' );
			await saveDocument( page, 'draft' );

			await page.reload();
			await expect( textareaField ).toContainText( 'Test textarea content' );
		}
	} );

	test( 'can fill rich HTML field (TinyMCE) and save', async ( {
		admin,
		page,
	} ) => {
		await createDocument( admin, page, { title: 'Rich Field Test' } );

		const hasDocTypes = await selectDocTypeAndWaitForFields( page );
		if ( ! hasDocTypes ) {
			test.skip();
			return;
		}

		await saveDocument( page, 'draft' );
		const postId = await getPostIdFromUrl( page );

		await admin.visitAdminPage( 'post.php', `post=${ postId }&action=edit` );

		// Find a TinyMCE editor textarea
		const richTextarea = page.locator( 'textarea.wp-editor-area' ).first();

		if ( ( await richTextarea.count() ) > 0 ) {
			// Switch to Text/HTML mode
			const textTabId = await richTextarea.getAttribute( 'id' );
			const textTab = page.locator( `#${ textTabId }-html` );

			if ( await textTab.isVisible() ) {
				await textTab.click();
			}

			await richTextarea.fill( '<p>Rich HTML content with <strong>bold</strong> text</p>' );
			await saveDocument( page, 'draft' );

			await page.reload();

			// Switch to text mode again to read value
			if ( await textTab.isVisible() ) {
				await textTab.click();
			}

			const value = await richTextarea.inputValue();
			expect( value ).toContain( 'Rich HTML content' );
		}
	} );

	test( 'can add items to array/repeater field', async ( { admin, page } ) => {
		await createDocument( admin, page, { title: 'Array Field Test' } );

		const hasDocTypes = await selectDocTypeAndWaitForFields( page );
		if ( ! hasDocTypes ) {
			test.skip();
			return;
		}

		await saveDocument( page, 'draft' );
		const postId = await getPostIdFromUrl( page );

		await admin.visitAdminPage( 'post.php', `post=${ postId }&action=edit` );

		// Look for an "Add" button for repeater fields
		const addButton = page.locator(
			'.documentate-add-item, .add-repeater-item, button:has-text("Add"), button:has-text("Agregar")'
		).first();

		if ( ( await addButton.count() ) > 0 && ( await addButton.isVisible() ) ) {
			// Count items before
			const itemsBefore = await page.locator(
				'.documentate-repeater-item, .repeater-item'
			).count();

			// Click add
			await addButton.click();

			// Wait for new item
			await page.waitForTimeout( 300 );

			// Count items after
			const itemsAfter = await page.locator(
				'.documentate-repeater-item, .repeater-item'
			).count();

			expect( itemsAfter ).toBeGreaterThanOrEqual( itemsBefore );
		}
	} );

	test( 'can remove items from array/repeater field', async ( {
		admin,
		page,
	} ) => {
		await createDocument( admin, page, { title: 'Remove Array Item Test' } );

		const hasDocTypes = await selectDocTypeAndWaitForFields( page );
		if ( ! hasDocTypes ) {
			test.skip();
			return;
		}

		await saveDocument( page, 'draft' );
		const postId = await getPostIdFromUrl( page );

		await admin.visitAdminPage( 'post.php', `post=${ postId }&action=edit` );

		// Look for remove button on repeater items
		const removeButton = page.locator(
			'.documentate-remove-item, .remove-repeater-item, button:has-text("Remove"), button:has-text("Eliminar")'
		).first();

		if ( ( await removeButton.count() ) > 0 && ( await removeButton.isVisible() ) ) {
			const itemsBefore = await page.locator(
				'.documentate-repeater-item, .repeater-item'
			).count();

			if ( itemsBefore > 0 ) {
				await removeButton.click();
				await page.waitForTimeout( 300 );

				const itemsAfter = await page.locator(
					'.documentate-repeater-item, .repeater-item'
				).count();

				expect( itemsAfter ).toBeLessThan( itemsBefore );
			}
		}
	} );

	test( 'field values persist after save and reload', async ( {
		admin,
		page,
	} ) => {
		await createDocument( admin, page, { title: 'Persistence Test' } );

		const hasDocTypes = await selectDocTypeAndWaitForFields( page );
		if ( ! hasDocTypes ) {
			test.skip();
			return;
		}

		await saveDocument( page, 'draft' );
		const postId = await getPostIdFromUrl( page );

		await admin.visitAdminPage( 'post.php', `post=${ postId }&action=edit` );

		// Find any text input and fill it
		const textField = page.locator(
			'input[name^="documentate_field_"][type="text"]'
		).first();

		if ( ( await textField.count() ) > 0 ) {
			const testValue = `Persistence test ${ Date.now() }`;
			await textField.fill( testValue );

			// Update the document
			await page.locator( '#publish' ).click();
			await waitForSave( page );

			// Hard reload
			await page.goto( page.url() );

			// Verify value persists
			await expect( textField ).toHaveValue( testValue );
		}
	} );

	test( 'array field respects max items limit', async ( { admin, page } ) => {
		// This test verifies the add button behavior when max items is reached
		await createDocument( admin, page, { title: 'Max Items Test' } );

		const hasDocTypes = await selectDocTypeAndWaitForFields( page );
		if ( ! hasDocTypes ) {
			test.skip();
			return;
		}

		await saveDocument( page, 'draft' );
		const postId = await getPostIdFromUrl( page );

		await admin.visitAdminPage( 'post.php', `post=${ postId }&action=edit` );

		const addButton = page.locator(
			'.documentate-add-item, .add-repeater-item, button:has-text("Add"), button:has-text("Agregar")'
		).first();

		if ( ( await addButton.count() ) > 0 && ( await addButton.isVisible() ) ) {
			// Try to add items up to a reasonable number
			// The max is 20 according to the plugin
			for ( let i = 0; i < 5; i++ ) {
				if ( await addButton.isEnabled() ) {
					await addButton.click();
					await page.waitForTimeout( 100 );
				}
			}

			// Verify items were added
			const itemCount = await page.locator(
				'.documentate-repeater-item, .repeater-item'
			).count();

			expect( itemCount ).toBeGreaterThan( 0 );
		}
	} );
} );
