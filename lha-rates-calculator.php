<?php
/**
 * Plugin Name: LHA Rates Calculator
 * Description: Fetch Local Housing Allowance (LHA) rates using PropertyData API.
 * Version: 1.0.0
 * Author: Abdul Wahab
 * Author URI: https://github.com/Wahab3917
 * Text Domain: lha-rates-calculator
 */

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

/**
 * Add settings page under Settings -> LHA Rates
 */
add_action('admin_menu', 'lha_rates_add_admin_menu');
function lha_rates_add_admin_menu()
{
  add_options_page(
    __('LHA Rates Settings', 'lha-rates-calculator'),
    __('LHA Rates', 'lha-rates-calculator'),
    'manage_options',
    'lha-rates-calculator',
    'lha_rates_settings_page'
  );
}

/**
 * Register option for API key
 */
add_action('admin_init', 'lha_rates_settings_init');
function lha_rates_settings_init()
{
  register_setting('lha_rates_options_group', 'lha_rates_api_key', array(
    'sanitize_callback' => 'sanitize_text_field',
    'default' => '',
  ));
  register_setting('lha_rates_options_group', 'lha_rates_notification_email', array(
    'sanitize_callback' => 'sanitize_email',
    'default' => '',
  ));
}

/**
 * Settings page markup
 */
function lha_rates_settings_page()
{
  if (!current_user_can('manage_options')) {
    return;
  }
  ?>
  <div class="wrap">
    <h1><?php esc_html_e('LHA Rates Calculator Settings', 'lha-rates-calculator'); ?></h1>
    <form method="post" action="options.php">
      <?php
      settings_fields('lha_rates_options_group');
      do_settings_sections('lha_rates_options_group');
      $key = get_option('lha_rates_api_key', '');
      $notification_email = get_option('lha_rates_notification_email', '');
      ?>
      <table class="form-table" role="presentation">
        <tr>
          <th scope="row"><label
              for="lha_rates_api_key"><?php esc_html_e('PropertyData API key', 'lha-rates-calculator'); ?></label></th>
          <td>
            <input name="lha_rates_api_key" type="text" id="lha_rates_api_key" value="<?php echo esc_attr($key); ?>"
              class="regular-text">
            <p class="description"><?php esc_html_e('Enter your PropertyData API key.', 'lha-rates-calculator'); ?></p>
          </td>
        </tr>
        <tr>
          <th scope="row"><label
              for="lha_rates_notification_email"><?php esc_html_e('Notification Email', 'lha-rates-calculator'); ?></label>
          </th>
          <td>
            <input name="lha_rates_notification_email" type="email" id="lha_rates_notification_email"
              value="<?php echo esc_attr($notification_email); ?>" class="regular-text">
            <p class="description">
              <?php esc_html_e('Email address to receive user details and calculation notifications.', 'lha-rates-calculator'); ?>
            </p>
          </td>
        </tr>
      </table>
      <?php submit_button(); ?>
    </form>

    <h2><?php esc_html_e('Usage', 'lha-rates-calculator'); ?></h2>
    <p>
      <?php esc_html_e('Use the following shortcode to display the calculator on any page or post:', 'lha-rates-calculator'); ?>
    </p>
    <code>[lha_rates_calculator]</code>
  </div>
  <?php
}


/**
 * Shortcode output (also enqueues assets only when shortcode is used)
 */
add_shortcode('lha_rates_calculator', 'lha_rates_calculator_shortcode');
function lha_rates_calculator_shortcode($atts = [])
{
  // Enqueue front-end assets
  wp_enqueue_style('lha-rates-style', plugin_dir_url(__FILE__) . 'assets/styles.css');
  wp_enqueue_script('lha-rates-script', plugin_dir_url(__FILE__) . 'assets/script.js', array(), '1.0.0', true);

  // Pass ajax url + nonce to script
  wp_localize_script('lha-rates-script', 'LHA_Rates', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('lha_rates_nonce'),
  ));

  ob_start();
  ?>
  <div class="lha-calculator">
    <!-- Step 1: User Details -->
    <div id="lha-step-1" class="lha-step">
      <h3>Enter Your Details</h3>
      <div class="lha-input-group">
        <label for="lha-name">Name *</label>
        <input id="lha-name" name="name" type="text" placeholder="Your name" />
      </div>

      <div class="lha-input-group">
        <label for="lha-email">Email *</label>
        <input id="lha-email" name="email" type="email" placeholder="your@email.com" />
      </div>

      <button class="lha-btn" id="lha-continueBtn" type="button">Continue</button>
    </div>

    <!-- Step 2: Calculator -->
    <div id="lha-step-2" class="lha-step lha-hidden">
      <h3>Calculate LHA Rate</h3>
      <div class="lha-input-group">
        <label for="lha-postcode">Enter postcode *</label>
        <input id="lha-postcode" name="postcode" type="text" placeholder="e.g. W149JH" />
      </div>

      <div class="lha-input-group">
        <label for="lha-bedrooms">Bedrooms (1–4) *</label>
        <input id="lha-bedrooms" name="bedrooms" type="number" min="1" max="4" placeholder="2" />
      </div>

      <button class="lha-btn" id="lha-getRateBtn" type="button">Get LHA Rate</button>
    </div>

    <!-- Result / error message -->
    <p id="lha-rate" class="lha-rate lha-hidden" aria-live="polite" role="status"></p>
  </div>
  <?php
  return ob_get_clean();
}

