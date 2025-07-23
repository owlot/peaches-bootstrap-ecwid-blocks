/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Mollie Subscription Save Component
 *
 * Renders the frontend markup with interactivity API attributes for subscription functionality.
 *
 * @param {Object} props            - Component props
 * @param {Object} props.attributes - Block attributes
 *
 * @return {JSX.Element} - Save component
 */
export default function save( { attributes } ) {
	const {
		selectedProductId = 0,
		subscriptionPlans = [],
		showPricing = true,
		buttonText = __( 'Subscribe Now', 'peaches' ),
		buttonStyle = 'btn-primary',
		showDescription = true,
		layoutStyle = 'cards',
		customCSS = '',
	} = attributes;

	// Don't render anything if not properly configured
	if ( ! selectedProductId || ! subscriptionPlans.length ) {
		return null;
	}

	const blockProps = useBlockProps.save( {
		'data-wp-interactive': 'peaches/mollie-subscription',
		'data-wp-context': JSON.stringify( {
			selectedProductId,
			subscriptionPlans,
			showPricing,
			buttonText,
			buttonStyle,
			showDescription,
			layoutStyle,
			// Runtime state
			selectedPlan: null,
			isLoading: false,
			error: null,
			customerData: null,
		} ),
		'data-wp-init': 'callbacks.initMollieSubscription',
		className: 'container-fluid py-4',
	} );

	const renderDescription = () => {
		if ( ! showDescription ) {
			return null;
		}

		return (
			<div className="row justify-content-center mb-4">
				<div className="col-12 col-lg-8 text-center">
					<h3 className="h4 fw-bold text-primary mb-3">
						<i className="fas fa-sync-alt me-2"></i>
						{ __( 'Subscribe and Save', 'peaches' ) }
					</h3>
					<p className="lead text-muted">
						{ __(
							'Get this product delivered automatically and save money with our subscription plans.',
							'peaches'
						) }
					</p>
				</div>
			</div>
		);
	};

	const renderCardLayout = () => (
		<div className="row g-4">
			{ subscriptionPlans.map( ( plan, index ) => {
				const colClass =
					subscriptionPlans.length === 1
						? 'col-12 col-md-8 offset-md-2'
						: subscriptionPlans.length === 2
						? 'col-12 col-md-6'
						: 'col-12 col-md-6 col-lg-4';

				return (
					<div key={ index } className={ colClass }>
						<div className="card h-100 shadow-sm border-0 position-relative overflow-hidden">
							{ index === 0 && subscriptionPlans.length > 1 && (
								<div className="badge bg-primary position-absolute top-0 end-0 m-3 px-3 py-2">
									{ __( 'Popular', 'peaches' ) }
								</div>
							) }

							<div className="card-body d-flex flex-column p-4">
								<div className="text-center mb-3">
									<h5 className="card-title fw-bold text-dark mb-2">
										{ plan.name }
									</h5>

									{ showPricing && (
										<>
											<div className="display-6 fw-bold text-primary mb-1">
												{ plan.currency }
												<span className="display-4">
													{ Math.floor(
														plan.amount
													) }
												</span>
												{ plan.amount % 1 !== 0 && (
													<small className="fs-4">
														.
														{ String(
															( plan.amount %
																1 ) *
																100
														).padStart( 2, '0' ) }
													</small>
												) }
											</div>
											<p className="text-muted mb-3">
												<small>
													{ __( 'per', 'peaches' ) }{ ' ' }
													{ plan.interval }
												</small>
											</p>
										</>
									) }
								</div>

								{ plan.description && (
									<div className="flex-grow-1 mb-4">
										<p className="text-muted text-center small lh-base">
											{ plan.description }
										</p>
									</div>
								) }

								<div className="d-grid mt-auto">
									<button
										type="button"
										className={ `btn ${ buttonStyle } btn-lg fw-semibold py-3` }
										data-wp-on--click="actions.selectPlan"
										data-plan-index={ index }
										data-wp-bind--disabled="state.isLoading"
									>
										<span
											data-wp-text={ `state.isLoading && state.selectedPlan === ${ index } ? '${ __(
												'Processing…',
												'peaches'
											) }' : '${ buttonText }'` }
										>
											{ buttonText }
										</span>
										<i className="fas fa-arrow-right ms-2"></i>
									</button>
								</div>
							</div>
						</div>
					</div>
				);
			} ) }
		</div>
	);

	const renderListLayout = () => (
		<div className="list-group list-group-flush">
			{ subscriptionPlans.map( ( plan, index ) => (
				<div
					key={ index }
					className="list-group-item list-group-item-action border-0 rounded-3 mb-3 shadow-sm"
				>
					<div className="row align-items-center py-3">
						<div className="col-12 col-md-8">
							<div className="d-flex align-items-center">
								<div className="flex-shrink-0 me-3">
									<div
										className="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center"
										style={ {
											width: '50px',
											height: '50px',
										} }
									>
										<i className="fas fa-sync-alt text-primary"></i>
									</div>
								</div>
								<div className="flex-grow-1">
									<h6 className="fw-bold mb-1 text-dark">
										{ plan.name }
									</h6>

									{ showPricing && (
										<p className="mb-1">
											<span className="h5 text-primary fw-bold me-1">
												{ plan.currency }{ ' ' }
												{ Number( plan.amount ).toFixed(
													2
												) }
											</span>
											<small className="text-muted">
												/ { plan.interval }
											</small>
										</p>
									) }

									{ plan.description && (
										<p className="text-muted small mb-0 lh-base">
											{ plan.description }
										</p>
									) }
								</div>
							</div>
						</div>
						<div className="col-12 col-md-4 text-md-end mt-3 mt-md-0">
							<button
								type="button"
								className={ `btn ${ buttonStyle } fw-semibold px-4 py-2` }
								data-wp-on--click="actions.selectPlan"
								data-plan-index={ index }
								data-wp-bind--disabled="state.isLoading"
							>
								<span
									data-wp-text={ `state.isLoading && state.selectedPlan === ${ index } ? '${ __(
										'Processing…',
										'peaches'
									) }' : '${ buttonText }'` }
								>
									{ buttonText }
								</span>
							</button>
						</div>
					</div>
				</div>
			) ) }
		</div>
	);

	const renderCompactLayout = () => (
		<div className="row g-3">
			{ subscriptionPlans.map( ( plan, index ) => {
				const colClass =
					subscriptionPlans.length > 2
						? 'col-12 col-sm-6 col-lg-4'
						: 'col-12 col-sm-6 col-lg-6';

				return (
					<div key={ index } className={ colClass }>
						<div className="border rounded-3 p-3 h-100 bg-light bg-opacity-50">
							<div className="d-flex justify-content-between align-items-start mb-2">
								<div className="flex-grow-1">
									<h6 className="fw-semibold mb-1 text-dark">
										{ plan.name }
									</h6>

									{ showPricing && (
										<p className="mb-2">
											<span className="fw-bold text-primary">
												{ plan.currency }{ ' ' }
												{ Number( plan.amount ).toFixed(
													2
												) }
											</span>
											<small className="text-muted ms-1">
												/ { plan.interval }
											</small>
										</p>
									) }

									{ plan.description && (
										<p className="text-muted small mb-3 lh-base">
											{ plan.description }
										</p>
									) }
								</div>
							</div>

							<div className="d-grid">
								<button
									type="button"
									className={ `btn ${ buttonStyle } btn-sm fw-semibold` }
									data-wp-on--click="actions.selectPlan"
									data-plan-index={ index }
									data-wp-bind--disabled="state.isLoading"
								>
									<span
										data-wp-text={ `state.isLoading && state.selectedPlan === ${ index } ? '${ __(
											'Processing…',
											'peaches'
										) }' : '${ buttonText }'` }
									>
										{ buttonText }
									</span>
								</button>
							</div>
						</div>
					</div>
				);
			} ) }
		</div>
	);

	const renderPlansLayout = () => {
		switch ( layoutStyle ) {
			case 'list':
				return renderListLayout();
			case 'compact':
				return renderCompactLayout();
			default: // cards
				return renderCardLayout();
		}
	};

	return (
		<div { ...blockProps }>
			{ renderDescription() }

			<div className="row justify-content-center">
				<div
					className={ `col-12 ${
						subscriptionPlans.length <= 2 ? 'col-lg-8' : 'col-lg-10'
					}` }
				>
					{ renderPlansLayout() }
				</div>
			</div>

			{ /* Error Alert - Bootstrap Alert Component */ }
			<div className="row justify-content-center mt-4">
				<div className="col-12 col-lg-8">
					<div
						className="alert alert-danger alert-dismissible fade show"
						data-wp-class--d-none="!state.error"
						role="alert"
					>
						<i className="fas fa-exclamation-triangle me-2"></i>
						<span data-wp-text="state.error"></span>
						<button
							type="button"
							className="btn-close"
							data-bs-dismiss="alert"
							aria-label={ __( 'Close', 'peaches' ) }
						></button>
					</div>
				</div>
			</div>

			{ /* Loading overlay */ }
			<div
				className="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-white bg-opacity-75"
				data-wp-class--d-none="!state.isLoading"
				style={ { zIndex: 10 } }
			>
				<div className="text-center">
					<div
						className="spinner-border text-primary mb-3"
						role="status"
					>
						<span className="visually-hidden">
							{ __( 'Loading…', 'peaches' ) }
						</span>
					</div>
					<p className="text-muted">
						{ __( 'Processing your subscription…', 'peaches' ) }
					</p>
				</div>
			</div>

			{ /* Custom CSS */ }
			{ customCSS && (
				<style
					dangerouslySetInnerHTML={ {
						__html: `
						[data-wp-interactive="peaches/mollie-subscription"] {
							${ customCSS }
						}
					`,
					} }
				/>
			) }
		</div>
	);
}
