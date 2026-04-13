/**
 * Settings Tab Component
 */

/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	Notice,
	Spinner,
	ToggleControl,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const SettingsTab = () => {
	const [ settings, setSettings ] = useState( null );
	const [ saving, setSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	useEffect( () => {
		apiFetch( { path: '/plugin-name/v1/settings' } )
			.then( ( data ) => setSettings( data.settings ) )
			.catch( () =>
				setNotice( {
					type: 'error',
					message: __( 'Failed to load settings.', 'plugin-name' ),
				} )
			);
	}, [] );

	const handleSave = async () => {
		setSaving( true );
		setNotice( null );
		try {
			await apiFetch( {
				path: '/plugin-name/v1/settings',
				method: 'POST',
				data: { settings },
			} );
			setNotice( {
				type: 'success',
				message: __( 'Settings saved successfully.', 'plugin-name' ),
			} );
		} catch {
			setNotice( {
				type: 'error',
				message: __( 'Failed to save settings.', 'plugin-name' ),
			} );
		} finally {
			setSaving( false );
		}
	};

	if ( ! settings ) {
		return (
			<div className="plugin-name-loading">
				<Spinner />
			</div>
		);
	}

	return (
		<div className="plugin-name-settings-tab">
			{ notice && (
				<Notice
					status={ notice.type }
					isDismissible
					onRemove={ () => setNotice( null ) }
				>
					{ notice.message }
				</Notice>
			) }

			<Card>
				<CardHeader>
					<h2>{ __( 'General Settings', 'plugin-name' ) }</h2>
				</CardHeader>
				<CardBody>
					{ /* ── Enable/Disable toggle ─────────────────────────────── */ }
					<ToggleControl
						label={ __( 'Enable Plugin', 'plugin-name' ) }
						help={ __(
							'Turn on or off the plugin functionality.',
							'plugin-name'
						) }
						checked={ settings.enabled }
						onChange={ ( value ) =>
							setSettings( { ...settings, enabled: value } )
						}
						__nextHasNoMarginBottom
					/>

					{ /* ── Add more settings controls here ──────────────────── */ }
				</CardBody>
			</Card>

			<div style={ { marginTop: '24px' } }>
				<Button
					variant="primary"
					onClick={ handleSave }
					isBusy={ saving }
					disabled={ saving }
				>
					{ __( 'Save Settings', 'plugin-name' ) }
				</Button>
			</div>
		</div>
	);
};

export default SettingsTab;
