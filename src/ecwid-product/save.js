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
	const { id } = attributes;

	const blockProps = useBlockProps.save( {
		className: computeClassName( attributes ),
		'data-wp-interactive': 'peaches-ecwid-product',
		'data-wp-context': `{ "productId": ${
			id || 0
		}, "isLoading": true, "product": null }`,
		'data-wp-init': 'callbacks.initProduct',
	} );

	return (
		<div { ...blockProps } className="card h-100 border-0">
			<div className="ratio ratio-1x1">
				<img
					className="card-img-top"
					data-wp-bind--src="state.productImage"
					data-wp-bind--alt="state.productName"
					alt={ __( 'Product image', 'ecwid-shopping-cart' ) }
				/>
			</div>
			<div className="card-body p-2 p-md-3 d-flex row-cols-1 flex-wrap align-content-between">
				<h5
					role="button"
					className="card-title"
					data-wp-text="state.productName"
					data-wp-on--click="actions.navigateToProduct"
				>
					Product Name
				</h5>
				<p
					className="card-text text-muted"
					data-wp-text="state.productSubtitle"
				></p>
			</div>
			<div className="card-footer border-0 hstack justify-content-between">
				<div
					className="card-text fw-bold lead"
					data-wp-text="state.productPrice"
				></div>
				{ attributes.showAddToCart && (
					<button
						title="Add to cart"
						className="add-to-cart btn pe-0"
						aria-label="Add to cart"
						data-wp-on--click="actions.addToCart"
					/>
				) }
			</div>

			<div
				data-wp-bind--hidden="!context.isLoading"
				className="text-center my-3"
			>
				<div className="spinner-border text-primary" role="status">
					<span className="visually-hidden">
						{ __( 'Loading product…', 'ecwid-shopping-cart' ) }
					</span>
				</div>
			</div>
		</div>
	);
}
