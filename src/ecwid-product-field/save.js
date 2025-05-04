/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { computeClassName } from '../../../peaches-bootstrap-blocks/src/utils/bootstrap_settings';

export default function save({ attributes }) {
	const { fieldType, htmlTag, customFieldKey } = attributes;

	const blockProps = useBlockProps.save({
		className: computeClassName(attributes),
		'data-wp-interactive': "peaches-ecwid-product-field",
		'data-wp-context': `{ "fieldType": "${fieldType}", "customFieldKey": "${customFieldKey}", "fieldValue": "" }`,
		'data-wp-init': "callbacks.initProductField",
	});

	const getEmptyContent = () => {
		switch (fieldType) {
			case 'title':
				return __('Loading title...', 'ecwid-shopping-cart');
			case 'subtitle':
				return '';
			case 'price':
				return __('Loading price...', 'ecwid-shopping-cart');
			case 'stock':
				return __('Loading stock status...', 'ecwid-shopping-cart');
			case 'description':
				return __('Loading description...', 'ecwid-shopping-cart');
			case 'custom':
				return __('Loading custom field...', 'ecwid-shopping-cart');
			default:
				return '';
		}
	};

	const elementProps = {
		'data-wp-text': 'context.fieldValue'  // Changed from state.fieldValue to context.fieldValue
	};

	return (
		<div {...blockProps}>
			{React.createElement(htmlTag, elementProps, getEmptyContent())}
		</div>
	);
}
