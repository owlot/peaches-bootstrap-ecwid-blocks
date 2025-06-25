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
	const { startOpened, selectedProductId } = attributes;

	const blockProps = useBlockProps.save( {
		className: computeClassName( attributes ),
		'data-wp-interactive': 'peaches-ecwid-product-ingredients',
		'data-wp-init': 'callbacks.initProductIngredients',
		'data-wp-context': JSON.stringify( {
			selectedProductId: selectedProductId || null,
			isLoading: true,
			ingredients: [],
		} ),
	} );

	return (
		<div { ...blockProps }>
			<div className="product-ingredients accordion">
				<template data-wp-each--ingredient="context.ingredients">
					<div className="accordion-item">
						<div className="accordion-header">
							<button
								className={ `accordion-button ${
									! startOpened ? 'collapsed' : ''
								}` }
								type="button"
								data-bs-toggle="collapse"
								data-wp-bind--data-bs-target="context.ingredient.targetId"
								data-wp-bind--aria-controls="context.ingredient.collapseId"
								data-wp-bind--aria-expande="!(context.ingredient.isCollapsed ?? true)"
								data-wp-on--click="actions.toggleAccordion"
							>
								<span data-wp-text="context.ingredient.name"></span>
							</button>
						</div>
						<div
							className="accordion-collapse"
							data-wp-class--collapse="context.ingredient.isCollapsed"
							data-wp-bind--id="context.ingredient.collapseId"
							data-wp-bind--aria-labelledby="context.ingredient.headingId"
						>
							<div
								className="accordion-body"
								data-wp-text="context.ingredient.description"
							></div>
						</div>
					</div>
				</template>

				<div
					data-wp-class--d-none="!context.isLoading"
					className="text-center my-3"
				>
					<div className="spinner-border text-primary" role="status">
						<span className="visually-hidden">
							{ __(
								'Loading ingredients…',
								'ecwid-shopping-cart'
							) }
						</span>
					</div>
				</div>
			</div>
		</div>
	);
}
