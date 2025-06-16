import { store, getContext, getElement } from '@wordpress/interactivity';

// Access the parent product detail store
const productDetailStore = store( 'peaches-ecwid-product-detail' );

const { state, actions } = store( 'peaches-ecwid-product-description', {
	state: {
		get productId() {
			return productDetailStore.state.productId;
		},
		get productData() {
			return productDetailStore.state.productData;
		},
	},

	actions: {
		*toggleCollapse() {
			const context = getContext();
			context.isCollapsed = !context.isCollapsed;
		},
	},

	callbacks: {
		*initProductDescription() {
			const context = getContext();
			const element = getElement();

			// Use product ID from context or fallback to block attribute
			const productId = state.productId || context.productId;

			if ( ! productId ) {
				console.error( 'Product ID not available for description block' );
				context.isLoading = false;
				context.hasError = true;
				context.errorMessage = wp.i18n.__( 'Product ID not available', 'peaches' );
				return;
			}

			context.isLoading = true;
			context.hasError = false;

			try {
				// Fetch descriptions from the server
				const response = yield fetch( window.EcwidSettings?.ajaxUrl || ajaxurl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams({
						action: 'get_ecwid_product_descriptions',
						product_id: productId,
						nonce: window.EcwidSettings?.ajaxNonce || ''
					})
				});

				const data = yield response.json();

				if ( data.success && data.data ) {
					const descriptions = data.data;

					// Find the description for the specified type
					const description = descriptions.find( desc => desc.type === context.descriptionType );

					if ( description ) {
						context.description = description;

						// Set the display title
						context.displayTitle = context.customTitle || description.title || '';

						context.isLoading = false;
						context.hasError = false;
					} else {
						// No description found for this type
						context.description = null;
						context.isLoading = false;
						context.hasError = false;
					}
				} else {
					console.error( 'Failed to load product descriptions:', data );
					context.isLoading = false;
					context.hasError = true;
					context.errorMessage = data.data || wp.i18n.__( 'Failed to load description', 'peaches' );
				}
			} catch ( error ) {
				console.error( 'Error loading product descriptions:', error );
				context.isLoading = false;
				context.hasError = true;
				context.errorMessage = wp.i18n.__( 'Error loading description', 'peaches' );
			}
		},

		*setDescriptionContent() {
			const context = getContext();
			const element = getElement();

			if ( context.description && context.description.content && element.ref ) {
				// Set the HTML content for the description
				element.ref.innerHTML = context.description.content;
			}
		},
	},
} );
