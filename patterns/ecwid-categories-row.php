<?php
/**
 * Title: Categories row
 * Description: A row with 2 categories shown per row on mobile and 4 categories per row on tablet and higher resolutions.
 * Slug: peaches-ecwid/ecwid-categories-row
 * Categories: peaches-ecwid, peaches-bootstrap
 * Viewport Width: 400
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

?>

<!-- wp:peaches/ecwid-category {"xs":{"rowCols":2,"marginY":"5","gapXY":"3","justifyContent":"center"},"md":{"rowCols":3},"lg":{"rowCols":4}} -->
<div class="wp-block-peaches-ecwid-category row row-cols-2 row-cols-md-3 row-cols-lg-4 justify-content-center my-5" data-wp-interactive="peaches-ecwid-category" data-wp-context="{ &quot;isLoading&quot;: true, &quot;categories&quot;: [] }" data-wp-init="callbacks.initCategories">
	<template data-wp-each--category="context.categories">
		<div class="col" data-wp-interactive="peaches-ecwid-category" data-wp-context="{ &quot;categoryId&quot;: context.category.id }" data-wp-on--click="actions.navigateToCategory">
			<div class="card h-100 border-0">
				<a class="ratio ratio-1x1" data-wp-bind--href="context.category.url">
					<img class="card-img-top" data-wp-bind--src="context.category.thumbnailUrl" data-wp-bind--alt="context.category.name" alt="Category image"/>
				</a>
				<div class="card-body p-2 p-md-3">
					<h5 class="card-title" data-wp-text="context.category.name"></h5>
				</div>
			</div>
		</div>
	</template>
	<div data-wp-bind--hidden="!context.isLoading" class="text-center my-3">
		<div class="spinner-border text-primary" role="status">
			<span class="visually-hidden">Loading categories...</span>
		</div>
	</div>
</div>
<!-- /wp:peaches/ecwid-category -->
