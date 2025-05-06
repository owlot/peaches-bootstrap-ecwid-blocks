/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
const { serverSideRender: ServerSideRender } = wp;
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

function ProductDetailEdit( { attributes, setAttributes } ) {
	const className = useMemo(
		() => computeClassName( attributes ),
		[ attributes ]
	);

	const blockProps = useBlockProps( { className } );
	const innerBlocksProps = useInnerBlocksProps( blockProps, {} );

	return (
		<>
			<InspectorControls>
				<BootstrapSettingsPanels
					setAttributes={ setAttributes }
					attributes={ attributes }
					supportedSettings={ SUPPORTED_SETTINGS }
				/>
			</InspectorControls>
			<div { ...innerBlocksProps } />
		</>
	);
}
/*
function ProductDetailEdit( props ) {
	const { attributes, setAttributes } = props;

	useEffect( () => {
		setAttributes( { classes: computeClassName( attributes ) } );
	}, [ attributes, setAttributes ] );

	const setAttributesCB = ( obj ) => {
		setAttributes( obj );
		setAttributes( { classes: computeClassName( attributes ) } );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Product Detail Template Info', 'ecwid-shopping-cart' ) }>
					<Notice status="info" isDismissible={false}>
						{ __( 'This block creates a dynamic product detail template. The product displayed will be determined by the URL on your storefront.', 'ecwid-shopping-cart' ) }
					</Notice>
				</PanelBody>

				<PanelBody title={ __( 'Display Options', 'ecwid-shopping-cart' ) } initialOpen={ true }>
					<ToggleControl
						label={ __( 'Show Title', 'ecwid-shopping-cart' ) }
						checked={ attributes.showTitle }
						onChange={ ( value ) => setAttributes( { showTitle: value } ) }
					/>
					<ToggleControl
						label={ __( 'Show Description', 'ecwid-shopping-cart' ) }
						checked={ attributes.showDescription }
						onChange={ ( value ) => setAttributes( { showDescription: value } ) }
					/>
					<ToggleControl
						label={ __( 'Show Price', 'ecwid-shopping-cart' ) }
						checked={ attributes.showPrice }
						onChange={ ( value ) => setAttributes( { showPrice: value } ) }
					/>
					<ToggleControl
						label={ __( 'Show Gallery', 'ecwid-shopping-cart' ) }
						checked={ attributes.showGallery }
						onChange={ ( value ) => setAttributes( { showGallery: value } ) }
					/>
					<ToggleControl
						label={ __( 'Show Add to Cart', 'ecwid-shopping-cart' ) }
						checked={ attributes.showAddToCart }
						onChange={ ( value ) => setAttributes( { showAddToCart: value } ) }
					/>

					{attributes.showGallery && (
						<SelectControl
							label={ __( 'Gallery Layout', 'ecwid-shopping-cart' ) }
							value={ attributes.galleryLayout }
							options={ [
								{ label: __( 'Standard', 'ecwid-shopping-cart' ), value: 'standard' },
								{ label: __( 'Thumbnails Below', 'ecwid-shopping-cart' ), value: 'thumbnails-below' },
								{ label: __( 'Thumbnails Side', 'ecwid-shopping-cart' ), value: 'thumbnails-side' }
							] }
							onChange={ ( value ) => setAttributes( { galleryLayout: value } ) }
						/>
					)}
				</PanelBody>

				<BootstrapSettingsPanels
					setAttributes={ setAttributesCB }
					attributes={ attributes }
					supportedSettings={ SUPPORTED_SETTINGS }
				/>
			</InspectorControls>

			<div className="ecwid-product-detail-placeholder p-4 bg-light text-center">
				<div className="container py-5">
					<div className="row">
						{attributes.showGallery && (
							<div className="col-12 col-md-6 mb-4 mb-md-0">
								<div className="placeholder-image bg-secondary bg-opacity-25 rounded" style={{height: '300px', display: 'flex', alignItems: 'center', justifyContent: 'center'}}>
									<div className="text-muted">
										{__('Product Gallery', 'ecwid-shopping-cart')}
									</div>
								</div>
								{attributes.galleryLayout !== 'standard' && (
									<div className="d-flex mt-2 justify-content-center">
										{[1, 2, 3, 4].map((i) => (
											<div key={i} className="px-1" style={{width: '60px'}}>
												<div className="bg-secondary bg-opacity-25 rounded" style={{height: '60px'}}></div>
											</div>
										))}
									</div>
								)}
							</div>
						)}

						<div className="col-12 col-md-6">
							{attributes.showTitle && (
								<>
									<div className="h3 mb-1">{__('Sample Product Title', 'ecwid-shopping-cart')}</div>
									<div className="text-muted mb-3">{__('Product Subtitle', 'ecwid-shopping-cart')}</div>
								</>
							)}

							{attributes.showPrice && (
								<div className="h4 my-3">€ 29,99</div>
							)}

							{attributes.showAddToCart && (
								<div className="my-3">
									<div className="d-flex align-items-center">
										<div className="input-group me-2" style={{width: '120px'}}>
											<button className="btn btn-outline-secondary" type="button">-</button>
											<input type="text" className="form-control text-center" value="1" readOnly />
											<button className="btn btn-outline-secondary" type="button">+</button>
										</div>
										<button className="btn btn-primary">
											{__('Add to Cart', 'ecwid-shopping-cart')}
										</button>
									</div>
									<div className="text-success mt-2">
										{__('In Stock', 'ecwid-shopping-cart')}
									</div>
								</div>
							)}

							{attributes.showDescription && (
								<div className="mt-4">
									<h4>{__('Description', 'ecwid-shopping-cart')}</h4>
									<div className="text-muted">
										{__('This is a dynamic product template. The actual product details will be displayed based on the URL when viewing on the frontend.', 'ecwid-shopping-cart')}
									</div>
								</div>
							)}
						</div>
					</div>
				</div>
				<div className="text-center text-muted mt-3">
					<small>
						{__('This is a preview. The actual product will be displayed based on the URL when viewed on the frontend.', 'ecwid-shopping-cart')}
					</small>
				</div>
			</div>
		</>
	);
}
*/

export default ProductDetailEdit;
