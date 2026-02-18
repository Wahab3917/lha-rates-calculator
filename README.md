## LHA Rates Calculator

LHA Rates Calculator is a WordPress plugin that lets you embed a simple, two‑step Local Housing Allowance (LHA) calculator anywhere on your site using a shortcode.  
It securely calls the PropertyData LHA Rate API using server‑side AJAX and can email each calculation (with user details and the result) to a notification address you control.

## Features

- **Two‑step calculator flow**
  - Step 1: Collect **name** and **email**.
  - Step 2: Collect **postcode** and **bedrooms**, then fetch the LHA rate.
- **Inline validation & error display**
  - Required fields validated in the browser.
- **Server‑side API calls**
  - PropertyData API key is never exposed in the browser.
  - Uses WordPress AJAX (`admin-ajax.php`) to proxy the request.
- **Email notifications**
  - Sends a summary email (user details + calculation + result) to a configured notification address.

## Requirements

- WordPress **5.0+** (recommended 6.x).
- PHP **7.4+**.
- A valid **PropertyData API key** with access to the LHA Rate endpoint.

## Installation

1. **Upload the plugin**
   - Copy the `lha-rates-calculator` folder into your WordPress `wp-content/plugins/` directory, or
   - Zip the folder and upload via **Plugins → Add New → Upload Plugin** in wp‑admin.
2. **Activate**
   - Go to **Plugins → Installed Plugins** and activate **“LHA Rates Calculator”**.

## Configuration

After activation, configure the plugin settings:

1. In wp‑admin, go to **Settings → LHA Rates**.
2. Fill in:
   - **PropertyData API key**  
     - Paste your API key exactly as provided by PropertyData.
   - **Notification Email**  
     - The email address that should receive calculation notifications.
3. Click **Save Changes**.

## Usage (Shortcode)

To display the calculator on any page or post, add this shortcode in the editor:

```text
[lha_rates_calculator]
```

You can place it in:
- A regular page or post (Block Editor / Classic Editor).
- A widget area that supports shortcodes.
- Page builder shortcode blocks/HTML modules (Elementor, Divi, etc.).

## Security Notes

- Direct access to the plugin file is blocked via the standard `ABSPATH` check.
- API key is stored in WordPress options and only used on the server.
- All AJAX requests are protected with a **nonce** (`lha_rates_nonce`) and use `check_ajax_referer`.
- User inputs are sanitized on both client and server sides before use.

## License

This plugin is provided as‑is. You are free to modify it for your own projects. If you redistribute it, please keep the original author credit.