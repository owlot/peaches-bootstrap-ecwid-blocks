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
	const { descriptionType, displayTitle } = attributes;

	const blockProps = useBlockProps.save( {
		className: computeClassName( attributes ),
		'data-wp-init': 'callbacks.initProductDescription',
		'data-wp-interactive': 'peaches-ecwid-product-description',
		'data-wp-context': `{ "descriptionType": "${ descriptionType }", "displayTitle": ${ displayTitle }, "customTitle": "${
			attributes.customTitle || ''
		}", "descriptionContent": "", "descriptionTitle": "" }`,
	} );

	return (
		<div { ...blockProps }>
			{ displayTitle && (
				<h3 className="product-description-title">
					{ __( 'Loading description title…', 'peaches' ) }
				</h3>
			) }
			<div className="product-description-content">
				{ __( 'Loading description…', 'peaches' ) }
			</div>
		</div>
	);
}
