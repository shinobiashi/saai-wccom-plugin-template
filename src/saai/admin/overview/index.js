/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './index.scss';

const SaaiOverviewPage = () => {
	return (
		<div className="saai-admin-layout">
			<div className="saai-admin__header">
				<div className="saai-admin__header-wrapper">
					<h1>{ __( 'SAAI Overview', 'plugin-name' ) }</h1>
				</div>
			</div>
			<div className="saai-admin-overview__content">
				<p>
					{ __(
						'This plugin is developed by Shinobiashi INC., a Woo Agency.',
						'plugin-name'
					) }
				</p>
				{ window.saaiBlocksData?.wooPartnerLogoUrl && (
					<a
						href="https://woocommerce.com/development-services/shinobiashi-inc/233150772/"
						target="_blank"
						rel="noreferrer"
					>
						<img
							src={ window.saaiBlocksData.wooPartnerLogoUrl }
							alt={ __( 'Woo Partner Logo', 'plugin-name' ) }
							className="saai-woo-partner-logo"
						/>
					</a>
				) }
			</div>
		</div>
	);
};

document.addEventListener( 'DOMContentLoaded', () => {
	const root = document.querySelector( '#saai-overview-root' );
	if ( root ) {
		const reactRoot = createRoot( root );
		reactRoot.render( <SaaiOverviewPage /> );
	}
} );
