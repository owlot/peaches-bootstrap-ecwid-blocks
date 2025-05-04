import { store, getContext } from '@wordpress/interactivity';

// Access the parent product detail store
const productDetailStore = store('peaches-ecwid-product-detail');

const { state } = store('peaches-ecwid-add-to-cart', {
    actions: {
        increaseAmount(e) {
            const context = getContext();
            context.amount = parseInt(context.amount) + 1;
        },
        decreaseAmount(e) {
            const context = getContext();
            if (context.amount > 1) {
                context.amount = parseInt(context.amount) - 1;
            }
        },
        setAmount(e) {
            const context = getContext();
            const value = parseInt(e.target.value);
            context.amount = value > 0 ? value : 1;
        },
        addToCart(e) {
            const context = getContext();
            // Get the product ID from the parent store
            const productId = productDetailStore.state.productId;

            if (productId) {
                Ecwid.Cart.addProduct({
                    id: productId,
                    quantity: parseInt(context.amount)
                });
            } else {
                console.error('Product ID not found');
            }
        },
    },
});
