/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { useMemo, useState, useEffect } from '@wordpress/element';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	TextControl,
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
 * Product Description Edit Component
 *
 * Simple product description display following the product-field pattern.
 *
 * @param {Object} props - Component props
 *
 * @return {JSX.Element} - Edit component
 */
function ProductDescriptionEdit( props ) {
	const { attributes, setAttributes, context } = props;
	const { descriptionType, displayTitle, customTitle } = attributes;

	// Get test product data from parent context
	const testProductData = context?.[ 'peaches/testProductData' ];

	const [ descriptionData, setDescriptionData ] = useState( null );
	const [ descriptionTypes, setDescriptionTypes ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( null );

	const className = useMemo(
		() => computeClassName( attributes ),
		[ attributes ]
	);

	const blockProps = useBlockProps( {
		className,
		'data-wp-interactive': 'peaches-ecwid-product-description',
	} );

	/**
	 * Fetch available description types
	 */
	useEffect( () => {
		const fetchDescriptionTypes = async () => {
			try {
				const response = await fetch(
					'/wp-json/peaches/v1/description-types',
					{
						headers: {
							Accept: 'application/json',
						},
						credentials: 'same-origin',
					}
				);

				if ( ! response.ok ) {
					throw new Error(
						`HTTP error! status: ${ response.status }`
					);
				}

				const data = await response.json();
				if ( data && data.success && data.types ) {
					const typeOptions = Object.entries( data.types ).map(
						( [ key, label ] ) => ( {
							label,
							value: key,
						} )
					);
					setDescriptionTypes( typeOptions );
				}
			} catch ( err ) {
				console.error( 'Error fetching description types:', err );
				// Fallback to default types
				setDescriptionTypes( [
					{ label: __( 'Product Usage', 'peaches' ), value: 'usage' },
					{
						label: __( 'Detailed Ingredients', 'peaches' ),
						value: 'ingredients',
					},
					{
						label: __( 'Care Instructions', 'peaches' ),
						value: 'care',
					},
					{
						label: __( 'Warranty Information', 'peaches' ),
						value: 'warranty',
					},
					{
						label: __( 'Key Features', 'peaches' ),
						value: 'features',
					},
					{
						label: __( 'Technical Specifications', 'peaches' ),
						value: 'technical',
					},
					{
						label: __( 'Custom Description', 'peaches' ),
						value: 'custom',
					},
				] );
			}
		};

		fetchDescriptionTypes();
	}, [] );

	/**
	 * Fetch description data when test product data or description type changes
	 */
	useEffect( () => {
		if ( testProductData?.id && descriptionType ) {
			setIsLoading( true );
			setError( null );

			// Use the new unified API endpoint
			fetch(
				`/wp-json/peaches/v1/product-descriptions/${ testProductData.id }/type/${ descriptionType }`,
				{
					headers: {
						Accept: 'application/json',
					},
					credentials: 'same-origin',
				}
			)
				.then( ( response ) => {
					if ( response.status === 404 ) {
						// Description not found for this type - this is expected
						setDescriptionData( null );
						setIsLoading( false );
						return;
					}

					if ( ! response.ok ) {
						throw new Error(
							`HTTP error! status: ${ response.status }`
						);
					}
					return response.json();
				} )
				.then( ( data ) => {
					if ( data && data.success && data.description ) {
						setDescriptionData( data.description );
					} else {
						setDescriptionData( null );
					}
					setIsLoading( false );
				} )
				.catch( ( err ) => {
					console.error( 'Error fetching description data:', err );
					setError( err.message );
					setDescriptionData( null );
					setIsLoading( false );
				} );
		} else {
			setDescriptionData( null );
		}
	}, [ testProductData?.id, descriptionType ] );

	/**
	 * Get display title for the description
	 */
	const getDisplayTitle = () => {
		if ( customTitle ) {
			return customTitle;
		}

		if ( descriptionData?.title ) {
			return descriptionData.title;
		}

		const typeOption = descriptionTypes.find(
			( t ) => t.value === descriptionType
		);
		return typeOption?.label || __( 'Product Description', 'peaches' );
	};

	/**
	 * Render preview content
	 */
	const renderPreviewContent = () => {
		if ( isLoading ) {
			return (
				<div className="d-flex justify-content-center align-items-center p-4">
					<Spinner />
					<span className="ms-2">
						{ __( 'Loading description…', 'peaches' ) }
					</span>
				</div>
			);
		}

		if ( error ) {
			return (
				<Notice status="error" isDismissible={ false }>
					{ __( 'Error loading description:', 'peaches' ) } { error }
				</Notice>
			);
		}

		if ( ! testProductData?.id ) {
			return (
				<Notice status="info" isDismissible={ false }>
					{ __(
						'This block will display product descriptions when added to a product detail template with test product data.',
						'peaches'
					) }
				</Notice>
			);
		}

		if ( ! descriptionData ) {
			return (
				<Notice status="warning" isDismissible={ false }>
					{ sprintf(
						__(
							'No "%s" description found for this product.',
							'peaches'
						),
						descriptionTypes.find(
							( t ) => t.value === descriptionType
						)?.label || descriptionType
					) }
				</Notice>
			);
		}

		const title = getDisplayTitle();
		const content = descriptionData.content || '';

		return (
			<div className="product-description">
				{ displayTitle && title && (
					<h3 className="product-description-title">{ title }</h3>
				) }
				<div
					className="product-description-content"
					dangerouslySetInnerHTML={ { __html: content } }
				/>
			</div>
		);
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Description Settings', 'peaches' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Description Type', 'peaches' ) }
						value={ descriptionType }
						options={ descriptionTypes }
						onChange={ ( value ) =>
							setAttributes( { descriptionType: value } )
						}
						help={ __(
							'Select which type of description to display.',
							'peaches'
						) }
					/>

					<ToggleControl
						label={ __( 'Display Title', 'peaches' ) }
						checked={ displayTitle }
						onChange={ ( value ) =>
							setAttributes( { displayTitle: value } )
						}
						help={ __(
							'Show or hide the description title.',
							'peaches'
						) }
					/>

					{ displayTitle && (
						<TextControl
							label={ __( 'Custom Title', 'peaches' ) }
							value={ customTitle }
							onChange={ ( value ) =>
								setAttributes( { customTitle: value } )
							}
							help={ __(
								'Override the default title. Leave empty to use the description title or type name.',
								'peaches'
							) }
							placeholder={ getDisplayTitle() }
						/>
					) }
				</PanelBody>

				<BootstrapSettingsPanels
					attributes={ attributes }
					setAttributes={ setAttributes }
					supportedSettings={ SUPPORTED_SETTINGS }
				/>
			</InspectorControls>

			<div { ...blockProps }>{ renderPreviewContent() }</div>
		</>
	);
}

export default ProductDescriptionEdit;
