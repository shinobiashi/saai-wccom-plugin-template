/**
 * Second Tab Component
 *
 * Displays a summary card and a paginated item list table.
 * Adapt the column headers and summary labels to match your plugin's data model.
 */

/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import {
	Card,
	CardBody,
	CardHeader,
	Spinner,
	Notice,
	Button,
	SelectControl,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const SecondTab = () => {
	const [ items, setItems ]               = useState( [] );
	const [ summary, setSummary ]           = useState( null );
	const [ loading, setLoading ]           = useState( true );
	const [ summaryLoading, setSummaryLoading ] = useState( true );
	const [ notice, setNotice ]             = useState( null );
	const [ statusFilter, setStatusFilter ] = useState( '' );
	const [ page, setPage ]                 = useState( 1 );
	const [ totalPages, setTotalPages ]     = useState( 1 );
	const [ total, setTotal ]               = useState( 0 );

	// ─── Data loaders ──────────────────────────────────────────────────────────

	const loadSummary = async () => {
		setSummaryLoading( true );
		try {
			const response = await apiFetch( {
				path: '/plugin-name/v1/summary',
			} );
			setSummary( response );
		} catch {
			// Summary failure is non-critical; items still load.
			setSummary( null );
		} finally {
			setSummaryLoading( false );
		}
	};

	const loadItems = async ( currentPage = 1, status = '' ) => {
		setLoading( true );
		try {
			const params = new URLSearchParams( {
				page: String( currentPage ),
				per_page: '20',
			} );
			if ( status ) {
				params.append( 'status', status );
			}
			const response = await apiFetch( {
				path: `/plugin-name/v1/items?${ params.toString() }`,
			} );
			if ( response ) {
				setItems( response.items ?? [] );
				setTotal( response.total ?? 0 );
				setTotalPages( response.total_pages ?? 1 );
			}
		} catch {
			setNotice( {
				type: 'error',
				message: __( 'Failed to load items.', 'plugin-name' ),
			} );
		} finally {
			setLoading( false );
		}
	};

	const handleRefresh = () => {
		setPage( 1 );
		loadSummary();
		loadItems( 1, statusFilter );
	};

	const handleStatusChange = ( value ) => {
		setStatusFilter( value );
		setPage( 1 );
		loadItems( 1, value );
	};

	const handlePageChange = ( newPage ) => {
		setPage( newPage );
		loadItems( newPage, statusFilter );
	};

	// ─── Date formatter ────────────────────────────────────────────────────────

	const formatDate = ( dateString ) => {
		if ( ! dateString ) return '—';
		const date = new Date( dateString );
		return date.toLocaleString( undefined, {
			year: 'numeric',
			month: '2-digit',
			day: '2-digit',
			hour: '2-digit',
			minute: '2-digit',
		} );
	};

	// ─── Mount ─────────────────────────────────────────────────────────────────

	useEffect( () => {
		loadSummary();
		loadItems();
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	// ─── Render helpers ────────────────────────────────────────────────────────

	const renderSummary = () => {
		if ( summaryLoading ) {
			return (
				<div className="plugin-name-loading plugin-name-loading--inline">
					<Spinner />
				</div>
			);
		}
		if ( ! summary ) {
			return <p>{ __( 'Failed to load summary.', 'plugin-name' ) }</p>;
		}
		return (
			<div className="plugin-name-summary">
				<div className="plugin-name-summary__item">
					<span className="plugin-name-summary__label">
						{ __( 'Total', 'plugin-name' ) }
					</span>
					<span className="plugin-name-summary__count">
						{ summary.total }
					</span>
				</div>
				<div className="plugin-name-summary__divider" aria-hidden="true" />
				<div className="plugin-name-summary__item">
					<span className="plugin-name-summary__label">
						{ __( 'Pending', 'plugin-name' ) }
					</span>
					<span className="plugin-name-summary__count">
						{ summary.pending }
					</span>
				</div>
				<div className="plugin-name-summary__divider" aria-hidden="true" />
				<div className="plugin-name-summary__item">
					<span className="plugin-name-summary__label">
						{ __( 'Active', 'plugin-name' ) }
					</span>
					<span className="plugin-name-summary__count">
						{ summary.active }
					</span>
				</div>
				<div className="plugin-name-summary__divider" aria-hidden="true" />
				<div className="plugin-name-summary__item">
					<span className="plugin-name-summary__label">
						{ __( 'Done', 'plugin-name' ) }
					</span>
					<span className="plugin-name-summary__count">
						{ summary.done }
					</span>
				</div>
			</div>
		);
	};

	const renderItems = () => {
		if ( loading ) {
			return (
				<div className="plugin-name-loading plugin-name-loading--inline">
					<Spinner />
				</div>
			);
		}
		if ( items.length === 0 ) {
			return <p>{ __( 'No items found.', 'plugin-name' ) }</p>;
		}
		return (
			<>
				<table className="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							{ /* Adapt columns to your data model */ }
							<th>{ __( 'ID', 'plugin-name' ) }</th>
							<th>{ __( 'Order', 'plugin-name' ) }</th>
							<th>{ __( 'Status', 'plugin-name' ) }</th>
							<th>{ __( 'Created', 'plugin-name' ) }</th>
							<th>{ __( 'Updated', 'plugin-name' ) }</th>
						</tr>
					</thead>
					<tbody>
						{ items.map( ( item ) => (
							<tr key={ item.id }>
								<td>{ item.id }</td>
								<td>
									{ item.order_id ? (
										<a
											href={ `${ window.pluginNameData?.adminUrl ?? '' }post.php?post=${ item.order_id }&action=edit` }
											target="_blank"
											rel="noreferrer"
										>
											#{ item.order_id }
										</a>
									) : (
										'—'
									) }
								</td>
								<td>
									<span className={ `plugin-name-status plugin-name-status--${ item.status }` }>
										{ item.status }
									</span>
								</td>
								<td>{ formatDate( item.created_at ) }</td>
								<td>{ formatDate( item.updated_at ) }</td>
							</tr>
						) ) }
					</tbody>
				</table>

				{ totalPages > 1 && (
					<div className="plugin-name-pagination">
						<Button
							variant="secondary"
							disabled={ page <= 1 }
							onClick={ () => handlePageChange( page - 1 ) }
						>
							{ __( '← Previous', 'plugin-name' ) }
						</Button>
						<span className="plugin-name-pagination__info">
							{ sprintf(
								/* translators: 1: current page, 2: total pages, 3: total items */
								__( 'Page %1$d of %2$d (%3$d items)', 'plugin-name' ),
								page,
								totalPages,
								total
							) }
						</span>
						<Button
							variant="secondary"
							disabled={ page >= totalPages }
							onClick={ () => handlePageChange( page + 1 ) }
						>
							{ __( 'Next →', 'plugin-name' ) }
						</Button>
					</div>
				) }
			</>
		);
	};

	// ─── Render ────────────────────────────────────────────────────────────────

	return (
		<div className="plugin-name-second-tab">
			{ notice && (
				<Notice
					status={ notice.type }
					isDismissible
					onRemove={ () => setNotice( null ) }
				>
					{ notice.message }
				</Notice>
			) }

			{ /* Summary card */ }
			<Card className="plugin-name-summary-card">
				<CardHeader>
					<h2>{ __( 'Summary', 'plugin-name' ) }</h2>
				</CardHeader>
				<CardBody>{ renderSummary() }</CardBody>
			</Card>

			{ /* Item list card */ }
			<Card>
				<CardHeader>
					<h2>{ __( 'Item List', 'plugin-name' ) }</h2>
					<div className="plugin-name-card-header-actions">
						<SelectControl
							label={ __( 'Status', 'plugin-name' ) }
							hideLabelFromVision
							value={ statusFilter }
							options={ [
								{ label: __( 'All statuses', 'plugin-name' ), value: '' },
								{ label: __( 'Pending', 'plugin-name' ), value: 'pending' },
								{ label: __( 'Active', 'plugin-name' ), value: 'active' },
								{ label: __( 'Done', 'plugin-name' ), value: 'done' },
							] }
							onChange={ handleStatusChange }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<Button
							variant="secondary"
							onClick={ handleRefresh }
							disabled={ loading || summaryLoading }
						>
							{ __( 'Refresh', 'plugin-name' ) }
						</Button>
					</div>
				</CardHeader>
				<CardBody>{ renderItems() }</CardBody>
			</Card>
		</div>
	);
};

export default SecondTab;
