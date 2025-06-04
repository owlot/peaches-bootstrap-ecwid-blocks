/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { computeClassName } from '../../../peaches-bootstrap-blocks/src/utils/bootstrap_settings';

export default function save( { attributes } ) {
	const {
		buttonThemeColor = 'primary',
		buttonSize = 'md',
		buttonText = 'Add to Cart',
		outOfStockText = 'Out of Stock',
		allowOutOfStockPurchase = false,
		showQuantitySelector = true,
		buttonBootstrapSettings = {},
		inputBootstrapSettings = {},
	} = attributes;

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
		'data-wp-context': `{"amount": 1, "allowOutOfStockPurchase": ${ allowOutOfStockPurchase }}`,
	} );

	return (
		<div { ...blockProps }>
			{ showQuantitySelector && (
				<div className={ `${ borderClasses } input-group` }>
					<button
						className={ `btn-${ inputBootstrapSettings.colors.background } btn quantity-decrease border-0 rounded-0` }
						type="button"
						data-wp-on--click="actions.decreaseAmount"
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
						data-wp-bind--value="context.amount"
						data-wp-on--input="actions.setAmount"
						data-wp-bind--disabled="state.shouldDisableControls"
					/>
					<button
						className={ `btn-${ inputBootstrapSettings.colors.background } btn quantity-increase border-0 rounded-0` }
						type="button"
						data-wp-on--click="actions.increaseAmount"
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
				<span data-wp-bind--hidden="state.shouldDisableControls">
					{ buttonText }
				</span>
				<span data-wp-bind--hidden="!state.shouldDisableControls">
					{ outOfStockText }
				</span>
			</button>
		</div>
	);
}
