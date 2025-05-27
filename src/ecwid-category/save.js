/**
 * WordPress dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { computeClassName } from '../../../peaches-bootstrap-blocks/src/utils/bootstrap_settings';

export default function save( { attributes } ) {
	const blockProps = useBlockProps.save( {
		className: clsx( 'row', computeClassName( attributes ), {} ),
		'data-wp-interactive': 'peaches-ecwid-category',
		'data-wp-context': '{ "isLoading": true, "categories": [] }',
		'data-wp-init': 'callbacks.initCategories',
	} );

	return (
		<div { ...blockProps }>
			<template data-wp-each--category="context.categories">
				<div
					className="col"
					data-wp-interactive="peaches-ecwid-category"
					data-wp-context='{ "categoryId": context.category.id }'
					data-wp-on--click="actions.navigateToCategory"
				>
					<div className="card h-100 border-0">
						<a
							className="ratio ratio-1x1"
							data-wp-bind--href="context.category.url"
						>
							<img
								className="card-img-top"
								data-wp-bind--src="context.category.thumbnailUrl"
								data-wp-bind--alt="context.category.name"
								alt={ __(
									'Category image',
									'ecwid-shopping-cart'
								) }
							/>
						</a>
						<div className="card-body p-2 p-md-3">
							<h5
								className="card-title"
								data-wp-text="context.category.name"
							></h5>
						</div>
					</div>
				</div>
			</template>

			<div
				data-wp-bind--hidden="!context.isLoading"
				className="text-center my-3"
			>
				<div className="spinner-border text-primary" role="status">
					<span className="visually-hidden">
						{ __( 'Loading categories…', 'ecwid-shopping-cart' ) }
					</span>
				</div>
			</div>
		</div>
	);
}
