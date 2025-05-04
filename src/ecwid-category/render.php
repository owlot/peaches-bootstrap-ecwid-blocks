<?php

function peaches_ecwid_category_render($attr, $content) {
	$api  = new Ecwid_Api_V3();
	$categories = $api->get_categories( array() );

	if ( $categories && $categories->total == 0 ) {
		return 'div class="bg-warning">No categories found</div>';
	}

	$classes = join(' ', [
		(array_key_exists('classes', $attr) ? $attr['classes'] : ''),
		(array_key_exists('className', $attr) ? $attr['className'] : '')
	]);

	$category_html = '<div class="ecwid-category '.$classes.'">'; //'<pre>'.print_r($categories,true).'</pre>';

	foreach( (array) $categories->items as $category) {
		$store_page_id = get_option('ecwid_store_page_id');
		$store_url = $store_page_id ? get_permalink($store_page_id) : home_url();
		$category_url = add_query_arg('category', $category->id, $store_url);

		$category_html = $category_html.'<div class="col">'
		.'<div class="card h-100 border-0 ">'
			.'<a class="ratio ratio-1x1" href="'.esc_url($category_url).'">'
				.'<img class="card-img-top " src="'.esc_url( $category->thumbnailUrl ).'"/>'
				.'<div class="card-img-top " itemprop="image" data-force-image="true"></div>'
			.'</a>'
			.'<div class="card-body p-2 p-md-3">'
				.'<h5 class="card-title">'.$category->name.'</h5>'
			.'</div>'
		.'</div></div>';
	}
	return $category_html.'</div>';
}
