<?php
/**
 * Plugin Name: Minimum de Commande
 * Description: Définit un montant minimum de commande pour WooCommerce.
 * Version: 1.0.0
 * Author: Yann Vallet
 * Text Domain: minimum-de-commande
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'plugins_loaded', 'mdc_init' );

function mdc_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p>'
				. esc_html__( 'Minimum de Commande nécessite WooCommerce. Veuillez l\'installer et l\'activer.', 'minimum-de-commande' )
				. '</p></div>';
		} );
		return;
	}

	add_action( 'woocommerce_checkout_process', 'mdc_verifier_minimum' );
	add_action( 'woocommerce_before_cart', 'mdc_afficher_notice_panier' );
}

function mdc_get_minimum() {
	return (float) get_option( 'mdc_montant_minimum', 50 );
}

function mdc_verifier_minimum() {
	$minimum = mdc_get_minimum();
	$total   = WC()->cart->get_subtotal();

	if ( $total < $minimum ) {
		wc_add_notice(
			sprintf(
				__( 'Le montant minimum de commande est de %s. Votre panier actuel est de %s.', 'minimum-de-commande' ),
				wc_price( $minimum ),
				wc_price( $total )
			),
			'error'
		);
	}
}

function mdc_afficher_notice_panier() {
	$minimum = mdc_get_minimum();
	$total   = WC()->cart->get_subtotal();

	if ( $total < $minimum ) {
		wc_print_notice(
			sprintf(
				__( 'Montant minimum de commande : %s (actuellement %s).', 'minimum-de-commande' ),
				wc_price( $minimum ),
				wc_price( $total )
			),
			'notice'
		);
	}
}

// Page de réglages dans l'admin WooCommerce
add_filter( 'woocommerce_get_sections_products', 'mdc_ajouter_section' );

function mdc_ajouter_section( $sections ) {
	$sections['minimum_de_commande'] = __( 'Minimum de commande', 'minimum-de-commande' );
	return $sections;
}

add_filter( 'woocommerce_get_settings_products', 'mdc_reglages', 10, 2 );

function mdc_reglages( $settings, $current_section ) {
	if ( 'minimum_de_commande' !== $current_section ) {
		return $settings;
	}

	return array(
		array(
			'title' => __( 'Minimum de commande', 'minimum-de-commande' ),
			'type'  => 'title',
			'id'    => 'mdc_section',
		),
		array(
			'title'   => __( 'Montant minimum', 'minimum-de-commande' ),
			'desc'    => __( 'Montant minimum requis pour passer une commande.', 'minimum-de-commande' ),
			'id'      => 'mdc_montant_minimum',
			'type'    => 'number',
			'default' => '50',
			'css'     => 'width:100px;',
		),
		array(
			'type' => 'sectionend',
			'id'   => 'mdc_section',
		),
	);
}
