<?php
/**
 * Title: Featured Products Carousel
 * Description: A carousel with 2 products shown on mobile, 3 products on tablet and 4 products on higher resolutions. It will not transform into a carousel if all products fit on the page.
 * Slug: peaches-ecwid/ecwid-featured-products-carousel
 * Categories: peaches-ecwid, peaches-bootstrap
 * Viewport Width: 400
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

?>
<!-- wp:peaches/bs-carousel {"initialized":true,"carouselId":"carousel-k1s2f70y","carousel":{"dots":true,"arrows":false,"infinite":true,"centerMode":true,"speed":300,"scrollOnDrag":true,"autoplay":false,"autoplaySpeed":3000,"smoothScrolling":false,"smoothScrollSpeed":"normal","smoothCycleDuration":12000,"initOnOverflowOnly":true,"slidesToScroll":1},"xs":{"carousel":[],"rowCols":2,"gapX":"4","gapY":"5","gutterX":null,"marginT":0},"sm":{"carousel":[],"rowCols":3},"lg":{"carousel":[],"rowCols":4,"marginT":0,"marginB":0,"paddingY":5,"marginX":0},"metadata":{"categories":["peaches-ecwid"],"patternName":"peaches-ecwid/ecwid-products-carousel","name":"Products Carousel"}} -->
<div class="wp-block-peaches-bs-carousel peaches-carousel-wrapper" data-wp-interactive="peaches-carousel" data-wp-context="{&quot;carouselId&quot;:&quot;carousel-k1s2f70y&quot;,&quot;infinite&quot;:true,&quot;showDots&quot;:true,&quot;showArrows&quot;:false,&quot;centerMode&quot;:true,&quot;scrollOnDrag&quot;:true,&quot;autoplay&quot;:false,&quot;autoplaySpeed&quot;:3000,&quot;smoothScrolling&quot;:false,&quot;smoothScrollSpeed&quot;:&quot;normal&quot;,&quot;smoothCycleDuration&quot;:12000,&quot;initOnOverflowOnly&quot;:true,&quot;currentIndex&quot;:0,&quot;targetIndex&quot;:0,&quot;totalSlides&quot;:0,&quot;translateX&quot;:0,&quot;immediateTransition&quot;:false,&quot;transitionDelay&quot;:300,&quot;carouselInitialized&quot;:false}" data-wp-init="callbacks.init" data-wp-watch="callbacks.watchTargetIndex" data-carousel="{&quot;carouselId&quot;:&quot;carousel-k1s2f70y&quot;,&quot;infinite&quot;:true,&quot;showDots&quot;:true,&quot;showArrows&quot;:false,&quot;centerMode&quot;:true,&quot;scrollOnDrag&quot;:true,&quot;autoplay&quot;:false,&quot;autoplaySpeed&quot;:3000,&quot;smoothScrolling&quot;:false,&quot;smoothScrollSpeed&quot;:&quot;normal&quot;,&quot;smoothCycleDuration&quot;:12000,&quot;initOnOverflowOnly&quot;:true,&quot;currentIndex&quot;:0,&quot;targetIndex&quot;:0,&quot;totalSlides&quot;:0,&quot;translateX&quot;:0,&quot;immediateTransition&quot;:false,&quot;transitionDelay&quot;:300,&quot;carouselInitialized&quot;:false}" data-carousel-id="carousel-k1s2f70y">
	<div class="peaches-carousel">
		<div class="peaches-carousel-track row row-cols-2 row-cols-sm-3 row-cols-lg-4 gx-null mt-0 mx-lg-0 mt-lg-0 mb-lg-0 py-lg-5" data-wp-style--transform="state.transform" data-wp-style--transition="state.transition" data-wp-class--grabbing="state.isDragging" data-wp-on--mousedown="actions.dragStart" data-wp-on--touchstart="actions.dragStart" data-wp-on--mousemove="actions.dragMove" data-wp-on--touchmove="actions.dragMove" data-wp-on--mouseup="actions.dragEnd" data-wp-on--touchend="actions.dragEnd" data-wp-on--mouseleave="actions.dragEnd">
			<!-- wp:peaches/ecwid-category-products {"selectedCategoryId":0,"translations":{"buttonText":{"en":"Add to cart"}},"maxProducts":12,"buttonText":"Plaats in shopper","hoverMediaTag":"secondary_1","isInCarousel":true} /-->
		</div>
		<div class="peaches-carousel-dots gap-3" data-wp-class--d-none="!state.isCarouselActive">
			<template data-wp-each--dot="state.dots">
				<button class="btn" data-wp-class--active="state.isActive" data-wp-on--click="actions.selectSlide" data-wp-bind--data-slide-index="context.dot" data-wp-bind--aria-label="state.getLabel">
				</button>
			</template>
		</div>
	</div>
</div>
<!-- /wp:peaches/bs-carousel -->
