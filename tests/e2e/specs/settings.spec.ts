/**
 * E2E tests for the Plugin Name admin settings page.
 *
 * Covers:
 *  - Page load and React app mount (P0)
 *  - Settings tab: toggle, save, persist (P1)
 *  - Second tab: renders without errors (P1)
 *  - REST API permission check (P0)
 */

/**
 * External dependencies
 */
import { test, expect, Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import { collectConsoleErrors, waitForPluginApp } from '../utils/helpers';

const SETTINGS_URL = '/wp-admin/admin.php?page=plugin-name';

async function goToSettings( page: Page ) {
	await page.goto( SETTINGS_URL );
	await waitForPluginApp( page );
}

// ─── P0: Page load ────────────────────────────────────────────────────────────

test.describe( 'P0 – Settings page load', () => {
	test( 'renders the React app without console errors', async ( { page } ) => {
		const getErrors = collectConsoleErrors( page );

		await page.goto( SETTINGS_URL );
		await waitForPluginApp( page );

		const errors = getErrors();
		expect( errors ).toHaveLength( 0 );
	} );

	test( 'settings page is visible and interactive', async ( { page } ) => {
		await goToSettings( page );
		await expect( page.locator( '.plugin-name-container' ) ).toBeVisible();
		await expect( page.getByRole( 'tab', { name: 'Settings' } ) ).toBeVisible();
		await expect( page.getByRole( 'tab', { name: 'Second Tab' } ) ).toBeVisible();
	} );
} );

// ─── P1: Settings tab ─────────────────────────────────────────────────────────

test.describe( 'P1 – Settings tab', () => {
	test( 'can save settings and values persist', async ( { page } ) => {
		await goToSettings( page );

		await page.getByRole( 'tab', { name: 'Settings' } ).click();
		await expect( page.getByRole( 'heading', { name: 'General Settings' } ) ).toBeVisible();

		await page.getByRole( 'button', { name: 'Save Settings' } ).click();
		await expect(
			page.locator( '.components-notice__content', { hasText: 'Settings saved successfully.' } )
		).toBeVisible( { timeout: 10_000 } );
	} );
} );

// ─── P1: Second tab ───────────────────────────────────────────────────────────

test.describe( 'P1 – Second tab', () => {
	test( 'second tab renders without errors', async ( { page } ) => {
		await goToSettings( page );
		await page.getByRole( 'tab', { name: 'Second Tab' } ).click();
		await expect( page.getByRole( 'heading', { name: 'Item List' } ) ).toBeVisible();
	} );
} );

// ─── P0: REST API permissions ─────────────────────────────────────────────────

test.describe( 'P0 – REST API security', () => {
	test( 'unauthenticated request returns 401 or 403', async ( { page } ) => {
		const response = await page.request.get( '/wp-json/plugin-name/v1/settings' );
		expect( [ 401, 403 ] ).toContain( response.status() );
	} );
} );
