/**
 * Mollie Subscription Block Edit Component
 *
 * Gutenberg block editor interface for creating Mollie subscription forms.
 *
 * @package
 * @since   0.4.0
 */

import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	TextControl,
	Button,
	Notice,
} from '@wordpress/components';
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies - following your pattern
 */
import {
	useEcwidProductData,
	ProductSelectionPanel,
} from '../utils/ecwid-product-utils';
import { computeClassName } from '../../../peaches-bootstrap-blocks/src/utils/bootstrap_settings';

/**
 * Subscription Plan Editor Component
 *
 * Manages individual subscription plan configuration.
 *
 * @param {Object} props          Component props
 *
 * @param          props.plan
 * @param          props.index
 * @param          props.onUpdate
 * @param          props.onDelete
 * @return {JSX.Element} Plan editor component
 */
const SubscriptionPlanEditor = ( { plan, index, onUpdate, onDelete } ) => {
	/**
	 * Update plan field value
	 *
	 * @param {string} field Field name
	 * @param {*}      value Field value
	 */
	const updatePlan = ( field, value ) => {
		onUpdate( index, {
			...plan,
			[ field ]: value,
		} );
	};

	return (
		<div className="subscription-plan-editor border p-3 mb-3 rounded">
			<div className="d-flex justify-content-between align-items-center mb-3">
				<h4 className="h6 mb-0">
					{ __( 'Subscription Plan', 'peaches' ) } #{ index + 1 }
				</h4>
				<Button
					isDestructive
					isSmall
					onClick={ () => onDelete( index ) }
				>
					{ __( 'Delete', 'peaches' ) }
				</Button>
			</div>

			<TextControl
				label={ __( 'Plan Name', 'peaches' ) }
				value={ plan.name || '' }
				onChange={ ( value ) => updatePlan( 'name', value ) }
				placeholder={ __( 'e.g., Monthly Subscription', 'peaches' ) }
			/>

			<div className="row">
				<div className="col-md-6">
					<TextControl
						label={ __( 'Amount', 'peaches' ) }
						type="number"
						step="0.01"
						min="0"
						value={ plan.amount || '' }
						onChange={ ( value ) =>
							updatePlan( 'amount', parseFloat( value ) || 0 )
						}
					/>
				</div>
				<div className="col-md-6">
					<SelectControl
						label={ __( 'Currency', 'peaches' ) }
						value={ plan.currency || 'EUR' }
						options={ Object.entries(
							window.PeachesMollieEditor?.currencies || {}
						).map( ( [ code, name ] ) => ( {
							label: `${ code } - ${ name }`,
							value: code,
						} ) ) }
						onChange={ ( value ) =>
							updatePlan( 'currency', value )
						}
					/>
				</div>
			</div>

			<SelectControl
				label={ __( 'Billing Interval', 'peaches' ) }
				value={ plan.interval || '1 month' }
				options={ Object.entries(
					window.PeachesMollieEditor?.intervals || {}
				).map( ( [ value, label ] ) => ( {
					label,
					value,
				} ) ) }
				onChange={ ( value ) => updatePlan( 'interval', value ) }
			/>

			<TextControl
				label={ __( 'Description', 'peaches' ) }
				value={ plan.description || '' }
				onChange={ ( value ) => updatePlan( 'description', value ) }
				placeholder={ __( 'Optional plan description', 'peaches' ) }
			/>
		</div>
	);
};

/**
 * Block Edit Component
 *
 * Main editor interface for the Mollie subscription block.
 *
 * @param {Object} props               Block props
 *
 * @param          props.attributes
 * @param          props.setAttributes
 * @param          props.context
 * @param          props.clientId
 * @return {JSX.Element} Block edit component
 */
