/**
 * Admin script for Media Tags management with Bootstrap integration
 *
 * Handles the dynamic interface for managing predefined media tags
 * using Bootstrap modals, forms, and components.
 *
 * @param $
 * @package
 * @since   0.2.0
 */

( function ( $ ) {
	$( document ).ready( function () {
		// Check if this is the right page and tab
		if (
			typeof window.location !== 'undefined' &&
			window.location.search.includes(
				'page=peaches-ecwid-product-settings'
			)
		) {
			// Initialize when media tags tab becomes active
			initializeWhenTabActive();
		}
	} );

	/**
	 * Initialize functionality when media tags tab becomes active
	 *
	 * Sets up event handlers that work with Bootstrap tabs.
	 */
	function initializeWhenTabActive() {
		// Check if we're starting on media tags tab
		const urlParams = new URLSearchParams( window.location.search );
		const activeTab = urlParams.get( 'tab' );

		if ( activeTab === 'media_tags' ) {
			initializeMediaTagsManagement();
		}

		// Listen for tab changes
		$( document ).on(
			'shown.bs.tab',
			'button[data-bs-target="#media-tags"]',
			function () {
				initializeMediaTagsManagement();
			}
		);
	}

	/**
	 * Initialize media tags management functionality
	 *
	 * Sets up event handlers for CRUD operations using Bootstrap components.
	 */
	function initializeMediaTagsManagement() {
		// Prevent double initialization
		if ( $( '#add-media-tag-form' ).data( 'initialized' ) ) {
			return;
		}
		$( '#add-media-tag-form' ).data( 'initialized', true );

		// Initialize tooltips
		initializeTooltips();

		// Initialize form handlers
		initializeFormHandlers();

		// Initialize action button handlers
		initializeActionHandlers();

		// Initialize form validation
		initializeFormValidation();
	}

	/**
	 * Initialize Bootstrap tooltips
	 *
	 * Enables tooltips on action buttons for better UX.
	 */
	function initializeTooltips() {
		// Initialize tooltips using Bootstrap's Tooltip component
		if ( typeof bootstrap !== 'undefined' && bootstrap.Tooltip ) {
			const tooltipTriggerList = document.querySelectorAll(
				'[data-bs-toggle="tooltip"]'
			);
			tooltipTriggerList.forEach( function ( tooltipTriggerEl ) {
				new bootstrap.Tooltip( tooltipTriggerEl );
			} );
		}
	}

	/**
	 * Initialize form handlers for add and edit modals
	 *
	 * Sets up form submission handlers for both add and edit operations.
	 */
	function initializeFormHandlers() {
		// Add tag form submission
		$( '#add-media-tag-form' )
			.off( 'submit' )
			.on( 'submit', function ( e ) {
				e.preventDefault();
				handleAddTag();
			} );

		// Edit tag form submission
		$( '#edit-media-tag-form' )
			.off( 'submit' )
			.on( 'submit', function ( e ) {
				e.preventDefault();
				handleEditTag();
			} );

		// Reset forms when modals are hidden
		$( '#addTagModal' ).on( 'hidden.bs.modal', function () {
			resetAddForm();
		} );

		$( '#editTagModal' ).on( 'hidden.bs.modal', function () {
			resetEditForm();
		} );
	}

	/**
	 * Initialize action button handlers
	 *
	 * Sets up click handlers for edit and delete buttons.
	 */
	function initializeActionHandlers() {
		// Edit button handler
		$( document )
			.off( 'click', '.edit-tag-btn' )
			.on( 'click', '.edit-tag-btn', function () {
				const tagKey = $( this ).data( 'tag-key' );
				const tagLabel = $( this ).data( 'tag-label' );
				const tagDescription = $( this ).data( 'tag-description' );
				const tagCategory = $( this ).data( 'tag-category' );

				openEditModal( tagKey, tagLabel, tagDescription, tagCategory );
			} );

		// Delete button handler
		$( document )
			.off( 'click', '.delete-tag-btn' )
			.on( 'click', '.delete-tag-btn', function () {
				const tagKey = $( this ).data( 'tag-key' );
				const tagLabel = $( this ).data( 'tag-label' );

				showDeleteConfirmation( tagKey, tagLabel );
			} );
	}

	/**
	 * Initialize form validation
	 *
	 * Sets up real-time validation for form fields with improved UX.
	 */
	function initializeFormValidation() {
		// Tag key validation for add form
		$( '#tag_key' ).on( 'input', function () {
			validateTagKey( $( this ) );
		} );

		// Auto-generate tag key from label with smart handling
		$( '#tag_label' ).on( 'input', function () {
			const label = $( this ).val().trim();
			const $tagKey = $( '#tag_key' );

			// Only auto-generate if:
			// 1. Tag key is empty, OR
			// 2. Tag key matches the previously auto-generated value
			const currentKey = $tagKey.val();
			const previousLabel = $( this ).data( 'previous-value' ) || '';
			const expectedKeyFromPrevious = generateTagKey( previousLabel );

			const shouldAutoGenerate =
				currentKey === '' || currentKey === expectedKeyFromPrevious;

			if ( shouldAutoGenerate && label.length > 0 ) {
				const generatedKey = generateTagKey( label );
				$tagKey.val( generatedKey ).trigger( 'input' );

				// Add visual indication using Bootstrap classes
				$tagKey.addClass( 'bg-light' );
			} else if ( label.length === 0 ) {
				// Clear the key if label is empty and it was auto-generated
				if ( $tagKey.hasClass( 'bg-light' ) ) {
					$tagKey
						.val( '' )
						.removeClass( 'bg-light' )
						.trigger( 'input' );
				}
			}

			// Store current value for next comparison
			$( this ).data( 'previous-value', label );
		} );

		// When user manually edits tag key, stop auto-generation
		$( '#tag_key' ).on( 'keydown', function ( e ) {
			// Don't disable auto-generation for programmatic changes or tab/arrow keys
			if (
				e.which !== 9 &&
				e.which !== 37 &&
				e.which !== 38 &&
				e.which !== 39 &&
				e.which !== 40
			) {
				$( this ).removeClass( 'bg-light' );
			}
		} );

		// Visual feedback for auto-generated fields using Bootstrap classes
		$( '#tag_key' )
			.on( 'focus', function () {
				if ( $( this ).hasClass( 'bg-light' ) ) {
					$( this )
						.siblings( '.form-text' )
						.addClass( 'text-primary fw-medium' )
						.html(
							'<i class="dashicons dashicons-admin-generic"></i> Auto-generated from label. You can modify this if needed.'
						);
				}
			} )
			.on( 'blur', function () {
				$( this )
					.siblings( '.form-text' )
					.removeClass( 'text-primary fw-medium' )
					.html(
						'Auto-generated from label. Lowercase letters, numbers, and underscores only.'
					);
			} );
	}

	/**
	 * Generate tag key from label
	 *
	 * Converts a human-readable label into a valid tag key.
	 *
	 * @param {string} label - The display label
	 *
	 * @return {string} Generated tag key
	 */
	function generateTagKey( label ) {
		return (
			label
				.toLowerCase()
				.trim()
				// Replace multiple spaces with single space
				.replace( /\s+/g, ' ' )
				// Replace spaces with underscores
				.replace( /\s/g, '_' )
				// Remove special characters except underscores
				.replace( /[^a-z0-9_]/g, '' )
				// Remove multiple underscores
				.replace( /_+/g, '_' )
				// Remove leading/trailing underscores
				.replace( /^_+|_+$/g, '' )
				// Limit length
				.substring( 0, 50 )
		);
	}

	/**
	 * Validate tag key format
	 *
	 * Ensures tag key contains only allowed characters.
	 *
	 * @param {jQuery} $input - The tag key input field
	 *
	 * @return {boolean} - True if valid, false otherwise
	 */
	function validateTagKey( $input ) {
		const value = $input.val();
		const isValid = /^[a-z0-9_]*$/.test( value );

		if ( ! isValid && value.length > 0 ) {
			$input.addClass( 'is-invalid' ).removeClass( 'is-valid' );

			let $feedback = $input.siblings( '.invalid-feedback' );
			if ( ! $feedback.length ) {
				$feedback = $( '<div class="invalid-feedback"></div>' );
				$input.after( $feedback );
			}
			$feedback.text(
				'Only lowercase letters, numbers, and underscores allowed.'
			);

			return false;
		}
		$input
			.removeClass( 'is-invalid' )
			.addClass( value.length > 0 ? 'is-valid' : '' );
		$input.siblings( '.invalid-feedback' ).remove();

		return true;
	}

	/**
	 * Handle add tag form submission
	 *
	 * Processes the add tag form and sends AJAX request.
	 */
	function handleAddTag() {
		const $form = $( '#add-media-tag-form' );
		const $submitBtn = $form.find( 'button[type="submit"]' );

		// Validate form
		if ( ! validateAddForm() ) {
			return;
		}

		// Show loading state
		setButtonLoading( $submitBtn, true, 'Adding...' );

		const formData = {
			action: 'add_media_tag',
			nonce: MediaTagsParams.nonce,
			tag_key: $form.find( '#tag_key' ).val(),
			tag_label: $form.find( '#tag_label' ).val(),
			tag_description: $form.find( '#tag_description' ).val(),
			tag_category: $form.find( '#tag_category' ).val(),
		};

		$.post( MediaTagsParams.ajaxUrl, formData )
			.done( function ( response ) {
				if ( response.success ) {
					// Hide modal
					const modal = bootstrap.Modal.getInstance(
						document.getElementById( 'addTagModal' )
					);
					modal.hide();

					// Add new row to table
					addTagRowToTable(
						response.data.tag_key,
						response.data.tag_data
					);

					// Show success message
					showAlert( response.data.message, 'success' );

					// Update empty state if needed
					handleEmptyStateUpdate();
				} else {
					showAlert( response.data, 'danger' );
				}
			} )
			.fail( function () {
				showAlert(
					'An error occurred while adding the tag.',
					'danger'
				);
			} )
			.always( function () {
				setButtonLoading( $submitBtn, false );
			} );
	}

	/**
	 * Handle edit tag form submission
	 *
	 * Processes the edit tag form and sends AJAX request.
	 */
	function handleEditTag() {
		const $form = $( '#edit-media-tag-form' );
		const $submitBtn = $form.find( 'button[type="submit"]' );

		// Show loading state
		setButtonLoading( $submitBtn, true, 'Updating...' );

		const formData = {
			action: 'update_media_tag',
			nonce: MediaTagsParams.nonce,
			tag_key: $form.find( '#edit_tag_key' ).val(),
			tag_label: $form.find( '#edit_tag_label' ).val(),
			tag_description: $form.find( '#edit_tag_description' ).val(),
			tag_category: $form.find( '#edit_tag_category' ).val(),
		};

		$.post( MediaTagsParams.ajaxUrl, formData )
			.done( function ( response ) {
				if ( response.success ) {
					// Hide modal
					const modal = bootstrap.Modal.getInstance(
						document.getElementById( 'editTagModal' )
					);
					modal.hide();

					// Update row in table
					updateTagRowInTable(
						formData.tag_key,
						response.data.tag_data
					);

					// Show success message
					showAlert( response.data.message, 'success' );
				} else {
					showAlert( response.data, 'danger' );
				}
			} )
			.fail( function () {
				showAlert(
					'An error occurred while updating the tag.',
					'danger'
				);
			} )
			.always( function () {
				setButtonLoading( $submitBtn, false );
			} );
	}

	/**
	 * Show delete confirmation dialog
	 *
	 * Displays a Bootstrap modal to confirm tag deletion.
	 *
	 * @param {string} tagKey   - Tag key to delete
	 * @param {string} tagLabel - Tag label for confirmation
	 */
	function showDeleteConfirmation( tagKey, tagLabel ) {
		// Create confirmation modal if it doesn't exist
		let $modal = $( '#deleteConfirmModal' );
		if ( ! $modal.length ) {
			$modal = createDeleteConfirmModal();
			$( 'body' ).append( $modal );
		}

		// Update modal content
		$modal
			.find( '.modal-body p' )
			.html(
				'Are you sure you want to delete the tag <strong>"' +
					escapeHtml( tagLabel ) +
					'"</strong>?<br>' +
					'<small class="text-muted">This action cannot be undone.</small>'
			);

		// Set up confirm button handler
		$modal
			.find( '.btn-danger' )
			.off( 'click' )
			.on( 'click', function () {
				handleDeleteTag( tagKey );
			} );

		// Show modal
		const modal = new bootstrap.Modal( $modal[ 0 ] );
		modal.show();
	}

	/**
	 * Handle tag deletion
	 *
	 * Sends AJAX request to delete the specified tag.
	 *
	 * @param {string} tagKey - Tag key to delete
	 */
	function handleDeleteTag( tagKey ) {
		const $modal = $( '#deleteConfirmModal' );
		const $confirmBtn = $modal.find( '.btn-danger' );

		// Show loading state
		setButtonLoading( $confirmBtn, true, 'Deleting...' );

		const requestData = {
			action: 'delete_media_tag',
			nonce: MediaTagsParams.nonce,
			tag_key: tagKey,
		};

		$.post( MediaTagsParams.ajaxUrl, requestData )
			.done( function ( response ) {
				if ( response.success ) {
					// Hide modal
					const modal = bootstrap.Modal.getInstance( $modal[ 0 ] );
					modal.hide();

					// Remove row from table
					removeTagRowFromTable( tagKey );

					// Show success message
					showAlert( response.data.message, 'success' );

					// Update empty state if needed
					handleEmptyStateUpdate();
				} else {
					showAlert( response.data, 'danger' );
				}
			} )
			.fail( function () {
				showAlert(
					'An error occurred while deleting the tag.',
					'danger'
				);
			} )
			.always( function () {
				setButtonLoading( $confirmBtn, false );
			} );
	}

	/**
	 * Open edit modal with tag data
	 *
	 * Populates and shows the edit modal with current tag information.
	 *
	 * @param {string} tagKey         - Tag key
	 * @param {string} tagLabel       - Tag label
	 * @param {string} tagDescription - Tag description
	 * @param {string} tagCategory    - Tag category
	 */
	function openEditModal( tagKey, tagLabel, tagDescription, tagCategory ) {
		const $form = $( '#edit-media-tag-form' );

		// Populate form fields
		$form.find( '#edit_tag_key' ).val( tagKey );
		$form.find( '#edit_tag_label' ).val( tagLabel );
		$form.find( '#edit_tag_description' ).val( tagDescription );
		$form.find( '#edit_tag_category' ).val( tagCategory );

		// Update modal title
		$( '#editTagModalLabel' ).html(
			'<i class="dashicons dashicons-edit"></i> Edit "' +
				escapeHtml( tagLabel ) +
				'"'
		);

		// Show modal
		const modal = new bootstrap.Modal(
			document.getElementById( 'editTagModal' )
		);
		modal.show();
	}

	/**
	 * Add new tag row to table
	 *
	 * Inserts a new row into the tags table with animation.
	 *
	 * @param {string} tagKey  - Tag key
	 * @param {Object} tagData - Tag data object
	 */
	function addTagRowToTable( tagKey, tagData ) {
		const categoryBadges = {
			primary: 'bg-primary',
			secondary: 'bg-secondary',
			reference: 'bg-info',
			media: 'bg-warning text-dark',
		};

		const badgeClass = categoryBadges[ tagData.category ] || 'bg-secondary';
		const description =
			tagData.description || '<em class="text-muted">No description</em>';

		const newRowHtml = `
			<tr class="media-tag-row" data-tag-key="${ escapeHtml(
				tagKey
			) }" style="display: none;">
				<td>
					<div class="d-flex align-items-center">
						<div class="me-3">
							<code class="bg-light px-2 py-1 rounded text-dark">${ escapeHtml(
								tagKey
							) }</code>
						</div>
						<div>
							<h6 class="mb-1">${ escapeHtml( tagData.label ) }</h6>
						</div>
					</div>
				</td>
				<td>
					<span class="badge ${ badgeClass }">
						${ escapeHtml(
							tagData.category.charAt( 0 ).toUpperCase() +
								tagData.category.slice( 1 )
						) }
					</span>
				</td>
				<td>${ description }</td>
				<td class="text-end">
					<div class="btn-group btn-group-sm" role="group">
						<button type="button"
								class="btn btn-outline-primary edit-tag-btn"
								data-tag-key="${ escapeHtml( tagKey ) }"
								data-tag-label="${ escapeHtml( tagData.label ) }"
								data-tag-description="${ escapeHtml( tagData.description ) }"
								data-tag-category="${ escapeHtml( tagData.category ) }"
								data-bs-toggle="tooltip"
								title="Edit tag">
							<i class="dashicons dashicons-edit"></i>
							<span class="visually-hidden">Edit</span>
						</button>
						<button type="button"
								class="btn btn-outline-danger delete-tag-btn"
								data-tag-key="${ escapeHtml( tagKey ) }"
								data-tag-label="${ escapeHtml( tagData.label ) }"
								data-bs-toggle="tooltip"
								title="Delete tag">
							<i class="dashicons dashicons-trash"></i>
							<span class="visually-hidden">Delete</span>
						</button>
					</div>
				</td>
			</tr>
		`;

		$( '#media-tags-list' ).append( newRowHtml );
		$( `.media-tag-row[data-tag-key="${ tagKey }"]` ).fadeIn(
			300,
			function () {
				// Re-initialize tooltips for new buttons
				initializeTooltips();
			}
		);

		// Update tag count in header
		updateTagCount( 1 );
	}

	/**
	 * Update existing tag row in table
	 *
	 * Updates an existing row with new tag data.
	 *
	 * @param {string} tagKey  - Tag key
	 * @param {Object} tagData - Updated tag data
	 */
	function updateTagRowInTable( tagKey, tagData ) {
		const $row = $( `.media-tag-row[data-tag-key="${ tagKey }"]` );

		if ( ! $row.length ) {
			return;
		}

		const categoryBadges = {
			primary: 'bg-primary',
			secondary: 'bg-secondary',
			reference: 'bg-info',
			media: 'bg-warning text-dark',
		};

		const badgeClass = categoryBadges[ tagData.category ] || 'bg-secondary';
		const description =
			tagData.description || '<em class="text-muted">No description</em>';

		// Update row content
		$row.find( 'h6' ).text( tagData.label );
		$row.find( '.badge' )
			.attr( 'class', `badge ${ badgeClass }` )
			.text(
				tagData.category.charAt( 0 ).toUpperCase() +
					tagData.category.slice( 1 )
			);
		$row.find( 'td' ).eq( 2 ).html( description );

		// Update button data attributes
		$row.find( '.edit-tag-btn' )
			.data( 'tag-label', tagData.label )
			.data( 'tag-description', tagData.description )
			.data( 'tag-category', tagData.category )
			.attr( 'data-tag-label', tagData.label )
			.attr( 'data-tag-description', tagData.description )
			.attr( 'data-tag-category', tagData.category );

		$row.find( '.delete-tag-btn' )
			.data( 'tag-label', tagData.label )
			.attr( 'data-tag-label', tagData.label );

		// Add visual feedback using Bootstrap utility classes
		$row.addClass( 'table-success' );
		setTimeout( function () {
			$row.removeClass( 'table-success' );
		}, 2000 );
	}

	/**
	 * Remove tag row from table
	 *
	 * Removes a row from the tags table with animation.
	 *
	 * @param {string} tagKey - Tag key to remove
	 */
	function removeTagRowFromTable( tagKey ) {
		const $row = $( `.media-tag-row[data-tag-key="${ tagKey }"]` );

		$row.fadeOut( 300, function () {
			$( this ).remove();
			updateTagCount( -1 );
		} );
	}

	/**
	 * Update tag count in header
	 *
	 * Updates the badge showing total number of tags.
	 *
	 * @param {number} change - Change in count (+1 or -1)
	 */
	function updateTagCount( change ) {
		const $badge = $( '.card-header .badge' );
		if ( $badge.length ) {
			const currentText = $badge.text();
			const currentCount =
				parseInt( currentText.match( /\d+/ )[ 0 ] ) || 0;
			const newCount = Math.max( 0, currentCount + change );
			$badge.text( `${ newCount } tags` );
		}
	}

	/**
	 * Handle empty state updates
	 *
	 * Shows or hides empty state based on number of tags.
	 */
	function handleEmptyStateUpdate() {
		const rowCount = $( '#media-tags-list tr' ).length;
		const $tableContainer = $( '.table-responsive' );
		const $emptyState = $( '.text-center.py-5' );

		if (
			rowCount === 0 &&
			$tableContainer.length &&
			! $emptyState.length
		) {
			// Show empty state
			$tableContainer.hide();
			$tableContainer.parent().append( createEmptyStateHtml() );
		} else if ( rowCount > 0 && $emptyState.length ) {
			// Hide empty state, show table
			$emptyState.remove();
			$tableContainer.show();
		}
	}

	/**
	 * Create empty state HTML
	 *
	 * Generates HTML for empty state display.
	 *
	 * @return {string} Empty state HTML
	 */
	function createEmptyStateHtml() {
		return `
			<div class="text-center py-5">
				<i class="dashicons dashicons-tag" style="font-size: 64px; color: #dee2e6; margin-bottom: 16px;"></i>
				<h4 class="text-muted">No media tags found</h4>
				<p class="text-muted mb-4">Create your first media tag to get started organizing your product media.</p>
				<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTagModal">
					<i class="dashicons dashicons-plus-alt2"></i>
					Create First Tag
				</button>
			</div>
		`;
	}

	/**
	 * Create delete confirmation modal
	 *
	 * Generates HTML for delete confirmation modal.
	 *
	 * @return {jQuery} Modal element
	 */
	function createDeleteConfirmModal() {
		const modalHtml = `
			<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title" id="deleteConfirmModalLabel">
								<i class="dashicons dashicons-warning text-warning"></i>
								Confirm Deletion
							</h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">
							<p></p>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
							<button type="button" class="btn btn-danger">Delete Tag</button>
						</div>
					</div>
				</div>
			</div>
		`;

		return $( modalHtml );
	}

	/**
	 * Set button loading state
	 *
	 * Shows/hides loading spinner on buttons.
	 *
	 * @param {jQuery}  $button     - Button element
	 * @param {boolean} isLoading   - Whether to show loading state
	 * @param {string}  loadingText - Optional loading text
	 */
	function setButtonLoading(
		$button,
		isLoading,
		loadingText = 'Loading...'
	) {
		if ( isLoading ) {
			$button.data( 'original-html', $button.html() );
			$button.html(
				`<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${ loadingText }`
			);
			$button.prop( 'disabled', true );
		} else {
			const originalHtml = $button.data( 'original-html' );
			if ( originalHtml ) {
				$button.html( originalHtml );
			}
			$button.prop( 'disabled', false );
		}
	}

	/**
	 * Validate add form
	 *
	 * Validates all fields in the add tag form.
	 *
	 * @return {boolean} True if form is valid
	 */
	function validateAddForm() {
		let isValid = true;
		const $form = $( '#add-media-tag-form' );

		// Validate tag key
		const $tagKey = $form.find( '#tag_key' );
		if ( ! validateTagKey( $tagKey ) || $tagKey.val().length === 0 ) {
			isValid = false;
		}

		// Validate tag label
		const $tagLabel = $form.find( '#tag_label' );
		if ( $tagLabel.val().trim().length === 0 ) {
			$tagLabel.addClass( 'is-invalid' );
			isValid = false;
		} else {
			$tagLabel.removeClass( 'is-invalid' ).addClass( 'is-valid' );
		}

		return isValid;
	}

	/**
	 * Reset add form
	 *
	 * Clears and resets the add tag form with auto-generation state.
	 */
	function resetAddForm() {
		const $form = $( '#add-media-tag-form' );
		$form[ 0 ].reset();
		$form
			.find( '.is-valid, .is-invalid' )
			.removeClass( 'is-valid is-invalid' );
		$form.find( '.invalid-feedback' ).remove();

		// Reset auto-generation state using Bootstrap classes
		$form.find( '#tag_key' ).removeClass( 'bg-light' );
		$form.find( '#tag_label' ).removeData( 'previous-value' );

		// Reset form text to default
		$form
			.find( '#tag_key' )
			.siblings( '.form-text' )
			.removeClass( 'text-primary fw-medium' )
			.html(
				'Auto-generated from label. Lowercase letters, numbers, and underscores only.'
			);

		// Focus on the label field (primary input)
		setTimeout( function () {
			$form.find( '#tag_label' ).focus();
		}, 300 );
	}

	/**
	 * Reset edit form
	 *
	 * Clears and resets the edit tag form.
	 */
	function resetEditForm() {
		const $form = $( '#edit-media-tag-form' );
		$form[ 0 ].reset();
		$form
			.find( '.is-valid, .is-invalid' )
			.removeClass( 'is-valid is-invalid' );
	}

	/**
	 * Show alert message
	 *
	 * Displays a Bootstrap alert with auto-dismiss functionality.
	 *
	 * @param {string} message - Alert message
	 * @param {string} type    - Alert type (success, danger, warning, info)
	 */
	function showAlert( message, type ) {
		// Remove existing alerts
		$( '.alert' ).remove();

		const alertHtml = `
			<div class="alert alert-${ type } alert-dismissible fade show" role="alert">
				${ escapeHtml( message ) }
				<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>
		`;

		// Insert alert at top of media tags content
		$( '#media-tags .d-flex' ).first().after( alertHtml );

		// Auto-dismiss success alerts
		if ( type === 'success' ) {
			setTimeout( function () {
				$( `.alert-${ type }` ).fadeOut( 300, function () {
					$( this ).remove();
				} );
			}, 5000 );
		}

		// Scroll to top of tab content to ensure alert is visible
		$( '#media-tags' )[ 0 ].scrollIntoView( {
			behavior: 'smooth',
			block: 'start',
		} );
	}

	/**
	 * Escape HTML characters
	 *
	 * Prevents XSS by escaping HTML characters in user input.
	 *
	 * @param {string} text - Text to escape
	 *
	 * @return {string} Escaped text
	 */
	function escapeHtml( text ) {
		if ( typeof text !== 'string' ) {
			return '';
		}

		const div = document.createElement( 'div' );
		div.textContent = text;
		return div.innerHTML;
	}
} )( jQuery );
