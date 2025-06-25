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
	const { fieldType, htmlTag, customFieldKey, selectedProductId } =
		attributes;

	// Fields that contain HTML content
	const htmlFields = [ 'description', 'price', 'stock' ];
	const isHtmlField = htmlFields.includes( fieldType );

	const blockProps = useBlockProps.save( {
		className: computeClassName( attributes ),
		'data-wp-init': isHtmlField ? 'callbacks.initProductField' : '',
		'data-wp-interactive': 'peaches-ecwid-product-field',
		'data-wp-context': JSON.stringify( {
			fieldType,
			customFieldKey,
			selectedProductId: selectedProductId || null,
			fieldValue: '',
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

	const elementProps = {
		'data-wp-init': isHtmlField ? '' : 'callbacks.initProductField',
		'data-wp-text': 'context.fieldValue',
	};

	return (
		<div { ...blockProps }>
			{ React.createElement( htmlTag, elementProps, getEmptyContent() ) }
		</div>
	);
}
