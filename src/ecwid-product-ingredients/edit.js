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

	const className = useMemo(
		() => computeClassName( attributes ),
		[ attributes ]
	);

	const blockProps = useBlockProps( {
		className,
		'data-wp-interactive': 'peaches-ecwid-product-ingredients',
	} );

	/**
	 * Fetch ingredients data when test product data changes
	 */
	useEffect( () => {
		if ( testProductData?.id ) {
			setIsLoading( true );
			setError( null );

			// Use the same API endpoint as the frontend
			fetch(
				`/wp-json/peaches/v1/product-ingredients/${ testProductData.id }`,
				{
					headers: {
						Accept: 'application/json',
					},
					credentials: 'same-origin',
				}
			)
				.then( ( response ) => {
					if ( ! response.ok ) {
						throw new Error(
							`HTTP error! status: ${ response.status }`
						);
					}
					return response.json();
				} )
				.then( ( data ) => {
					setIsLoading( false );
					if (
						data &&
						data.ingredients &&
						Array.isArray( data.ingredients )
					) {
						setIngredientsData( data );
						setError( null );
					} else {
						// No ingredients found - this is okay, just show empty state
						setIngredientsData( { ingredients: [] } );
						setError( null );
					}
				} )
				.catch( ( err ) => {
					setIsLoading( false );
					setError( err.message );
					setIngredientsData( null );
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
