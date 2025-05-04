<?php
/**
 * REST API endpoint for product ingredients
 */
class Peaches_Ingredients_API {

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route('peaches/v1', '/product-ingredients/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_product_ingredients'),
            'permission_callback' => '__return_true', // Public endpoint
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
    }

    /**
     * Get ingredients for a specific product
     */
	public function get_product_ingredients($request) {
		$product_id = $request['id'];

		// Get ingredients using the existing helper function
		$raw_ingredients = peaches_get_product_ingredients($product_id);

		// Check if we found ingredients
		if (empty($raw_ingredients)) {
			return new WP_REST_Response(array(
				'status' => 404,
				'message' => __('No ingredients found for this product', 'ecwid-shopping-cart'),
				'ingredients' => array()
			), 404);
		}

		// Use the unified language function
		$current_lang = peaches_get_current_language();

		// Process ingredients with multilingual support
		$processed_ingredients = array();
		foreach ($raw_ingredients as $ingredient) {
			if (isset($ingredient['type']) && $ingredient['type'] === 'master' && isset($ingredient['master_id'])) {
				// Get master ingredient data
				$master_post = get_post($ingredient['master_id']);

				if ($master_post) {
					// Get translated name
					$name_key = $current_lang && $current_lang !== 'en' ?
						'_ingredient_name_' . $current_lang :
						null;

					$name = $name_key ? get_post_meta($master_post->ID, $name_key, true) : '';

					// Fallback to default name if translation not found
					if (empty($name)) {
						$name = $master_post->post_title;
					}

					// Get translated description
					$description_key = $current_lang && $current_lang !== 'en' ?
						'_ingredient_description_' . $current_lang :
						'_ingredient_description';

					$description = get_post_meta($master_post->ID, $description_key, true);

					// Fallback to default language if translation not found
					if (empty($description) && $current_lang && $current_lang !== 'en') {
						$description = get_post_meta($master_post->ID, '_ingredient_description', true);
					}

					$processed_ingredients[] = array(
						'name' => $name,
						'description' => $description
					);
				}
			} else {
				// Handle custom ingredients (legacy support)
				$name = $ingredient['name'];
				$description = $ingredient['description'];

				$processed_ingredients[] = array(
					'name' => $name,
					'description' => $description
				);
			}
		}

		return new WP_REST_Response(array(
			'status' => 200,
			'ingredients' => $processed_ingredients,
			'language' => $current_lang
		), 200);
	}
}

// Initialize the API class
new Peaches_Ingredients_API();

/**
 * Helper function to register strings for translation with Polylang/WPML
 */
function peaches_register_ingredient_strings_for_translation($post_id) {
    $ingredients = get_post_meta($post_id, '_product_ingredients', true);

    if (is_array($ingredients)) {
        foreach ($ingredients as $ingredient) {
            // Check if this is a master ingredient
            if (isset($ingredient['type']) && $ingredient['type'] === 'master' && isset($ingredient['master_id'])) {
                // Get master ingredient data
                $master_post = get_post($ingredient['master_id']);
                if ($master_post) {
                    $name = $master_post->post_title;
                    $description = get_post_meta($master_post->ID, '_ingredient_description', true);

                    // Register for translation
                    if ($name) {
                        if (function_exists('pll_register_string')) {
                            pll_register_string('ingredient_name_' . md5($name), $name, 'Ecwid Shopping Cart', false);
                        }
                        if (function_exists('wpml_register_single_string')) {
                            wpml_register_single_string('ecwid-shopping-cart', 'ingredient_name_' . md5($name), $name);
                        }
                    }

                    if ($description) {
                        if (function_exists('pll_register_string')) {
                            pll_register_string('ingredient_desc_' . md5($description), $description, 'Ecwid Shopping Cart', false);
                        }
                        if (function_exists('wpml_register_single_string')) {
                            wpml_register_single_string('ecwid-shopping-cart', 'ingredient_desc_' . md5($description), $description);
                        }
                    }
                }
            } elseif (isset($ingredient['name']) && isset($ingredient['description'])) {
                // This is a custom ingredient with the old structure
                if ($ingredient['name']) {
                    if (function_exists('pll_register_string')) {
                        pll_register_string('ingredient_name_' . md5($ingredient['name']), $ingredient['name'], 'Ecwid Shopping Cart', false);
                    }
                    if (function_exists('wpml_register_single_string')) {
                        wpml_register_single_string('ecwid-shopping-cart', 'ingredient_name_' . md5($ingredient['name']), $ingredient['name']);
                    }
                }

                if ($ingredient['description']) {
                    if (function_exists('pll_register_string')) {
                        pll_register_string('ingredient_desc_' . md5($ingredient['description']), $ingredient['description'], 'Ecwid Shopping Cart', false);
                    }
                    if (function_exists('wpml_register_single_string')) {
                        wpml_register_single_string('ecwid-shopping-cart', 'ingredient_desc_' . md5($ingredient['description']), $ingredient['description']);
                    }
                }
            }
        }
    }
}

// Hook to register strings when saving product ingredients
add_action('save_post_product_ingredients', 'peaches_register_ingredient_strings_for_translation', 10, 1);
