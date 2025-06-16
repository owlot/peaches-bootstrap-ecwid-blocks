/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { useMemo, useState, useEffect } from '@wordpress/element';
import {
	PanelBody,
	SelectControl,
	TextControl,
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

const DESCRIPTION_TYPES = [
	{ label: __( 'Product Usage', 'peaches' ), value: 'usage' },
	{ label: __( 'Detailed Ingredients', 'peaches' ), value: 'ingredients' },
	{ label: __( 'Care Instructions', 'peaches' ), value: 'care' },
	{ label: __( 'Warranty Information', 'peaches' ), value: 'warranty' },
	{ label: __( 'Key Features', 'peaches' ), value: 'features' },
	{ label: __( 'Technical Specifications', 'peaches' ), value: 'technical' },
	{ label: __( 'Custom Description', 'peaches' ), value: 'custom' },
];

const TITLE_TAGS = [
	{ label: __( 'Heading 1 (h1)', 'peaches' ), value: 'h1' },
	{ label: __( 'Heading 2 (h2)', 'peaches' ), value: 'h2' },
	{ label: __( 'Heading 3 (h3)', 'peaches' ), value: 'h3' },
	{ label: __( 'Heading 4 (h4)', 'peaches' ), value: 'h4' },
	{ label: __( 'Heading 5 (h5)', 'peaches' ), value: 'h5' },
	{ label: __( 'Heading 6 (h6)', 'peaches' ), value: 'h6' },
	{ label: __( 'Div', 'peaches' ), value: 'div' },
	{ label: __( 'Span', 'peaches' ), value: 'span' },
];

/**
 * Product Description Edit Component
 *
 * Displays additional product descriptions with test data when available from parent context.
 *
 * @param {Object} props - Component props
 *
 * @return {JSX.Element} - Edit component
 */
function ProductDescriptionEdit( props ) {
	const { attributes, setAttributes, context } = props;
	const { descriptionType, showTitle, titleTag, customTitle, collapseInitially, productId } = attributes;

	// Get test product data from parent context
	const testProductData = context?.[ 'peaches/testProductData' ];
	const currentProductId = testProductData?.id || productId;

	// State for managing descriptions data
	const [descriptions, setDescriptions] = useState([]);
	const [loading, setLoading] = useState(false);
	const [error, setError] = useState('');

	const className = useMemo(
		() => computeClassName( attributes ),
		[ attributes ]
	);

	const blockProps = useBlockProps( {
		className,
		'data-wp-interactive': 'peaches-ecwid-product-description',
	} );

	/**
	 * Fetch product descriptions from API
	 */
	const fetchDescriptions = async (id) => {
		if (!id || id <= 0) {
			setDescriptions([]);
			return;
		}

		setLoading(true);
		setError('');

		try {
			const response = await fetch(window.EcwidGutenbergParams?.ajaxUrl || ajaxurl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					action: 'get_ecwid_product_descriptions',
					product_id: id,
					nonce: window.EcwidGutenbergParams?.nonce || ''
				})
			});

			const data = await response.json();

			if (data.success) {
				setDescriptions(data.data || []);
			} else {
				setError(data.data || __('Failed to load descriptions', 'peaches'));
				setDescriptions([]);
			}
		} catch (err) {
			setError(__('Error loading descriptions', 'peaches'));
			setDescriptions([]);
			console.error('Error fetching descriptions:', err);
		} finally {
			setLoading(false);
		}
	};

	/**
	 * Effect to fetch descriptions when product ID changes
	 */
	useEffect(() => {
		if (currentProductId) {
			fetchDescriptions(currentProductId);
		}
	}, [currentProductId]);

	/**
	 * Get current description
	 */
	const currentDescription = descriptions.find(desc => desc.type === descriptionType);

	/**
	 * Get preview content for the selected description type
	 *
	 * @return {string|JSX.Element} - Preview content to display
	 */
	const getPreviewContent = () => {
		if (loading) {
			return (
				<div className="d-flex justify-content-center align-items-center p-4">
					<Spinner />
					<span className="ms-2">{__('Loading descriptions...', 'peaches')}</span>
				</div>
			);
		}

		if (error) {
			return (
				<Notice status="error" isDismissible={false}>
					{error}
				</Notice>
			);
		}

		if (!currentProductId) {
			return (
				<Notice status="warning" isDismissible={false}>
					{__('Please select a product or use this block within a Product Detail Template.', 'peaches')}
				</Notice>
			);
		}

		if (!currentDescription) {
			const selectedType = DESCRIPTION_TYPES.find(type => type.value === descriptionType);
			return (
				<Notice status="info" isDismissible={false}>
					{__('No description found for type:', 'peaches')} <strong>{selectedType?.label}</strong>
					<br />
					<small>{__('Add descriptions in the product settings admin.', 'peaches')}</small>
				</Notice>
			);
		}

		// Determine title to display
		const displayTitle = customTitle || currentDescription.title;

		return (
			<div className={`product-description-preview ${collapseInitially ? 'collapsed-preview' : ''}`}>
				{showTitle && displayTitle && (
					React.createElement(titleTag, {
						className: 'product-description-title'
					}, displayTitle)
				)}
				<div
					className="product-description-content"
					dangerouslySetInnerHTML={{ __html: currentDescription.content }}
				/>
				{collapseInitially && (
					<small className="text-muted mt-2 d-block">
						{__('(Will be collapsible on frontend)', 'peaches')}
					</small>
				)}
			</div>
		);
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Product Description Settings',
						'peaches'
					) }
					initialOpen={true}
				>
					{ testProductData ? (
						<Notice status="success" isDismissible={ false }>
							{ __(
								'Using test product data:',
								'peaches'
							) }{ ' ' }
							<strong>{ testProductData.name }</strong>
						</Notice>
					) : (
						<>
							<Notice status="info" isDismissible={ false }>
								{ __(
									'Using placeholder data. Configure a test product in the parent block to preview real data.',
									'peaches'
								) }
							</Notice>
							<TextControl
								label={__('Product ID', 'peaches')}
								value={productId}
								onChange={(value) => setAttributes({ productId: parseInt(value) || 0 })}
								type="number"
								min="1"
								help={__('Enter the Ecwid product ID to display descriptions for.', 'peaches')}
							/>
						</>
					) }

					<SelectControl
						label={ __( 'Description Type', 'peaches' ) }
						value={ descriptionType }
						options={ DESCRIPTION_TYPES }
						onChange={ ( value ) =>
							setAttributes( { descriptionType: value } )
						}
						help={__('Choose which type of description to display.', 'peaches')}
					/>
				</PanelBody>

				<PanelBody
					title={ __(
						'Display Options',
						'peaches'
					) }
					initialOpen={false}
				>
					<ToggleControl
						label={ __( 'Show Title', 'peaches' ) }
						checked={ showTitle }
						onChange={ ( value ) =>
							setAttributes( { showTitle: value } )
						}
						help={__('Display the description title above the content.', 'peaches')}
					/>

					{ showTitle && (
						<>
							<SelectControl
								label={ __( 'Title HTML Tag', 'peaches' ) }
								value={ titleTag }
								options={ TITLE_TAGS }
								onChange={ ( value ) =>
									setAttributes( { titleTag: value } )
								}
							/>

							<TextControl
								label={ __( 'Custom Title', 'peaches' ) }
								value={ customTitle }
								onChange={ ( value ) =>
									setAttributes( { customTitle: value } )
								}
								help={__('Override the default title. Leave empty to use the title from admin.', 'peaches')}
							/>
						</>
					) }

					<ToggleControl
						label={ __( 'Collapsible Content', 'peaches' ) }
						checked={ collapseInitially }
						onChange={ ( value ) =>
							setAttributes( { collapseInitially: value } )
						}
						help={__('Make the description collapsible with a toggle button.', 'peaches')}
					/>
				</PanelBody>

				{descriptions.length > 0 && (
					<PanelBody
						title={__('Available Descriptions', 'peaches')}
						initialOpen={false}
					>
						<div className="peaches-descriptions-list">
							{descriptions.map((desc, index) => {
								const typeLabel = DESCRIPTION_TYPES.find(type => type.value === desc.type)?.label || desc.type;
								const isSelected = desc.type === descriptionType;
								return (
									<div key={index} className={`description-item mb-2 p-2 border rounded ${isSelected ? 'border-primary bg-light' : ''}`}>
										<div className="d-flex justify-content-between align-items-start">
											<div>
												<strong>{typeLabel}</strong>
												{desc.title && <div className="small text-muted">{desc.title}</div>}
											</div>
											{!isSelected && (
												<button
													type="button"
													className="btn btn-sm btn-outline-primary"
													onClick={() => setAttributes({ descriptionType: desc.type })}
												>
													{__('Select', 'peaches')}
												</button>
											)}
											{isSelected && (
												<span className="badge bg-primary">{__('Selected', 'peaches')}</span>
											)}
										</div>
									</div>
								);
							})}
						</div>
					</PanelBody>
				)}

				<BootstrapSettingsPanels
					setAttributes={ setAttributes }
					attributes={ attributes }
					supportedSettings={ SUPPORTED_SETTINGS }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				{getPreviewContent()}
			</div>
		</>
	);
}

export default ProductDescriptionEdit;
