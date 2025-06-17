import { store, getContext } from '@wordpress/interactivity';

const { state } = store( 'peaches-ecwid-category', {
	state: {
		get categories() {
			const context = getContext();
			return context.categories;
		},
		get isLoading() {
			const context = getContext();
			return context.isLoading;
		},
	},

	actions: {
		*navigateToCategory() {
			const context = getContext();
			const category = context.category;
			if ( category && category.url ) {
				window.location.href = category.url;
			}
		},
	},

	callbacks: {
		*initCategories() {
			const context = getContext();

			context.isLoading = true;

			try {
				// Use REST API instead of AJAX
				const response = yield fetch(
					'/wp-json/peaches/v1/categories',
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

				const data = yield response.json();

				if ( data && data.success && data.data ) {
					// Map categories with URLs (preserve original logic)
					const storePageId = window.EcwidSettings?.storePageId;
					const storeUrl = storePageId
						? window.EcwidSettings.storeUrl
						: window.location.origin;

					context.categories = data.data.map( ( category ) => ( {
						id: category.id,
						name: category.name,
						thumbnailUrl: category.thumbnailUrl,
						url: storeUrl + '?category=' + category.id,
					} ) );

					context.isLoading = false;
				} else {
					throw new Error(
						'Failed to fetch categories: ' +
							( data?.message || 'Invalid response format' )
					);
				}
			} catch ( error ) {
				console.error( 'Error loading categories:', error );
				context.isLoading = false;
				context.categories = [];
			}
		},
	},
} );
