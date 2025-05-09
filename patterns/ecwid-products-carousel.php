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

<!-- wp:peaches/bs-carousel {"xs":{"carousel":[],"rowCols":2,"gapX":"4","gapY":"5","gutterX":null},"sm":{"carousel":[],"rowCols":3},"lg":{"carousel":[],"rowCols":4},"carousel":{"scrollOnDrag":true,"dots":true,"arrows":false,"infinite":true,"centerMode":false,"slidesToScroll":1,"autoplay":false,"autoplaySpeed":3000,"smoothCycleDuration":12000,"smoothScrolling":false,"smoothScrollSpeed":"normal","initOnOverflowOnly":true},"metadata":{"categories":["peaches-ecwid"],"patternName":"peaches-ecwid/ecwid-products-carousel","name":"Products Carousel"},"className":"user-select-none"} -->
<div class="wp-block-peaches-bs-carousel peaches-carousel-wrapper user-select-none" data-wp-interactive="peaches-carousel" data-wp-context="{&quot;infinite&quot;:true,&quot;showDots&quot;:true,&quot;showArrows&quot;:false,&quot;centerMode&quot;:false,&quot;scrollOnDrag&quot;:true,&quot;autoplay&quot;:false,&quot;autoplaySpeed&quot;:3000,&quot;smoothScrolling&quot;:false,&quot;smoothScrollSpeed&quot;:&quot;normal&quot;,&quot;smoothCycleDuration&quot;:12000,&quot;initOnOverflowOnly&quot;:true,&quot;currentIndex&quot;:0,&quot;totalSlides&quot;:0,&quot;translateX&quot;:0,&quot;immediateTransition&quot;:false,&quot;transitionDelay&quot;:300,&quot;carouselInitialized&quot;:false}" data-wp-init="callbacks.init" data-carousel="{&quot;infinite&quot;:true,&quot;showDots&quot;:true,&quot;showArrows&quot;:false,&quot;centerMode&quot;:false,&quot;scrollOnDrag&quot;:true,&quot;autoplay&quot;:false,&quot;autoplaySpeed&quot;:3000,&quot;smoothScrolling&quot;:false,&quot;smoothScrollSpeed&quot;:&quot;normal&quot;,&quot;smoothCycleDuration&quot;:12000,&quot;initOnOverflowOnly&quot;:true,&quot;currentIndex&quot;:0,&quot;totalSlides&quot;:0,&quot;translateX&quot;:0,&quot;immediateTransition&quot;:false,&quot;transitionDelay&quot;:300,&quot;carouselInitialized&quot;:false}">
	<div class="peaches-carousel">
		<div class="peaches-carousel-track row row-cols-2 row-cols-sm-3 row-cols-lg-4 gx-null" data-wp-style--transform="state.transform" data-wp-style--transition="state.transition" data-wp-class--grabbing="state.isDragging" data-wp-on--mousedown="actions.dragStart" data-wp-on--touchstart="actions.dragStart" data-wp-on--mousemove="actions.dragMove" data-wp-on--touchmove="actions.dragMove" data-wp-on--mouseup="actions.dragEnd" data-wp-on--touchend="actions.dragEnd" data-wp-on--mouseleave="actions.dragEnd">

			<!-- wp:peaches/bs-col -->
			<div class="wp-block-peaches-bs-col col">
				<!-- wp:peaches/ecwid-product -->
				<div class="card h-100 border-0" data-wp-interactive="peaches-ecwid-product" data-wp-context="{ &quot;productId&quot;: 0, &quot;isLoading&quot;: true, &quot;product&quot;: null }" data-wp-init="callbacks.initProduct" data-wp-on--click="actions.navigateToProduct" data-wp-bind--style--cursor="state.product ? 'pointer' : 'default'">
					<div class="ratio ratio-1x1">
						<img class="card-img-top" data-wp-bind--src="state.productImage" data-wp-bind--alt="state.productName" alt="Product image"/>
					</div>
					<div class="card-body p-2 p-md-3 d-flex row-cols-1 flex-wrap align-content-between">
						<h5 class="card-title" data-wp-text="state.productName"></h5>
						<p class="card-text text-muted" data-wp-text="state.productSubtitle"></p>
					</div>
					<div class="card-footer border-0">
						<div class="card-text fw-bold" data-wp-text="state.productPrice"></div>
					</div>
					<div data-wp-bind--hidden="!context.isLoading" class="text-center my-3">
						<div class="spinner-border text-primary" role="status">
							<span class="visually-hidden">Loading product...</span>
						</div>
					</div>
				</div>
				<!-- /wp:peaches/ecwid-product -->
			</div>
			<!-- /wp:peaches/bs-col -->

			<!-- wp:peaches/bs-col -->
			<div class="wp-block-peaches-bs-col col">
				<!-- wp:peaches/ecwid-product -->
				<div class="card h-100 border-0" data-wp-interactive="peaches-ecwid-product" data-wp-context="{ &quot;productId&quot;: 0, &quot;isLoading&quot;: true, &quot;product&quot;: null }" data-wp-init="callbacks.initProduct" data-wp-on--click="actions.navigateToProduct" data-wp-bind--style--cursor="state.product ? 'pointer' : 'default'">
					<div class="ratio ratio-1x1">
						<img class="card-img-top" data-wp-bind--src="state.productImage" data-wp-bind--alt="state.productName" alt="Product image"/>
					</div>
					<div class="card-body p-2 p-md-3 d-flex row-cols-1 flex-wrap align-content-between">
						<h5 class="card-title" data-wp-text="state.productName"></h5>
						<p class="card-text text-muted" data-wp-text="state.productSubtitle"></p>
					</div>
					<div class="card-footer border-0">
						<div class="card-text fw-bold" data-wp-text="state.productPrice"></div>
					</div>
					<div data-wp-bind--hidden="!context.isLoading" class="text-center my-3">
						<div class="spinner-border text-primary" role="status">
							<span class="visually-hidden">Loading product...</span>
						</div>
					</div>
				</div>
				<!-- /wp:peaches/ecwid-product -->
			</div>
			<!-- /wp:peaches/bs-col -->

			<!-- wp:peaches/bs-col -->
			<div class="wp-block-peaches-bs-col col">
				<!-- wp:peaches/ecwid-product -->
				<div class="card h-100 border-0" data-wp-interactive="peaches-ecwid-product" data-wp-context="{ &quot;productId&quot;: 0, &quot;isLoading&quot;: true, &quot;product&quot;: null }" data-wp-init="callbacks.initProduct" data-wp-on--click="actions.navigateToProduct" data-wp-bind--style--cursor="state.product ? 'pointer' : 'default'">
					<div class="ratio ratio-1x1">
						<img class="card-img-top" data-wp-bind--src="state.productImage" data-wp-bind--alt="state.productName" alt="Product image"/>
					</div>
					<div class="card-body p-2 p-md-3 d-flex row-cols-1 flex-wrap align-content-between">
						<h5 class="card-title" data-wp-text="state.productName"></h5>
						<p class="card-text text-muted" data-wp-text="state.productSubtitle"></p>
					</div>
					<div class="card-footer border-0">
						<div class="card-text fw-bold" data-wp-text="state.productPrice"></div>
					</div>
					<div data-wp-bind--hidden="!context.isLoading" class="text-center my-3">
						<div class="spinner-border text-primary" role="status">
							<span class="visually-hidden">Loading product...</span>
						</div>
					</div>
				</div>
				<!-- /wp:peaches/ecwid-product -->
			</div>
			<!-- /wp:peaches/bs-col -->

			<!-- wp:peaches/bs-col -->
			<div class="wp-block-peaches-bs-col col">
				<!-- wp:peaches/ecwid-product -->
				<div class="card h-100 border-0" data-wp-interactive="peaches-ecwid-product" data-wp-context="{ &quot;productId&quot;: 0, &quot;isLoading&quot;: true, &quot;product&quot;: null }" data-wp-init="callbacks.initProduct" data-wp-on--click="actions.navigateToProduct" data-wp-bind--style--cursor="state.product ? 'pointer' : 'default'">
					<div class="ratio ratio-1x1">
						<img class="card-img-top" data-wp-bind--src="state.productImage" data-wp-bind--alt="state.productName" alt="Product image"/>
					</div>
					<div class="card-body p-2 p-md-3 d-flex row-cols-1 flex-wrap align-content-between">
						<h5 class="card-title" data-wp-text="state.productName"></h5>
						<p class="card-text text-muted" data-wp-text="state.productSubtitle"></p>
					</div>
					<div class="card-footer border-0">
						<div class="card-text fw-bold" data-wp-text="state.productPrice"></div>
					</div>
					<div data-wp-bind--hidden="!context.isLoading" class="text-center my-3">
						<div class="spinner-border text-primary" role="status">
							<span class="visually-hidden">Loading product...</span>
						</div>
					</div>
				</div>
				<!-- /wp:peaches/ecwid-product -->
			</div>
			<!-- /wp:peaches/bs-col -->

		</div>
		<div class="peaches-carousel-dots gap-3 pt-5" data-wp-class--d-none="!state.isCarouselActive">
			<template data-wp-each--dot="state.dots">
				<button class="btn" data-wp-class--active="state.isActive" data-wp-on--click="actions.selectSlide" data-wp-bind--data-slide-index="context.dot" data-wp-bind--aria-label="state.getLabel"></button>
			</template>
		</div>
	</div>
</div>
<!-- /wp:peaches/bs-carousel -->
