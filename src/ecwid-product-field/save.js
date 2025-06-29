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
		showImage,
		imageMediaTag,
		imageSize,
		imagePosition,
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
			imageMediaTag,
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
		const sizeClasses = {
			small: 'width-32 height-32',
			medium: 'width-48 height-48',
			large: 'width-64 height-64',
		};
		const imageClasses = `object-fit-cover ${
			sizeClasses[ imageSize ] || sizeClasses.small
		} ${ imagePosition === 'after' ? 'ms-2' : 'me-2' }`;

		if ( displayMode === 'list' ) {
			return (
				<ul className="list-unstyled">
					<template data-wp-each--line="context.productLines">
						<li
							data-wp-bind--id="context.line.id"
							className={ computedClassName }
						>
							{ showImage && imagePosition === 'before' && (
								<img
									className={ imageClasses }
									data-wp-bind--src="state.lineImageUrl"
									data-wp-bind--alt="state.lineImageAlt"
									data-wp-class--d-none="!state.hasImage"
								/>
							) }
							{ React.createElement( htmlTag, {
								'data-wp-bind--id': 'context.line.id',
								'data-wp-text': 'state.decodedLineContent',
							} ) }
							{ showImage && imagePosition === 'after' && (
								<img
									className={ imageClasses }
									data-wp-bind--src="state.lineImageUrl"
									data-wp-bind--alt="state.lineImageAlt"
									data-wp-class--d-none="!state.hasImage"
								/>
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
				{ showImage && imagePosition === 'before' && (
					<img
						className={ imageClasses }
						data-wp-bind--src="state.lineImageUrl"
						data-wp-bind--alt="state.lineImageAlt"
						data-wp-class--d-none="!state.hasImage"
					/>
				) }
				{ React.createElement( htmlTag, {
					className,
					'data-wp-bind--id': 'context.line.id',
					'data-wp-text': 'state.decodedLineContent',
				} ) }
				{ showImage && imagePosition === 'after' && (
					<img
						className={ imageClasses }
						data-wp-bind--src="state.lineImageUrl"
						data-wp-bind--alt="state.lineImageAlt"
						data-wp-class--d-none="!state.hasImage"
					/>
				) }
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
