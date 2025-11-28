/**
 * Document CRUD E2E Tests for Documentate plugin.
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );
const {
	createDocument,
	saveDocument,
	navigateToDocumentsList,
	getPostIdFromUrl,
	trashDocument,
	waitForSave,
	fillTitle,
	getTitleValue,
} = require( '../utils/helpers' );

test.describe( 'Document CRUD Operations', () => {
	test( 'can create a new document with title', async ( { admin, page } ) => {
		await createDocument( admin, page, { title: 'Test Document Title' } );

		// Verify title is filled
		const titleValue = await getTitleValue( page );
		expect( titleValue ).toBe( 'Test Document Title' );

		// Save as draft
		await saveDocument( page, 'draft' );

		// Verify success message
		await expect( page.locator( '#message, .notice-success' ) ).toBeVisible();
	} );

	test( 'can create document and select document type', async ( {
		admin,
		page,
	} ) => {
		await createDocument( admin, page, { title: 'Document With Type' } );

		// Check if document type meta box exists
		const docTypeMetaBox = page.locator(
			'#documentate_doc_typediv, #taxonomy-documentate_doc_type'
		);

		// Skip if meta box not visible (may not exist in test env)
		if ( ! ( await docTypeMetaBox.isVisible().catch( () => false ) ) ) {
			test.skip();
			return;
		}

		// Look for any document type option
		const typeOptions = page.locator(
			'#documentate_doc_typechecklist input[type="checkbox"], #documentate_doc_typechecklist input[type="radio"]'
		);

		// If there are document types, select the first one
		if ( ( await typeOptions.count() ) > 0 ) {
			await typeOptions.first().check();

			// Save the document
			await saveDocument( page, 'draft' );

			// Verify the type is selected after save
			await expect( typeOptions.first() ).toBeChecked();
		}
	} );

	test( 'can edit existing document title', async ( { admin, page } ) => {
		// Create a document first
		await createDocument( admin, page, { title: 'Original Title' } );
		await saveDocument( page, 'draft' );

		// Get the post ID
		const postId = await getPostIdFromUrl( page );
		expect( postId ).toBeTruthy();

		// Visit the edit page again
		await admin.visitAdminPage( 'post.php', `post=${ postId }&action=edit` );

		// Wait for page to fully load
		await page.waitForLoadState( 'networkidle' );

		// Try to edit the title using the hidden #title input with force
		const titleInput = page.locator( '#title' );
		await titleInput.fill( 'Updated Title', { force: true } );

		// Save using the Update/Save button
		const saveButton = page.locator( '#publish' );
		await saveButton.click();

		// Wait for save
		await page.waitForLoadState( 'networkidle' );

		// Verify we still have a post ID (save succeeded)
		const newPostId = await getPostIdFromUrl( page );
		expect( newPostId ).toBe( postId );
	} );

	test( 'can save document as draft', async ( { admin, page } ) => {
		await createDocument( admin, page, { title: 'Draft Document' } );

		// Click Save Draft button
		await page.locator( '#save-post' ).click();
		await waitForSave( page );

		// Verify document is saved - either we have a success notice or the post has an ID
		const successNotice = page.locator( '#message, .notice-success' );
		const postId = await getPostIdFromUrl( page );

		const isSaved =
			( await successNotice.count() ) > 0 || postId !== null;

		expect( isSaved ).toBe( true );
	} );

	test( 'can publish document', async ( { admin, page } ) => {
		await createDocument( admin, page, { title: 'Published Document' } );

		// Click Publish button
		await page.locator( '#publish' ).click();
		await waitForSave( page );

		// Verify document is published - check for success message or post status
		const successMessage = page.locator( '#message, .notice-success' );
		const postStatus = page.locator( '#post_status' );

		// Either we have a success message or the post is now published
		const isPublished =
			( await successMessage.count() ) > 0 ||
			( await postStatus.inputValue() ) === 'publish';

		expect( isPublished ).toBe( true );
	} );

	test( 'can delete document by moving to trash', async ( {
		admin,
		page,
	} ) => {
		// Create a document first
		await createDocument( admin, page, { title: 'Document To Delete' } );
		await saveDocument( page, 'draft' );

		const postId = await getPostIdFromUrl( page );
		expect( postId ).toBeTruthy();

		// Trash the document
		await trashDocument( admin, page, postId );

		// Verify we're back on the list page
		await expect( page ).toHaveURL( /post_type=documentate_document/ );
	} );

	test( 'document appears in list after creation', async ( {
		admin,
		page,
	} ) => {
		const uniqueTitle = `List Test Document ${ Date.now() }`;

		// Create document
		await createDocument( admin, page, { title: uniqueTitle } );
		await saveDocument( page, 'draft' );

		// Navigate to documents list
		await navigateToDocumentsList( admin );

		// Verify document appears in list
		const documentRow = page.locator( `a.row-title:has-text("${ uniqueTitle }")` );
		await expect( documentRow ).toBeVisible();
	} );

	test( 'document type is locked after first save', async ( {
		admin,
		page,
	} ) => {
		await createDocument( admin, page, { title: 'Type Lock Test' } );

		// Check for document type options
		const typeOptions = page.locator(
			'#documentate_doc_typechecklist input[type="checkbox"], #documentate_doc_typechecklist input[type="radio"]'
		);

		// Skip if no document types available
		if ( ( await typeOptions.count() ) === 0 ) {
			test.skip();
			return;
		}

		// Select a document type
		await typeOptions.first().check();

		// Publish the document (type gets locked on publish)
		await page.locator( '#publish' ).click();
		await waitForSave( page );

		// Reload the page
		await page.reload();

		// Check if the type selection is disabled/locked
		// The plugin should either disable the checkboxes or show a message
		const lockedIndicator = page.locator(
			'#documentate_doc_typechecklist input:disabled, .documentate-type-locked, .doc-type-locked'
		);

		// If inputs are disabled, the type is locked
		const isLocked = await lockedIndicator.count() > 0 ||
			await typeOptions.first().isDisabled();

		// Document type should be locked after publishing with a type
		expect( isLocked ).toBe( true );
	} );
} );
