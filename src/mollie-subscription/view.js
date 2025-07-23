/**
 * Mollie Subscription Block Frontend Interactivity
 *
 * Handles frontend subscription functionality using WordPress Interactivity API.
 *
 * @package
 * @since   0.4.0
 */

import { store, getContext } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import { getProductIdWithFallback } from '../utils/ecwid-view-utils';

/**
 * Mollie Subscription Store
 *
 * Manages state and actions for subscription functionality following WordPress Interactivity API patterns.
 */
const { state, actions } = store( 'peaches/mollie-subscription', {
	state: {
		/**
		 * Get current loading state
		 *
		 * @return {boolean} Loading state
		 */
		get isLoading() {
			const context = getContext();
			return context.isLoading || false;
		},

		/**
		 * Get current error message
		 *
		 * @return {string|null} Error message
		 */
		get error() {
			const context = getContext();
			return context.error || null;
		},

		/**
		 * Get selected subscription plan index
		 *
		 * @return {number|null} Selected plan index
		 */
		get selectedPlan() {
			const context = getContext();
			return context.selectedPlan;
		},

		/**
		 * Get available subscription plans
		 *
		 * @return {Array} Available plans
		 */
		get plans() {
			const context = getContext();
			return context.subscriptionPlans || [];
		},

		/**
		 * Check if we should show loading overlay
		 *
		 * @return {boolean} True if loading overlay should be visible
		 */
		get showLoadingOverlay() {
			const context = getContext();
			return context.isLoading && context.selectedPlan !== null;
		},
	},

	actions: {
		/**
		 * Select subscription plan and initiate checkout
		 *
		 * @param {Event} event Click event from plan button
		 */
		*selectPlan( event ) {
			const context = getContext();
			const planIndex = parseInt( event.target.dataset.planIndex, 10 );
			const plan = context.subscriptionPlans[ planIndex ];

			if ( ! plan ) {
				context.error = 'Invalid subscription plan selected';
				return;
			}

			// Set loading state
			context.isLoading = true;
			context.selectedPlan = planIndex;
			context.error = null;

			try {
				// Get product ID from context
				const productId = getProductIdWithFallback(
					context.selectedProductId
				);

				if ( ! productId ) {
					throw new Error( 'Product ID not found' );
				}

				// Check if customer is logged in or has stored data
				const customerData = yield* actions.getCustomerData();

				if ( ! customerData ) {
					// Show customer data collection modal
					yield* actions.showCustomerModal( plan, productId );
					return;
				}

				// Create subscription directly if customer data is available
				// Use a helper function that returns a Promise
				const subscription = yield actions.createSubscriptionPromise(
					productId,
					plan,
					customerData
				);

				if ( subscription.checkout_url ) {
					// Redirect to Mollie checkout
					window.location.href = subscription.checkout_url;
				} else {
					throw new Error( 'Failed to create subscription checkout' );
				}
			} catch ( error ) {
				console.error( 'Subscription error:', error );
				context.error =
					error.message ||
					'An error occurred while processing your subscription';
			} finally {
				context.isLoading = false;
				context.selectedPlan = null;
			}
		},

		/**
		 * Get customer data from current user or session storage
		 *
		 * @return {Object|null} Customer data or null if not available
		 */
		*getCustomerData() {
			// Check if user is logged in via WordPress
			if ( window.PeachesMollieSettings?.currentUser ) {
				return window.PeachesMollieSettings.currentUser;
			}

			// Check for stored customer data in session
			try {
				const storedData = sessionStorage.getItem(
					'peaches_customer_data'
				);
				if ( storedData ) {
					return JSON.parse( storedData );
				}
			} catch ( e ) {
				console.warn( 'Invalid stored customer data' );
			}

			return null;
		},

		/**
		 * Show customer data collection modal
		 *
		 * @param {Object} plan      Selected subscription plan
		 * @param {string} productId Product ID
		 */
		*showCustomerModal( plan, productId ) {
			// Create and show Bootstrap modal for customer data collection
			const modal = actions.createCustomerModal( plan, productId );
			document.body.appendChild( modal );

			// Initialize Bootstrap modal if available
			if ( window.bootstrap?.Modal ) {
				const bsModal = new window.bootstrap.Modal( modal );
				bsModal.show();
			} else {
				// Fallback: show modal manually
				modal.classList.add( 'show' );
				modal.style.display = 'block';
			}

			// Focus on first input
			setTimeout( () => {
				const firstInput = modal.querySelector( '#customer-email' );
				if ( firstInput ) {
					firstInput.focus();
				}
			}, 150 );
		},

		/**
		 * Create customer data collection modal using Bootstrap components
		 *
		 * @param {Object} plan      Selected subscription plan
		 * @param {string} productId Product ID
		 *
		 * @return {HTMLElement} Modal element
		 */
		createCustomerModal( plan, productId ) {
			const modalHTML = `
				<div class="modal fade" tabindex="-1" aria-hidden="true">
					<div class="modal-dialog modal-dialog-centered modal-lg">
						<div class="modal-content border-0 shadow">
							<div class="modal-header bg-light border-bottom">
								<h5 class="modal-title fw-bold text-dark">
									<i class="fas fa-credit-card me-2 text-primary"></i>
									Complete Your Subscription
								</h5>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
							</div>
							<div class="modal-body p-4">
								<!-- Plan Summary -->
								<div class="row mb-4">
									<div class="col-12">
										<div class="card bg-primary bg-opacity-10 border-primary border-opacity-25">
											<div class="card-body p-3">
												<div class="d-flex justify-content-between align-items-center">
													<div>
														<h6 class="card-title mb-1 text-dark">
															<i class="fas fa-sync-alt me-2 text-primary"></i>
															Selected Plan
														</h6>
														<p class="h5 fw-bold mb-1 text-dark">${ plan.name }</p>
														<p class="mb-0">
															<span class="h4 fw-bold text-primary">
																${ plan.currency } ${ parseFloat( plan.amount ).toFixed( 2 ) }
															</span>
															<small class="text-muted ms-2">/ ${ plan.interval }</small>
														</p>
													</div>
													<div class="text-end">
														<i class="fas fa-check-circle fa-3x text-success opacity-50"></i>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>

								<!-- Customer Form -->
								<form class="customer-form">
									<div class="row">
										<div class="col-12 mb-3">
											<label for="customer-email" class="form-label fw-semibold">
												<i class="fas fa-envelope me-2 text-muted"></i>
												Email Address
												<span class="text-danger">*</span>
											</label>
											<input
												type="email"
												class="form-control form-control-lg"
												id="customer-email"
												name="email"
												required
												placeholder="your@email.com"
											/>
											<div class="form-text">
												<i class="fas fa-info-circle me-1"></i>
												We'll send your subscription confirmations here
											</div>
										</div>
									</div>

									<div class="row">
										<div class="col-md-6 mb-3">
											<label for="customer-first-name" class="form-label fw-semibold">
												<i class="fas fa-user me-2 text-muted"></i>
												First Name
												<span class="text-danger">*</span>
											</label>
											<input
												type="text"
												class="form-control form-control-lg"
												id="customer-first-name"
												name="first_name"
												required
												placeholder="John"
											/>
										</div>
										<div class="col-md-6 mb-3">
											<label for="customer-last-name" class="form-label fw-semibold">
												<i class="fas fa-user me-2 text-muted"></i>
												Last Name
												<span class="text-danger">*</span>
											</label>
											<input
												type="text"
												class="form-control form-control-lg"
												id="customer-last-name"
												name="last_name"
												required
												placeholder="Doe"
											/>
										</div>
									</div>

									<div class="row">
										<div class="col-12 mb-3">
											<label for="customer-phone" class="form-label fw-semibold">
												<i class="fas fa-phone me-2 text-muted"></i>
												Phone Number
												<small class="text-muted">(optional)</small>
											</label>
											<input
												type="tel"
												class="form-control form-control-lg"
												id="customer-phone"
												name="phone"
												placeholder="Your phone number"
											/>
										</div>
									</div>

									<div class="row">
										<div class="col-12 mb-4">
											<div class="form-check">
												<input
													class="form-check-input"
													type="checkbox"
													id="save-customer-data"
													checked
												/>
												<label class="form-check-label" for="save-customer-data">
													<i class="fas fa-save me-2 text-muted"></i>
													Remember my information for future purchases
												</label>
											</div>
										</div>
									</div>

									<!-- Form Actions -->
									<div class="row">
										<div class="col-12">
											<div class="d-grid gap-2 d-md-flex justify-content-md-end">
												<button type="button" class="btn btn-outline-secondary btn-lg px-4" data-bs-dismiss="modal">
													<i class="fas fa-times me-2"></i>
													Cancel
												</button>
												<button type="submit" class="btn btn-primary btn-lg px-4 fw-semibold">
													<i class="fas fa-credit-card me-2"></i>
													Proceed to Payment
												</button>
											</div>
										</div>
									</div>
								</form>

								<!-- Error Alert -->
								<div class="alert alert-danger border-0 d-none mt-3" role="alert">
									<i class="fas fa-exclamation-triangle me-2"></i>
									<span class="error-message"></span>
								</div>
							</div>
						</div>
					</div>
				</div>
			`;

			// Create modal element
			const modalWrapper = document.createElement( 'div' );
			modalWrapper.innerHTML = modalHTML;
			const modal = modalWrapper.firstElementChild;

			// Add event listeners
			const form = modal.querySelector( '.customer-form' );
			const errorAlert = modal.querySelector( '.alert-danger' );

			// Handle form submission
			form.addEventListener( 'submit', async ( e ) => {
				e.preventDefault();

				const submitBtn = form.querySelector( 'button[type="submit"]' );
				const originalHTML = submitBtn.innerHTML;

				submitBtn.disabled = true;
				submitBtn.innerHTML =
					'<i class="fas fa-spinner fa-spin me-2"></i>Processing...';

				try {
					// Collect form data
					const formData = new FormData( form );
					const customerData = {
						email: formData.get( 'email' ),
						first_name: formData.get( 'first_name' ),
						last_name: formData.get( 'last_name' ),
						phone: formData.get( 'phone' ) || '',
					};

					// Validate required fields
					if (
						! customerData.email ||
						! customerData.first_name ||
						! customerData.last_name
					) {
						throw new Error( 'Please fill in all required fields' );
					}

					// Save customer data if requested
					if (
						modal.querySelector( '#save-customer-data' ).checked
					) {
						sessionStorage.setItem(
							'peaches_customer_data',
							JSON.stringify( customerData )
						);
					}

					// Create subscription using regular async/await
					const subscription = await actions.createSubscription(
						productId,
						plan,
						customerData
					);

					// Close modal
					if ( window.bootstrap?.Modal ) {
						const bsModal =
							window.bootstrap.Modal.getInstance( modal );
						if ( bsModal ) {
							bsModal.hide();
						}
					} else {
						modal.classList.remove( 'show' );
						modal.style.display = 'none';
					}
					modal.remove();

					if ( subscription.checkout_url ) {
						// Redirect to Mollie checkout
						window.location.href = subscription.checkout_url;
					} else {
						throw new Error(
							'Failed to create subscription checkout'
						);
					}
				} catch ( error ) {
					console.error( 'Customer form error:', error );
					const errorMessage =
						errorAlert.querySelector( '.error-message' );
					errorMessage.textContent = error.message;
					errorAlert.classList.remove( 'd-none' );

					submitBtn.disabled = false;
					submitBtn.innerHTML = originalHTML;
				}
			} );

			// Handle modal close - reset loading state
			modal.addEventListener( 'hidden.bs.modal', () => {
				const context = getContext();
				context.isLoading = false;
				context.selectedPlan = null;
				modal.remove();
			} );

			// Handle manual close for non-Bootstrap environments
			const handleClose = () => {
				const context = getContext();
				context.isLoading = false;
				context.selectedPlan = null;
				modal.remove();
			};

			// Add close button handlers for non-Bootstrap environments
			const closeButtons = modal.querySelectorAll(
				'[data-bs-dismiss="modal"]'
			);
			closeButtons.forEach( ( button ) => {
				button.addEventListener( 'click', handleClose );
			} );

			return modal;
		},

		/**
		 * Create subscription via AJAX (Promise-based for use with yield)
		 *
		 * @param {string} productId    Product ID
		 * @param {Object} plan         Subscription plan
		 * @param {Object} customerData Customer data
		 *
		 * @return {Promise<Object>} Subscription response
		 */
		createSubscriptionPromise( productId, plan, customerData ) {
			const formData = new FormData();
			formData.append( 'action', 'create_mollie_subscription' );
			formData.append( 'nonce', window.PeachesMollieSettings.nonce );
			formData.append( 'product_id', productId );
			formData.append( 'plan', JSON.stringify( plan ) );
			formData.append( 'customer', JSON.stringify( customerData ) );

			return fetch( window.PeachesMollieSettings.ajaxUrl, {
				method: 'POST',
				body: formData,
			} )
				.then( ( response ) => response.json() )
				.then( ( result ) => {
					if ( ! result.success ) {
						throw new Error(
							result.data || 'Subscription creation failed'
						);
					}
					return result.data;
				} );
		},

		/**
		 * Create subscription via AJAX (async version for event listeners)
		 *
		 * @param {string} productId    Product ID
		 * @param {Object} plan         Subscription plan
		 * @param {Object} customerData Customer data
		 *
		 * @return {Promise<Object>} Subscription response
		 */
		async createSubscription( productId, plan, customerData ) {
			return this.createSubscriptionPromise(
				productId,
				plan,
				customerData
			);
		},

		/**
		 * Handle return from Mollie checkout
		 *
		 * @param {string} result Checkout result parameter
		 */
		handleCheckoutReturn( result ) {
			// Clear URL parameters
			const url = new URL( window.location );
			url.searchParams.delete( 'subscription_result' );
			window.history.replaceState( {}, document.title, url.toString() );

			// Show appropriate message based on result
			switch ( result ) {
				case 'success':
					actions.showNotification(
						'Subscription created successfully!',
						'success'
					);
					break;
				case 'cancelled':
					actions.showNotification(
						'Subscription was cancelled.',
						'warning'
					);
					break;
				case 'failed':
					actions.showNotification(
						'Subscription creation failed. Please try again.',
						'error'
					);
					break;
			}
		},

		/**
		 * Show notification to user using Bootstrap toast
		 *
		 * @param {string} message Notification message
		 * @param {string} type    Notification type (success, warning, error)
		 */
		showNotification( message, type = 'info' ) {
			const alertClass = type === 'error' ? 'danger' : type;
			const iconClass =
				{
					success: 'fas fa-check-circle',
					warning: 'fas fa-exclamation-triangle',
					error: 'fas fa-exclamation-circle',
					info: 'fas fa-info-circle',
				}[ type ] || 'fas fa-info-circle';

			// Create Bootstrap toast
			const toastHTML = `
				<div class="toast align-items-center text-white bg-${ alertClass } border-0" role="alert" aria-live="assertive" aria-atomic="true">
					<div class="d-flex">
						<div class="toast-body d-flex align-items-center">
							<i class="${ iconClass } me-3 fs-5"></i>
							<div class="flex-grow-1">${ message }</div>
						</div>
						<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
					</div>
				</div>
			`;

			// Create toast container if it doesn't exist
			let toastContainer = document.querySelector( '.toast-container' );
			if ( ! toastContainer ) {
				toastContainer = document.createElement( 'div' );
				toastContainer.className =
					'toast-container position-fixed top-0 end-0 p-3';
				toastContainer.style.zIndex = '1060';
				document.body.appendChild( toastContainer );
			}

			// Add toast to container
			const toastWrapper = document.createElement( 'div' );
			toastWrapper.innerHTML = toastHTML;
			const toast = toastWrapper.firstElementChild;
			toastContainer.appendChild( toast );

			// Initialize and show Bootstrap toast
			if ( window.bootstrap?.Toast ) {
				const bsToast = new window.bootstrap.Toast( toast, {
					autohide: true,
					delay: 5000,
				} );
				bsToast.show();

				// Remove toast element after it's hidden
				toast.addEventListener( 'hidden.bs.toast', () => {
					toast.remove();
				} );
			} else {
				// Fallback without Bootstrap
				toast.classList.add( 'show' );
				setTimeout( () => {
					toast.remove();
				}, 5000 );

				// Handle manual close
				const closeBtn = toast.querySelector( '.btn-close' );
				if ( closeBtn ) {
					closeBtn.addEventListener( 'click', () => {
						toast.remove();
					} );
				}
			}
		},
	},

	callbacks: {
		/**
		 * Initialize Mollie subscription block
		 *
		 * Sets up the block state and handles URL parameters for checkout returns.
		 */
		*initMollieSubscription() {
			const context = getContext();

			// Initialize state
			context.isLoading = false;
			context.error = null;
			context.selectedPlan = null;

			// Check for URL parameters indicating return from payment
			const urlParams = new URLSearchParams( window.location.search );
			const subscriptionResult = urlParams.get( 'subscription_result' );

			if ( subscriptionResult ) {
				// Handle return from Mollie checkout
				actions.handleCheckoutReturn( subscriptionResult );
			}

			console.log( 'Mollie subscription block initialized:', context );
		},
	},
} );