/**
 * AJAX handler - server-side fetch to protect API key
 */
add_action('wp_ajax_lha_rates_fetch', 'lha_rates_fetch_ajax');
add_action('wp_ajax_nopriv_lha_rates_fetch', 'lha_rates_fetch_ajax');

function lha_rates_fetch_ajax()
{
  // Verify nonce
  check_ajax_referer('lha_rates_nonce', 'nonce');

  // Read and sanitize inputs
  $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
  $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
  $postcode = isset($_POST['postcode']) ? sanitize_text_field(wp_unslash($_POST['postcode'])) : '';
  $bedrooms = isset($_POST['bedrooms']) ? intval($_POST['bedrooms']) : 0;

  if (empty($postcode) || empty($bedrooms)) {
    wp_send_json_error('Missing postcode or bedrooms.');
  }

  if (empty($name) || empty($email)) {
    wp_send_json_error('Missing name or email.');
  }

  // Retrieve API key from options
  $api_key = get_option('lha_rates_api_key', '');
  if (empty($api_key)) {
    wp_send_json_error('API key not configured. Go to Settings > LHA Rates.');
  }

  // Build API URL
  $api_url = add_query_arg(array(
    'key' => rawurlencode($api_key),
    'postcode' => rawurlencode($postcode),
    'bedrooms' => $bedrooms,
  ), 'https://api.propertydata.co.uk/lha-rate');

  $response = wp_remote_get($api_url, array('timeout' => 15));

  if (is_wp_error($response)) {
    wp_send_json_error($response->get_error_message());
  }

  $code = wp_remote_retrieve_response_code($response);
  $body = wp_remote_retrieve_body($response);

  if ($code !== 200) {
    // pass raw body as message if needed
    wp_send_json_error('API request failed with HTTP ' . $code . ' — ' . wp_trim_words($body, 30));
  }

  // Decode JSON and return it
  $json = json_decode($body, true);
  if (json_last_error() !== JSON_ERROR_NONE) {
    wp_send_json_error('Invalid JSON from API.');
  }

  // Send email notification
  lha_rates_send_notification($name, $email, $postcode, $bedrooms, $json);

  wp_send_json_success($json);
}

/**
 * Send email notification with user details and LHA rate result
 */
function lha_rates_send_notification($name, $email, $postcode, $bedrooms, $api_response)
{
  $notification_email = get_option('lha_rates_notification_email', '');

  // Don't send if no notification email is configured
  if (empty($notification_email)) {
    return;
  }

  // Extract rate information from API response
  $brma = '';
  $rate = '';

  if (isset($api_response['data']['brma'])) {
    $brma = $api_response['data']['brma'];
  } elseif (isset($api_response['brma'])) {
    $brma = $api_response['brma'];
  }

  if (isset($api_response['data']['rate'])) {
    $rate = $api_response['data']['rate'];
  } elseif (isset($api_response['rate'])) {
    $rate = $api_response['rate'];
  }

  // Prepare email content
  $subject = 'New LHA Rate Calculation - ' . $name;

  $message = "New LHA Rate calculation submitted:\n\n";
  $message .= "User Details:\n";
  $message .= "Name: " . $name . "\n";
  $message .= "Email: " . $email . "\n\n";
  $message .= "Calculation Details:\n";
  $message .= "Postcode: " . $postcode . "\n";
  $message .= "Bedrooms: " . $bedrooms . "\n\n";
  $message .= "Result:\n";
  $message .= "BRMA: " . $brma . "\n";
  $message .= "Rate: £" . $rate . "\n\n";
  $message .= "---\n";
  $message .= "This email was sent from LHA Rates Calculator plugin.\n";
  $message .= "Time: " . current_time('mysql');

  // Set headers
  $headers = array(
    'Content-Type: text/plain; charset=UTF-8',
    'Reply-To: ' . $email,
  );

  // Send email
  wp_mail($notification_email, $subject, $message, $headers);
}
