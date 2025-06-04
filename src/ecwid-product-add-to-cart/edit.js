/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { useMemo, useEffect } from '@wordpress/element';
import {
	PanelBody,
	Notice,
	CustomSelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	BootstrapSettingsPanels,
	computeClassName,
	initializeBootstrapAttributes,
} from '../../../peaches-bootstrap-blocks/src/utils/bootstrap_settings';

/**
 * Styles
 */
import './style.scss';

// Supported settings for container
const CONTAINER_SUPPORTED_SETTINGS = {
	responsive: {
		sizes: {
			ratio: true,
			rowCols: true,
		},
		display: {
			opacity: true,
			display: true,
		},
		placements: {
			zIndex: true,
			textAlign: true,
			justifyContent: true,
			alignSelf: true,
			alignItems: true,
		},
		spacings: {
			margin: true,
			padding: true,
			gutter: true,
		},
	},
	general: {
		border: {
			rounded: true,
			display: true,
		},
		sizes: {
			width: true,
			height: true,
		},
		spacings: {
			gaps: true,
		},
		placements: {
			stack: true,
		},
	},
};

// Supported settings for button
const BUTTON_SUPPORTED_SETTINGS = {
	responsive: {
		display: {
			opacity: true,
			display: true,
		},
		placements: {
			zIndex: true,
			textAlign: true,
			justifyContent: true,
			alignSelf: true,
			alignItems: true,
		},
		spacings: {
			margin: true,
			padding: true,
		},
	},
	general: {
		border: {
			rounded: true,
			display: true,
		},
		sizes: {
			width: true,
			height: true,
		},
	},
};

// Supported settings for input
const INPUT_SUPPORTED_SETTINGS = {
	responsive: {
		display: {
			opacity: true,
			display: true,
		},
		placements: {
			zIndex: true,
			textAlign: true,
			justifyContent: true,
			alignSelf: true,
			alignItems: true,
		},
		spacings: {
			margin: true,
			padding: true,
		},
	},
	general: {
		sizes: {
			width: true,
			height: true,
		},
		colors: {
			background: true,
			text: true,
		},
	},
};

/**
 * Add To Cart Edit Component
 *
 * Enhanced with Bootstrap styling options and stock handling.
 *
 * @param {Object} props - Component props
 *
 * @return {JSX.Element} - Edit component
 */
