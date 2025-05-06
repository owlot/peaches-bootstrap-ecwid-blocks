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
				const ajaxUrl =
					window.EcwidSettings?.ajaxUrl || '/wp-admin/admin-ajax.php';
				const response = yield fetch( ajaxUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams( {
						action: 'get_ecwid_categories',
						nonce: window.EcwidSettings?.ajaxNonce || '',
					} ),
				} );

				const data = yield response.json();

				if ( data && data.success && data.data ) {
					// Map categories with URLs
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
							( data?.data || 'Unknown error' )
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
