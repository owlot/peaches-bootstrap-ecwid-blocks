import { store, getContext } from '@wordpress/interactivity';

// Access the parent product detail store
const productDetailStore = store('peaches-ecwid-product-detail');

const { state, actions } = store('peaches-ecwid-product-ingredients', {
    state: {
        get productId() {
            return productDetailStore.state.productId;
        },
        get productData() {
            return productDetailStore.state.productData;
        }
    },

    actions: {
        toggleAccordion() {
            const context = getContext();
            context.ingredient.isCollapsed = !context.ingredient.isCollapsed;
        }
    },

    callbacks: {
        initProductIngredients: function*() {
            const context = getContext();
            const productId = state.productId;

            if (!productId) {
                console.error('Product ID not found');
                context.isLoading = false;
                return;
            }

            try {
                // Fetch ingredients from WordPress API
                const response = yield window.fetch(
                    `/wp-json/peaches/v1/product-ingredients/${productId}`,
                    {
                        headers: {
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin'
                    }
                );

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = yield response.json();

                // Transform ingredients data for the accordion
                if (data && data.ingredients && Array.isArray(data.ingredients)) {
                    context.ingredients = data.ingredients.map((ingredient, index) => ({
                        name: ingredient.name,
                        description: ingredient.description,
                        targetId: `#collapse-${productId}-${index}`,
                        collapseId: `collapse-${productId}-${index}`,
                        headingId: `heading-${productId}-${index}`,
                        isCollapsed: true // Start collapsed by default
                    }));
                } else {
                    context.ingredients = [];
                }

                context.isLoading = false;
            } catch (error) {
                console.error('Error fetching product ingredients:', error);
                context.ingredients = [];
                context.isLoading = false;
            }
        }
    }
});
