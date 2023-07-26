<?php
add_filter('woocommerce_payment_gateways', 'add_my_wallet_gateway');
add_action('plugins_loaded', 'init_your_gateway_class');
function add_my_wallet_gateway($methods)
{
    $methods[] = 'WC_My_Wallet_Gateway';
    return $methods;
}
function init_your_gateway_class()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }
    class WC_My_Wallet_Gateway extends WC_Payment_Gateway
    {
        public function __construct()
        {
            $this->id = 'my_wallet_gateway';
            $this->icon = ''; // Add the URL of the gateway icon if needed.
            $this->has_fields = false;
            $this->method_title = 'My Wallet Gateway';
            $this->method_description = 'Pay using your wallet balance.';

            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_thankyou', array($this, 'update_wallet_balance_after_order'), 10, 1);
            add_action('woocommerce_review_order_before_payment', array($this, 'display_wallet_balance'));
            add_action('woocommerce_review_order_before_submit', array($this, 'validate_user_logged_in'));
            add_action('woocommerce_checkout_process', 'validate_wallet_balance');
        }

        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'textdomain'),
                    'type' => 'checkbox',
                    'label' => __('Enable My Wallet Gateway', 'textdomain'),
                    'default' => 'yes',
                ),
                'title' => array(
                    'title' => __('Title', 'textdomain'),
                    'type' => 'text',
                    'description' => __('This controls the payment method title that the customer sees during checkout.', 'textdomain'),
                    'default' => __('My Wallet', 'textdomain'),
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => __('Description', 'textdomain'),
                    'type' => 'textarea',
                    'description' => __('Payment method description that the customer will see on your website.', 'textdomain'),
                    'default' => __('Pay using your wallet balance.', 'textdomain'),
                    'desc_tip' => true,
                ),
            );
        }
        public function admin_options()
        {
            echo '<h3>' . __('WooWallet', 'woo-payment-gateway-for-vivapayments') . '</h3>';
            echo '<p>' . __('Wallet Payment Gateway allows you to accept payment through in built wallet system', 'wc_wlt') . '</p>';

            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';
        }

        function process_payment($order_id)
        {
            $user_id = get_current_user_id();
            $wallet_balance = get_user_meta($user_id, 'wallet_balance', true);
            $order = wc_get_order($order_id);
            $order_total = $order->get_total();
            if (!is_user_logged_in()) {
                wc_add_notice(__('You must be logged in to use the My Wallet Gateway payment method.', 'textdomain'), 'error');
                return array(
                    'result' => 'fail',
                    'redirect' => '',
                );
            }
            if ($order_total >= $wallet_balance) {
                $currency_symbol = wc_price($wallet_balance);
                $message = sprintf(__('Your Wallet Balance is %s which is less than your Order. Contact Admin', 'textdomain'), $currency_symbol);
                wc_add_notice(__($message, 'textdomain'), 'error');
                return array(
                    'result' => 'fail',
                    'redirect' => '',
                );
            }
            global $woocommerce;
            $order = new WC_Order($order_id);

            // Mark as on-hold (we're awaiting the cheque)
            $order->update_status('on-hold', __('Awaiting cheque payment', 'woocommerce'));

            // Remove cart
            $woocommerce->cart->empty_cart();

            // Return thankyou redirect
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        }

        public function update_wallet_balance_after_order($order_id)
        {
            // Update the user's wallet balance after a successful order is placed.
            if ($order_id) {
                $order = wc_get_order($order_id);
                $user_id = $order->get_user_id();
                $wallet_balance = floatval(get_user_meta($user_id, 'wallet_balance', true));
                $order_total = $order->get_total();

                if ($wallet_balance >= $order_total) {
                    $new_balance = $wallet_balance - $order_total;
                    update_user_meta($user_id, 'wallet_balance', $new_balance);
                }
            }
        }
        public function display_wallet_balance($order_id)
        {
            $user_id = get_current_user_id();
            $wallet_balance = get_user_meta($user_id, 'wallet_balance', true);

            if ($user_id && $wallet_balance) {
                ?>
                <tr class="wallet-balance">
                    <th>
                        <?php _e('Wallet Balance:', 'wc_wlt'); ?>
                    </th>
                    <td>
                        <?php echo wc_price($wallet_balance); ?>
                    </td>
                </tr>
                <?php
            }
        }
        public function validate_user_logged_in()
        {
            if (is_user_logged_in()) {
                return; // User is logged in, no need to show the error message
            }

            // Check if the My Wallet Gateway payment method is selected
            if (isset($_POST['payment_method']) && $_POST['payment_method'] === $this->id) {
                // Show an error message
                wc_add_notice(__('You must be logged in to use the My Wallet Gateway payment method.', 'textdomain'), 'error');
            }
        }
        function validate_wallet_balance() {
            if (is_user_logged_in() && isset($_POST['payment_method']) && $_POST['payment_method'] === 'my_wallet_gateway') {
                $user_id = get_current_user_id();
                $wallet_balance = get_user_meta($user_id, 'wallet_balance', true);
                
                if ($wallet_balance < WC()->cart->total) {
                    $difference = wc_price(WC()->cart->total - $wallet_balance);
                    $message = sprintf(__('Your wallet balance is low. You need %s more to place this order.', 'textdomain'), $difference);
                    wc_add_notice($message, 'error');
                    return;
                }
            }
        }
    }
}