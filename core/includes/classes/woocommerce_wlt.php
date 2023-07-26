<?php 
/**
 * Adding and managing a WooCommerce wallet system with the WC_Payment_Gateway class.
 * This allows users to use their wallet balance as a payment method during checkout.
 */

// Add the custom wallet gateway to the list of available payment gateways.
add_filter('woocommerce_payment_gateways', 'add_my_wallet_gateway');

// Initialize the wallet gateway class when plugins are loaded.
add_action('plugins_loaded', 'init_your_gateway_class');

// Function to add the custom wallet gateway to available payment gateways.
function add_my_wallet_gateway($methods)
{
    $methods[] = 'WC_My_Wallet_Gateway';
    return $methods;
}

// Function to initialize the custom wallet gateway class.
function init_your_gateway_class()
{
    // Check if the WC_Payment_Gateway class exists, if not, return.
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    // Create the custom wallet gateway class by extending WC_Payment_Gateway.
    class WC_My_Wallet_Gateway extends WC_Payment_Gateway
    {
        public function __construct()
        {
            // Initialize the custom wallet gateway properties.
            $this->id = 'my_wallet_gateway';
            $this->icon = ''; // Add the URL of the gateway icon if needed.
            $this->has_fields = false;
            $this->method_title = 'My Wallet Gateway';
            $this->method_description = 'Pay using your wallet balance.';

            // Initialize form fields and settings.
            $this->init_form_fields();
            $this->init_settings();

            // Get the title and description from settings.
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');

            // Hook to save the settings when admin saves the options.
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            // Hook to update wallet balance after successful order placement.
            add_action('woocommerce_thankyou', array($this, 'update_wallet_balance_after_order'), 10, 1);

            // Hook to display wallet balance on the checkout page.
            add_action('woocommerce_review_order_before_payment', array($this, 'display_wallet_balance'));

            // Hook to validate user login before placing the order.
            add_action('woocommerce_review_order_before_submit', array($this, 'validate_user_logged_in'));

            // Hook to validate wallet balance before processing the order.
            add_action('woocommerce_checkout_process', 'validate_wallet_balance');
        }

        // Initialize form fields for the wallet gateway settings in WooCommerce admin.
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

        // Display wallet balance on the WooCommerce checkout page.
        public function display_wallet_balance()
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

        // Validate if the user is logged in before proceeding to place the order.
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

        // Validate wallet balance before processing the order.
        public function validate_wallet_balance()
        {
            if (is_user_logged_in() && isset($_POST['payment_method']) && $_POST['payment_method'] === 'my_wallet_gateway') {
                $user_id = get_current_user_id();
                $wallet_balance = get_user_meta($user_id, 'wallet_balance', true);

                if ($wallet_balance < WC()->cart->total) {
                    $difference = wc_price(WC()->cart->total - $wallet_balance);
                    $currency_symbol = get_woocommerce_currency_symbol();
                    $message = sprintf(__('Your wallet balance is low. You need %s more to place this order.', 'textdomain'), $currency_symbol . $difference);
                    wc_add_notice($message, 'error');
                    return;
                }
            }
        }

        // Process the payment when the "Place Order" button is clicked.
        public function process_payment($order_id)
        {
            $user_id = get_current_user_id();
            $wallet_balance = get_user_meta($user_id, 'wallet_balance', true);
            $order = wc_get_order($order_id);
            $order_total = $order->get_total();

            // Validate user login before processing the order.
            if (!is_user_logged_in()) {
                wc_add_notice(__('You must be logged in to use the My Wallet Gateway payment method.', 'textdomain'), 'error');
                return array(
                    'result' => 'fail',
                    'redirect' => '',
                );
            }

            // Check if the wallet balance is sufficient to place the order.
            if ($order_total >= $wallet_balance) {
                $currency_symbol = wc_price($wallet_balance);
                $message = sprintf(__('Your Wallet Balance is %s which is less than your Order. Contact Admin', 'textdomain'), $currency_symbol);
                wc_add_notice(__($message, 'textdomain'), 'error');
                return array(
                    'result' => 'fail',
                    'redirect' => '',
                );
            }

            // Process the payment and mark the order as on-hold.
            global $woocommerce;
            $order = new WC_Order($order_id);
            $order->update_status('on-hold', __('Awaiting cheque payment', 'woocommerce'));

            // Remove cart items after successful order placement.
            $woocommerce->cart->empty_cart();

            // Return thankyou redirect after successful order placement.
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        }

        // Update the user's wallet balance after a successful order is placed.
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
    }
}
