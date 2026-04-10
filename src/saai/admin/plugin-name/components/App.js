/**
 * Main App Component
 */

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { TabPanel } from '@wordpress/components';

/**
 * Internal dependencies
 */
import SettingsTab from './SettingsTab';
import SecondTab from './SecondTab';
import './App.scss';

const App = () => {
	const tabs = [
		{
			name: 'settings',
			title: __( 'Settings', 'plugin-name' ),
			className: 'plugin-name-tab-settings',
		},
		{
			name: 'second',
			title: __( 'Second Tab', 'plugin-name' ),
			className: 'plugin-name-tab-second',
		},
	];

	return (
		<div className="plugin-name-container">
			<div className="plugin-name-header">
				<h1>{ __( 'Plugin Name Settings', 'plugin-name' ) }</h1>
			</div>

			<TabPanel
				className="plugin-name-tabs"
				activeClass="is-active"
				tabs={ tabs }
			>
				{ ( tab ) => {
					switch ( tab.name ) {
						case 'settings':
							return <SettingsTab />;
						case 'second':
							return <SecondTab />;
						default:
							return null;
					}
				} }
			</TabPanel>
		</div>
	);
};

export default App;
