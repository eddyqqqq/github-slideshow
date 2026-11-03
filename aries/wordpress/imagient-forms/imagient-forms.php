<?php
/**
 * Plugin Name:       Imagient Forms
 * Description:       Forms built from blocks, as in Elementor: a Form block whose fields (text, email, phone, message, dropdown, checkboxes, radio buttons, consent, number, date, hidden) are added, ordered and set in the editor, with its recipients, subject, reply-to, confirmation email and what happens after sending (a message or a redirect to any page) in the block's sidebar. Every message is kept under Forms in the admin (CSV export) and sent by email through WordPress (an SMTP plugin works with it). Spam is stopped without a visible captcha; Cloudflare Turnstile can be switched on.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Imagient
 * Author URI:        https://imagient.ro
 * License:           GPL-2.0-or-later
 * Text Domain:       imagient-forms
 */

defined( 'ABSPATH' ) || exit;

define( 'IGF_VERSION', '1.0.0' );
define( 'IGF_DIR', plugin_dir_path( __FILE__ ) );
define( 'IGF_URL', plugin_dir_url( __FILE__ ) );

require IGF_DIR . 'inc/fields.php';
require IGF_DIR . 'inc/blocks.php';
require IGF_DIR . 'inc/submit.php';
require IGF_DIR . 'inc/admin.php';
