/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { useMemo } from '@wordpress/element';
const { PanelBody, ToggleControl, SelectControl, Notice } = wp.components;

/**
 * Internal dependencies
 */
import {
	BootstrapSettingsPanels,
	computeClassName,
} from '../../../peaches-bootstrap-blocks/src/utils/bootstrap_settings';

const SUPPORTED_SETTINGS = {
	responsive: {
		spacings: {
			margin: true,
			padding: true,
		},
	},
};

function AddToCartEdit( props ) {
	const { attributes, setAttributes } = props;

	const className = useMemo(
		() => computeClassName( attributes ),
		[ attributes ]
	);

	const blockProps = useBlockProps( {
		className,
		'data-wp-interactive': 'peaches-ecwid-add-to-cart',
		'data-wp-context': '{"amount": 1}',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Product Add to Cart Template Info',
						'ecwid-shopping-cart'
					) }
				>
					<Notice status="info" isDismissible={ false }>
						{ __(
							'This block creates a dynamic add to cart template. The product added will be determined by the URL on your storefront.',
							'ecwid-shopping-cart'
						) }
					</Notice>
				</PanelBody>

				<BootstrapSettingsPanels
					setAttributes={ setAttributes }
					attributes={ attributes }
					supportedSettings={ SUPPORTED_SETTINGS }
				/>
			</InspectorControls>
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
		</>
	);
}

export default AddToCartEdit;
