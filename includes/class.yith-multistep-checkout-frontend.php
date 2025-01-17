<?php
/*
 * This file belongs to the YIT Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */
if ( ! defined( 'YITH_WCMS_VERSION' ) ) {
    exit( 'Direct access forbidden.' );
}

/**
 *
 *
 * @class      YITH_Multistep_Checkout_Frontend
 * @package    Yithemes
 * @since      Version 1.0.0
 * @author     Andrea Grillo <andrea.grillo@yithemes.com>
 *
 */

if ( ! class_exists( 'YITH_Multistep_Checkout_Frontend' ) ) {
    /**
     * Class YITH_Multistep_Checkout_Frontend
     *
     * @author Andrea Grillo <andrea.grillo@yithemes.com>
     */
    class YITH_Multistep_Checkout_Frontend {

        /**
         * Construct
         *
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         * @since 1.0
         */
        public function __construct(){

            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	        add_action( 'wp_head', array( $this, 'timeline_style' ) );

	        /* === Change Checkout Template === */
            add_filter( 'woocommerce_locate_template', array( $this, 'multistep_checkout' ), 10, 3 );

            /* === Checkout Hack === */
            remove_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );
            remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
            remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
            remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

	        add_action( 'yith_woocommerce_show_wc_notices', 'wc_print_notices', 10 );
            add_action( 'yith_woocommerce_checkout_coupon', 'woocommerce_checkout_coupon_form', 10 );
            add_action( 'yith_woocommerce_checkout_order_review', 'woocommerce_order_review', 20 );
            add_action( 'yith_woocommerce_checkout_payment', 'woocommerce_checkout_payment', 10 );
	        add_action( 'yith_wcms_checkout_login_form', 'yith_wcms_login_form', 10, 1 );

            /* Prevent Empty Shipping Tab */
            add_filter( 'woocommerce_enable_order_notes_field', '__return_true' );

            if( defined( 'YITH_WCMS_FREE_INIT' ) ){
            	add_filter( 'pre_option_yith_wcms_show_step_number', '__return_empty_string' );
            	add_action( 'pre_option_yith_wcms_timeline_template', 'yith_get_default_timeline_template' );
            	add_action( 'yith_wcms_step_use_icon', '__return_false' );
            	add_filter( 'pre_option_yith_wcms_timeline_options_skip_login', array( $this, 'skip_login_button' ) );
            }

	        /* === Timeline Customizzation === */
	        add_filter( 'yith_wcms_timeline_labels', array( $this, 'timeline_labels' ) );

            /* === Support to YITH WooCommerce Gift Cards === */
            if( function_exists( 'YITH_YWGC' ) ){
                remove_action ( 'woocommerce_before_checkout_form' , array ( YITH_YWGC()->frontend , 'show_field_for_gift_code' ) );
                add_action ( 'yith_woocommerce_checkout_coupon' , array ( YITH_YWGC()->frontend , 'show_field_for_gift_code' ), 5 );
            }

            /* === Support to Avada Theme === */
            if( class_exists( 'Avada' ) ){
                require_once( YITH_WCMS_PATH . 'includes/compatibility/avada.php' );
            }

            /* === Support to The Retailer Theme === */
            if( function_exists( 'theretailer_styles' ) ){
                require_once( YITH_WCMS_PATH . 'includes/compatibility/theretailer.php' );
            }

            /* === Support to StoreFront Theme === */
            if( class_exists( 'Storefront_WooCommerce' ) ){
                require_once( YITH_WCMS_PATH . 'includes/compatibility/storefront.php' );
            }

	        /* === Support to Astra Theme === */
	        if( class_exists( 'Astra_Woocommerce' ) ){
		        require_once( YITH_WCMS_PATH . 'includes/compatibility/astra.php' );
	        }

	        /* === Support to Porto Theme === */
	        if( defined( 'PORTO_VERSION' ) ){
		        require_once( YITH_WCMS_PATH . 'includes/compatibility/porto.php' );
	        }

	        /* === Support to Electro Theme === */
	        if( function_exists( 'electro_setup' ) ){
		        require_once( YITH_WCMS_PATH . 'includes/compatibility/electro.php' );
	        }

	        /* === Support to Medizin Theme === */
	        if( class_exists( 'Medizin_Woo' ) ){
		        require_once( YITH_WCMS_PATH . 'includes/compatibility/medizin.php' );
	        }

            /* === Support to WooCommerce Secure Submit Gateway === */
            if( class_exists( 'WC_Gateway_SecureSubmit' ) ){
                $secure_submit_options = get_option( 'woocommerce_securesubmit_settings' );
                if( ! empty( $secure_submit_options['use_iframes'] ) && 'yes' == $secure_submit_options['use_iframes'] ){
                    add_filter( 'option_woocommerce_securesubmit_settings', array( $this, 'woocommerce_securesubmit_support' ), 10, 2 );
                }
            }

            /* === Support to WooCommerce Points and Rewards === */
            if( class_exists( 'WC_Points_Rewards' ) ){
                global $wc_points_rewards;
                if( $wc_points_rewards instanceof WC_Points_Rewards ){
                    remove_action( 'woocommerce_before_checkout_form', array( $wc_points_rewards->cart, 'render_earn_points_message' ), 5 );
                    remove_action( 'woocommerce_before_checkout_form', array( $wc_points_rewards->cart, 'render_redeem_points_message' ), 6 );
                    add_action( 'woocommerce_checkout_before_customer_details', array( $wc_points_rewards->cart, 'render_earn_points_message' ), 5 );
                    add_action( 'yith_woocommerce_checkout_coupon', array( $wc_points_rewards->cart, 'render_redeem_points_message' ), 6 );
                }
            }
        }

        /**
         * Enqueue Scripts
         *
         * Register and enqueue scripts for Frontend
         *
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         * @since 1.0
         * @return void
         */
        public function enqueue_scripts(){
            /* === Style === */
	        $main_style_handle = 'yith-wcms-checkout';
            wp_register_style( $main_style_handle, YITH_WCMS_ASSETS_URL . 'css/frontend.css', array(), YITH_WCMS_VERSION );

            /* === Script === */
	        $script_version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? rand() : YITH_WCMS_VERSION;
            $script = apply_filters( 'yith_wcms_main_script', 'multistep.js' );
            $script = function_exists( 'yit_load_js_file' ) ? yit_load_js_file( $script ) : str_replace( '.js', '.min.js', $script );

            $wcms_deps = array( 'wc-checkout', 'wc-country-select', 'wc-password-strength-meter' );

            if( class_exists( 'WC_Ship_Multiple' ) ){
                $wcms_deps[] = 'wcms-country-select';
            }

            wp_register_script( 'yith-wcms-step', YITH_WCMS_ASSETS_URL . 'js/' . $script, $wcms_deps, $script_version, true );

            do_action( 'yith_wcms_enqueue_scripts' );

            if( is_checkout() ){
	            /* === Localize Script === */
	            $dom = apply_filters( 'yith_wcms_frontend_dom_object', array(
			            'login'                     => '#checkout_login',
			            'billing'                   => '#customer_billing_details',
			            'shipping'                  => '#customer_shipping_details',
			            'order'                     => '#order_info',
			            'payment'                   => '#order_checkout_payment',
			            'form_actions'              => '#form_actions',
			            'coupon'                    => '#checkout_coupon',
			            'button_prev'               => '.yith-wcms-button.prev',
			            'button_next'               => '.yith-wcms-button.next',
			            'checkout_timeline'         => '#checkout_timeline',
			            'checkout_form'             => 'form.woocommerce-checkout',
			            'timeline_id_prefix'        => '#timeline-',
		            )
	            );

	            $to_localize = apply_filters( 'yith_wcms_frontend_to_localize_object', array(
			            'dom'                  => $dom,
			            'steps_timeline'       => $this->get_steps_timeline(),
			            'wc_shipping_multiple' => class_exists( 'WC_Ship_Multiple' ),
			            'transition_duration'  => get_option( 'yith_wcms_timeline_fade_duration', 200 ),
		            )
	            );
                wp_enqueue_style( $main_style_handle );

                //Add step separator for text style
	            if( 'yes' == get_option( 'yith_wcms_text_step_separator_onoff', 'yes' ) ){
		            $step_separator = sprintf( '#checkout_timeline.horizontal.text li:not(:last-child) .timeline-wrapper:after{content: "%s";}', get_option( 'yith_wcms_text_step_separator', '/' ) );
		            wp_add_inline_style( $main_style_handle, $step_separator );
	            }

                wp_enqueue_script( 'yith-wcms-step' );
                wp_localize_script( 'yith-wcms-step', 'yith_wcms', $to_localize );
            }
        }

        /**
         * Enable multistep checkout
         *
         * Check if you want to load classic checkout or multistep checkout
         *
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         * @since  1.0
         *
         * @param $template
         * @param $template_name
         * @param $template_path
         *
         * @return void
         */
        public function multistep_checkout( $template, $template_name, $template_path ){
            if( apply_filters( 'yith_wcms_load_checkout_template_from_plugin', true ) && 'checkout/form-checkout.php' == $template_name ){
                $template = apply_filters( 'yith_wcms_template_path_checkout_form',YITH_WCMS_WC_TEMPLATE_PATH . 'checkout/form-checkout.php');
            }

            return $template;
        }

        /**
         * Disable iFrame on Secure Submit Payments Gateway
         *
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         * @since  1.4
         *
         * @param $value
         * @param $options
         *
         * @return array
         */
        public function woocommerce_securesubmit_support( $value, $options ){
            $value['use_iframes'] = 'no';
            return $value;
        }

	    /**
	     * Get the checkout style
	     *
	     * @author Andrea Grillo <andrea.grillo@yithemes.com>
	     * @since  2.0
	     *
	     * @return string checkout style
	     */
	    public function get_checkout_style(){
		    return get_option( 'yith_wcms_timeline_template', 'text' );
	    }

	    /**
	     * Check if a step is enabled
	     *
	     * @var string $step the step to check
	     * @author Andrea Grillo <andrea.grillo@yithemes.com>
	     * @since  2.0
	     *
	     * @return bool true if enabled, false otherwise
	     */
	    public function is_step_enabled( $step ){
		    return true;
	    }

	    /**
	     * get steps and timeline orders
	     *
	     * @return array steps
	     * @since  2.0.0
	     *
	     * @author Andrea Grillo <andrea.grillo@yithemes.com>
	     */
	    public function get_steps_timeline() {
		    $steps_timeline = array(
			    'login'    => array(
				    'prev' => false,
				    'next' => 'billing',
			    ),
			    'billing'  => array(
				    'prev' => 'login',
				    'next' => 'shipping',
			    ),
			    'shipping' => array(
				    'prev' => 'billing',
				    'next' => 'order'
			    ),
			    'order'    => array(
				    'prev' => 'shipping',
				    'next' => 'payment'
			    ),
			    'payment'  => array(
				    'next' => false,
				    'prev' => 'order'
			    )
		    );

		    /**
		     * If Shipping step isn't available
		     * I need to change next and prev steps for
		     * billing and order
		     */
		    if ( ! $this->is_step_enabled( 'shipping' ) ) {
			    $steps_timeline['billing']['next'] = 'order';
			    $steps_timeline['order']['prev']   = 'billing';
		    }

		    /**
		     * If Payment step isn't available
		     * I need to change next step for order
		     */
		    if ( ! $this->is_step_enabled( 'payment' ) ) {
			    $steps_timeline['order']['next'] = false;
		    }

		    /**
		     * If billing is the first step I need to set
		     * the prev option to false.
		     *
		     * Billing is the first step if:
		     * 1. Current user is logged in
		     * 2. Login box disabled
		     */
		    $woocommerce_enable_checkout_login_reminder = get_option( 'woocommerce_enable_checkout_login_reminder', 'no' );

		    if ( is_user_logged_in() || 'no' === $woocommerce_enable_checkout_login_reminder ) {
			    $steps_timeline['billing']['prev'] = false;
		    }

		    return $steps_timeline;
	    }

	    /**
	     * Add timeline style
	     *
	     * @return   array
	     * @since    1.0
	     * @author   Andrea Grillo <andrea.grillo@yithemes.com>
	     */
	    public function timeline_style() {
		    if ( ! is_checkout() ) {
			    return false;
		    }

		    ob_start();
		    yith_wcms_get_template( "timeline-text.php", array(), 'style' );
		    echo ob_get_clean();
	    }

	    /**
	     * Change Timeline and Button Label
	     *
	     * @author Andrea Grillo <andrea.grillo@yithemes.com>
	     * @since  2.0
	     */
	    public function timeline_labels( $labels = array() ) {
		    $default_labels = array(
			    'next'          => esc_html__( 'Next', 'yith-woocommerce-multi-step-checkout' ),
			    'skip_login'    => esc_html__( 'Next', 'yith-woocommerce-multi-step-checkout' ),
			    'prev'          => esc_html__( 'Previous', 'yith-woocommerce-multi-step-checkout' ),
			    'login'         => esc_html_x( 'Login', 'Checkout: user timeline', 'yith-woocommerce-multi-step-checkout' ),
			    'billing'       => esc_html_x( 'Billing', 'Checkout: user timeline', 'yith-woocommerce-multi-step-checkout' ),
			    'shipping'      => esc_html_x( 'Shipping', 'Checkout: user timeline', 'yith-woocommerce-multi-step-checkout' ),
			    'order'         => esc_html_x( 'Order Info', 'Checkout: user timeline', 'yith-woocommerce-multi-step-checkout' ),
			    'payment'       => esc_html_x( 'Payment Info', 'Checkout: user timeline', 'yith-woocommerce-multi-step-checkout' ),
			    'back_to_cart'  => esc_html_x( 'Back to cart', 'Frontend: button label', 'yith-woocommerce-multi-step-checkout' )
		    );

		    $labels = empty( $labels ) ? $default_labels : $labels;

		    return array(
			    'login'        => get_option( 'yith_wcms_timeline_options_login', $labels['login'] ),
			    'skip_login'   => get_option( 'yith_wcms_timeline_options_skip_login', $labels['skip_login'] ),
			    'billing'      => get_option( 'yith_wcms_timeline_options_billing', $labels['billing'] ),
			    'shipping'     => get_option( 'yith_wcms_timeline_options_shipping', $labels['shipping'] ),
			    'order'        => get_option( 'yith_wcms_timeline_options_order', $labels['order'] ),
			    'payment'      => get_option( 'yith_wcms_timeline_options_payment', $labels['payment'] ),
			    'next'         => get_option( 'yith_wcms_timeline_options_next', $labels['next'] ),
			    'prev'         => get_option( 'yith_wcms_timeline_options_prev', $labels['prev'] ),
			    'back_to_cart' => get_option( 'yith_wcms_timeline_options_back_to_cart', $labels['back_to_cart'] ),
		    );
	    }

	    /**
	     * Get the next string for skip login button
	     *
	     * @param $pre mixed the pre option
	     *
	     * @return string next button string
	     * @author Andrea Grillo <andrea.grillo@yithemes.com>
	     * @since 2.0
	     */
	    public function skip_login_button( $pre ){
	    	$default = esc_html__( 'Next', 'yith-woocommerce-multi-step-checkout' );
	    	$pre = get_option( 'yith_wcms_timeline_options_next', $default );
	    	return $pre;
	    }
    }
}
