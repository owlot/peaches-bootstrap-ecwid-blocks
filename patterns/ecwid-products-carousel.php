<?php
/**
 * Title: Products Carousel
 * Description: A carousel with 2 products shown on mobile, 3 products on tablet and 4 products on higher resolutions. More products can be added.
 * Slug: peaches-ecwid/ecwid-products-carousel
 * Categories: peaches-ecwid, peaches-bootstrap
 * Viewport Width: 400
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

?>
<!-- wp:peaches/bs-carousel {"initialized":true,"carouselId":"carousel-hv3uh6ma","xs":{"carousel":[],"rowCols":2,"gapX":"4","gapY":"5","gutterX":null,"marginT":5},"sm":{"carousel":[],"rowCols":3},"lg":{"carousel":[],"rowCols":4},"carousel":{"dots":true,"arrows":false,"infinite":true,"centerMode":true,"speed":300,"scrollOnDrag":true,"autoplay":false,"autoplaySpeed":3000,"smoothScrolling":false,"smoothScrollSpeed":"normal","smoothCycleDuration":12000,"initOnOverflowOnly":true,"slidesToScroll":1}} -->
<div class="wp-block-peaches-bs-carousel peaches-carousel-wrapper" data-wp-interactive="peaches-carousel" data-wp-context="{&quot;carouselId&quot;:&quot;carousel-hv3uh6ma&quot;,&quot;infinite&quot;:true,&quot;showDots&quot;:true,&quot;showArrows&quot;:false,&quot;centerMode&quot;:true,&quot;scrollOnDrag&quot;:true,&quot;autoplay&quot;:false,&quot;autoplaySpeed&quot;:3000,&quot;smoothScrolling&quot;:false,&quot;smoothScrollSpeed&quot;:&quot;normal&quot;,&quot;smoothCycleDuration&quot;:12000,&quot;initOnOverflowOnly&quot;:true,&quot;currentIndex&quot;:0,&quot;targetIndex&quot;:0,&quot;totalSlides&quot;:0,&quot;translateX&quot;:0,&quot;immediateTransition&quot;:false,&quot;transitionDelay&quot;:300,&quot;carouselInitialized&quot;:false}" data-wp-init="callbacks.init" data-wp-watch="callbacks.watchTargetIndex" data-carousel="{&quot;carouselId&quot;:&quot;carousel-hv3uh6ma&quot;,&quot;infinite&quot;:true,&quot;showDots&quot;:true,&quot;showArrows&quot;:false,&quot;centerMode&quot;:true,&quot;scrollOnDrag&quot;:true,&quot;autoplay&quot;:false,&quot;autoplaySpeed&quot;:3000,&quot;smoothScrolling&quot;:false,&quot;smoothScrollSpeed&quot;:&quot;normal&quot;,&quot;smoothCycleDuration&quot;:12000,&quot;initOnOverflowOnly&quot;:true,&quot;currentIndex&quot;:0,&quot;targetIndex&quot;:0,&quot;totalSlides&quot;:0,&quot;translateX&quot;:0,&quot;immediateTransition&quot;:false,&quot;transitionDelay&quot;:300,&quot;carouselInitialized&quot;:false}" data-carousel-id="carousel-hv3uh6ma">
	<div class="peaches-carousel">
		<div class="peaches-carousel-track row row-cols-2 row-cols-sm-3 row-cols-lg-4 gx-null mt-5" data-wp-style--transform="state.transform" data-wp-style--transition="state.transition" data-wp-class--grabbing="state.isDragging" data-wp-on--mousedown="actions.dragStart" data-wp-on--touchstart="actions.dragStart" data-wp-on--mousemove="actions.dragMove" data-wp-on--touchmove="actions.dragMove" data-wp-on--mouseup="actions.dragEnd" data-wp-on--touchend="actions.dragEnd" data-wp-on--mouseleave="actions.dragEnd">

			<!-- wp:peaches/bs-col -->
			<div class="wp-block-peaches-bs-col col">
				<!-- wp:peaches/ecwid-product -->
				<div class="card h-100 border-0" data-wp-interactive="peaches-ecwid-product" data-wp-context="{ &quot;productId&quot;: 0, &quot;isLoading&quot;: true, &quot;product&quot;: null }" data-wp-init="callbacks.initProduct">
					<div class="ratio ratio-1x1">
						<img class="card-img-top" data-wp-bind--src="state.productImage" data-wp-bind--alt="state.productName" alt="Product image"/>
					</div>
					<div class="card-body p-2 p-md-3 d-flex row-cols-1 flex-wrap align-content-between">
						<h5 role="button" class="card-title" data-wp-text="state.productName" data-wp-on--click="actions.navigateToProduct">
							Product Name
						</h5>
						<p class="card-text text-muted" data-wp-text="state.productSubtitle"></p>
					</div>
					<div class="card-footer border-0 hstack justify-content-between">
						<div class="card-text fw-bold lead" data-wp-text="state.productPrice"></div>
						<button title="Add to cart" class="add-to-cart btn pe-0" aria-label="Add to cart" data-wp-on--click="actions.addToCart"></button>
					</div>
					<div data-wp-bind--hidden="!context.isLoading" class="text-center my-3">
						<div class="spinner-border text-primary" role="status">
							<span class="visually-hidden">Loading product…</span>
						</div>
					</div>
				</div>
				<!-- /wp:peaches/ecwid-product -->
			</div>
			<!-- /wp:peaches/bs-col -->

			<!-- wp:peaches/bs-col -->
			<div class="wp-block-peaches-bs-col col">
				<!-- wp:peaches/ecwid-product -->
				<div class="card h-100 border-0" data-wp-interactive="peaches-ecwid-product" data-wp-context="{ &quot;productId&quot;: 0, &quot;isLoading&quot;: true, &quot;product&quot;: null }" data-wp-init="callbacks.initProduct">
					<div class="ratio ratio-1x1">
						<img class="card-img-top" data-wp-bind--src="state.productImage" data-wp-bind--alt="state.productName" alt="Product image"/>
					</div>
					<div class="card-body p-2 p-md-3 d-flex row-cols-1 flex-wrap align-content-between">
						<h5 role="button" class="card-title" data-wp-text="state.productName" data-wp-on--click="actions.navigateToProduct">
							Product Name
						</h5>
						<p class="card-text text-muted" data-wp-text="state.productSubtitle"></p>
					</div>
					<div class="card-footer border-0 hstack justify-content-between">
						<div class="card-text fw-bold lead" data-wp-text="state.productPrice"></div>
						<button title="Add to cart" class="add-to-cart btn pe-0" aria-label="Add to cart" data-wp-on--click="actions.addToCart"></button>
					</div>
					<div data-wp-bind--hidden="!context.isLoading" class="text-center my-3">
						<div class="spinner-border text-primary" role="status">
							<span class="visually-hidden">Loading product…</span>
						</div>
					</div>
				</div>
				<!-- /wp:peaches/ecwid-product -->
			</div>
			<!-- /wp:peaches/bs-col -->

			<!-- wp:peaches/bs-col -->
			<div class="wp-block-peaches-bs-col col">
				<!-- wp:peaches/ecwid-product -->
				<div class="card h-100 border-0" data-wp-interactive="peaches-ecwid-product" data-wp-context="{ &quot;productId&quot;: 0, &quot;isLoading&quot;: true, &quot;product&quot;: null }" data-wp-init="callbacks.initProduct">
					<div class="ratio ratio-1x1">
						<img class="card-img-top" data-wp-bind--src="state.productImage" data-wp-bind--alt="state.productName" alt="Product image"/>
					</div>
					<div class="card-body p-2 p-md-3 d-flex row-cols-1 flex-wrap align-content-between">
						<h5 role="button" class="card-title" data-wp-text="state.productName" data-wp-on--click="actions.navigateToProduct">
							Product Name
						</h5>
						<p class="card-text text-muted" data-wp-text="state.productSubtitle"></p>
					</div>
					<div class="card-footer border-0 hstack justify-content-between">
						<div class="card-text fw-bold lead" data-wp-text="state.productPrice"></div>
						<button title="Add to cart" class="add-to-cart btn pe-0" aria-label="Add to cart" data-wp-on--click="actions.addToCart"></button>
					</div>
					<div data-wp-bind--hidden="!context.isLoading" class="text-center my-3">
						<div class="spinner-border text-primary" role="status">
							<span class="visually-hidden">Loading product…</span>
						</div>
					</div>
				</div>
				<!-- /wp:peaches/ecwid-product -->
			</div>
			<!-- /wp:peaches/bs-col -->

			<!-- wp:peaches/bs-col -->
			<div class="wp-block-peaches-bs-col col">
				<!-- wp:peaches/ecwid-product -->
				<div class="card h-100 border-0" data-wp-interactive="peaches-ecwid-product" data-wp-context="{ &quot;productId&quot;: 0, &quot;isLoading&quot;: true, &quot;product&quot;: null }" data-wp-init="callbacks.initProduct">
					<div class="ratio ratio-1x1">
						<img class="card-img-top" data-wp-bind--src="state.productImage" data-wp-bind--alt="state.productName" alt="Product image"/>
					</div>
					<div class="card-body p-2 p-md-3 d-flex row-cols-1 flex-wrap align-content-between">
						<h5 role="button" class="card-title" data-wp-text="state.productName" data-wp-on--click="actions.navigateToProduct">
							Product Name
						</h5>
						<p class="card-text text-muted" data-wp-text="state.productSubtitle"></p>
					</div>
					<div class="card-footer border-0 hstack justify-content-between">
						<div class="card-text fw-bold lead" data-wp-text="state.productPrice"></div>
						<button title="Add to cart" class="add-to-cart btn pe-0" aria-label="Add to cart" data-wp-on--click="actions.addToCart"></button>
					</div>
					<div data-wp-bind--hidden="!context.isLoading" class="text-center my-3">
						<div class="spinner-border text-primary" role="status">
							<span class="visually-hidden">Loading product…</span>
						</div>
					</div>
				</div>
				<!-- /wp:peaches/ecwid-product -->
			</div>
			<!-- /wp:peaches/bs-col -->

		</div>
		<div class="peaches-carousel-dots gap-3 pt-5" data-wp-class--d-none="!state.isCarouselActive">
			<template data-wp-each--dot="state.dots">
				<button class="btn" data-wp-class--active="state.isActive" data-wp-on--click="actions.selectSlide" data-wp-bind--data-slide-index="context.dot" data-wp-bind--aria-label="state.getLabel">
				</button>
			</template>
		</div>
	</div>
</div>
<!-- /wp:peaches/bs-carousel -->
