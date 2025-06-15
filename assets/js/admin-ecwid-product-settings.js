/**
 * Admin script for Ecwid Product Settings page
 *
 * Handles AJAX loading, searching, sorting, and lazy loading of Ecwid products
 * with the ability to create product settings posts on demand.
 * This is separate from admin-product-settings.js which handles individual post editing.
 *
 * @param $
 * @package
 * @since   0.2.3
 */

( function ( $ ) {
	$( document ).ready( function () {
		// Check if this is the Ecwid product settings admin page
		if (
			typeof PeachesEcwidProductSettings !== 'undefined' &&
			typeof pagenow !== 'undefined' &&
			pagenow === PeachesEcwidProductSettings.pageNow
		) {
			initializeProductsTable();
		}
	} );

	/**
	 * Initialize the enhanced products table
	 *
	 * Sets up search, sorting, lazy loading, and create post functionality.
	 */
	function initializeProductsTable() {
		const state = {
			offset: 0,
			limit: 20,
			sortBy: 'name',
			sortOrder: 'ASC',
			search: '',
			statusFilter: 'all',
			loading: false,
			hasMore: true,
			total: 0,
			loadedProducts: [],
		};

		const elements = {
			$tableBody: $( '#products-table-body' ),
			$loadingIndicator: $( '#products-loading' ),
			$noProducts: $( '#no-products' ),
			$loadMoreContainer: $( '#load-more-container' ),
			$loadMoreBtn: $( '#load-more-products' ),
			$searchInput: $( '#product-search-input' ),
			$statusFilter: $( '#status-filter' ),
			$countInfo: $( '#products-count-info' ),
			$sortableHeaders: $( '.sortable' ),
		};

		let searchTimeout;

		/**
		 * Load products from the API
		 *
		 * @param {boolean} append - Whether to append to existing results or replace
		 */
		function loadProducts( append = false ) {
			if ( state.loading ) {
				return;
			}

			state.loading = true;
			elements.$loadingIndicator.removeClass( 'd-none' );
			elements.$loadMoreBtn
				.find( '.load-more-spinner' )
				.removeClass( 'd-none' );
			elements.$loadMoreBtn.prop( 'disabled', true );

			const requestData = {
				action: 'get_ecwid_products_list',
				nonce: $( '#products-nonce' ).val(),
				offset: append ? state.offset : 0,
				limit: state.limit,
				sortBy: state.sortBy,
				sortOrder: state.sortOrder,
				search: state.search,
			};

			$.post( $( '#ajax-url' ).val(), requestData )
				.done( function ( response ) {
					if ( response.success && response.data ) {
						const data = response.data;

						if ( ! append ) {
							state.loadedProducts = [];
							elements.$tableBody.empty();
							state.offset = 0;
						}

						state.total = data.total;
						state.hasMore = data.hasMore;
						state.offset += data.count;

						// Filter products based on status filter
						const filteredProducts = filterProductsByStatus(
							data.products
						);
						state.loadedProducts = append
							? state.loadedProducts.concat( filteredProducts )
							: filteredProducts;

						renderProducts( filteredProducts, append );
						updateLoadMoreButton();
						updateCountInfo();

						// Show/hide no results message
						if (
							state.loadedProducts.length === 0 &&
							! state.hasMore
						) {
							// Check if this is a search with no results
							if ( state.search && state.search.trim() !== '' ) {
								showNoSearchResults( state.search );
							} else {
								elements.$noProducts.removeClass( 'd-none' );
							}
							elements.$tableBody.parent().addClass( 'd-none' );
						} else {
							elements.$noProducts.addClass( 'd-none' );
							hideNoSearchResults();
							elements.$tableBody
								.parent()
								.removeClass( 'd-none' );
						}
					} else {
						showError(
							response.data?.message || 'Failed to load products'
						);
					}
				} )
				.fail( function ( xhr, status, error ) {
					showError(
						'Network error occurred while loading products'
					);
				} )
				.always( function () {
					state.loading = false;
					elements.$loadingIndicator.addClass( 'd-none' );
					elements.$loadMoreBtn
						.find( '.load-more-spinner' )
						.addClass( 'd-none' );
					elements.$loadMoreBtn.prop( 'disabled', false );
				} );
		}

		/**
		 * Filter products based on status filter
		 *
		 * @param {Array} products - Array of products to filter
		 *
		 * @return {Array} Filtered products
		 */
		function filterProductsByStatus( products ) {
			if ( state.statusFilter === 'all' ) {
				return products;
			}

			return products.filter( function ( product ) {
				if ( state.statusFilter === 'with_posts' ) {
					return product.hasPost;
				} else if ( state.statusFilter === 'without_posts' ) {
					return ! product.hasPost;
				}
				return true;
			} );
		}

		/**
		 * Render products in the table
		 *
		 * @param {Array}   products - Products to render
		 * @param {boolean} append   - Whether to append or replace
		 */
		function renderProducts( products, append = false ) {
			const rows = products.map( function ( product ) {
				return createProductRow( product );
			} );

			if ( append ) {
				elements.$tableBody.append( rows.join( '' ) );
			} else {
				elements.$tableBody.html( rows.join( '' ) );
			}

			// Reinitialize event handlers for new rows
			bindProductRowEvents();
		}

		/**
		 * Create HTML for a product table row
		 *
		 * @param {Object} product - Product data
		 *
		 * @return {string} HTML string for the row
		 */
		function createProductRow( product ) {
			const thumbnailHtml = product.thumbnailUrl
				? `<img src="${ escapeHtml(
						product.thumbnailUrl
				  ) }" alt="Product thumbnail" class="product-thumbnail me-2">`
				: `<div class="product-thumbnail me-2 bg-light d-flex align-items-center justify-content-center">
					<i class="dashicons dashicons-format-image text-muted"></i>
				   </div>`;

			const statusClass = product.hasPost ? 'has-post' : 'no-post';
			const statusText = product.hasPost
				? 'Has Configuration'
				: 'No Configuration';

			const actionButtons = product.hasPost
				? `<a href="${ escapeHtml( getEditUrl( product.postId ) ) }"
					  class="btn btn-sm btn-outline-primary me-1"
					  title="Edit Product Configuration">
					  <i class="dashicons dashicons-edit"></i>
				   </a>
				   <button type="button"
						  class="btn btn-sm btn-outline-danger btn-delete-post"
						  data-product-id="${ escapeHtml( String( product.id ) ) }"
						  data-product-name="${ escapeHtml( product.name ) }"
						  data-post-id="${ escapeHtml( String( product.postId ) ) }"
						  title="Delete Product Configuration">
						  <i class="dashicons dashicons-trash"></i>
				   </button>`
				: `<button type="button"
						  class="btn btn-sm btn-success btn-create-post"
						  data-product-id="${ escapeHtml( String( product.id ) ) }"
						  data-product-name="${ escapeHtml( product.name ) }"
						  data-product-sku="${ escapeHtml( product.sku || '' ) }"
						  title="Create Product Configuration">
						  <i class="dashicons dashicons-plus-alt2"></i>
						  Create
				   </button>`;

			const priceDisplay =
				product.price !== null && product.price !== undefined
					? `€${ parseFloat( product.price ).toFixed( 2 ) }`
					: '—';

			const productId = String( product.id || '' );

			// Components display - show counts if post exists
			let componentsHtml = '<span class="text-muted">—</span>';
			if ( product.hasPost && product.components ) {
				const components = product.components;
				componentsHtml = `
					<div class="d-flex justify-content-center gap-1">
						<span class="badge bg-success" title="Ingredients">${ components.ingredients } I</span>
						<span class="badge bg-info" title="Media">${ components.media } M</span>
						<span class="badge bg-warning text-dark" title="Lines">${ components.lines } L</span>
						<span class="badge bg-secondary" title="Tags">${ components.tags } T</span>
					</div>
				`;
			}

			return `
				<tr data-product-id="${ escapeHtml( productId ) }">
					<td>
						<div class="d-flex align-items-center">
							${ thumbnailHtml }
							<div>
								<h6 class="mb-1">${ escapeHtml( product.name ) }</h6>
								${
									product.enabled
										? ''
										: '<span class="badge bg-warning text-dark">Disabled</span>'
								}
							</div>
						</div>
					</td>
					<td class="text-center">
						<span class="badge bg-primary">${ productId }</span>
					</td>
					<td class="text-center">
						${
							product.sku
								? `<span class="badge bg-secondary">${ escapeHtml(
										product.sku
								  ) }</span>`
								: '<span class="text-muted">—</span>'
						}
					</td>
					<td class="text-center">
						${ priceDisplay }
					</td>
					<td class="text-center">
						${ componentsHtml }
					</td>
					<td class="text-center">
						<span class="status-indicator ${ statusClass }"></span>
						<small class="text-muted">${ statusText }</small>
					</td>
					<td class="text-end">
						${ actionButtons }
					</td>
				</tr>
			`;
		}

		/**
		 * Bind event handlers to product row elements
		 */
		function bindProductRowEvents() {
			// Create post button handler
			$( '.btn-create-post' )
				.off( 'click' )
				.on( 'click', function ( e ) {
					e.preventDefault();

					const $btn = $( this );
					const productId = $btn.data( 'product-id' );
					const productName = $btn.data( 'product-name' );
					const productSku = $btn.data( 'product-sku' );

					createProductPost(
						productId,
						productName,
						productSku,
						$btn
					);
				} );

			// Delete post button handler
			$( '.btn-delete-post' )
				.off( 'click' )
				.on( 'click', function ( e ) {
					e.preventDefault();

					const $btn = $( this );
					const productId = $btn.data( 'product-id' );
					const productName = $btn.data( 'product-name' );
					const postId = $btn.data( 'post-id' );

					deleteProductPost( productId, productName, postId, $btn );
				} );
		}

		/**
		 * Update tab badge counter
		 *
		 * @param {string} tabId  - Tab identifier (product_settings, ingredients_library, etc.)
		 * @param {number} change - Number to add/subtract from current count (+1 or -1)
		 */
		function updateTabBadge( tabId, change ) {
			let targetSelector;

			switch ( tabId ) {
				case 'product_settings':
					targetSelector = '#product-settings-tab .badge';
					break;
				case 'ingredients_library':
					targetSelector = '#ingredients-library-tab .badge';
					break;
				case 'media_tags':
					targetSelector = '#media-tags-tab .badge';
					break;
				case 'product_lines':
					targetSelector = '#product-lines-tab .badge';
					break;
				default:
					return;
			}

			const $badge = $( targetSelector );

			if ( $badge.length > 0 ) {
				// Get current count and update it
				const currentCount = parseInt( $badge.text() ) || 0;
				const newCount = Math.max( 0, currentCount + change );

				if ( newCount > 0 ) {
					$badge.text( newCount ).show();
				} else {
					$badge.hide();
				}
			} else if ( change > 0 ) {
				// Create badge if it doesn't exist and we're adding
				const $tab = $( targetSelector.replace( ' .badge', '' ) );
				if ( $tab.length > 0 ) {
					$tab.append(
						` <span class="badge bg-secondary ms-1">${ change }</span>`
					);
				}
			}
		}

		/**
		 * Create a new product settings post
		 *
		 * @param {number} productId   - Ecwid product ID
		 * @param {string} productName - Product name
		 * @param {string} productSku  - Product SKU
		 * @param {jQuery} $btn        - Button element
		 */
		function createProductPost( productId, productName, productSku, $btn ) {
			// Show loading state
			const originalHtml = $btn.html();
			$btn.prop( 'disabled', true ).html(
				'<span class="spinner-border spinner-border-sm me-1"></span>Creating...'
			);

			const requestData = {
				action: 'create_product_post',
				nonce: $( '#create-post-nonce' ).val(),
				productId,
				productName,
				productSku,
			};

			$.post( $( '#ajax-url' ).val(), requestData )
				.done( function ( response ) {
					if ( response.success && response.data ) {
						// Show success message
						showSuccess( response.data.message );

						// Update the row to show edit button instead of create button
						const $row = $btn.closest( 'tr' );
						updateRowAfterPostCreation(
							$row,
							response.data.postId
						);

						// Update tab badge counter (+1 for product settings)
						updateTabBadge( 'product_settings', 1 );

						// Optional: redirect to edit page
						if (
							confirm(
								PeachesEcwidProductSettings.strings.confirmEdit
							)
						) {
							window.location.href = response.data.editUrl;
						}
					} else {
						showError(
							response.data?.message ||
								PeachesEcwidProductSettings.strings.createError
						);
						$btn.prop( 'disabled', false ).html( originalHtml );
					}
				} )
				.fail( function () {
					showError(
						PeachesEcwidProductSettings.strings.networkError
					);
					$btn.prop( 'disabled', false ).html( originalHtml );
				} );
		}

		/**
		 * Delete a product settings post
		 *
		 * @param {number} productId   - Ecwid product ID
		 * @param {string} productName - Product name
		 * @param {number} postId      - WordPress post ID
		 * @param {jQuery} $btn        - Button element
		 */
		function deleteProductPost( productId, productName, postId, $btn ) {
			// Show confirmation dialog
			const confirmMessage = `Are you sure you want to delete the configuration for "${ productName }"?\n\nThis action cannot be undone.`;

			if ( ! confirm( confirmMessage ) ) {
				return;
			}

			// Show loading state
			const originalHtml = $btn.html();
			$btn.prop( 'disabled', true ).html(
				'<span class="spinner-border spinner-border-sm me-1"></span>Deleting...'
			);

			const requestData = {
				action: 'delete_product_post',
				nonce: $( '#delete-post-nonce' ).val(),
				productId,
				postId,
			};

			$.post( $( '#ajax-url' ).val(), requestData )
				.done( function ( response ) {
					if ( response.success ) {
						// Show success message
						showSuccess(
							response.data?.message ||
								'Product configuration deleted successfully.'
						);

						// Update the row to show create button instead of edit/delete buttons
						const $row = $btn.closest( 'tr' );
						updateRowAfterPostDeletion(
							$row,
							productId,
							productName
						);

						// Update tab badge counter (-1 for product settings)
						updateTabBadge( 'product_settings', -1 );
					} else {
						showError(
							response.data?.message ||
								'Failed to delete product configuration.'
						);
						$btn.prop( 'disabled', false ).html( originalHtml );
					}
				} )
				.fail( function () {
					showError(
						'Network error occurred while deleting product configuration.'
					);
					$btn.prop( 'disabled', false ).html( originalHtml );
				} );
		}

		/**
		 * Update table row after post creation
		 *
		 * @param {jQuery} $row   - Table row
		 * @param {number} postId - Created post ID
		 */
		function updateRowAfterPostCreation( $row, postId ) {
			const $componentsCell = $row.find( 'td:nth-child(5)' );
			const $statusCell = $row.find( 'td:nth-child(6)' );
			const $actionsCell = $row.find( 'td:nth-child(7)' );
			const productId = $row.data( 'product-id' );
			const productName = $row.find( 'h6' ).text();

			// Update components (initially empty for new posts)
			$componentsCell.html( `
				<div class="d-flex justify-content-center gap-1">
					<span class="badge bg-success" title="Ingredients">0 I</span>
					<span class="badge bg-info" title="Media">0 M</span>
					<span class="badge bg-warning text-dark" title="Lines">0 L</span>
					<span class="badge bg-secondary" title="Tags">0 T</span>
				</div>
			` );

			// Update status
			$statusCell.html( `
				<span class="status-indicator has-post"></span>
				<small class="text-muted">Has Configuration</small>
			` );

			// Update actions
			$actionsCell.html( `
				<a href="${ escapeHtml( getEditUrl( postId ) ) }"
				   class="btn btn-sm btn-outline-primary me-1"
				   title="Edit Product Configuration">
				   <i class="dashicons dashicons-edit"></i>
				</a>
				<button type="button"
					   class="btn btn-sm btn-outline-danger btn-delete-post"
					   data-product-id="${ escapeHtml( String( productId ) ) }"
					   data-product-name="${ escapeHtml( productName ) }"
					   data-post-id="${ escapeHtml( String( postId ) ) }"
					   title="Delete Product Configuration">
					   <i class="dashicons dashicons-trash"></i>
				</button>
			` );

			// Rebind events for the new buttons
			bindProductRowEvents();
		}

		/**
		 * Update table row after post deletion
		 *
		 * @param {jQuery} $row        - Table row
		 * @param {number} productId   - Ecwid product ID
		 * @param {string} productName - Product name
		 */
		function updateRowAfterPostDeletion( $row, productId, productName ) {
			const $componentsCell = $row.find( 'td:nth-child(5)' );
			const $statusCell = $row.find( 'td:nth-child(6)' );
			const $actionsCell = $row.find( 'td:nth-child(7)' );

			// Update components (no components for deleted posts)
			$componentsCell.html( '<span class="text-muted">—</span>' );

			// Update status
			$statusCell.html( `
				<span class="status-indicator no-post"></span>
				<small class="text-muted">No Configuration</small>
			` );

			// Update actions
			$actionsCell.html( `
				<button type="button"
					   class="btn btn-sm btn-success btn-create-post"
					   data-product-id="${ escapeHtml( String( productId ) ) }"
					   data-product-name="${ escapeHtml( productName ) }"
					   data-product-sku=""
					   title="Create Product Configuration">
					   <i class="dashicons dashicons-plus-alt2"></i>
					   Create
				</button>
			` );

			// Rebind events for the new button
			bindProductRowEvents();
		}

		/**
		 * Update the load more button state
		 */
		function updateLoadMoreButton() {
			if ( state.hasMore && state.loadedProducts.length > 0 ) {
				elements.$loadMoreContainer.show();
			} else {
				elements.$loadMoreContainer.hide();
			}
		}

		/**
		 * Update the products count information
		 */
		function updateCountInfo() {
			if ( state.total > 0 ) {
				const showing = state.loadedProducts.length;
				elements.$countInfo.text(
					`Showing ${ showing } of ${ state.total } products`
				);
			} else {
				elements.$countInfo.text( '' );
			}
		}

		/**
		 * Handle search input changes
		 */
		elements.$searchInput.on( 'input', function () {
			clearTimeout( searchTimeout );
			const query = $( this ).val().trim();

			searchTimeout = setTimeout( function () {
				if ( state.search !== query ) {
					state.search = query;
					state.offset = 0;
					loadProducts( false );
				}
			}, 300 );
		} );

		/**
		 * Handle status filter changes
		 */
		elements.$statusFilter.on( 'change', function () {
			const newFilter = $( this ).val();
			if ( state.statusFilter !== newFilter ) {
				state.statusFilter = newFilter;
				state.offset = 0;
				loadProducts( false );
			}
		} );

		/**
		 * Handle sortable header clicks
		 */
		elements.$sortableHeaders.on( 'click', function () {
			const $this = $( this );
			const sortBy = $this.data( 'sort' );

			// Update sort order
			if ( state.sortBy === sortBy ) {
				state.sortOrder = state.sortOrder === 'ASC' ? 'DESC' : 'ASC';
			} else {
				state.sortBy = sortBy;
				state.sortOrder = 'ASC';
			}

			// Update visual indicators
			elements.$sortableHeaders.removeClass( 'sort-asc sort-desc' );
			$this.addClass(
				state.sortOrder === 'ASC' ? 'sort-asc' : 'sort-desc'
			);

			// Reload products
			state.offset = 0;
			loadProducts( false );
		} );

		/**
		 * Handle load more button click
		 */
		elements.$loadMoreBtn.on( 'click', function () {
			loadProducts( true );
		} );

		/**
		 * Utility function to escape HTML
		 *
		 * @param {string} unsafe - Unsafe string
		 *
		 * @return {string} Escaped string
		 */
		function escapeHtml( unsafe ) {
			const str = String( unsafe || '' );
			return str
				.replace( /&/g, '&amp;' )
				.replace( /</g, '&lt;' )
				.replace( />/g, '&gt;' )
				.replace( /"/g, '&quot;' )
				.replace( /'/g, '&#039;' );
		}

		/**
		 * Get edit URL for a post
		 *
		 * @param {number} postId - Post ID
		 *
		 * @return {string} Edit URL
		 */
		function getEditUrl( postId ) {
			return `${ window.location.origin }/wp-admin/post.php?post=${ postId }&action=edit`;
		}

		/**
		 * Show no search results message
		 *
		 * @param {string} searchTerm - The search term that found no results
		 */
		function showNoSearchResults( searchTerm ) {
			hideNoSearchResults(); // Remove any existing message

			const $searchMessage = $( `
				<div id="no-search-results" class="alert alert-info mt-3" role="alert">
					<i class="dashicons dashicons-search"></i>
					<strong>No products found</strong> for "${ escapeHtml( searchTerm ) }"
					<br><small class="text-muted">Try a different search term or <a href="#" id="clear-search-link">clear the search</a> to see all products.</small>
				</div>
			` );

			elements.$tableBody.parent().before( $searchMessage );

			// Handle clear search link
			$( '#clear-search-link' ).on( 'click', function ( e ) {
				e.preventDefault();
				elements.$searchInput.val( '' ).trigger( 'input' );
			} );
		}

		/**
		 * Hide no search results message
		 */
		function hideNoSearchResults() {
			$( '#no-search-results' ).remove();
		}

		/**
		 * Show success message
		 *
		 * @param {string} message - Success message
		 */
		function showSuccess( message ) {
			const $notice = $( `
				<div class="notice notice-success is-dismissible">
					<p>${ escapeHtml( message ) }</p>
					<button type="button" class="notice-dismiss">
						<span class="screen-reader-text">Dismiss this notice.</span>
					</button>
				</div>
			` );

			$( 'h1.wp-heading-inline' ).after( $notice );

			// Auto-dismiss after 5 seconds
			setTimeout( function () {
				$notice.fadeOut();
			}, 5000 );
		}

		/**
		 * Show error message
		 *
		 * @param {string} message - Error message
		 */
		function showError( message ) {
			const $notice = $( `
				<div class="notice notice-error is-dismissible">
					<p>${ escapeHtml( message ) }</p>
					<button type="button" class="notice-dismiss">
						<span class="screen-reader-text">Dismiss this notice.</span>
					</button>
				</div>
			` );

			$( 'h1.wp-heading-inline' ).after( $notice );

			// Auto-dismiss after 8 seconds
			setTimeout( function () {
				$notice.fadeOut();
			}, 8000 );
		}

		// Initialize the table on page load
		loadProducts( false );
	}
} )( jQuery );
