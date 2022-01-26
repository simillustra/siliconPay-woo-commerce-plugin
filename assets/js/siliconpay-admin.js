jQuery( function( $ ) {
	'use strict';

	/**
	 * Object to handle SiliconPay admin functions.
	 */
	var wc_siliconpay_app_admin = {
		/**
		 * Initialize.
		 */
		init: function() {

			// Toggle api key settings.
			$( document.body ).on( 'change', '#woocommerce_siliconpay_app_testmode', function() {
				var test_secret_key = $( '#woocommerce_siliconpay_app_test_secret_key' ).parents( 'tr' ).eq( 0 ),
					test_encryption_key = $( '#woocommerce_siliconpay_app_test_encryption_key' ).parents( 'tr' ).eq( 0 ),
					live_secret_key = $( '#woocommerce_siliconpay_app_live_secret_key' ).parents( 'tr' ).eq( 0 ),
					live_encryption_key = $( '#woocommerce_siliconpay_app_live_encryption_key' ).parents( 'tr' ).eq( 0 );

				if ( $( this ).is( ':checked' ) ) {
					test_secret_key.show();
					test_encryption_key.show();
					live_secret_key.hide();
					live_encryption_key.hide();
				} else {
					test_secret_key.hide();
					test_encryption_key.hide();
					live_secret_key.show();
					live_encryption_key.show();
				}
			} );

			$( '#woocommerce_siliconpay_app_testmode' ).change();

			$( document.body ).on( 'change', '.woocommerce_siliconpay_app_split_payment', function() {
				var subaccount_code = $( '.woocommerce_siliconpay_app_subaccount_code' ).parents( 'tr' ).eq( 0 ),
					subaccount_charge = $( '.woocommerce_siliconpay_app_split_payment_charge_account' ).parents( 'tr' ).eq( 0 ),
					transaction_charge = $( '.woocommerce_siliconpay_app_split_payment_transaction_charge' ).parents( 'tr' ).eq( 0 );

				if ( $( this ).is( ':checked' ) ) {
					subaccount_code.show();
					subaccount_charge.show();
					transaction_charge.show();
				} else {
					subaccount_code.hide();
					subaccount_charge.hide();
					transaction_charge.hide();
				}
			} );

			$( '#woocommerce_siliconpay_app_split_payment' ).change();

			// Toggle Custom Metadata settings.
			$( '.wc-siliconpay-metadata' ).change( function() {
				if ( $( this ).is( ':checked' ) ) {
					$( '.wc-siliconpay-meta-order-id, .wc-siliconpay-meta-name, .wc-siliconpay-meta-email, .wc-siliconpay-meta-phone, .wc-siliconpay-meta-billing-address, .wc-siliconpay-meta-shipping-address, .wc-siliconpay-meta-products' ).closest( 'tr' ).show();
				} else {
					$( '.wc-siliconpay-meta-order-id, .wc-siliconpay-meta-name, .wc-siliconpay-meta-email, .wc-siliconpay-meta-phone, .wc-siliconpay-meta-billing-address, .wc-siliconpay-meta-shipping-address, .wc-siliconpay-meta-products' ).closest( 'tr' ).hide();
				}
			} ).change();

			// Toggle Bank filters settings.
			$( '.wc-siliconpay-payment-channels' ).on( 'change', function() {

				var channels = $( ".wc-siliconpay-payment-channels" ).val();

				if ( $.inArray( 'card', channels ) != '-1' ) {
					$( '.wc-siliconpay-cards-allowed' ).closest( 'tr' ).show();
					$( '.wc-siliconpay-banks-allowed' ).closest( 'tr' ).show();
				}
				else {
					$( '.wc-siliconpay-cards-allowed' ).closest( 'tr' ).hide();
					$( '.wc-siliconpay-banks-allowed' ).closest( 'tr' ).hide();
				}

			} ).change();

			$( ".wc-siliconpay-payment-icons" ).select2( {
				templateResult: formatSiliconPayPaymentIcons,
				templateSelection: formatSiliconPayPaymentIconDisplay
			} );

		}
	};

	function formatSiliconPayPaymentIcons( payment_method ) {
		if ( !payment_method.id ) {
			return payment_method.text;
		}

		var $payment_method = $(
			'<span><img src=" ' + wc_siliconpay_app_admin_params.plugin_url + '/assets/images/' + payment_method.element.value.toLowerCase() + '.png" class="img-flag" style="height: 15px; weight:18px;" /> ' + payment_method.text + '</span>'
		);

		return $payment_method;
	};

	function formatSiliconPayPaymentIconDisplay( payment_method ) {
		return payment_method.text;
	};

	wc_siliconpay_app_admin.init();

} );
