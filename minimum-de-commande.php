<?php
/**
 * Plugin Name: Minimum de Commande
 * Description: Définit un montant minimum de commande pour WooCommerce.
 * Version: 1.0.1
 * Author: Yann Vallet
 * Text Domain: minimum-de-commande
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Minimum_De_Commande {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'admin_menu',     array( $this, 'ajouter_menu' ) );
	}

	public function init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'notice_woocommerce_manquant' ) );
			return;
		}

		add_action( 'woocommerce_checkout_process',  array( $this, 'verifier_minimum' ) );
		add_action( 'woocommerce_before_cart',        array( $this, 'afficher_notice_panier' ) );
	}

	public function notice_woocommerce_manquant() {
		echo '<div class="notice notice-error"><p>'
			. esc_html__( 'Minimum de Commande nécessite WooCommerce. Veuillez l\'installer et l\'activer.', 'minimum-de-commande' )
			. '</p></div>';
	}

	public function get_montant_minimum() {
		return (float) get_option( 'mdc_montant_minimum', 50 );
	}

	public function verifier_minimum() {
		if ( ! WC()->cart ) {
			return;
		}

		$minimum = $this->get_montant_minimum();
		$total   = WC()->cart->get_subtotal();

		if ( $total < $minimum ) {
			wc_add_notice(
				sprintf(
					/* translators: 1: montant minimum, 2: total panier */
					__( 'Le montant minimum de commande est de %1$s. Votre panier actuel est de %2$s.', 'minimum-de-commande' ),
					wc_price( $minimum ),
					wc_price( $total )
				),
				'error'
			);
		}
	}

	public function afficher_notice_panier() {
		if ( ! WC()->cart ) {
			return;
		}

		$minimum = $this->get_montant_minimum();
		$total   = WC()->cart->get_subtotal();

		if ( $total < $minimum ) {
			wc_add_notice(
				sprintf(
					/* translators: 1: montant minimum, 2: total panier */
					__( 'Montant minimum de commande : %1$s (actuellement %2$s).', 'minimum-de-commande' ),
					wc_price( $minimum ),
					wc_price( $total )
				),
				'notice'
			);
		}
	}

	public function ajouter_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Minimum de Commande', 'minimum-de-commande' ),
			__( 'Minimum de Commande', 'minimum-de-commande' ),
			'manage_woocommerce',
			'minimum-de-commande',
			array( $this, 'afficher_page_reglages' )
		);
	}

	public function afficher_page_reglages() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( isset( $_POST['mdc_montant_minimum'] ) && check_admin_referer( 'mdc_enregistrer' ) ) {
			update_option( 'mdc_montant_minimum', floatval( $_POST['mdc_montant_minimum'] ) );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Réglages enregistrés.', 'minimum-de-commande' ) . '</p></div>';
		}

		$montant = $this->get_montant_minimum();
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
}

Minimum_De_Commande::get_instance();
