/**
 * External dependencies
 */
import clsx from 'clsx';

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
	const blockProps = useBlockProps.save( {
		className: computeClassName( attributes ),
		'data-wp-interactive': 'peaches-ecwid-add-to-cart',
		'data-wp-context': '{"amount": 1}',
	} );
	return (
		<div { ...blockProps }>
			<div className="input-group">
				<button
					className="btn btn-light quantity-decrease"
					type="button"
					data-wp-on--click="actions.decreaseAmount"
				>
					-
				</button>
				<input
					type="number"
					className="form-control bg-light border-0 text-center"
					data-wp-bind--value="context.amount"
					min="1"
					data-wp-on--input="actions.setAmount"
				/>
				<button
					className="btn btn-light quantity-increase"
					type="button"
					data-wp-on--click="actions.increaseAmount"
				>
					+
				</button>
			</div>
			<button
				className="btn btn-secondary text-nowrap"
				data-wp-on--click="actions.addToCart"
			>
				{ __( 'Add to Cart', 'ecwid-shopping-cart' ) }
			</button>
		</div>
	);
}
