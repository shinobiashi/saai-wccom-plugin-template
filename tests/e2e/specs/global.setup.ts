/**
 * External dependencies
 */
import { test as setup, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';

const AUTH_FILE = path.join( __dirname, '..', '.auth', 'admin.json' );

setup( 'authenticate as WP admin', async ( { page } ) => {
	if ( process.env.STORAGE_STATE ) {
		return;
	}

	fs.mkdirSync( path.dirname( AUTH_FILE ), { recursive: true } );

	const baseURL = process.env.BASE_URL ?? 'http://localhost:8889';

	await page.goto( `${ baseURL }/wp-login.php` );
	await page.fill( '#user_login', process.env.ADMIN_USER ?? 'admin' );
	await page.fill( '#user_pass', process.env.ADMIN_PASSWORD ?? 'password' );
	await page.click( '#wp-submit' );
	await page.waitForURL( '**/wp-admin/**' );
	await expect( page.locator( '#wpadminbar' ) ).toBeVisible();

	await page.context().storageState( { path: AUTH_FILE } );
} );
