(function($) {
    $(document).ready(function() {
        // Check if this is the product_ingredients post type
        if (typeof pagenow !== 'undefined' && pagenow !== 'product_ingredients') {
            return;
        }

        // Initialize only once
        initializeProductIngredients();

        function initializeProductIngredients() {
            let ingredientIndex = $('.ingredient-item').length;

            // Simple product search functionality
            let searchTimeout;

            $('#product-search').on('keyup', function(e) {
                // Prevent form submission on Enter key
                if (e.key === 'Enter') {
                    e.preventDefault();
                    return false;
                }

                clearTimeout(searchTimeout);
                const query = $(this).val();

                if (query.length >= 2) {
                    searchTimeout = setTimeout(function() {
                        searchProducts(query);
                    }, 300);
                } else {
                    $('#product-search-results').hide();
                }
            });

            // Prevent form submission on Enter key for the search input
            $('#product-search').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    return false;
                }
            });

            // Add new ingredient button
            $('#add-ingredient').on('click', function() {
                const template = `
                    <div class="ingredient-item postbox" data-index="${ingredientIndex}">
                        <div class="postbox-header">
                            <h2 class="hndle ui-sortable-handle">
                                <span>New Ingredient</span>
                            </h2>
                            <div class="handle-actions hide-if-no-js">
                                <button type="button" class="handlediv button-link remove-ingredient" aria-expanded="true">
                                    <span class="screen-reader-text">Remove Ingredient</span>
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>
                        <div class="inside">
                            <p>
                                <label>Ingredient Type:</label><br>
                                <label>
                                    <input type="radio" name="ingredient_type[${ingredientIndex}]" value="master" class="ingredient-type-radio">
                                    From Library
                                </label>
                                <label>
                                    <input type="radio" name="ingredient_type[${ingredientIndex}]" value="custom" checked class="ingredient-type-radio">
                                    Custom
                                </label>
                            </p>

                            <div class="master-ingredient-selector" style="display:none;">
                                <p>
                                    <label for="master_ingredient_${ingredientIndex}">Select Ingredient:</label>
                                    <select id="master_ingredient_${ingredientIndex}" name="master_ingredient_id[]" class="widefat master-ingredient-select">
                                        ${getMasterIngredientsOptions()}
                                    </select>
                                </p>
                            </div>

                            <div class="custom-ingredient-fields">
                                <p>
                                    <label for="ingredient_name_${ingredientIndex}">Ingredient Name:</label>
                                    <input type="text" id="ingredient_name_${ingredientIndex}" name="ingredient_name[]" value="" class="widefat ingredient-name-field">
                                </p>
                                <p>
                                    <label for="ingredient_description_${ingredientIndex}">Description:</label>
                                    <textarea id="ingredient_description_${ingredientIndex}" name="ingredient_description[]" rows="4" class="widefat"></textarea>
                                </p>
                            </div>
                        </div>
                    </div>
                `;

                $('#ingredients-container').append(template);
                ingredientIndex++;
            });

            // Handle ingredient type switching - use event delegation
            $(document).on('change', '.ingredient-type-radio', function() {
                const $item = $(this).closest('.ingredient-item');
                const isMaster = $(this).val() === 'master';

                $item.find('.master-ingredient-selector').toggle(isMaster);
                $item.find('.custom-ingredient-fields').toggle(!isMaster);

                if (isMaster) {
                    // Clear custom fields
                    $item.find('.ingredient-name-field').val('');
                    $item.find('textarea[name="ingredient_description[]"]').val('');
                } else {
                    // Clear master selection
                    $item.find('.master-ingredient-select').val('');
                    $item.find('.hndle span').text('New Ingredient');
                }
            });

            // Update ingredient title when master ingredient is selected
            $(document).on('change', '.master-ingredient-select', function() {
                const $item = $(this).closest('.ingredient-item');
                const selectedText = $(this).find('option:selected').text();
                $item.find('.hndle span').text(selectedText || 'New Ingredient');
            });

            // Update ingredient title on custom name change
            $(document).on('input', '.ingredient-name-field', function() {
                const $input = $(this);
                const $title = $input.closest('.ingredient-item').find('.hndle span');
                const newName = $input.val();
                $title.text(newName || 'New Ingredient');
            });

            // Remove ingredient button
            $(document).on('click', '.remove-ingredient', function() {
                $(this).closest('.ingredient-item').remove();
            });

            // Initialize sortable
            if ($('#ingredients-container').length) {
                $('#ingredients-container').sortable({
                    items: '.ingredient-item',
                    cursor: 'move',
                    opacity: 0.65,
                    handle: '.hndle'
                });
            }
        }

        function searchProducts(query) {
            // Show loading indicator
            $('#product-search-results').html('<div style="padding: 10px;">Searching...</div>').show();

            $.ajax({
                url: window.ajaxurl,
                method: 'POST',
                data: {
                    action: 'search_ecwid_products',
                    nonce: EcwidIngredientsParams.searchNonce,
                    query: query
                },
                success: function(response) {
                    if (response.success && response.data.products) {
                        displaySearchResults(response.data.products);
                    } else {
                        $('#product-search-results').html('<div style="padding: 10px; color: red;">Error: ' +
                            (response.data.message || 'Unable to search products') + '</div>');
                    }
                },
                error: function() {
                    $('#product-search-results').html('<div style="padding: 10px; color: red;">Error searching products</div>');
                }
            });
        }

		function displaySearchResults(products) {
			const $results = $('#product-search-results');
			$results.empty();

			if (products.length > 0) {
				products.forEach(function(product) {
					const $item = $('<div>')
						.addClass('product-search-item')
						.css({
							padding: '10px',
							borderBottom: '1px solid #eee',
							cursor: 'pointer'
						});

					// Replace .hover() with .on()
					$item.on('mouseenter', function() {
						$(this).css('background', '#f5f5f5');
					}).on('mouseleave', function() {
						$(this).css('background', 'white');
					});

					// Replace .click() with .on()
					$item.on('click', function() {
						selectProduct(product);
					});

					const $content = $('<div>')
						.html('<strong>' + product.name + '</strong><br>' +
							  'ID: ' + product.id +
							  (product.sku ? ' | SKU: ' + product.sku : ''));

					if (product.price) {
						$content.append('<br>Price: €' + product.price);
					}

					$item.append($content);
					$results.append($item);
				});
			} else {
				$results.html('<div style="padding: 10px;">No products found</div>');
			}

			$results.show();
		}

		function selectProduct(product) {
			// Fill in the product ID and SKU
			$('#ecwid_product_id').val(product.id);
			$('#ecwid_product_sku').val(product.sku || '');
			$('#product-search-results').hide();

			// Auto-fill the post title with the product name
			$('#title').val(product.name);

			// Update the title prompt if it exists
			if ($('#title-prompt-text').length) {
				$('#title-prompt-text').hide();
			}

			// Show success message
			$('<div class="notice notice-success is-dismissible"><p>Product selected successfully!</p></div>')
				.insertAfter('h1.wp-heading-inline');
		}

        // Helper function to get master ingredients options
        function getMasterIngredientsOptions() {
            let options = '<option value="">Select an ingredient...</option>';

            // Check if we have localized data
            if (typeof EcwidIngredientsParams !== 'undefined' && EcwidIngredientsParams.masterIngredients) {
                EcwidIngredientsParams.masterIngredients.forEach(function(ingredient) {
                    options += `<option value="${ingredient.id}">${ingredient.title}</option>`;
                });
            } else {
                // Try to get options from existing selectors
                const $firstSelect = $('.master-ingredient-select').first();
                if ($firstSelect.length) {
                    $firstSelect.find('option').each(function() {
                        options += `<option value="${$(this).val()}">${$(this).text()}</option>`;
                    });
                }
            }

            return options;
        }
    });
})(jQuery);
