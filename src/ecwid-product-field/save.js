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
		fieldType,
		htmlTag,
		customFieldKey,
		selectedProductId,
		lineType,
		displayMode,
		showLineDescriptions,
		maxLines,
		lineSeparator,
		descriptionSeparator,
	} = attributes;

	const computedClassName = computeClassName( attributes );

	// Fields that contain HTML content
	const htmlFields = [ 'description', 'price', 'stock' ];
	const isHtmlField = htmlFields.includes( fieldType );

	const blockProps = useBlockProps.save( {
		'data-wp-interactive':
			fieldType === 'lines' || fieldType === 'lines_filtered'
				? 'peaches-ecwid-product-lines'
				: 'peaches-ecwid-product-field',
		'data-wp-context': JSON.stringify( {
			fieldType,
			customFieldKey,
			selectedProductId: selectedProductId || null,
			fieldValue: '',
			lineType,
			displayMode,
			maxLines,
			lineSeparator,
			descriptionSeparator,
			showLineDescriptions,
		} ),
	} );

	const getEmptyContent = () => {
		switch ( fieldType ) {
			case 'title':
				return __( 'Loading title…', 'ecwid-shopping-cart' );
			case 'subtitle':
				return '';
			case 'price':
				return __( 'Loading price…', 'ecwid-shopping-cart' );
			case 'stock':
				return __( 'Loading stock status…', 'ecwid-shopping-cart' );
			case 'description':
				return __( 'Loading description…', 'ecwid-shopping-cart' );
			case 'custom':
				return __( 'Loading custom field…', 'ecwid-shopping-cart' );
			default:
				return '';
		}
	};
	/**
	 * Get template for product lines with proper styling
	 *
	 * @return {JSX.Element|string} - Template content
	 */
	const getProductLinesContent = () => {
		if ( displayMode === 'list' ) {
			return (
				<ul className="list-unstyled">
					<template data-wp-each--line="context.productLines">
						<li
							data-wp-bind--id="context.line.id"
							className={ computedClassName }
						>
							<span data-wp-text="state.decodedName" />
							{ showLineDescriptions && (
								<span data-wp-text="state.decodedDescription" />
							) }
						</li>
					</template>
				</ul>
			);
		}

		const className =
			displayMode === 'badges'
				? `${ computedClassName } badge`
				: computedClassName;

		return (
			<template data-wp-each--line="context.productLines">
				{ React.createElement( htmlTag, {
					className,
					'data-wp-bind--id': 'context.line.id',
					'data-wp-text': 'state.decodedLineContent',
				} ) }
			</template>
		);
	};

	if ( fieldType === 'lines' || fieldType === 'lines_filtered' ) {
		return (
			<div { ...blockProps } data-wp-init="callbacks.initProductField">
				{ getProductLinesContent() }
			</div>
		);
	}
	const elementProps = {
		'data-wp-init': 'callbacks.initProductField',
		'data-wp-text': 'context.fieldValue',
	};

	return (
		<div { ...blockProps }>
			{ React.createElement( htmlTag, elementProps, getEmptyContent() ) }
		</div>
	);
}
