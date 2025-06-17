/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { useMemo, useState, useEffect } from '@wordpress/element';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import {
	BootstrapSettingsPanels,
	computeClassName,
} from '../../../peaches-bootstrap-blocks/src/utils/bootstrap_settings';

const SUPPORTED_SETTINGS = {
	responsive: {
		sizes: {
			rowCols: true,
		},
		display: {
			opacity: true,
			display: true,
		},
		placements: {
			textAlign: true,
			justifyContent: true,
			alginItems: true,
		},
		spacings: {
			margin: true,
			padding: true,
			gutter: true,
		},
	},
};

function CategoryEdit( props ) {
	const { attributes, setAttributes } = props;
	const [ categories, setCategories ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( false );

	const className = useMemo(
		() => clsx( 'row', computeClassName( attributes ), {} ),
		[ attributes ]
	);

	const blockProps = useBlockProps( {
		className,
		'data-wp-interactive': 'peaches-ecwid-category',
	} );

	// Fetch categories data when component mounts - using REST API instead of AJAX
	useEffect( () => {
		setIsLoading( true );

		// Use REST API directly - works in both editor and frontend
		fetch( '/wp-json/peaches/v1/categories', {
			headers: {
				'X-WP-Nonce': wpApiSettings?.nonce || '',
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
			.then( ( responseData ) => {
				setIsLoading( false );
				if (
					responseData &&
					responseData.success &&
					responseData.data
				) {
					setCategories( responseData.data );
				} else {
					console.error( 'Categories not found:', responseData );
				}
			} )
			.catch( ( error ) => {
				setIsLoading( false );
				console.error( 'REST API Error:', {
					error: error.message,
				} );
			} );
	}, [] );

	return (
		<>
			<InspectorControls>
				<BootstrapSettingsPanels
					setAttributes={ setAttributes }
					attributes={ attributes }
					supportedSettings={ SUPPORTED_SETTINGS }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				{ isLoading && (
					<div className="text-center my-5">
						<div
							className="spinner-border text-primary"
							role="status"
						>
							<span className="visually-hidden">
								{ __(
									'Loading categories…',
									'ecwid-shopping-cart'
								) }
							</span>
						</div>
					</div>
				) }

				{ ! isLoading && categories.length === 0 && (
					<div className="alert alert-info">
						{ __(
							'No categories found or unable to load categories.',
							'ecwid-shopping-cart'
						) }
					</div>
				) }

				{ ! isLoading &&
					categories.length > 0 &&
					categories.map( ( category ) => (
						<div key={ category.id } className="col">
							<div className="card h-100 border-0">
								<div className="ratio ratio-1x1">
									{ category.thumbnailUrl ? (
										<img
											className="card-img-top"
											src={ category.thumbnailUrl }
											alt={ category.name }
										/>
									) : (
										<div className="card-img-top bg-light d-flex align-items-center justify-content-center">
											<span className="text-muted">
												{ __(
													'Category Image',
													'ecwid-shopping-cart'
												) }
											</span>
										</div>
									) }
								</div>
								<div className="card-body p-2 p-md-3 text-center">
									<h5 className="card-title">
										{ category.name }
									</h5>
								</div>
							</div>
						</div>
					) ) }
			</div>
		</>
	);
}

export default CategoryEdit;
