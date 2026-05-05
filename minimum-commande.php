<?php
/**
 * Plugin Name: Minimum de commande
 * Description: Définit un montant minimum de commande HT (hors frais de port) et expose un réglage dans le menu WooCommerce.
 * Version:     1.0.0
 * Text Domain: minimum-commande
 * Requires Plugins: woocommerce
 */

defined( 'ABSPATH' ) || exit;

// ──────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────

function wmc_get_minimum(): float {
    return (float) get_option( 'wmc_order_minimum', 80 );
}

function wmc_notice( float $minimum, float $total ): string {
    return sprintf(
        'Commande minimum de <strong>%s&nbsp;€ HT</strong>. Votre panier actuel est de <strong>%s&nbsp;€ HT</strong>.',
        number_format( $minimum, 2, ',', '&nbsp;' ),
        number_format( $total,   2, ',', '&nbsp;' )
    );
}

// ──────────────────────────────────────────────
// 1. Notice sur la page panier
// ──────────────────────────────────────────────

add_action( 'woocommerce_check_cart_items', function () {
    $minimum = wmc_get_minimum();
    $total   = WC()->cart->get_subtotal();

    if ( $total < $minimum ) {
        wc_add_notice( wmc_notice( $minimum, $total ), 'error' );
    }
} );

// ──────────────────────────────────────────────
// 2. Bloquer l'accès au checkout par URL directe
// ──────────────────────────────────────────────

add_action( 'template_redirect', function () {
    if ( ! is_checkout() || is_order_received_page() ) {
        return;
    }

    $minimum = wmc_get_minimum();
    $total   = WC()->cart->get_subtotal();

    if ( WC()->cart && $total > 0 && $total < $minimum ) {
        wc_add_notice( wmc_notice( $minimum, $total ), 'error' );
        wp_redirect( wc_get_cart_url() );
        exit;
    }
} );

// ──────────────────────────────────────────────
// 3. Bloquer la soumission du checkout (sécurité)
// ──────────────────────────────────────────────

add_action( 'woocommerce_checkout_process', function () {
    $minimum = wmc_get_minimum();
    $total   = WC()->cart->get_subtotal();

    if ( $total < $minimum ) {
        wc_add_notice(
            sprintf(
                'Commande minimum de <strong>%s&nbsp;€ HT</strong>.',
                number_format( $minimum, 2, ',', '&nbsp;' )
            ),
            'error'
        );
    }
} );

// ──────────────────────────────────────────────
// 4. Page de réglages dans le menu WooCommerce
// ──────────────────────────────────────────────

add_filter( 'woocommerce_get_settings_pages', function ( array $settings ): array {
    $settings[] = new WMC_Minimum_Settings_Page();
    return $settings;
} );

class WMC_Minimum_Settings_Page extends WC_Settings_Page {

    public function __construct() {
        $this->id    = 'wmc_minimum';
        $this->label = 'Minimum de commande';
        parent::__construct();
    }

    public function get_settings(): array {
        return [
            [
                'title' => 'Minimum de commande',
                'type'  => 'title',
                'desc'  => 'Définissez le montant minimum HT (hors frais de port) requis pour valider une commande.',
                'id'    => 'wmc_minimum_section',
            ],
            [
                'title'             => 'Montant minimum (€ HT)',
                'type'              => 'number',
                'desc'              => 'Le client ne pourra pas accéder au paiement si son panier HT est inférieur à cette valeur.',
                'id'                => 'wmc_order_minimum',
                'default'           => '80',
                'css'               => 'width:100px;',
                'custom_attributes' => [
                    'min'  => '0',
                    'step' => '0.01',
                ],
            ],
            [
                'type' => 'sectionend',
                'id'   => 'wmc_minimum_section',
            ],
        ];
    }
}
