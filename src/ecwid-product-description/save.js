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
		descriptionType,
		showTitle,
		titleTag,
		customTitle,
		collapseInitially,
		productId
	} = attributes;

	const blockProps = useBlockProps.save( {
		className: computeClassName( attributes ),
		'data-wp-init': 'callbacks.initProductDescription',
		'data-wp-interactive': 'peaches-ecwid-product-description',
		'data-wp-context': JSON.stringify({
			descriptionType,
			showTitle,
			titleTag,
			customTitle,
			collapseInitially,
			productId,
			description: null,
			isLoading: true,
			hasError: false,
			isCollapsed: collapseInitially
		}),
	} );

	/**
	 * Generate unique ID for collapsible content
	 */
	const generateCollapseId = () => {
		return `desc-${descriptionType}-${Math.random().toString(36).substr(2, 9)}`;
	};

	const collapseId = collapseInitially ? generateCollapseId() : null;

	return (
		<div { ...blockProps }>
			<div
				className="product-description-loading d-flex justify-content-center align-items-center p-4"
				data-wp-class--d-none="!context.isLoading"
			>
				<div className="spinner-border spinner-border-sm me-2" role="status">
					<span className="visually-hidden">{__('Loading...', 'peaches')}</span>
				</div>
				<span>{__('Loading description...', 'peaches')}</span>
			</div>

			<div
				className="product-description-error alert alert-warning"
				data-wp-class--d-none="!context.hasError"
				data-wp-text="context.errorMessage"
			>
			</div>

			<div
				className="product-description-content"
				data-wp-class--d-none="context.isLoading || context.hasError || !context.description"
			>
				{collapseInitially ? (
					<>
						<div className="description-toggle" data-wp-show="context.showTitle && (context.customTitle || context.description?.title)">
							{React.createElement(titleTag, {
								className: 'product-description-title mb-0'
							}, [
								React.createElement('button', {
									key: 'toggle-button',
									className: 'btn btn-link p-0 text-start fw-bold text-decoration-none',
									type: 'button',
									'data-bs-toggle': 'collapse',
									'data-bs-target': `#${collapseId}`,
									'aria-expanded': 'false',
									'aria-controls': collapseId,
									'data-wp-on--click': 'actions.toggleCollapse',
									'data-wp-text': 'context.displayTitle'
								}),
								React.createElement('i', {
									key: 'toggle-icon',
									className: 'fas ms-2',
									'data-wp-class--fa-chevron-down': 'context.isCollapsed',
									'data-wp-class--fa-chevron-up': '!context.isCollapsed'
								})
							])}
						</div>
						<div
							className="collapse"
							id={collapseId}
							data-wp-class--show="!context.isCollapsed"
						>
							<div
								className="product-description-text mt-2"
								data-wp-init="callbacks.setDescriptionContent"
							>
							</div>
						</div>
					</>
				) : (
					<>
						{React.createElement(titleTag, {
							className: 'product-description-title',
							'data-wp-show': 'context.showTitle && (context.customTitle || context.description?.title)',
							'data-wp-text': 'context.displayTitle'
						})}
						<div
							className="product-description-text"
							data-wp-init="callbacks.setDescriptionContent"
						>
						</div>
					</>
				)}
			</div>

			<div
				className="product-description-empty alert alert-info"
				data-wp-class--d-none="context.isLoading || context.hasError || context.description"
			>
				{__('No description available for this product.', 'peaches')}
			</div>
		</div>
	);
}
