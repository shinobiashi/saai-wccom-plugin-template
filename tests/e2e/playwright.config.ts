/**
 * External dependencies
 */
import { defineConfig, devices } from '@playwright/test';
import fs from 'fs';
import path from 'path';

/**
 * QIT Custom E2E Test Configuration
 *
 * Run with QIT:
 *   ./vendor/bin/qit run:e2e plugin-name --zip=./plugin-name.zip --test-package=./tests/e2e
 *
 * Run locally with wp-env (one-time setup):
 *   npx playwright install chromium
 *   npm run test:e2e
 */

const AUTH_FILE = path.join( __dirname, '.auth', 'admin.json' );

function resolveStorageState(): string | undefined {
	if ( process.env.STORAGE_STATE ) return process.env.STORAGE_STATE;
	if ( fs.existsSync( AUTH_FILE ) ) return AUTH_FILE;
	return undefined;
}

export default defineConfig( {
	testDir: './specs',
	fullyParallel: false,
	forbidOnly: !! process.env.CI,
	retries: process.env.CI ? 2 : 0,
	workers: 1,
	timeout: 60_000,

	reporter: [
		[ 'list' ],
		[
			'html',
			{
				open: 'never',
				outputFolder: path.join( __dirname, 'test-results', 'report' ),
			},
		],
	],

	use: {
		baseURL: process.env.BASE_URL ?? 'http://localhost:8889',
		storageState: resolveStorageState(),
		trace: 'on-first-retry',
		screenshot: 'only-on-failure',
		video: 'on-first-retry',
	},

	outputDir: path.join( __dirname, 'test-results', 'artifacts' ),

	projects: [
		{
			name: 'setup',
			testMatch: /global\.setup\.ts/,
		},
		{
			name: 'plugin-name',
			use: { ...devices[ 'Desktop Chrome' ] },
			dependencies: process.env.STORAGE_STATE ? [] : [ 'setup' ],
		},
	],
} );
