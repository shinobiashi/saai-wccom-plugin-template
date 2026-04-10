/**
 * External dependencies
 */
import { Page, APIRequestContext, expect } from '@playwright/test';

// ─── Auth ─────────────────────────────────────────────────────────────────────

/**
 * Log into WP admin.
 * Use this only when no storageState is available (e.g. a fresh browser context).
 */
export async function loginAdmin( page: Page ): Promise< void > {
	const baseURL = process.env.BASE_URL ?? 'http://localhost:8889';
	await page.goto( `${ baseURL }/wp-login.php` );
	await page.fill( '#user_login', process.env.ADMIN_USER ?? 'admin' );
	await page.fill( '#user_pass', process.env.ADMIN_PASSWORD ?? 'password' );
	await page.click( '#wp-submit' );
	await page.waitForURL( '**/wp-admin/**' );
}

// ─── REST API helpers ─────────────────────────────────────────────────────────

/**
 * Return the WP REST API nonce available on the plugin's settings page.
 * Assumes the page is already navigated to wp-admin/admin.php?page=plugin-name.
 */
export async function getPluginNonce( page: Page ): Promise< string > {
	const nonce = await page.evaluate(
		() =>
			(
				window as Window &
					typeof globalThis & {
						pluginNameData?: { nonce?: string };
					}
			 ).pluginNameData?.nonce ?? ''
	);
	if ( ! nonce ) {
		throw new Error(
			'pluginNameData.nonce not found — is the plugin settings page loaded?'
		);
	}
	return nonce as string;
}

/**
 * Fetch the WP REST nonce from the plugin settings page.
 */
export async function getRestNonce( page: Page ): Promise< string > {
	await page.goto( '/wp-admin/admin.php?page=plugin-name' );
	await page.waitForSelector( '.plugin-name-container' );
	return getPluginNonce( page );
}

// ─── WooCommerce REST helpers ─────────────────────────────────────────────────

interface ProductData {
	name: string;
	regular_price: string;
	manage_stock?: boolean;
	stock_quantity?: number;
}

/**
 * Create a WooCommerce simple product via REST API.
 * Uses the admin cookie auth + WP REST nonce obtained from the plugin page.
 *
 * @return The numeric product ID.
 */
export async function createSimpleProduct(
	page: Page,
	data: ProductData = { name: 'E2E Test Product', regular_price: '9.99' }
): Promise< number > {
	const nonce = await getRestNonce( page );

	const response = await page.request.post( '/wp-json/wc/v3/products', {
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': nonce,
		},
		data: {
			type: 'simple',
			status: 'publish',
			...data,
		},
	} );

	expect(
		response.ok(),
		`createSimpleProduct: ${ response.status() } ${ response.statusText() }`
	).toBeTruthy();
	const product = await response.json();
	return product.id as number;
}

/**
 * Delete a WooCommerce product (cleanup after tests).
 */
export async function deleteProduct(
	page: Page,
	productId: number
): Promise< void > {
	const nonce = await getRestNonce( page );
	await page.request.delete(
		`/wp-json/wc/v3/products/${ productId }?force=true`,
		{
			headers: { 'X-WP-Nonce': nonce },
		}
	);
}

/**
 * Enable Cash on Delivery payment method via WC REST API.
 */
export async function enableCashOnDelivery( page: Page ): Promise< void > {
	const nonce = await getRestNonce( page );
	await page.request.put( '/wp-json/wc/v3/payment_gateways/cod', {
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': nonce,
		},
		data: { enabled: true },
	} );
}

// ─── Cart & Checkout helpers ──────────────────────────────────────────────────

/**
 * Add a product to the cart using the WooCommerce StoreAPI.
 * Does not require authentication.
 */
export async function addToCartViaStoreApi(
	request: APIRequestContext,
	baseURL: string,
	productId: number,
	quantity = 1
): Promise< void > {
	const response = await request.post(
		`${ baseURL }/wp-json/wc/store/v1/cart/add-item`,
		{
			data: { id: productId, quantity },
		}
	);
	expect( response.ok(), `addToCart: ${ response.status() }` ).toBeTruthy();
}

/**
 * Fill in the block checkout billing/shipping form with test data.
 */
export async function fillBlockCheckoutForm( page: Page ): Promise< void > {
	const firstNameField = page.getByLabel( 'First name' ).first();
	await firstNameField.fill( 'E2E' );

	const lastNameField = page.getByLabel( 'Last name' ).first();
	await lastNameField.fill( 'Tester' );

	const countrySelect = page
		.locator( '.wc-block-components-country-input input' )
		.first();
	if ( await countrySelect.isVisible() ) {
		await countrySelect.fill( 'Japan' );
		await page.getByRole( 'option', { name: 'Japan' } ).first().click();
	}

	const address1 = page.getByLabel( 'Address', { exact: false } ).first();
	if ( await address1.isVisible() ) {
		await address1.fill( '1-1 Test Street' );
	}

	const city = page.getByLabel( 'City' ).first();
	if ( await city.isVisible() ) {
		await city.fill( 'Tokyo' );
	}

	const postcode = page.getByLabel( /post.*code|zip/i ).first();
	if ( await postcode.isVisible() ) {
		await postcode.fill( '100-0001' );
	}

	const phone = page.getByLabel( 'Phone' ).first();
	if ( await phone.isVisible() ) {
		await phone.fill( '0312345678' );
	}

	const email = page.getByLabel( 'Email address' ).first();
	if ( await email.isVisible() ) {
		const currentVal = await email.inputValue();
		if ( ! currentVal ) {
			await email.fill( 'e2e-test@example.com' );
		}
	}
}

// ─── Assertion helpers ────────────────────────────────────────────────────────

/**
 * Collect console errors from a page and return them.
 * Attach the listener BEFORE navigating to the page.
 */
export function collectConsoleErrors( page: Page ): () => string[] {
	const errors: string[] = [];
	page.on( 'console', ( msg ) => {
		if ( msg.type() === 'error' ) {
			errors.push( msg.text() );
		}
	} );
	return () => errors;
}

/**
 * Wait for the plugin's React app to finish loading (spinner gone, card visible).
 */
export async function waitForPluginApp( page: Page ): Promise< void > {
	await page.waitForSelector( '.plugin-name-container', { timeout: 15_000 } );
	await page
		.waitForSelector( '.plugin-name-loading', {
			state: 'detached',
			timeout: 15_000,
		} )
		.catch( () => {
			// Spinner may never appear if loading is instant
		} );
}