const Edit = ( { attributes, setAttributes, context, clientId } ) => {
	const {
		subscriptionPlans,
		showPricing,
		buttonText,
		buttonStyle,
		showDescription,
		layoutStyle,
		customCSS,
	} = attributes;

	// Use your existing product data hook pattern
	const {
		productData,
		isLoading: productLoading,
		error: productError,
		hasProductDetailAncestor,
		selectedProductId,
		contextProductData,
		openEcwidProductPopup,
		clearSelectedProduct,
	} = useEcwidProductData( context, attributes, setAttributes, clientId );

	const className = useMemo(
		() => computeClassName( attributes ),
		[ attributes ]
	);

	const blockProps = useBlockProps( {
		className,
		'data-wp-interactive': 'peaches/mollie-subscription',
	} );

	/**
	 * Add new subscription plan
	 */
	const addPlan = () => {
		const newPlan = {
			name: __( 'New Plan', 'peaches' ),
			amount: 0,
			currency: 'EUR',
			interval: '1 month',
			description: '',
		};
		setAttributes( {
			subscriptionPlans: [ ...subscriptionPlans, newPlan ],
		} );
	};

	/**
	 * Update subscription plan
	 *
	 * @param {number} index Plan index
	 * @param {Object} plan  Updated plan data
	 */
	const updatePlan = ( index, plan ) => {
		const updatedPlans = [ ...subscriptionPlans ];
		updatedPlans[ index ] = plan;
		setAttributes( { subscriptionPlans: updatedPlans } );
	};

	/**
	 * Delete subscription plan
	 *
	 * @param {number} index Plan index to delete
	 */
	const deletePlan = ( index ) => {
		const updatedPlans = subscriptionPlans.filter(
			( _, i ) => i !== index
		);
		setAttributes( { subscriptionPlans: updatedPlans } );
	};

	// Check if Mollie integration is available
	const mollieActive = window.PeachesMollieEditor?.mollieActive || false;

	return (
		<div { ...blockProps }>
			<InspectorControls>
				{ /* Use your existing ProductSelectionPanel utility */ }
				<ProductSelectionPanel
					productData={ productData }
					isLoading={ productLoading }
					error={ productError }
					hasProductDetailAncestor={ hasProductDetailAncestor }
					selectedProductId={ selectedProductId }
					contextProductData={ contextProductData }
					openEcwidProductPopup={ openEcwidProductPopup }
					clearSelectedProduct={ clearSelectedProduct }
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>

				<PanelBody
					title={ __( 'Subscription Plans', 'peaches' ) }
					initialOpen={ true }
				>
					{ subscriptionPlans.map( ( plan, index ) => (
						<SubscriptionPlanEditor
							key={ index }
							plan={ plan }
							index={ index }
							onUpdate={ updatePlan }
							onDelete={ deletePlan }
						/>
					) ) }

					<Button
						variant="secondary"
						onClick={ addPlan }
						className="w-100"
					>
						{ __( 'Add Plan', 'peaches' ) }
					</Button>
				</PanelBody>

				<PanelBody
					title={ __( 'Display Options', 'peaches' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __( 'Show Description', 'peaches' ) }
						checked={ showDescription }
						onChange={ ( value ) =>
							setAttributes( { showDescription: value } )
						}
					/>

					<ToggleControl
						label={ __( 'Show Pricing', 'peaches' ) }
						checked={ showPricing }
						onChange={ ( value ) =>
							setAttributes( { showPricing: value } )
						}
					/>

					<SelectControl
						label={ __( 'Layout Style', 'peaches' ) }
						value={ layoutStyle }
						options={ [
							{ label: __( 'Cards', 'peaches' ), value: 'cards' },
							{ label: __( 'List', 'peaches' ), value: 'list' },
							{
								label: __( 'Compact', 'peaches' ),
								value: 'compact',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { layoutStyle: value } )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Button Settings', 'peaches' ) }
					initialOpen={ false }
				>
					<TextControl
						label={ __( 'Button Text', 'peaches' ) }
						value={ buttonText }
						onChange={ ( value ) =>
							setAttributes( { buttonText: value } )
						}
					/>

					<SelectControl
						label={ __( 'Button Style', 'peaches' ) }
						value={ buttonStyle }
						options={ [
							{
								label: __( 'Primary', 'peaches' ),
								value: 'btn-primary',
							},
							{
								label: __( 'Secondary', 'peaches' ),
								value: 'btn-secondary',
							},
							{
								label: __( 'Success', 'peaches' ),
								value: 'btn-success',
							},
							{
								label: __( 'Danger', 'peaches' ),
								value: 'btn-danger',
							},
							{
								label: __( 'Warning', 'peaches' ),
								value: 'btn-warning',
							},
							{
								label: __( 'Info', 'peaches' ),
								value: 'btn-info',
							},
							{
								label: __( 'Light', 'peaches' ),
								value: 'btn-light',
							},
							{
								label: __( 'Dark', 'peaches' ),
								value: 'btn-dark',
							},
							{
								label: __( 'Outline Primary', 'peaches' ),
								value: 'btn-outline-primary',
							},
							{
								label: __( 'Outline Secondary', 'peaches' ),
								value: 'btn-outline-secondary',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { buttonStyle: value } )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Custom CSS', 'peaches' ) }
					initialOpen={ false }
				>
					<TextControl
						label={ __( 'Custom CSS', 'peaches' ) }
						value={ customCSS }
						onChange={ ( value ) =>
							setAttributes( { customCSS: value } )
						}
						help={ __(
							'Add custom CSS styles for this block',
							'peaches'
						) }
						placeholder=".my-custom-style { color: red; }"
					/>
				</PanelBody>
			</InspectorControls>

			<div className="peaches-mollie-subscription-editor">
				{ ! mollieActive && (
					<Notice status="warning" isDismissible={ false }>
						<p>
							{ __(
								'Mollie payment plugin is not active. Please install and activate a Mollie payment plugin to use subscription features.',
								'peaches'
							) }
						</p>
					</Notice>
				) }

				{ ! selectedProductId && ! hasProductDetailAncestor ? (
					<div className="text-center p-4 border border-dashed rounded">
						<h4>
							{ __( 'Mollie Subscription Block', 'peaches' ) }
						</h4>
						<p className="text-muted mb-3">
							{ __(
								'Select a product to configure subscription options',
								'peaches'
							) }
						</p>
						<p className="text-muted small">
							{ __(
								'Use the Product Selection panel in the sidebar to choose an Ecwid product',
								'peaches'
							) }
						</p>
					</div>
				) : subscriptionPlans.length === 0 ? (
					<div className="text-center p-4 border rounded">
						<h4>{ __( 'Product Selected', 'peaches' ) }</h4>
						<p className="text-muted mb-3">
							{ hasProductDetailAncestor
								? __( 'Using product from context', 'peaches' )
								: `${ __(
										'Product ID:',
										'peaches'
								  ) } ${ selectedProductId }` }
						</p>
						{ productData && (
							<p className="text-success mb-3">
								<strong>{ productData.name }</strong>
							</p>
						) }
						<p className="text-muted mb-3">
							{ __(
								'Add subscription plans to get started',
								'peaches'
							) }
						</p>
						<Button variant="primary" onClick={ addPlan }>
							{ __( 'Add First Plan', 'peaches' ) }
						</Button>
					</div>
				) : (
					<div className="subscription-preview border p-4 rounded">
						<h4 className="mb-3">
							{ __( 'Subscription Preview', 'peaches' ) }
						</h4>

						{ productData && (
							<div className="mb-3 p-3 bg-light rounded">
								<h6 className="mb-1">
									{ __( 'Selected Product:', 'peaches' ) }
								</h6>
								<p className="mb-0">
									<strong>{ productData.name }</strong>
								</p>
							</div>
						) }

						{ showDescription && (
							<div className="subscription-description mb-4 text-center">
								<h5 className="h6 mb-2 text-primary">
									<i className="fas fa-sync-alt me-2"></i>
									{ __( 'Subscribe and Save', 'peaches' ) }
								</h5>
								<p className="text-muted small">
									{ __(
										'Get this product delivered automatically and save money with our subscription plans.',
										'peaches'
									) }
								</p>
							</div>
						) }

						<div
							className={ `subscription-plans-preview ${ layoutStyle }` }
						>
							{ subscriptionPlans.map( ( plan, index ) => (
								<div
									key={ index }
									className={ `plan-preview mb-3 p-3 border rounded ${
										layoutStyle === 'cards'
											? 'bg-light'
											: ''
									}` }
								>
									<div className="d-flex justify-content-between align-items-center">
										<div className="plan-info">
											<h6 className="plan-title mb-1 fw-bold">
												{ plan.name }
											</h6>
											{ showPricing && (
												<p className="plan-price mb-1">
													<span className="h6 text-primary fw-bold me-1">
														{ plan.currency }{ ' ' }
														{ parseFloat(
															plan.amount || 0
														).toFixed( 2 ) }
													</span>
													<small className="text-muted">
														/ { plan.interval }
													</small>
												</p>
											) }
											{ plan.description && (
												<p className="plan-description text-muted small mb-0">
													{ plan.description }
												</p>
											) }
										</div>
										<div className="plan-action">
											<button
												type="button"
												className={ `btn ${ buttonStyle }` }
												disabled
											>
												{ buttonText }
											</button>
										</div>
									</div>
								</div>
							) ) }
						</div>

						<div className="mt-3">
							<small className="text-muted">
								{ __(
									'Preview only - subscription functionality will work on the frontend',
									'peaches'
								) }
							</small>
						</div>
					</div>
				) }
			</div>
		</div>
	);
};

export default Edit;