function AddToCartEdit( props ) {
	const { attributes, setAttributes, context } = props;
	const {
		buttonThemeColor,
		buttonSize,
		buttonText,
		outOfStockText,
		allowOutOfStockPurchase,
		showQuantitySelector,
		buttonBootstrapSettings,
		inputBootstrapSettings,
	} = attributes;

	// Initialize Bootstrap attributes with defaults
	useEffect( () => {
		const initializedAttributes =
			initializeBootstrapAttributes( attributes );

		// Check if we need to set the default display value
		if ( ! initializedAttributes.xs.display ) {
			initializedAttributes.xs.display = 'inline-flex';
		}

		// Only update if there are actual changes
		if (
			JSON.stringify( initializedAttributes ) !==
			JSON.stringify( attributes )
		) {
			setAttributes( initializedAttributes );
		}
	}, [] );

	// Get test product data from parent context
	const testProductData = context?.[ 'peaches/testProductData' ];

	/**
	 * Get stock status info from test product data
	 *
	 * @return {Object} - Stock status object with inStock boolean and text
	 */
	const getStockStatus = () => {
		if ( testProductData ) {
			return {
				inStock: testProductData.inStock !== false, // Default to true if not specified
				text:
					testProductData.inStock !== false
						? __( 'In Stock', 'peaches' )
						: __( 'Out of Stock', 'peaches' ),
				class:
					testProductData.inStock !== false
						? 'text-success'
						: 'text-danger',
			};
		}

		// Fallback for when no test product is available
		return {
			inStock: true,
			text: __( 'In Stock', 'peaches' ),
			class: 'text-success',
		};
	};

	const stockStatus = getStockStatus();
	const shouldDisableControls =
		! stockStatus.inStock && ! allowOutOfStockPurchase;

	// Compute border classes
	const borderClasses = useMemo( () => {
		return computeClassName( { border: attributes.border } );
	}, [ attributes.border ] );

	// compute container classes excluding border settings
	const className = useMemo(
		() =>
			computeClassName(
				Object.fromEntries(
					Object.entries( attributes ).filter(
						( [ key ] ) => key !== 'border'
					)
				)
			),
		[ attributes ]
	);

	// Compute button classes
	const buttonClasses = useMemo( () => {
		const baseClasses = [
			'text-nowrap',
			'btn',
			`btn-${ buttonThemeColor }`,
		];

		if ( buttonSize && buttonSize !== 'md' ) {
			baseClasses.push( `btn-${ buttonSize }` );
		}

		// Add Bootstrap settings classes for button
		if ( buttonBootstrapSettings ) {
			const buttonBootstrapClasses = computeClassName(
				buttonBootstrapSettings
			);
			if ( buttonBootstrapClasses ) {
				baseClasses.push( buttonBootstrapClasses );
			}
		}

		return baseClasses.join( ' ' );
	}, [ buttonThemeColor, buttonSize, buttonBootstrapSettings ] );

	// Compute input classes
	const inputClasses = useMemo( () => {
		return computeClassName( inputBootstrapSettings );
	}, [ inputBootstrapSettings ] );

	const blockProps = useBlockProps( {
		className,
		'data-wp-interactive': 'peaches-ecwid-add-to-cart',
		'data-wp-context': '{"amount": 1}',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Add to Cart Settings', 'peaches' ) }
					initialOpen={ true }
				>
					{ testProductData ? (
						<Notice status="success" isDismissible={ false }>
							{ __( 'Using test product data:', 'peaches' ) }{ ' ' }
							<strong>{ testProductData.name }</strong>
							<br />
							{ __( 'Stock status:', 'peaches' ) }{ ' ' }
							<span className={ stockStatus.class }>
								{ stockStatus.text }
							</span>
						</Notice>
					) : (
						<Notice status="info" isDismissible={ false }>
							{ __(
								'Using placeholder data. Configure a test product in the parent block to preview real stock status.',
								'peaches'
							) }
						</Notice>
					) }

					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Button Text', 'peaches' ) }
						value={ buttonText }
						onChange={ ( value ) =>
							setAttributes( { buttonText: value } )
						}
						help={ __(
							'Text displayed on the add to cart button',
							'peaches'
						) }
					/>

					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Out of Stock Text', 'peaches' ) }
						value={ outOfStockText }
						onChange={ ( value ) =>
							setAttributes( { outOfStockText: value } )
						}
						help={ __(
							'Text displayed when product is out of stock',
							'peaches'
						) }
					/>

					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Allow out of stock purchase', 'peaches' ) }
						checked={ allowOutOfStockPurchase }
						onChange={ ( value ) =>
							setAttributes( { allowOutOfStockPurchase: value } )
						}
						help={ __(
							'Allow customers to add out of stock products to cart',
							'peaches'
						) }
					/>

					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show quantity selector', 'peaches' ) }
						checked={ showQuantitySelector }
						onChange={ ( value ) =>
							setAttributes( { showQuantitySelector: value } )
						}
						help={ __(
							'Display quantity input and +/- buttons',
							'peaches'
						) }
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Button Styling', 'peaches' ) }
					initialOpen={ false }
				>
					<CustomSelectControl
						__next40pxDefaultSize
						label={ __( 'Theme Color', 'peaches' ) }
						value={ buttonThemeColor }
						className="btn"
						options={ [
							{
								key: __( 'Primary', 'peaches' ),
								name: 'primary',
								className: 'btn-primary',
							},
							{
								key: __( 'Secondary', 'peaches' ),
								name: 'secondary',
								className: 'btn-secondary',
							},
							{
								key: __( 'Tertiary', 'peaches' ),
								name: 'tertiary',
								className: 'btn-tertiary',
							},
							{
								key: __( 'Dark', 'peaches' ),
								name: 'dark',
								className: 'btn-dark',
							},
							{
								key: __( 'Light', 'peaches' ),
								name: 'light',
								className: 'btn-light',
							},
							{
								key: __( 'Success', 'peaches' ),
								name: 'success',
								className: 'btn-success',
							},
							{
								key: __( 'Danger', 'peaches' ),
								name: 'danger',
								className: 'btn-danger',
							},
							{
								key: __( 'Warning', 'peaches' ),
								name: 'warning',
								className: 'btn-warning',
							},
							{
								key: __( 'Info', 'peaches' ),
								name: 'info',
								className: 'btn-info',
							},
							{
								key: __( 'Link', 'peaches' ),
								name: 'link',
								className: 'btn-link',
							},
						] }
						onChange={ ( newVal ) =>
							setAttributes( {
								buttonThemeColor: newVal.selectedItem.name,
							} )
						}
					/>
					<CustomSelectControl
						__next40pxDefaultSize
						label={ __( 'Button Size', 'peaches' ) }
						value={ buttonSize || 'md' }
						className="btn"
						options={ [
							{
								key: __( 'Small', 'peaches' ),
								name: 'sm',
								className: `btn btn-sm btn-${ buttonThemeColor }`,
							},
							{
								key: __( 'Normal', 'peaches' ),
								name: 'md',
								className: `btn btn-${ buttonThemeColor }`,
							},
							{
								key: __( 'Large', 'peaches' ),
								name: 'lg',
								className: `btn btn-lg btn-${ buttonThemeColor }`,
							},
						] }
						onChange={ ( newVal ) => {
							setAttributes( {
								buttonSize: newVal.selectedItem.name,
							} );
						} }
					/>
					<BootstrapSettingsPanels
						setAttributes={ setAttributes }
						attributes={ attributes }
						supportedSettings={ BUTTON_SUPPORTED_SETTINGS }
						attributePrefix="buttonBootstrapSettings"
					/>
				</PanelBody>

				{ showQuantitySelector && (
					<PanelBody
						title={ __( 'Input Styling', 'peaches' ) }
						initialOpen={ false }
					>
						<BootstrapSettingsPanels
							setAttributes={ setAttributes }
							attributes={ attributes }
							supportedSettings={ INPUT_SUPPORTED_SETTINGS }
							attributePrefix="inputBootstrapSettings"
						/>
					</PanelBody>
				) }

				{ /* Main container styling - applied to the block wrapper */ }
				<BootstrapSettingsPanels
					setAttributes={ setAttributes }
					attributes={ attributes }
					supportedSettings={ CONTAINER_SUPPORTED_SETTINGS }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				{ showQuantitySelector && (
					<div className={ `${ borderClasses } input-group` }>
						<button
							className={ `btn-${ inputBootstrapSettings.colors.background } btn quantity-decrease border-0 rounded-0` }
							type="button"
							data-wp-on--click="actions.decreaseAmount"
							disabled={ shouldDisableControls }
						>
							-
						</button>
						<input
							id="quantity-input"
							type="number"
							className={ `${ inputClasses } form-control text-center border-0 rounded-0` }
							defaultValue="1"
							min="1"
							data-wp-bind--value="context.amount"
							data-wp-on--input="actions.setAmount"
							disabled={ shouldDisableControls }
						/>
						<button
							className={ `btn-${ inputBootstrapSettings.colors.background } btn quantity-increase border-0 rounded-0` }
							type="button"
							data-wp-on--click="actions.increaseAmount"
							disabled={ shouldDisableControls }
						>
							+
						</button>
					</div>
				) }

				<button
					className={ buttonClasses }
					data-wp-on--click="actions.addToCart"
					disabled={ shouldDisableControls }
				>
					{ shouldDisableControls && ! allowOutOfStockPurchase
						? outOfStockText
						: buttonText }
				</button>
			</div>
		</>
	);
}

export default AddToCartEdit;
