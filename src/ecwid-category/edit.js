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

	// Fetch categories data when component mounts
	useEffect( () => {
		setIsLoading( true );

		// Use WordPress AJAX to fetch categories data from server
		window.jQuery.ajax( {
			url: window.ajaxurl || '/wp-admin/admin-ajax.php',
			method: 'POST',
			dataType: 'json',
			data: {
				action: 'get_ecwid_categories',
				_ajax_nonce: window.EcwidGutenbergParams?.nonce || '',
				nonce: window.EcwidGutenbergParams?.nonce || '',
				security: window.EcwidGutenbergParams?.nonce || '',
			},
			success: function ( response ) {
				setIsLoading( false );
				if ( response && response.success && response.data ) {
					setCategories( response.data );
				} else {
					console.error( 'Categories not found:', response );
				}
			},
			error: function ( xhr, status, error ) {
				setIsLoading( false );
				console.error( 'AJAX Error:', {
					status: status,
					error: error,
					responseText: xhr.responseText,
					statusCode: xhr.status,
				} );
			},
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
									'Loading categories...',
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

				{ ! isLoading && categories.length > 0 && (
					<>
						{ categories.map( ( category ) => (
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
									<div className="card-body p-2 p-md-3">
										<h5 className="card-title">
											{ category.name }
										</h5>
									</div>
								</div>
							</div>
						) ) }
					</>
				) }
			</div>
		</>
	);
}

export default CategoryEdit;
