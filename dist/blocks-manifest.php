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
			),
			'interactivity' => true
		),
		'editorScript' => 'file:./index.js',
		'viewScriptModule' => 'file:./view.js',
		'editorStyle' => 'peaches-ecwid-category-editor',
		'attributes' => array(
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
			),
			'interactivity' => true
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => array(
			'file:./index.css'
		),
		'style' => 'file:./style-index.css',
		'viewScriptModule' => 'file:./view.js',
		'render' => 'file:./render.php',
		'attributes' => array(
			'id' => array(
				'type' => 'integer'
			),
			'showAddToCart' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showCardHoverShadow' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showCardHoverJump' => array(
				'type' => 'boolean',
				'default' => true
			),
			'hoverMediaTag' => array(
				'type' => 'string',
				'default' => ''
			),
			'computedClassName' => array(
				'type' => 'string',
				'default' => ''
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
		'title' => 'Bootstrap ECWID Product Add to cart',
		'category' => 'peaches-bootstrap',
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
			'html' => false,
			'layout' => false,
			'color' => array(
				'overlay' => true
			),
			'interactivity' => true
		),
		'usesContext' => array(
			'peaches/testProductData'
		),
		'viewScriptModule' => 'file:./view.js',
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'attributes' => array(
			'selectedProductId' => array(
				'type' => 'number'
			),
			'buttonThemeColor' => array(
				'type' => 'string',
				'default' => 'primary'
			),
			'buttonSize' => array(
				'type' => 'string',
				'default' => 'md'
			),
			'buttonText' => array(
				'type' => 'string',
				'default' => 'Add to Cart'
			),
			'outOfStockText' => array(
				'type' => 'string',
				'default' => 'Out of Stock'
			),
			'translations' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'allowOutOfStockPurchase' => array(
				'type' => 'boolean',
				'default' => false
			),
			'showQuantitySelector' => array(
				'type' => 'boolean',
				'default' => true
			),
			'buttonBootstrapSettings' => array(
				'type' => 'object',
				'default' => array(
					'xs' => array(
						
					),
					'sm' => array(
						
					),
					'md' => array(
						
					),
					'lg' => array(
						
					),
					'xl' => array(
						
					),
					'xxl' => array(
						
					),
					'border' => array(
						'type_location' => array(
							
						),
						'rounded' => 0
					),
					'sizes' => array(
						
					)
				)
			),
			'inputBootstrapSettings' => array(
				'type' => 'object',
				'default' => array(
					'xs' => array(
						
					),
					'sm' => array(
						
					),
					'md' => array(
						
					),
					'lg' => array(
						
					),
					'xl' => array(
						
					),
					'xxl' => array(
						
					),
					'colors' => array(
						'background' => 'light'
					),
					'sizes' => array(
						
					)
				)
			),
			'xs' => array(
				'type' => 'object',
				'default' => array(
					'display' => 'inline-flex'
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
			),
			'border' => array(
				'type' => 'object',
				'default' => array(
					'type_location' => array(
						
					),
					'color' => 'light',
					'rounded' => 0
				)
			),
			'placements' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'spacings' => array(
				'type' => 'object',
				'default' => array(
					'gap' => 2
				)
			),
			'sizes' => array(
				'type' => 'object',
				'default' => array(
					
				)
			)
		)
	),
	'ecwid-product-description' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'peaches/ecwid-product-description',
		'version' => '0.1.0',
		'title' => 'Bootstrap ECWID Product Description',
		'category' => 'peaches-bootstrap',
		'description' => 'Display product descriptions with Bootstrap styling.',
		'keywords' => array(
			'bootstrap',
			'product',
			'description',
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
		'usesContext' => array(
			'peaches/testProductData'
		),
		'attributes' => array(
			'selectedProductId' => array(
				'type' => 'number'
			),
			'descriptionType' => array(
				'type' => 'string',
				'default' => 'usage'
			),
			'displayTitle' => array(
				'type' => 'boolean',
				'default' => true
			),
			'customTitle' => array(
				'type' => 'string',
				'default' => ''
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
		),
		'viewScriptModule' => 'file:./view.js',
		'editorScript' => 'file:./index.js'
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
			'html' => false,
			'layout' => false,
			'color' => array(
				'overlay' => true
			),
			'interactivity' => true
		),
		'providesContext' => array(
			'peaches/testProductData' => 'testProductData'
		),
		'editorScript' => 'file:./index.js',
		'render' => 'file:./render.php',
		'attributes' => array(
			'testProductId' => array(
				'type' => 'number',
				'default' => 0
			),
			'testProductData' => array(
				'type' => 'object',
				'default' => null
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
	'ecwid-product-field' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'peaches/ecwid-product-field',
		'version' => '0.1.0',
		'title' => 'Bootstrap ECWID Product Field',
		'category' => 'peaches-bootstrap',
		'description' => 'Display product field for ECWID products with Bootstrap styling.',
		'keywords' => array(
			'bootstrap',
			'product',
			'field',
			'peaches',
			'ecwid',
			'template'
		),
		'textdomain' => 'peaches',
		'supports' => array(
			'html' => false,
			'layout' => false,
			'interactivity' => true
		),
		'usesContext' => array(
			'peaches/testProductData'
		),
		'attributes' => array(
			'selectedProductId' => array(
				'type' => 'number'
			),
			'fieldType' => array(
				'type' => 'string',
				'default' => 'title'
			),
			'htmlTag' => array(
				'type' => 'string',
				'default' => 'p'
			),
			'customFieldKey' => array(
				'type' => 'string',
				'default' => ''
			),
			'lineType' => array(
				'type' => 'string',
				'default' => ''
			),
			'displayMode' => array(
				'type' => 'string',
				'default' => 'badges',
				'enum' => array(
					'badges',
					'list',
					'inline'
				)
			),
			'showLineDescriptions' => array(
				'type' => 'boolean',
				'default' => false
			),
			'descriptionSeparator' => array(
				'type' => 'string',
				'default' => ' | '
			),
			'maxLines' => array(
				'type' => 'number',
				'default' => 0
			),
			'lineSeparator' => array(
				'type' => 'string',
				'default' => ', '
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
			),
			'border' => array(
				'type' => 'object',
				'default' => array(
					'type_location' => array(
						
					)
				)
			),
			'colors' => array(
				'type' => 'object',
				'default' => array(
					
				)
			)
		),
		'viewScriptModule' => 'file:./view.js',
		'editorScript' => 'file:./index.js'
	),
	'ecwid-product-gallery-image' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'peaches/ecwid-product-gallery-image',
		'version' => '0.1.0',
		'title' => 'Bootstrap ECWID Product Gallery Image',
		'category' => 'peaches-bootstrap',
		'description' => 'Display a specific product media item based on media tag selection with Bootstrap styling and media type-specific controls.',
		'keywords' => array(
			'bootstrap',
			'product',
			'media',
			'gallery',
			'image',
			'video',
			'audio',
			'document',
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
		'usesContext' => array(
			'peaches/testProductData'
		),
		'attributes' => array(
			'selectedProductId' => array(
				'type' => 'number'
			),
			'selectedMediaTag' => array(
				'type' => 'string',
				'default' => ''
			),
			'hideIfMissing' => array(
				'type' => 'boolean',
				'default' => true
			),
			'fallbackType' => array(
				'type' => 'string',
				'default' => 'none'
			),
			'fallbackTagKey' => array(
				'type' => 'string',
				'default' => ''
			),
			'fallbackMediaId' => array(
				'type' => 'number',
				'default' => 0
			),
			'videoAutoplay' => array(
				'type' => 'boolean',
				'default' => false
			),
			'videoMuted' => array(
				'type' => 'boolean',
				'default' => false
			),
			'videoLoop' => array(
				'type' => 'boolean',
				'default' => false
			),
			'videoControls' => array(
				'type' => 'boolean',
				'default' => true
			),
			'audioAutoplay' => array(
				'type' => 'boolean',
				'default' => false
			),
			'audioLoop' => array(
				'type' => 'boolean',
				'default' => false
			),
			'audioControls' => array(
				'type' => 'boolean',
				'default' => true
			),
			'border' => array(
				'type' => 'object',
				'default' => array(
					'type_location' => array(
						
					)
				)
			),
			'placements' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'sizes' => array(
				'type' => 'object',
				'default' => array(
					
				)
			)
		),
		'viewScriptModule' => 'file:./view.js',
		'editorScript' => 'file:./index.js'
	),
	'ecwid-product-images' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'peaches/ecwid-product-images',
		'version' => '0.1.0',
		'title' => 'Bootstrap ECWID Product Images',
		'category' => 'peaches-bootstrap',
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
		'usesContext' => array(
			'peaches/testProductData'
		),
		'attributes' => array(
			'selectedProductId' => array(
				'type' => 'number'
			),
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
		'usesContext' => array(
			'peaches/testProductData'
		),
		'attributes' => array(
			'selectedProductId' => array(
				'type' => 'number'
			),
			'startOpened' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'viewScriptModule' => 'file:./view.js',
		'editorScript' => 'file:./index.js'
	),
	'ecwid-product-related-products' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'peaches/ecwid-product-related-products',
		'version' => '0.1.0',
		'title' => 'Bootstrap ECWID Product Related Products',
		'category' => 'peaches-bootstrap',
		'description' => 'Display related products for ECWID products using existing product blocks.',
		'keywords' => array(
			'bootstrap',
			'product',
			'related',
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
		'usesContext' => array(
			'peaches/testProductData'
		),
		'attributes' => array(
			'selectedProductId' => array(
				'type' => 'number'
			),
			'showTitle' => array(
				'type' => 'boolean',
				'default' => true
			),
			'customTitle' => array(
				'type' => 'string',
				'default' => ''
			),
			'maxProducts' => array(
				'type' => 'number',
				'default' => 4
			),
			'showAddToCart' => array(
				'type' => 'boolean',
				'default' => true
			),
			'computedClassName' => array(
				'type' => 'string',
				'default' => ''
			),
			'isInCarousel' => array(
				'type' => 'boolean',
				'default' => false
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
		),
		'render' => 'file:./render.php',
		'editorScript' => 'file:./index.js'
	)
);
