<?php
// This file is generated. Do not modify it manually.
return array(
	'ecwid-category' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'peaches/ecwid-category',
		'version' => '0.1.0',
		'title' => 'Bootstrap ECWID category',
		'category' => 'peaches-bootstrap',
		'description' => 'A ECWID category bootstrap themed block.',
		'keywords' => array(
			'bootstrap',
			'grid',
			'peaches',
			'category',
			'ecwid'
		),
		'textdomain' => 'peaches',
		'supports' => array(
			'html' => false,
			'layout' => false,
			'color' => array(
				'overlay' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'peaches-ecwid-category-editor',
		'attributes' => array(
			'classes' => array(
				'type' => 'string'
			),
			'xs' => array(
				'type' => 'object',
				'default' => array(
					'rowCols' => 4
				)
			),
			'sm' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'md' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'lg' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'xl' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'xxl' => array(
				'type' => 'object',
				'default' => array(
					
				)
			)
		)
	),
	'ecwid-product' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'peaches/ecwid-product',
		'version' => '0.1.0',
		'title' => 'Bootstrap ECWID product',
		'category' => 'peaches-bootstrap',
		'description' => 'A ECWID product bootstrap themed block.',
		'keywords' => array(
			'bootstrap',
			'grid',
			'peaches',
			'product',
			'ecwid'
		),
		'textdomain' => 'peaches',
		'supports' => array(
			'html' => false,
			'layout' => false,
			'color' => array(
				'overlay' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'attributes' => array(
			'classes' => array(
				'type' => 'string'
			),
			'id' => array(
				'type' => 'integer'
			),
			'xs' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'sm' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'md' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'lg' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'xl' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'xxl' => array(
				'type' => 'object',
				'default' => array(
					
				)
			)
		)
	),
	'ecwid-product-add-to-cart' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'peaches/ecwid-product-add-to-cart',
		'version' => '0.1.0',
		'title' => 'Bootstrap ECWID Product Add to cart Template',
		'category' => 'peaches-bootstrap',
		'ancestor' => array(
			'peaches/ecwid-product-detail'
		),
		'description' => 'A dynamic ECWID product add to cart template with Bootstrap styling.',
		'keywords' => array(
			'bootstrap',
			'product',
			'detail',
			'peaches',
			'ecwid',
			'template',
			'cart'
		),
		'textdomain' => 'peaches',
		'supports' => array(
			'interactivity' => true
		),
		'viewScriptModule' => 'file:./view.js',
		'editorScript' => 'file:./index.js',
		'attributes' => array(
			'xs' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'sm' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'md' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'lg' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'xl' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'xxl' => array(
				'type' => 'object',
				'default' => array(
					
				)
			)
		)
	),
	'ecwid-product-detail' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'peaches/ecwid-product-detail',
		'version' => '0.1.0',
		'title' => 'Bootstrap ECWID Product Detail Template',
		'category' => 'peaches-bootstrap',
		'description' => 'A dynamic ECWID product detail template with Bootstrap styling.',
		'keywords' => array(
			'bootstrap',
			'product',
			'detail',
			'peaches',
			'ecwid',
			'template'
		),
		'textdomain' => 'peaches',
		'supports' => array(
			'interactivity' => true
		),
		'editorScript' => 'file:./index.js',
		'render' => 'file:./render.php',
		'attributes' => array(
			'xs' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'sm' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'md' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'lg' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'xl' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'xxl' => array(
				'type' => 'object',
				'default' => array(
					
				)
			)
		)
	),
	'ecwid-product-images' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'peaches/ecwid-product-images',
		'version' => '0.1.0',
		'title' => 'Bootstrap ECWID Product Images',
		'category' => 'peaches-bootstrap',
		'ancestor' => array(
			'peaches/ecwid-product-detail'
		),
		'description' => 'Display product images for ECWID products with Bootstrap styling.',
		'keywords' => array(
			'bootstrap',
			'product',
			'images',
			'gallery',
			'peaches',
			'ecwid',
			'template'
		),
		'textdomain' => 'peaches',
		'supports' => array(
			'html' => false,
			'layout' => false,
			'color' => array(
				'overlay' => true
			),
			'interactivity' => true
		),
		'attributes' => array(
			'imageSize' => array(
				'type' => 'string',
				'default' => 'medium'
			),
			'showThumbnails' => array(
				'type' => 'boolean',
				'default' => true
			),
			'maxThumbnails' => array(
				'type' => 'number',
				'default' => 5
			)
		),
		'viewScriptModule' => 'file:./view.js',
		'editorScript' => 'file:./index.js'
	),
	'ecwid-product-ingredients' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'peaches/ecwid-product-ingredients',
		'version' => '0.1.0',
		'title' => 'Bootstrap ECWID Product Ingredients',
		'category' => 'peaches-bootstrap',
		'ancestor' => array(
			'peaches/ecwid-product-detail'
		),
		'description' => 'Display product ingredients for ECWID products with Bootstrap accordion styling.',
		'keywords' => array(
			'bootstrap',
			'product',
			'ingredients',
			'accordion',
			'peaches',
			'ecwid'
		),
		'textdomain' => 'peaches',
		'supports' => array(
			'html' => false,
			'layout' => false,
			'color' => array(
				'overlay' => true
			),
			'interactivity' => true
		),
		'attributes' => array(
			'startOpened' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'viewScriptModule' => 'file:./view.js',
		'editorScript' => 'file:./index.js'
	)
);
