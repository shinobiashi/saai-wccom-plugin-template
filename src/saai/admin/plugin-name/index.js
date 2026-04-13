/**
 * Plugin Name Settings Page
 *
 * Entry point for the admin settings page.
 */

/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import App from './components/App';
import './style.scss';

// Register WP REST nonce for all apiFetch requests.
if ( window.pluginNameData?.nonce ) {
	apiFetch.use(
		apiFetch.createNonceMiddleware( window.pluginNameData.nonce )
	);
}

const rootElement = document.getElementById( 'plugin-name-root' );
if ( rootElement ) {
	const root = createRoot( rootElement );
	root.render( <App /> );
}
