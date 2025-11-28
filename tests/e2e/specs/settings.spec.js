/**
 * Settings Page E2E Tests for Documentate plugin.
 *
 * Tests the plugin settings page functionality.
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );
const { navigateToSettings } = require( '../utils/helpers' );

test.describe( 'Settings Page', () => {
	test( 'can navigate to settings page', async ( { admin, page } ) => {
		await navigateToSettings( admin );

		// Verify we're on the settings page
		await expect( page ).toHaveURL( /page=documentate_settings/ );

		// Page should have a settings form
		const settingsForm = page.locator( 'form, .wrap' );
		await expect( settingsForm.first() ).toBeVisible();
	} );

	test( 'settings page shows conversion engine options', async ( {
		admin,
		page,
	} ) => {
		await navigateToSettings( admin );

		// Look for conversion engine radio buttons or select
		const engineOption = page.locator(
			'input[name*="engine"], select[name*="engine"], input[name*="conversion"], select[name*="conversion"]'
		);

		// At least one engine option should exist
		await expect( engineOption.first() ).toBeVisible();
	} );

	test( 'can select conversion engine', async ( { admin, page } ) => {
		await navigateToSettings( admin );

		// Find engine options (radio buttons)
		const collaboraOption = page.locator(
			'input[type="radio"][value="collabora"], input[type="radio"]:has(+ label:has-text("Collabora"))'
		);

		const wasmOption = page.locator(
			'input[type="radio"][value="wasm"], input[type="radio"]:has(+ label:has-text("WASM")), input[type="radio"]:has(+ label:has-text("LibreOffice"))'
		);

		// Try to select Collabora if available
		if ( ( await collaboraOption.count() ) > 0 ) {
			await collaboraOption.first().check();
			await expect( collaboraOption.first() ).toBeChecked();
		} else if ( ( await wasmOption.count() ) > 0 ) {
			await wasmOption.first().check();
			await expect( wasmOption.first() ).toBeChecked();
		}
	} );

	test( 'can configure Collabora base URL', async ( { admin, page } ) => {
		await navigateToSettings( admin );

		// Find Collabora URL input
		const urlInput = page.locator(
			'input[name*="collabora_url"], input[name*="base_url"], input[name*="collabora"][type="url"], input[name*="collabora"][type="text"]'
		).first();

		if ( ( await urlInput.count() ) === 0 ) {
			test.skip();
			return;
		}

		// Fill in a test URL
		await urlInput.fill( 'https://collabora.example.com' );

		// Verify the value is set
		await expect( urlInput ).toHaveValue( 'https://collabora.example.com' );
	} );

	test( 'can save settings successfully', async ( { admin, page } ) => {
		await navigateToSettings( admin );

		// Find any text input to modify
		const textInput = page.locator(
			'input[type="text"], input[type="url"]'
		).first();

		if ( ( await textInput.count() ) > 0 ) {
			// Get current value
			const originalValue = await textInput.inputValue();

			// Modify slightly (add/remove a space or similar)
			const newValue = originalValue.trim() + ' ';
			await textInput.fill( newValue.trim() );
		}

		// Find and click save button
		const saveButton = page.locator(
			'input[type="submit"], button[type="submit"], #submit'
		);

		await saveButton.click();

		// Wait for page to reload with success message
		await page.waitForSelector(
			'.notice-success, .updated, #setting-error-settings_updated',
			{ timeout: 10000 }
		);

		// Verify success message is visible
		const successMessage = page.locator(
			'.notice-success, .updated, #setting-error-settings_updated'
		);
		await expect( successMessage.first() ).toBeVisible();
	} );

	test( 'settings persist after save and reload', async ( {
		admin,
		page,
	} ) => {
		await navigateToSettings( admin );

		// Find a text input to test persistence
		const urlInput = page.locator(
			'input[name*="collabora_url"], input[name*="base_url"], input[type="url"]'
		).first();

		if ( ( await urlInput.count() ) === 0 ) {
			test.skip();
			return;
		}

		// Set a unique value
		const testValue = `https://test-${ Date.now() }.example.com`;
		await urlInput.fill( testValue );

		// Save
		await page.locator( 'input[type="submit"], button[type="submit"], #submit' ).click();

		// Wait for save
		await page.waitForSelector( '.notice-success, .updated', { timeout: 10000 } );

		// Reload the page
		await page.reload();

		// Verify the value persisted
		await expect( urlInput ).toHaveValue( testValue );
	} );
} );
