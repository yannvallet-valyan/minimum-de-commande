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

// Page de réglages dédiée dans le menu WordPress (Réglages → Minimum de commande)
add_action( 'admin_menu', 'mdc_ajouter_page_reglages' );

function mdc_ajouter_page_reglages() {
	add_options_page(
		__( 'Minimum de Commande', 'minimum-de-commande' ),
		__( 'Minimum de Commande', 'minimum-de-commande' ),
		'manage_options',
		'minimum-de-commande',
		'mdc_afficher_page_reglages'
	);
}

function mdc_afficher_page_reglages() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_POST['mdc_montant_minimum'] ) && check_admin_referer( 'mdc_enregistrer' ) ) {
		update_option( 'mdc_montant_minimum', floatval( $_POST['mdc_montant_minimum'] ) );
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Réglages enregistrés.', 'minimum-de-commande' ) . '</p></div>';
	}

	$montant = mdc_get_minimum();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Minimum de Commande', 'minimum-de-commande' ); ?></h1>
		<form method="post">
			<?php wp_nonce_field( 'mdc_enregistrer' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="mdc_montant_minimum"><?php esc_html_e( 'Montant minimum (€)', 'minimum-de-commande' ); ?></label>
					</th>
					<td>
						<input
							type="number"
							id="mdc_montant_minimum"
							name="mdc_montant_minimum"
							value="<?php echo esc_attr( $montant ); ?>"
							min="0"
							step="0.01"
							style="width:100px;"
						/>
						<p class="description"><?php esc_html_e( 'Montant minimum requis pour passer une commande.', 'minimum-de-commande' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Enregistrer', 'minimum-de-commande' ) ); ?>
		</form>
	</div>
	<?php
}
