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

const SUPPORTED_SETTINGS = {
	responsive: {
		spacings: {
			margin: true,
			padding: true,
		},
	},
};

/**
 * Get current language for API requests
 *
 * @return {string} Current language code (normalized to 2 characters)
 */
function getCurrentLanguageForAPI() {
	let language = '';

	// In block editor - check for peaches-multilingual store
	if ( typeof wp !== 'undefined' && wp.data && wp.data.select ) {
		try {
			const multilingualStore = wp.data.select( 'peaches/multilingual' );
			if (
				multilingualStore &&
				typeof multilingualStore.getCurrentEditorLanguage === 'function'
			) {
				const editorLang = multilingualStore.getCurrentEditorLanguage();
				if ( editorLang ) {
					language = editorLang;
				}
			}
		} catch ( error ) {
			// Peaches multilingual store not available, continue with fallbacks
		}
	}

	// Frontend - check HTML lang attribute (format: "en-US", "fr-FR", "nl-NL", etc.)
	if ( ! language ) {
		const htmlLang = document.documentElement.lang;
		if ( htmlLang ) {
			language = htmlLang;
		}
	}

	// Fallback - check for language in body class (common pattern)
	if ( ! language ) {
		const bodyClasses = document.body.className;
		const langMatch = bodyClasses.match( /\blang-([a-z]{2})\b/ );
		if ( langMatch ) {
			language = langMatch[ 1 ];
		}
	}

	// Check URL for language parameter
	if ( ! language ) {
		const urlParams = new URLSearchParams( window.location.search );
		const langParam = urlParams.get( 'lang' );
		if ( langParam && /^[a-z]{2}/.test( langParam ) ) {
			language = langParam;
		}
	}

	// Normalize the language code to match ingredient storage format
	return normalizeLanguageCode( language || 'en' );
}

/**
 * Normalize language code to match ingredient storage format.
 *
 * Converts codes like 'nl_NL', 'en-US', 'fr-FR' to 'nl', 'en', 'fr'
 *
 * @param {string} languageCode - Raw language code
 * @return {string} Normalized language code (2 characters)
 */
function normalizeLanguageCode( languageCode ) {
	if ( ! languageCode ) {
		return 'en';
	}

	// Convert to lowercase
	languageCode = languageCode.toLowerCase();

	// Handle formats like 'nl_NL', 'nl-NL'
	if ( languageCode.includes( '_' ) ) {
		return languageCode.split( '_' )[ 0 ];
	}

	if ( languageCode.includes( '-' ) ) {
		return languageCode.split( '-' )[ 0 ];
	}

	// Already normalized (should be 2 characters)
	return languageCode.length > 2
		? languageCode.substring( 0, 2 )
		: languageCode;
}

/**
 * Enhanced fetch function that includes language headers
 *
 * @param {string} url     - API endpoint URL
 * @param {Object} options - Fetch options
 * @return {Promise} Fetch promise
 */
function fetchWithLanguage( url, options = {} ) {
	const currentLang = getCurrentLanguageForAPI();

	// Add language headers for the API
	const headers = {
		Accept: 'application/json',
		'X-Peaches-Language': currentLang,
		...options.headers,
	};

	// For editor requests, also add editor-specific header
	if ( typeof wp !== 'undefined' && wp.data ) {
		headers[ 'X-Editor-Language' ] = currentLang;
	}

	return fetch( url, {
		credentials: 'same-origin',
		...options,
		headers,
	} );
}

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
	const { attributes, setAttributes, context } = props;
	const { startOpened } = attributes;

	// Get test product data from parent context
	const testProductData = context?.[ 'peaches/testProductData' ];

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
					if ( testProductData?.id ) {
						// Call your existing fetch logic here
						setIsLoading( true );
						setError( null );

						const currentLang = getCurrentLanguageForAPI();
						const url = `/wp-json/peaches/v1/product-ingredients/${ testProductData.id }`;
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
		if ( testProductData?.id ) {
			setIsLoading( true );
			setError( null );

			const currentLang = getCurrentLanguageForAPI();
			const url = `/wp-json/peaches/v1/product-ingredients/${ testProductData.id }`;
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
	}, [ testProductData?.id ] );

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
				<PanelBody
					title={ __(
						'Product Ingredients Settings',
						'ecwid-shopping-cart'
					) }
				>
					{ ! testProductData && (
						<Notice status="warning" isDismissible={ false }>
							{ __(
								'No test product configured in parent block. Configure a test product to preview real ingredients.',
								'ecwid-shopping-cart'
							) }
						</Notice>
					) }

					{ testProductData && isLoading && (
						<Notice status="info" isDismissible={ false }>
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

					{ testProductData && ! isLoading && error && (
						<Notice status="error" isDismissible={ false }>
							{ __(
								'Error loading ingredients:',
								'ecwid-shopping-cart'
							) }{ ' ' }
							{ error }
						</Notice>
					) }

					{ testProductData &&
						! isLoading &&
						! error &&
						ingredientsData && (
							<Notice status="success" isDismissible={ false }>
								{ ingredientsData.ingredients.length > 0 ? (
									<>
										{ __(
											'Using ingredients from test product:',
											'ecwid-shopping-cart'
										) }{ ' ' }
										<strong>
											{ testProductData.name }
										</strong>
										<br />
										{ __(
											'Found',
											'ecwid-shopping-cart'
										) }{ ' ' }
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
										<strong>
											{ testProductData.name }
										</strong>
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

					{ ! testProductData && (
						<Notice status="info" isDismissible={ false }>
							{ __(
								'This block displays product ingredients dynamically based on the product detail block.',
								'ecwid-shopping-cart'
							) }
						</Notice>
					) }

					<ToggleControl
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
										index === 0 && startOpened ? 'show' : ''
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
		</>
	);
}

export default ProductIngredientsEdit;
