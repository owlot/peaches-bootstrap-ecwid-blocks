/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { useMemo, useState, useEffect } from '@wordpress/element';
import {
	PanelBody,
	ToggleControl,
	Notice,
	Spinner,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	BootstrapSettingsPanels,
	computeClassName,
} from '../../../peaches-bootstrap-blocks/src/utils/bootstrap_settings';
import {
	getCurrentLanguageForAPI,
	useEcwidProductData,
	ProductSelectionPanel,
} from '../utils/ecwid-product-utils';

const SUPPORTED_SETTINGS = {
	responsive: {
		spacings: {
			margin: true,
			padding: true,
		},
	},
};

/**
 * Product Ingredients Edit Component
 *
 * Renders the editor interface with test data when available from parent context.
 *
 * @param {Object} props - Component props
 *
 * @return {JSX.Element} - Edit component
 */
function ProductIngredientsEdit( props ) {
	const { attributes, setAttributes, context, clientId } = props;
	const { startOpened } = attributes;

	// Use unified product data hook
	const {
		productData,
		isLoading: productLoading,
		error: productError,
		hasProductDetailAncestor,
		selectedProductId,
		contextProductData,
		openEcwidProductPopup,
		clearSelectedProduct,
	} = useEcwidProductData( context, attributes, setAttributes, clientId );

	const [ ingredientsData, setIngredientsData ] = useState( null );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ currentLanguage, setCurrentLanguage ] = useState( '' );

	const className = useMemo(
		() => computeClassName( attributes ),
		[ attributes ]
	);

	const blockProps = useBlockProps( {
		className,
		'data-wp-interactive': 'peaches-ecwid-product-ingredients',
	} );

	/**
	 * Listen for language changes in the editor (if peaches-multilingual is active)
	 */
	useEffect( () => {
		if ( typeof wp !== 'undefined' && wp.data && wp.data.subscribe ) {
			let currentLang = getCurrentLanguageForAPI();
			setCurrentLanguage( currentLang );

			const unsubscribe = wp.data.subscribe( () => {
				const newLang = getCurrentLanguageForAPI();
				if ( newLang !== currentLang ) {
					currentLang = newLang;
					setCurrentLanguage( newLang );
					// Refetch ingredients when language changes
					if ( productData?.id ) {
						// Call your existing fetch logic here
						setIsLoading( true );
						setError( null );

						const currentLang = getCurrentLanguageForAPI();
						const url = `/wp-json/peaches/v1/product-ingredients/${ productData.id }`;
						const urlWithLang = `${ url }?lang=${ encodeURIComponent(
							currentLang
						) }`;

						fetchWithLanguage( urlWithLang )
							.then( ( response ) => {
								if ( ! response.ok ) {
									throw new Error(
										`HTTP error! status: ${ response.status }`
									);
								}
								return response.json();
							} )
							.then( ( data ) => {
								if ( data.success ) {
									setIngredientsData( data );
									setCurrentLanguage(
										data.language || currentLang
									);
								} else {
									throw new Error(
										'Failed to fetch ingredients'
									);
								}
							} )
							.catch( ( fetchError ) => {
								console.error(
									'Error fetching ingredients:',
									fetchError
								);
								setError( fetchError.message );
								setIngredientsData( null );
							} )
							.finally( () => {
								setIsLoading( false );
							} );
					}
				}
			} );

			return unsubscribe;
		}

		// Set initial language for frontend
		setCurrentLanguage( getCurrentLanguageForAPI() );
	}, [] );

	/**
	 * Fetch ingredients data when test product data changes
	 */
	useEffect( () => {
		if ( productData?.id ) {
			setIsLoading( true );
			setError( null );

			const currentLang = getCurrentLanguageForAPI();
			const url = `/wp-json/peaches/v1/product-ingredients/${ productData.id }`;
			const urlWithLang = `${ url }?lang=${ encodeURIComponent(
				currentLang
			) }`;

			fetch( urlWithLang, {
				headers: {
					Accept: 'application/json',
				},
				credentials: 'same-origin',
			} )
				.then( ( response ) => {
					if ( ! response.ok ) {
						throw new Error(
							`HTTP error! status: ${ response.status }`
						);
					}
					return response.json();
				} )
				.then( ( data ) => {
					if ( data.success ) {
						setIngredientsData( data );
						setCurrentLanguage( data.language || currentLang );
					} else {
						throw new Error( 'Failed to fetch ingredients' );
					}
				} )
				.catch( ( fetchError ) => {
					console.error( 'Error fetching ingredients:', fetchError );
					setError( fetchError.message );
					setIngredientsData( null );
				} )
				.finally( () => {
					setIsLoading( false );
				} );
		} else {
			setIngredientsData( null );
			setError( null );
		}
	}, [ productData?.id ] );

	/**
	 * Get preview ingredients for display
	 *
	 * @return {Array} - Array of ingredient objects for preview
	 */
	const getPreviewIngredients = () => {
		if (
			ingredientsData?.ingredients &&
			ingredientsData.ingredients.length > 0
		) {
			// Use real ingredients data
			return ingredientsData.ingredients;
		}

		// Fallback to sample ingredients
		return [
			{
				name: __( 'Prickly Pear Seed Oil', 'ecwid-shopping-cart' ),
				description: __(
					'Rich in antioxidants and fatty acids.',
					'ecwid-shopping-cart'
				),
			},
			{
				name: __( 'Coriander Seed Oil', 'ecwid-shopping-cart' ),
				description: __(
					'Known for its hydrating properties.',
					'ecwid-shopping-cart'
				),
			},
		];
	};

	const previewIngredients = getPreviewIngredients();

	return (
		<>
			<InspectorControls>
				<ProductSelectionPanel
					productData={ productData }
					isLoading={ productLoading }
					error={ productError }
					hasProductDetailAncestor={ hasProductDetailAncestor }
					selectedProductId={ selectedProductId }
					contextProductData={ contextProductData }
					openEcwidProductPopup={ openEcwidProductPopup }
					clearSelectedProduct={ clearSelectedProduct }
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
				<PanelBody
					title={ __(
						'Product Ingredients Settings',
						'ecwid-shopping-cart'
					) }
				>
					{ productData && isLoading && (
						<Notice
							className="mb-2"
							status="info"
							isDismissible={ false }
						>
							<div className="d-flex align-items-center gap-2">
								<Spinner />
								<span>
									{ __(
										'Loading ingredients for test product…',
										'ecwid-shopping-cart'
									) }
								</span>
							</div>
						</Notice>
					) }

					{ productData && ! isLoading && error && (
						<Notice
							className="mb-2"
							status="error"
							isDismissible={ false }
						>
							{ __(
								'Error loading ingredients:',
								'ecwid-shopping-cart'
							) }{ ' ' }
							{ error }
						</Notice>
					) }

					{ productData &&
						! isLoading &&
						! error &&
						ingredientsData && (
							<Notice
								className="mb-2"
								status="success"
								isDismissible={ false }
							>
								{ ingredientsData.ingredients.length > 0 ? (
									<>
										{ __( 'Found', 'ecwid-shopping-cart' ) }{ ' ' }
										{ ingredientsData.ingredients.length }{ ' ' }
										{ __(
											'ingredients',
											'ecwid-shopping-cart'
										) }
									</>
								) : (
									<>
										{ __(
											'Test product has no ingredients configured:',
											'ecwid-shopping-cart'
										) }{ ' ' }
										<strong>{ productData.name }</strong>
										<br />
										{ __(
											'Showing sample ingredients for preview.',
											'ecwid-shopping-cart'
										) }
									</>
								) }
								{ currentLanguage &&
									currentLanguage !== 'en' && (
										<>
											<br />
											<small>
												{ __(
													'Language:',
													'ecwid-shopping-cart'
												) }{ ' ' }
												{ currentLanguage }
											</small>
										</>
									) }
							</Notice>
						) }

					<ToggleControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Start Opened', 'ecwid-shopping-cart' ) }
						checked={ startOpened }
						onChange={ ( value ) =>
							setAttributes( { startOpened: value } )
						}
					/>
				</PanelBody>

				<BootstrapSettingsPanels
					setAttributes={ setAttributes }
					attributes={ attributes }
					supportedSettings={ SUPPORTED_SETTINGS }
				/>
			</InspectorControls>

			{ productLoading && (
				<div className="text-center p-2">
					<div
						className="spinner-border spinner-border-sm"
						role="status"
					>
						<span className="visually-hidden">
							{ __(
								'Loading product data…',
								'ecwid-shopping-cart'
							) }
						</span>
					</div>
				</div>
			) }

			{ ! productLoading && (
				<div { ...blockProps }>
					<div className="product-ingredients-preview">
						<div className="accordion" id="ingredientsPreview">
							{ previewIngredients.map( ( ingredient, index ) => (
								<div key={ index } className="accordion-item">
									<div className="accordion-header">
										<button
											className={ `accordion-button ${
												index === 0 && startOpened
													? ''
													: 'collapsed'
											}` }
											type="button"
										>
											{ ingredient.name }
										</button>
									</div>
									<div
										className={ `accordion-collapse collapse ${
											index === 0 && startOpened
												? 'show'
												: ''
										}` }
									>
										<div className="accordion-body">
											{ ingredient.description }
										</div>
									</div>
								</div>
							) ) }

							{ previewIngredients.length === 0 && (
								<div className="text-center text-muted py-4">
									<p>
										{ __(
											'No ingredients configured for this product.',
											'ecwid-shopping-cart'
										) }
									</p>
								</div>
							) }
						</div>
					</div>
				</div>
			) }
		</>
	);
}

export default ProductIngredientsEdit;
