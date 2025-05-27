/**
 * Admin script for Product Settings management
 *
 * Handles the dynamic interface for managing product settings including
 * ingredients table view, media with tags, and line assignments.
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
		const mediaIndex = $( '.product-media-item' ).length;

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
		 * Initialize media management with tags
		 *
		 * Sets up media selection and tag management functionality.
		 */
		function initializeMediaManagement() {
			// Handle media selection for tag-based items
			$( document ).on( 'click', '.select-media-button', function ( e ) {
				e.preventDefault();

				const $button = $( this );
				const $container = $button.closest( '.media-tag-item' );
				const tagKey = $container.data( 'tag-key' );

				// Show loading state
				$button
					.html(
						'<span class="dashicons dashicons-update spin"></span>Loading...'
					)
					.prop( 'disabled', true );

				// Create media frame
				const mediaFrame = wp.media( {
					title:
						ProductSettingsParams.selectMediaTitle ||
						'Select Product Media',
					button: {
						text:
							ProductSettingsParams.selectMediaButton ||
							'Use this media',
					},
					multiple: false,
				} );

				// When media is selected
				mediaFrame.on( 'select', function () {
					const attachment = mediaFrame
						.state()
						.get( 'selection' )
						.first()
						.toJSON();

					updateMediaTagItem( $container, attachment, tagKey );
				} );

				// When media frame is closed without selection
				mediaFrame.on( 'close', function () {
					// Restore button state
					const hasMedia =
						$container.find( '.media-attachment-id' ).val() !== '';
					$button
						.html(
							'<span class="dashicons dashicons-' +
								( hasMedia ? 'update' : 'plus-alt2' ) +
								'"></span>' +
								( hasMedia ? 'Change' : 'Select' )
						)
						.prop( 'disabled', false );
				} );

				mediaFrame.open();
			} );

			// Handle media removal
			$( document ).on( 'click', '.remove-media-button', function ( e ) {
				e.preventDefault();

				const $button = $( this );
				const $container = $button.closest( '.media-tag-item' );
				const tagKey = $container.data( 'tag-key' );

				// Confirm removal
				if (
					! confirm( 'Are you sure you want to remove this media?' )
				) {
					return;
				}

				removeMediaFromTag( $container, tagKey );
			} );

			// Initialize media item states
			initializeMediaItemStates();
		}

		/**
		 * Update media tag item with selected attachment
		 *
		 * Updates the UI and form data when media is selected for a tag.
		 *
		 * @param {jQuery} $container - Media tag container element
		 * @param {Object} attachment - WordPress media attachment object
		 * @param {string} tagKey     - Media tag key
		 */
		function updateMediaTagItem( $container, attachment, tagKey ) {
			// Update hidden input with attachment ID
			$container.find( '.media-attachment-id' ).val( attachment.id );

			// Update preview image
			const $preview = $container.find( '.media-preview' );
			$preview.html(
				'<img src="' +
					attachment.url +
					'" class="img-fluid rounded" style="max-height: 100px;" alt="' +
					attachment.alt +
					'">'
			);

			// Update select button
			const $selectBtn = $container.find( '.select-media-button' );
			$selectBtn
				.removeClass( 'btn-primary' )
				.addClass( 'btn-outline-primary' )
				.html(
					'<span class="dashicons dashicons-update"></span>Change'
				)
				.prop( 'disabled', false );

			// Add/show remove button if not present
			let $removeBtn = $container.find( '.remove-media-button' );
			if ( $removeBtn.length === 0 ) {
				$removeBtn = $(
					'<button type="button" class="btn btn-outline-danger btn-sm remove-media-button"><span class="dashicons dashicons-trash"></span>Remove</button>'
				);
				$selectBtn.after( $removeBtn );
			} else {
				$removeBtn.show();
			}

			// Mark attachment as product media
			markAttachmentAsProductMedia( attachment.id, tagKey );

			// Show success feedback
			showMediaFeedback(
				$container,
				'Media selected successfully!',
				'success'
			);
		}

		/**
		 * Remove media from tag
		 *
		 * Removes media assignment from a tag and updates the UI.
		 *
		 * @param {jQuery} $container - Media tag container element
		 * @param {string} tagKey     - Media tag key
		 */
		function removeMediaFromTag( $container, tagKey ) {
			// Clear hidden input
			$container.find( '.media-attachment-id' ).val( '' );

			// Reset preview to empty state
			const $preview = $container.find( '.media-preview' );
			$preview.html(
				'<span class="dashicons dashicons-format-image" style="font-size: 48px; color: #ccc;"></span>'
			);

			// Update select button
			const $selectBtn = $container.find( '.select-media-button' );
			$selectBtn
				.removeClass( 'btn-outline-primary' )
				.addClass( 'btn-primary' )
				.html(
					'<span class="dashicons dashicons-plus-alt2"></span>Select'
				);

			// Hide remove button
			$container.find( '.remove-media-button' ).hide();

			// Show success feedback
			showMediaFeedback(
				$container,
				'Media removed successfully!',
				'info'
			);
		}

		/**
		 * Initialize media item states
		 *
		 * Sets up the initial state for all media tag items based on existing data.
		 */
		function initializeMediaItemStates() {
			$( '.media-tag-item' ).each( function () {
				const $container = $( this );
				const hasMedia =
					$container.find( '.media-attachment-id' ).val() !== '';

				if ( ! hasMedia ) {
					// Hide remove button for empty items
					$container.find( '.remove-media-button' ).hide();
				}
			} );
		}

		/**
		 * Mark attachment as product media
		 *
		 * Makes an AJAX call to mark the attachment with appropriate meta data.
		 *
		 * @param {number} attachmentId - WordPress attachment ID
		 * @param {string} tagKey       - Media tag key
		 */
		function markAttachmentAsProductMedia( attachmentId, tagKey ) {
			$.post( ajaxurl, {
				action: 'mark_product_media',
				attachment_id: attachmentId,
				tag_key: tagKey,
				nonce: ProductSettingsParams.searchNonce,
			} ).fail( function () {
				console.warn( 'Failed to mark attachment as product media' );
			} );
		}

		/**
		 * Show media feedback message
		 *
		 * Displays a temporary feedback message for media operations.
		 *
		 * @param {jQuery} $container - Media tag container element
		 * @param {string} message    - Feedback message
		 * @param {string} type       - Message type (success, info, warning, danger)
		 */
		function showMediaFeedback( $container, message, type ) {
			// Remove existing feedback
			$container.find( '.media-feedback' ).remove();

			// Create feedback element
			const $feedback = $(
				'<div class="media-feedback alert alert-' +
					type +
					' alert-sm mt-2 mb-0">' +
					message +
					'</div>'
			);

			// Add feedback
			$container.append( $feedback );

			// Auto-remove after 3 seconds
			setTimeout( function () {
				$feedback.fadeOut( 300, function () {
					$( this ).remove();
				} );
			}, 3000 );
		}

		/**
		 * Initialize form validation
		 *
		 * Validates form data before submission to ensure data integrity.
		 * Note: Hero image is no longer mandatory.
		 */
		function initializeFormValidation() {
			$( 'form#post' ).on( 'submit', function ( e ) {
				let hasErrors = false;
				const errors = [];

				// Validate media tags are unique within this product
				const mediaTags = [];
				$( '.media-tag-field' ).each( function () {
					const tag = $( this ).val().trim();
					if ( tag ) {
						if ( mediaTags.includes( tag ) ) {
							hasErrors = true;
							errors.push( 'Duplicate media tag: ' + tag );
							$( this ).addClass( 'error' );
						} else {
							mediaTags.push( tag );
							$( this ).removeClass( 'error' );
						}
					}
				} );

				// Validate media tag format (alphanumeric, underscore, dash)
				$( '.media-tag-field' ).each( function () {
					const tag = $( this ).val().trim();
					if ( tag && ! /^[a-zA-Z0-9_-]+$/.test( tag ) ) {
						hasErrors = true;
						errors.push(
							'Invalid media tag format: ' +
								tag +
								'. Use only letters, numbers, underscore, and dash.'
						);
						$( this ).addClass( 'error' );
					}
				} );

				// Check that media items with tags also have attachments
				$( '.product-media-item' ).each( function () {
					const $item = $( this );
					const tag = $item.find( '.media-tag-field' ).val().trim();
					const attachmentId = $item
						.find( '.media-attachment-id' )
						.val();

					if ( tag && ! attachmentId ) {
						hasErrors = true;
						errors.push(
							'Media tag "' +
								tag +
								'" requires a media file to be selected.'
						);
						$item.css( 'border-color', '#dc3232' );
					} else {
						$item.css( 'border-color', '' );
					}
				} );

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
		 * Sets up autocomplete for media tags and product tags.
		 */
		function initializeTagSuggestions() {
			// Enhanced autocomplete for media tags
			$( document ).on( 'input', '.media-tag-field', function () {
				const $field = $( this );
				const value = $field.val().toLowerCase();

				// Get existing suggestions from datalist
				const $datalist = $( '#media_tag_suggestions' );
				const suggestions = [];

				$datalist.find( 'option' ).each( function () {
					const optionValue = $( this ).val();
					if ( optionValue.toLowerCase().includes( value ) ) {
						suggestions.push( optionValue );
					}
				} );

				// You could implement a dropdown here instead of relying on datalist
				// For now, the datalist provides the functionality
			} );

			// Product tags autocomplete (WordPress built-in functionality)
			if ( $( 'input[name="product_tags"]' ).length ) {
				// WordPress tag autocomplete would be implemented here
				// For now, users can type comma-separated tags
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
			// Add loading states to buttons
			$( document ).on( 'click', '.select-media-button', function () {
				const $button = $( this );
				const originalText = $button.text();
				$button.text( 'Loading...' ).prop( 'disabled', true );

				// Re-enable after media frame loads (timeout as fallback)
				setTimeout( function () {
					$button.text( originalText ).prop( 'disabled', false );
				}, 1000 );
			} );

			// Add confirmation for removing items
			$( document ).on(
				'click',
				'.remove-ingredient, .remove-media',
				function ( e ) {
					const itemType = $( this ).hasClass( 'remove-ingredient' )
						? 'ingredient'
						: 'media item';
					if (
						! confirm(
							'Are you sure you want to remove this ' +
								itemType +
								'?'
						)
					) {
						e.preventDefault();
						e.stopPropagation();
					}
				}
			);

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
				'.media-tag-field':
					'Use descriptive names like "hero_image", "gallery_1", or "size_chart". These tags can be used to target specific media in your Gutenberg blocks.',
				'.ingredient-name-field':
					'Enter the ingredient name as it should appear on your product pages.',
				'input[name="product_tags"]':
					'Add descriptive tags like "waterproof", "organic", or "limited-edition" to help customers find products.',
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

		/**
		 * Initialize enhanced media UI features
		 *
		 * Adds polish and user experience improvements for media management.
		 */
		function initializeEnhancedMediaUI() {
			// Add hover effects to media tag items
			$( document )
				.on( 'mouseenter', '.media-tag-item', function () {
					$( this ).addClass( 'border-primary' );
				} )
				.on( 'mouseleave', '.media-tag-item', function () {
					$( this ).removeClass( 'border-primary' );
				} );

			// Add loading states for better UX
			$( document ).on( 'click', '.select-media-button', function () {
				const $container = $( this ).closest( '.media-tag-item' );
				$container.addClass( 'media-loading' );
			} );

			// Remove loading state when media frame opens
			$( document ).on( 'DOMNodeInserted', function ( e ) {
				if ( $( e.target ).hasClass( 'media-modal' ) ) {
					$( '.media-tag-item' ).removeClass( 'media-loading' );
				}
			} );

			// Add drag and drop visual feedback
			$( document )
				.on( 'dragover', '.media-preview', function ( e ) {
					e.preventDefault();
					$( this ).addClass(
						'border-dashed border-primary bg-light'
					);
				} )
				.on( 'dragleave', '.media-preview', function () {
					$( this ).removeClass(
						'border-dashed border-primary bg-light'
					);
				} );

			// Handle drag and drop (basic implementation)
			$( document ).on( 'drop', '.media-preview', function ( e ) {
				e.preventDefault();
				$( this ).removeClass(
					'border-dashed border-primary bg-light'
				);

				// Note: Full drag and drop implementation would require additional
				// WordPress media handling - this is a placeholder for future enhancement
				console.log(
					'Drag and drop functionality can be enhanced in future versions'
				);
			} );
		}

		// Initialize all functionality
		initializeProductSearch();
		initializeIngredientsManagement();
		initializeMediaManagement();
		initializeFormValidation();
		initializeTagSuggestions();
		initializeLinesManagement();
		initializeEnhancedUI();
		initializeHelpSystem();
		initializeEnhancedMediaUI();

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
