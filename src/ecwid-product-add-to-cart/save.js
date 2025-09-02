/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { computeClassName } from '../../../peaches-bootstrap-blocks/src/utils/bootstrap_settings';

/**
 * Prepare translation data for frontend
 *
 * Passes all translation data to the frontend so view.js can handle
 * language switching dynamically, ensuring cache compatibility.
 *
 * @param {Object} attributes - Block attributes
 *
 * @return {Object} - Translation data for context
 */
function prepareTranslationData( attributes ) {
	const {
		buttonText = __( 'Add to Cart', 'peaches' ),
		outOfStockText = __( 'Out of Stock', 'peaches' ),
		translations = {},
	} = attributes;

	return {
		// Default texts (fallbacks)
		defaultButtonText: buttonText,
		defaultOutOfStockText: outOfStockText,
		// All available translations
		buttonTextTranslations: translations.buttonText || {},
		outOfStockTextTranslations: translations.outOfStockText || {},
	};
}

export default function save( { attributes } ) {
	const {
		selectedProductId,
		buttonThemeColor = 'primary',
		buttonSize = 'md',
		allowOutOfStockPurchase = false,
		showQuantitySelector = true,
		buttonBootstrapSettings = {},
		inputBootstrapSettings = {},
	} = attributes;

	// Get translation data for frontend
	const translationData = prepareTranslationData( attributes );

	// Compute border classes
	const borderClasses = computeClassName( { border: attributes.border } );

	// compute container classes excluding border settings
	const className = computeClassName(
		Object.fromEntries(
			Object.entries( attributes ).filter(
				( [ key ] ) => key !== 'border'
			)
		)
	);

	// Compute button classes
	const buttonClasses = ( () => {
		const baseClasses = [
			'text-nowrap',
			'btn',
			`btn-${ buttonThemeColor }`,
		];

		if ( buttonSize && buttonSize !== 'md' ) {
			baseClasses.push( `btn-${ buttonSize }` );
		}

		// Add Bootstrap settings classes for button
		const buttonBootstrapClasses = computeClassName(
			buttonBootstrapSettings
		);
		if ( buttonBootstrapClasses ) {
			baseClasses.push( buttonBootstrapClasses );
		}

		return baseClasses.join( ' ' );
	} )();

	// Compute input classes
	const inputClasses = ( () => {
		return computeClassName( inputBootstrapSettings );
	} )();

	const blockProps = useBlockProps.save( {
		className,
		'data-wp-interactive': 'peaches-ecwid-add-to-cart',
		'data-wp-context': JSON.stringify( {
			selectedProductId: selectedProductId || null,
			quantity: 1,
			allowOutOfStockPurchase,
			...translationData,
		} ),
	} );

	return (
		<div { ...blockProps }>
			{ showQuantitySelector && (
				<div className={ `${ borderClasses } w-auto input-group` }>
					<button
						className={ `btn-${
							inputBootstrapSettings.colors?.background || 'light'
						} btn quantity-decrease border-0 rounded-0` }
						type="button"
						data-wp-on--click="actions.decreaseQuantity"
						data-wp-bind--disabled="state.shouldDisableControls"
					>
						-
					</button>
					<input
						id="quantity-input"
						type="number"
						className={ `${ inputClasses } form-control text-center border-0 rounded-0` }
						defaultValue="1"
						min="1"
						data-wp-bind--value="context.quantity"
						data-wp-on--input="actions.setQuantity"
						data-wp-bind--disabled="state.shouldDisableControls"
					/>
					<button
						className={ `btn-${
							inputBootstrapSettings.colors?.background || 'light'
						} btn quantity-increase border-0 rounded-0` }
						type="button"
						data-wp-on--click="actions.increaseQuantity"
						data-wp-bind--disabled="state.shouldDisableControls"
					>
						+
					</button>
				</div>
			) }

			<button
				className={ buttonClasses }
				data-wp-on--click="actions.addToCart"
				data-wp-bind--disabled="state.shouldDisableControls"
				data-wp-class--disabled="state.shouldDisableControls"
			>
				<span data-wp-text="state.buttonText"></span>
			</button>
		</div>
	);
}
