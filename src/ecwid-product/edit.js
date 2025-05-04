/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
const { serverSideRender: ServerSideRender } = wp;
const { Fragment, useEffect } = wp.element;

import { InspectorControls } from '@wordpress/block-editor';

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

function ProductEdit( props ) {
	const { attributes, setAttributes } = props;

	useEffect( () => {
		setAttributes( { classes: computeClassName( attributes ) } );
	}, [ attributes, setAttributes ] );

	const setAttributesCB = ( obj ) => {
		setAttributes( obj );
		setAttributes( { classes: computeClassName( attributes ) } );
	};

	const saveCallback = function ( params ) {
		const newAttributes = {
			id: params.newProps.product.id,
		};

		window.EcwidGutenbergParams.products[ params.newProps.product.id ] = {
			name: params.newProps.product.name,
			imageUrl: params.newProps.product.thumb,
		};

		params.originalProps.setAttributes( newAttributes );
	};

	function openEcwidProductPopup( popupProps ) {
		window.ecwid_open_product_popup( {
			saveCallback,
			popupProps,
		} );
	}

	return (
		<>
			<InspectorControls>
				<div className="block-editor-block-card">
					{ attributes.id && (
						<div>
							<div className="ec-store-inspector-row">
								<label className="ec-store-inspector-subheader">
									{ __(
										'Displayed product',
										'ecwid-shopping-cart'
									) }
								</label>
							</div>
							<div className="ec-store-inspector-row">
								{ window.EcwidGutenbergParams.products &&
									window.EcwidGutenbergParams.products[
										attributes.id
									] && <label>{ attributes.name }</label> }

								<button
									className="button"
									onClick={ () =>
										openEcwidProductPopup( props )
									}
								>
									{ __( 'Change', 'ecwid-shopping-cart' ) }
								</button>
							</div>
						</div>
					) }
					{ ! attributes.id && (
						<div className="ec-store-inspector-row">
							<button
								className="button"
								onClick={ () => openEcwidProductPopup( props ) }
							>
								{ __(
									'Choose product',
									'ecwid-shopping-cart'
								) }
							</button>
						</div>
					) }
				</div>

				<BootstrapSettingsPanels
					setAttributes={ setAttributesCB }
					attributes={ attributes }
					supportedSettings={ SUPPORTED_SETTINGS }
				/>
			</InspectorControls>
			{ ! attributes.id && (
				<div className="ratio ratio-1x1 text-center">
					<button
						className="btn btn-primary"
						onClick={ () => {
							const params = {
								saveCallback,
								props,
							};
							ecwid_open_product_popup( params );
						} }
					>
						{ window.EcwidGutenbergParams.chooseProduct }
					</button>
				</div>
			) }
			{ attributes.id && (
				<ServerSideRender
					className="h-100"
					attributes={ attributes }
					block="peaches/ecwid-product"
				/>
			) }
		</>
	);
}

export default ProductEdit;
