import * as __WEBPACK_EXTERNAL_MODULE__wordpress_interactivity_8e89b257__ from "@wordpress/interactivity";
/******/ var __webpack_modules__ = ({

/***/ "@wordpress/interactivity":
/*!*******************************************!*\
  !*** external "@wordpress/interactivity" ***!
  \*******************************************/
/***/ ((module) => {

module.exports = __WEBPACK_EXTERNAL_MODULE__wordpress_interactivity_8e89b257__;

/***/ })

/******/ });
/************************************************************************/
/******/ // The module cache
/******/ var __webpack_module_cache__ = {};
/******/ 
/******/ // The require function
/******/ function __webpack_require__(moduleId) {
/******/ 	// Check if module is in cache
/******/ 	var cachedModule = __webpack_module_cache__[moduleId];
/******/ 	if (cachedModule !== undefined) {
/******/ 		return cachedModule.exports;
/******/ 	}
/******/ 	// Create a new module (and put it into the cache)
/******/ 	var module = __webpack_module_cache__[moduleId] = {
/******/ 		// no module.id needed
/******/ 		// no module.loaded needed
/******/ 		exports: {}
/******/ 	};
/******/ 
/******/ 	// Execute the module function
/******/ 	__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 
/******/ 	// Return the exports of the module
/******/ 	return module.exports;
/******/ }
/******/ 
/************************************************************************/
/******/ /* webpack/runtime/make namespace object */
/******/ (() => {
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = (exports) => {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/ })();
/******/ 
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!******************************************!*\
  !*** ./src/ecwid-product-images/view.js ***!
  \******************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/interactivity */ "@wordpress/interactivity");


// Access the parent product detail store
const productDetailStore = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.store)('peaches-ecwid-product-detail');

// Store definition with proper lifecycle methods
const {
  state,
  actions
} = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.store)('peaches-ecwid-product-images', {
  state: {
    get productId() {
      return productDetailStore.state.productId;
    },
    get productData() {
      return productDetailStore.state.productData;
    },
    productName: '',
    currentImage: '',
    galleryImages: [],
    processProductData() {
      try {
        const product = state.productData;
        if (!product) {
          console.error('Product data not found');
          return;
        }

        // Set the product data
        state.productName = product.name;

        // Process images based on image size setting
        const element = document.querySelector('[data-wp-interactive="peaches-ecwid-product-images"]');
        const imageSize = element.getAttribute('data-image-size') || 'medium';

        // Updated size mapping to match the actual properties in the product media object
        const sizeMapping = {
          small: 'image160pxUrl',
          medium: 'image400pxUrl',
          large: 'image800pxUrl',
          xlarge: 'image1500pxUrl',
          original: 'imageOriginalUrl'
        };
        const imageSizeKey = sizeMapping[imageSize] || 'image400pxUrl'; // Default to medium

        // Handle media.images first (newer API structure)
        if (product.media && product.media.images && product.media.images.length > 0) {
          // Set the main image using the correct URL property
          const mainImage = product.media.images[0];
          state.currentImage = mainImage[imageSizeKey] || mainImage.image400pxUrl;

          // Set gallery images with the correct URL properties
          state.galleryImages = product.media.images.map(image => ({
            thumbnailUrl: image.image160pxUrl,
            // Use the smallest image for thumbnails
            imageUrl: image[imageSizeKey] || image.image400pxUrl,
            isCurrent: image[imageSizeKey] === state.currentImage
          }));
        }
        // Fall back to legacy galleryImages if available
        else if (product.images && product.images.length > 0) {
          // For backward compatibility with older product data structure
          const fallbackSizeMapping = {
            small: 'thumbnailUrl',
            medium: 'imageUrl',
            large: 'largeImageUrl',
            original: 'originalImageUrl'
          };
          const fallbackImageSizeKey = fallbackSizeMapping[imageSize] || 'imageUrl';
          state.currentImage = product.images[0][fallbackImageSizeKey] || product.images[0].imageUrl;
          state.galleryImages = product.images.map(image => ({
            thumbnailUrl: image.thumbnailUrl,
            imageUrl: image[fallbackImageSizeKey] || image.imageUrl,
            isCurrent: (image[fallbackImageSizeKey] || image.imageUrl) === state.currentImage
          }));
        }
        // Last resort, use the main product image
        else if (product.imageUrl) {
          state.currentImage = product.imageUrl;
          state.galleryImages = [];
        } else {
          console.error('No images found for product');
          state.currentImage = ''; // Set to empty or a placeholder image URL
          state.galleryImages = [];
        }

        // Debug output to verify the image URLs
        console.log('Current image set to:', state.currentImage);
        console.log('Gallery images:', state.galleryImages);
      } catch (error) {
        console.error('Error processing product data:', error);
      }
    }
  },
  actions: {
    setCurrentImage(event) {
      const imageUrl = event.target.getAttribute('data-imageUrl');
      if (imageUrl) {
        state.currentImage = imageUrl;
        state.galleryImages.forEach(image => {
          image.isCurrent = image.imageUrl === imageUrl;
        });
      }
    }
  },
  // The callbacks section for lifecycle methods
  callbacks: {
    // This will be automatically called when the component is mounted via data-wp-init
    initProductImages: function* () {
      if (!state.productId || !state.productData) {
        console.error('Product data not available');
        return;
      }
      try {
        // Process product data that's already available from parent block
        state.processProductData();
      } catch (error) {
        console.error('Error processing product images:', error);
      }
    }
  }
});
})();


//# sourceMappingURL=view.js.map