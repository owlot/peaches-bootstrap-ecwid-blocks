/**
 * Admin script for Product Settings management
 *
 * Handles the dynamic interface for managing product settings including
 * ingredients table view and line assignments.
 * Media management is handled by admin-product-media.js
 *
 * @param $
 * @package
 * @since   0.2.0
 */

( function ( $ ) {
	$( document ).ready( function () {
		// Check if this is the product_settings post type
		if (
			typeof pagenow !== 'undefined' &&
			pagenow !== 'product_settings'
		) {
			return;
		}

		let ingredientRowIndex = $( '.ingredient-row' ).length;

		/**
		 * Initialize product search functionality
		 *
		 * Sets up autocomplete search for Ecwid products.
		 */
		function initializeProductSearch() {
			let searchTimeout;

			$( '#product-search' ).on( 'keyup', function ( e ) {
				// Prevent form submission on Enter key
				if ( e.key === 'Enter' ) {
					e.preventDefault();
					return false;
				}

				clearTimeout( searchTimeout );
				const query = $( this ).val();

				if ( query.length >= 2 ) {
					searchTimeout = setTimeout( function () {
						searchProducts( query );
					}, 300 );
				} else {
					$( '#product-search-results' ).hide();
				}
			} );

			// Prevent form submission on Enter key for the search input
			$( '#product-search' ).on( 'keydown', function ( e ) {
				if ( e.key === 'Enter' ) {
					e.preventDefault();
					return false;
				}
			} );
		}

		/**
		 * Search for products via AJAX
		 *
		 * Makes an AJAX request to search for Ecwid products.
		 *
		 * @param {string} query - Search query
		 */
		function searchProducts( query ) {
			// Show loading indicator
			$( '#product-search-results' )
				.html( '<div style="padding: 10px;">Searching...</div>' )
				.show();

			$.ajax( {
				url: window.ajaxurl,
				method: 'POST',
				data: {
					action: 'search_ecwid_products',
					nonce: ProductSettingsParams.searchNonce,
					query,
				},
				success( response ) {
					if ( response.success && response.data.products ) {
						displaySearchResults( response.data.products );
					} else {
						$( '#product-search-results' ).html(
							'<div style="padding: 10px; color: red;">Error: ' +
								( response.data.message ||
									'Unable to search products' ) +
								'</div>'
						);
					}
				},
				error() {
					$( '#product-search-results' ).html(
						'<div style="padding: 10px; color: red;">Error searching products</div>'
					);
				},
			} );
		}

		/**
		 * Display search results
		 *
		 * Renders the product search results in a selectable list.
		 *
		 * @param {Array} products - Array of products
		 */
		function displaySearchResults( products ) {
			const $results = $( '#product-search-results' );
			$results.empty();

			if ( products.length > 0 ) {
				products.forEach( function ( product ) {
					const $item = $( '<div>' )
						.addClass( 'product-search-item' )
						.css( {
							padding: '10px',
							borderBottom: '1px solid #eee',
							cursor: 'pointer',
						} );

					$item
						.on( 'mouseenter', function () {
							$( this ).css( 'background', '#f5f5f5' );
						} )
						.on( 'mouseleave', function () {
							$( this ).css( 'background', 'white' );
						} );

					$item.on( 'click', function () {
						selectProduct( product );
					} );

					const $content = $( '<div>' ).html(
						'<strong>' +
							product.name +
							'</strong><br>' +
							'ID: ' +
							product.id +
							( product.sku ? ' | SKU: ' + product.sku : '' )
					);

					if ( product.price ) {
						$content.append( '<br>Price: €' + product.price );
					}

					$item.append( $content );
					$results.append( $item );
				} );
			} else {
				$results.html(
					'<div style="padding: 10px;">No products found</div>'
				);
			}

			$results.show();
		}

		/**
		 * Select a product from search results
		 *
		 * Populates the product reference fields with selected product data.
		 *
		 * @param {Object} product - Product object
		 */
		function selectProduct( product ) {
			// Fill in the product ID and SKU
			$( '#ecwid_product_id' ).val( product.id );
			$( '#ecwid_product_sku' ).val( product.sku || '' );
			$( '#product-search-results' ).hide();

			// Auto-fill the post title with the product name
			$( '#title' ).val( product.name );

			// Update the title prompt if it exists
			if ( $( '#title-prompt-text' ).length ) {
				$( '#title-prompt-text' ).hide();
			}

			// Show success message
			$(
				'<div class="notice notice-success is-dismissible"><p>Product selected successfully!</p></div>'
			).insertAfter( 'h1.wp-heading-inline' );
		}

		/**
		 * Initialize ingredients table management
		 *
		 * Sets up add/remove functionality for ingredients table.
		 */
		function initializeIngredientsManagement() {
			// Handle the case where we start with no ingredients
			if ( $( '#ingredients-container .alert' ).length ) {
				// We're starting with no ingredients - set up initial add button
				$( document ).on( 'click', '#add-ingredient', function () {
					// Replace the alert with the table structure
					const tableHtml = `
						<div class="table-responsive">
							<table class="table table-striped table-hover">
								<thead class="table-light">
									<tr>
										<th scope="col" class="fw-semibold">Ingredient</th>
										<th scope="col" class="fw-semibold">Description</th>
										<th scope="col" class="fw-semibold text-center" style="width: 100px;">Actions</th>
									</tr>
								</thead>
								<tbody id="ingredients-table-body">
								</tbody>
							</table>
						</div>
						<div class="mt-3">
							<button type="button" id="add-ingredient" class="btn btn-primary">
								<i class="fas fa-plus me-1"></i>
								Add Another Ingredient
							</button>
						</div>
					`;

					$( '#ingredients-container' ).html( tableHtml );

					// Add the first row
					addIngredientRow();
				} );
			} else {
				// We already have a table - set up the add button for existing table
				$( document ).on( 'click', '#add-ingredient', function () {
					addIngredientRow();
				} );
			}

			// Handle ingredient selection change
			$( document ).on(
				'change',
				'.library-ingredient-select',
				function () {
					const $select = $( this );
					const $row = $select.closest( '.ingredient-row' );
					const selectedId = $select.val();

					updateIngredientDescription( $row, selectedId );

					// Update all dropdowns to reflect current selections
					updateAllIngredientDropdowns();
				}
			);

			// Handle ingredient removal
			$( document ).on( 'click', '.remove-ingredient', function () {
				if (
					confirm(
						'Are you sure you want to remove this ingredient?'
					)
				) {
					const $row = $( this ).closest( '.ingredient-row' );

					$row.fadeOut( 300, function () {
						$( this ).remove();

						// Update all dropdowns after removal
						updateAllIngredientDropdowns();

						// If no rows left, show the initial state
						if (
							$( '#ingredients-table-body .ingredient-row' )
								.length === 0
						) {
							showEmptyIngredientsState();
						}
					} );
				}
			} );
		}

		/**
		 * Add a new ingredient row to the table
		 *
		 * Creates and appends a new ingredient row with proper form fields.
		 */
		function addIngredientRow() {
			const rowHtml = `
				<tr class="ingredient-row" data-index="${ ingredientRowIndex }">
					<td>
						<select name="product_ingredient_id[]" class="form-select library-ingredient-select" required>
							${ getLibraryIngredientsOptions() }
						</select>
					</td>
					<td>
						<div class="ingredient-description text-muted small">
							Select an ingredient to see its description
						</div>
					</td>
					<td class="text-center">
						<button type="button" class="btn btn-sm btn-outline-danger remove-ingredient" title="Remove ingredient">
							<span class="dashicons dashicons-trash"></span>
						</button>
					</td>
				</tr>
			`;

			$( '#ingredients-table-body' ).append( rowHtml );
			ingredientRowIndex++;

			// Update all dropdowns to reflect current selections
			updateAllIngredientDropdowns();
		}

		/**
		 * Update ingredient description in table row
		 *
		 * Updates the description column when an ingredient is selected.
		 *
		 * @param {jQuery} $row         - Table row element
		 * @param {string} ingredientId - Selected ingredient ID
		 */
		function updateIngredientDescription( $row, ingredientId ) {
			const $descriptionDiv = $row.find( '.ingredient-description' );

			if (
				! ingredientId ||
				! ProductSettingsParams.productIngredients
			) {
				$descriptionDiv.text(
					'Select an ingredient to see its description'
				);
				return;
			}

			// Find the ingredient data
			const ingredient = ProductSettingsParams.productIngredients.find(
				( item ) => item.id == ingredientId
			);

			if ( ingredient && ingredient.description ) {
				$descriptionDiv.html( ingredient.description );
			} else {
				$descriptionDiv.text( 'No description available' );
			}
		}

		/**
		 * Show empty ingredients state
		 *
		 * Displays the initial empty state when all ingredients are removed.
		 */
		function showEmptyIngredientsState() {
			const emptyStateHtml = `
				<div class="alert alert-info">
					<p class="mb-2">No ingredients selected yet.</p>
					<button type="button" id="add-ingredient" class="btn btn-primary btn-sm">
						<i class="fas fa-plus me-1"></i>
						Add First Ingredient
					</button>
				</div>
			`;

			$( '#ingredients-container' ).html( emptyStateHtml );
		}

		/**
		 * Get library ingredients options HTML
		 *
		 * Generates HTML options for the library ingredients dropdown, excluding already selected ingredients.
		 *
		 * @param {string} currentValue - Current value of this dropdown (to keep it available)
		 *
		 * @return {string} Options HTML
		 */
		function getLibraryIngredientsOptions( currentValue = '' ) {
			let options = '<option value="">Select an ingredient...</option>';

			if (
				typeof ProductSettingsParams !== 'undefined' &&
				ProductSettingsParams.productIngredients
			) {
				if ( ProductSettingsParams.productIngredients.length === 0 ) {
					options +=
						'<option value="" disabled>No ingredients available - create one first</option>';
				} else {
					// Get currently selected ingredient IDs (excluding the current dropdown)
					const selectedIngredients =
						getCurrentlySelectedIngredients( currentValue );

					ProductSettingsParams.productIngredients.forEach(
						function ( ingredient ) {
							// Only include if not already selected, or if it's the current value
							if (
								! selectedIngredients.includes(
									ingredient.id.toString()
								) ||
								ingredient.id.toString() === currentValue
							) {
								options += `<option value="${ ingredient.id }">${ ingredient.title }</option>`;
							}
						}
					);
				}
			}

			return options;
		}

		/**
		 * Get currently selected ingredient IDs
		 *
		 * Returns an array of ingredient IDs that are currently selected in all dropdowns.
		 *
		 * @param {string} excludeValue - Value to exclude from the selection (current dropdown)
		 *
		 * @return {Array} Array of selected ingredient IDs
		 */
		function getCurrentlySelectedIngredients( excludeValue = '' ) {
			const selectedIds = [];

			$( '.library-ingredient-select' ).each( function () {
				const value = $( this ).val();
				if ( value && value !== excludeValue && value !== '' ) {
					selectedIds.push( value );
				}
			} );

			return selectedIds;
		}

		/**
		 * Update all ingredient dropdowns to reflect current selections
		 *
		 * Refreshes all dropdown options to exclude already selected ingredients.
		 */
		function updateAllIngredientDropdowns() {
			$( '.library-ingredient-select' ).each( function () {
				const $select = $( this );
				const currentValue = $select.val();

				// Store current selection
				const selectedValue = currentValue;

				// Get updated options
				const newOptions = getLibraryIngredientsOptions( currentValue );

				// Update the dropdown
				$select.html( newOptions );

				// Restore selection if it's still valid
				if ( selectedValue ) {
					$select.val( selectedValue );
				}
			} );
		}

		/**
		 * Initialize form validation
		 *
		 * Validates form data before submission to ensure data integrity.
		 */
		function initializeFormValidation() {
			$( 'form#post' ).on( 'submit', function ( e ) {
				let hasErrors = false;
				const errors = [];

				// Validate that we have either an Ecwid Product ID or SKU
				const productId = $( '#ecwid_product_id' ).val().trim();
				const productSku = $( '#ecwid_product_sku' ).val().trim();

				if ( ! productId && ! productSku ) {
					hasErrors = true;
					errors.push(
						'Please provide either an Ecwid Product ID or SKU to link this configuration.'
					);
					$( '#ecwid_product_id, #ecwid_product_sku' ).addClass(
						'error'
					);
				} else {
					$( '#ecwid_product_id, #ecwid_product_sku' ).removeClass(
						'error'
					);
				}

				if ( hasErrors ) {
					e.preventDefault();
					alert(
						'Please fix the following errors:\n\n' +
							errors.join( '\n' )
					);
					return false;
				}
			} );
		}

		/**
		 * Initialize tag suggestions functionality
		 *
		 * Sets up autocomplete for product tags.
		 */
		function initializeTagSuggestions() {
			// Product tags autocomplete (WordPress built-in functionality)
			if ( $( 'input[name="product_tags"]' ).length ) {
				// WordPress tag autocomplete would be implemented here
				// For now, users can type comma-separated tags
				$( 'input[name="product_tags"]' ).on( 'input', function () {
					// Could add real-time tag suggestions here
				} );
			}
		}

		/**
		 * Initialize lines checklist functionality
		 *
		 * Enhances the product lines selection interface.
		 */
		function initializeLinesManagement() {
			// Add hover effects to line checkboxes
			$( '.lines-checklist label' )
				.on( 'mouseenter', function () {
					$( this ).css( 'background', 'rgba(0,123,255,0.1)' );
				} )
				.on( 'mouseleave', function () {
					$( this ).css( 'background', '' );
				} );

			// Add visual feedback for selected lines
			$( document ).on(
				'change',
				'.lines-checklist input[type="checkbox"]',
				function () {
					const $label = $( this ).closest( 'label' );
					if ( $( this ).is( ':checked' ) ) {
						$label.css( 'font-weight', 'bold' );
					} else {
						$label.css( 'font-weight', '' );
					}
				}
			);

			// Initialize already selected lines
			$( '.lines-checklist input[type="checkbox"]:checked' ).each(
				function () {
					$( this ).closest( 'label' ).css( 'font-weight', 'bold' );
				}
			);
		}

		/**
		 * Initialize enhanced UI features
		 *
		 * Adds polish and user experience improvements.
		 */
		function initializeEnhancedUI() {
			// Add confirmation for removing ingredients
			$( document ).on( 'click', '.remove-ingredient', function ( e ) {
				if (
					! confirm(
						'Are you sure you want to remove this ingredient?'
					)
				) {
					e.preventDefault();
					e.stopPropagation();
				}
			} );

			// Auto-expand textareas
			$( document ).on( 'input', 'textarea', function () {
				this.style.height = 'auto';
				this.style.height = this.scrollHeight + 'px';
			} );

			// Initialize existing textareas
			$( 'textarea' ).each( function () {
				this.style.height = 'auto';
				this.style.height = this.scrollHeight + 'px';
			} );
		}

		/**
		 * Initialize help tooltips and guidance
		 *
		 * Provides contextual help for complex features.
		 */
		function initializeHelpSystem() {
			// Add help tooltips for complex fields
			const helpTexts = {
				'.ingredient-name-field':
					'Enter the ingredient name as it should appear on your product pages.',
				'input[name="product_tags"]':
					'Add descriptive tags like "waterproof", "organic", or "limited-edition" to help customers find products.',
				'#ecwid_product_id':
					'Enter the numeric ID of the Ecwid product you want to link to this configuration.',
				'#ecwid_product_sku':
					'Enter the SKU (Stock Keeping Unit) of the Ecwid product as an alternative to Product ID.',
			};

			Object.keys( helpTexts ).forEach( function ( selector ) {
				$( document ).on( 'focus', selector, function () {
					const $field = $( this );
					if ( ! $field.data( 'help-shown' ) ) {
						const helpText = helpTexts[ selector ];
						$field.attr( 'title', helpText );
						$field.data( 'help-shown', true );
					}
				} );
			} );
		}

		// Initialize all functionality
		initializeProductSearch();
		initializeIngredientsManagement();
		initializeFormValidation();
		initializeTagSuggestions();
		initializeLinesManagement();
		initializeEnhancedUI();
		initializeHelpSystem();

		// Update ingredient dropdowns on page load to handle existing selections
		if ( $( '.library-ingredient-select' ).length > 0 ) {
			updateAllIngredientDropdowns();
		}

		// Show success message for form saves
		if ( window.location.search.includes( 'message=1' ) ) {
			$(
				'<div class="notice notice-success is-dismissible"><p>Product settings saved successfully!</p></div>'
			).insertAfter( 'h1.wp-heading-inline' );
		}
	} );
} )( jQuery );
