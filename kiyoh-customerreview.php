<?php
/**
 * Plugin Name: Kiyoh Customerreview
 * Plugin URI: http://www.interactivated.me/
 * Description: KiyOh.nl-gebruikers kunnen met deze plug-in automatisch klantbeoordelingen verzamelen, publiceren en delen in social media. Wanneer een klant een bestelling heeft gemaakt in uw WooCommerce, wordt een e-mail uitnodiging automatisch na een paar dagen verstuurd om u te beoordelen. De e-mail wordt uit naam en e-mailadres van uw organisatie gestuurd, zodat uw klanten u herkennen. De e-mail tekst is aanpasbaar en bevat een persoonlijke en veilige link naar de pagina om te beoordelen. Vanaf nu worden de beoordelingen dus automatisch verzameld, gepubliceerd en gedeeld. Dat is nog eens handig!
 * Version: 1.0.32
 * Author: kiyoh
 * Author URI: http://www.interactivated.me/webshop-modules/kiyoh-reviews-module-for-woocommerce.html
 * License: GPLv2 or later
 * Text Domain: kiyoh-customerreview
 * Domain Path: /i18n/languages/
*/

define('KIYOH__PLUGIN_URL', plugin_dir_url(__FILE__));
define('KIYOH__PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once(KIYOH__PLUGIN_DIR . 'functions.php');
require_once(KIYOH__PLUGIN_DIR . 'widget.php');
include_once(ABSPATH . 'wp-admin/includes/plugin.php');

if (is_plugin_active('woocommerce/woocommerce.php')) {
    $kiyoh_options = kiyoh_getOption();
    if ($kiyoh_options['enable'] == 'Yes') {
        if (!is_admin()) {
            if ($kiyoh_options['event'] == 'Purchase') {
                add_action("shutdown", "kiyohProccessPurchaseAction");
            }
        }
        add_action("save_post", "check_kiyoh_review", 10, 2);
        add_action("woocommerce_after_order_object_save", "check_kiyoh_reviewNew", 10, 2);
    }
}

function check_kiyoh_reviewNew($post_id, $post)
{
    $post_id->post_type = 'shop_order';
    check_kiyoh_review($post_id->id, $post_id);
}
function check_kiyoh_review($post_id, $post)
{
    $kiyoh_options = kiyoh_getOption();
    if ($post->post_type != 'shop_order') {
        return;
    }
    $order = new WC_Order($post_id);
    $wpmlLanguage = $order->get_meta('wpml_language');
    if ($wpmlLanguage){
        $kiyoh_options = kiyoh_getOption(null,$wpmlLanguage);
    }
    $status = $order->get_status();
    $email = false;
    if (method_exists($order, 'get_billing_email')) {
        $email = $order->get_billing_email();
    } elseif (method_exists($order, 'get_address')) {
        $address = $order->get_address();
        $email = $address['email'];
    }
    $firstname = '';
    $lastname = '';
    if (method_exists($order, 'get_billing_first_name')) {
        $firstname = $order->get_billing_first_name();
        $lastname = $order->get_billing_last_name();
    }
    if (isset($_POST, $_POST['billing_email']) && !empty($_POST['billing_email'])) {
        $email = $_POST['billing_email'];
    }
    if (!$email) return;
    $status_old = '';
    if (isset($_POST['post_status'])){
        $status_old = trim(strip_tags($_POST['post_status']));
    }
    $status_old = str_replace('wc-', '', $status_old);

    if ($status == 'pending' || $status == 'processing' || $status == 'on-hold'
        || $status == 'completed' || $status == 'cancelled' || $status == 'fraud'
        || $status == 'refunded' || $status == 'failed'
    ) {

        //check change status, check excule_groups
        $corect_event = false;
        if ($kiyoh_options['event'] == 'Orderstatus') {
            if (in_array($status, $kiyoh_options['order_status'])) {
                $corect_event = true;
            }
        }
        if ($corect_event && $status_old != $status) {
            $user_id = '';
            if (isset($_POST['customer_user'])){
                $user_id = trim(strip_tags($_POST['customer_user']));
            }
            $user_id = (int)$user_id;
            if (kiyoh_checkExculeGroups($kiyoh_options['excule_groups'], $user_id) == true) {
                $optionsSendMail = array('option' => $kiyoh_options, 'email' => $email, 'firstname' => $firstname, 'lastname' => $lastname);

                kiyoh_createTableKiyoh();
                global $wpdb;
                $table_name = $wpdb->prefix . 'kiyoh';
                if ($kiyoh_options['send_method'] == 'kiyoh') {
                    kiyoh_sendMail($optionsSendMail);
                } else if (!kiyoh_checkSendedMail($table_name, $order->get_id(), $status)) {
                    kiyoh_insertRow($table_name, $order->get_id(), $status);
                    if ($kiyoh_options['delay'] == 0) {
                        kiyoh_sendMail($optionsSendMail);
                    } else {
                        $delay = time() + $kiyoh_options['delay'] * 24 * 3600;
                        wp_schedule_single_event($delay, 'kiyoh_sendMail', array('optionsSendMail' => $optionsSendMail));
                    }
                }
            }
        }
    }
}
//{"all":{"enable":"Yes","send_method":"kiyoh","connector":"asdf","custom_user":"asdf","email_template_language":"","enable_microdata":false,"company_id":false,"link":"","email":"","delay":"0","event":"Purchase","order_status":["pending","processing","on-hold"],"server":"newkiyoh.com","excule_groups":null,"tmpl_en":"","tmpl_du":"","company_name":"","hash":"c2af1092-78fa-45b0-8764-b7ae263391c0","locationId":"000-dtg-demo-kvtilburg-01","language1":"nl","excule":null},"nl":{"enable":"Yes","link":"","email":"","delay":"0","event":"Purchase","order_status":["pending","processing","on-hold"],"server":"klantenvertellen.nl","excule_groups":null,"tmpl_en":"","tmpl_du":"","excule":null,"company_name":"","send_method":"kiyoh","connector":"asdf","custom_user":"asdf","email_template_language":"","hash":"c2af1092-78fa-45b0-8764-b7ae263391c0","locationId":"000-dtg-demo-kvtilburg-01","language1":"nl"},"en":{"enable":"Yes","link":"","email":"","delay":"0","event":"Orderstatus","order_status":["pending","processing","on-hold"],"server":"klantenvertellen.nl","excule_groups":null,"tmpl_en":"","tmpl_du":"","excule":null,"company_name":"","send_method":"kiyoh","connector":"asdf","custom_user":"asdf","email_template_language":"","hash":"c2af1092-78fa-45b0-8764-b7ae263391c0","locationId":"000-dtg-demo-kvtilburg-01","language1":"EN"}}

function enqueue_my_scripts()
{
    wp_enqueue_script('kiyoh-script', KIYOH__PLUGIN_URL . 'js/script.js', array('jquery'), '1.0.17');
}

add_action('admin_init', 'enqueue_my_scripts');

function register_mysettings()
{
    register_setting('kiyoh-settings-group', 'kiyoh_option_enable');
    register_setting('kiyoh-settings-group', 'kiyoh_option_link');
    register_setting('kiyoh-settings-group', 'kiyoh_option_email');
    register_setting('kiyoh-settings-group', 'kiyoh_option_delay');
    register_setting('kiyoh-settings-group', 'kiyoh_option_event');
    register_setting('kiyoh-settings-group', 'kiyoh_option_order_status');
    register_setting('kiyoh-settings-group', 'kiyoh_option_server');
    register_setting('kiyoh-settings-group', 'kiyoh_option_excule_groups');
    register_setting('kiyoh-settings-group', 'kiyoh_option_tmpl_en');
    register_setting('kiyoh-settings-group', 'kiyoh_option_tmpl_du');
    register_setting('kiyoh-settings-group', 'kiyoh_option_excule');
    register_setting('kiyoh-settings-group', 'kiyoh_option_company_name');
    register_setting('kiyoh-settings-group', 'kiyoh_option_send_method');
    register_setting('kiyoh-settings-group', 'kiyoh_option_connector');
    register_setting('kiyoh-settings-group', 'kiyoh_option_custom_user');
    register_setting('kiyoh-settings-group', 'kiyoh_option_email_template_language');
    register_setting('kiyoh-settings-group', 'Klantenvertellen_option_hash');
    register_setting('kiyoh-settings-group', 'Klantenvertellen_option_locationId');
    register_setting('kiyoh-settings-group', 'Klantenvertellen_option_email_template_language');

    //register_setting('kiyoh-settings-group', 'kiyoh_options');
    //register_setting( 'kiyoh-settings-group', 'kiyoh_option_enable_microdata' );
    //register_setting( 'kiyoh-settings-group', 'kiyoh_option_company_id' );
}
add_filter('pre_update_option', 'kiyoh_update_option',10,3);

function kiyoh_create_menu()
{
    add_menu_page('Kiyoh Customerreview Settings', 'Kiyoh Settings', 'administrator', __FILE__, 'kiyoh_settings_page', '', 10);
    add_action('admin_init', 'register_mysettings');
}

add_action('admin_menu', 'kiyoh_create_menu');

function kiyoh_settings_page()
{
    ?>
    <div class="wrap">
        <?php if (is_plugin_active('woocommerce/woocommerce.php')) : ?>
            <h2><?php echo __('Kiyoh Customerreview Settings', 'kiyoh-customerreview'); ?></h2>
            <?php if (isset($_GET['settings-updated'])) { ?>
                <div id="message" class="updated">
                    <p><strong><?php _e('Settings saved.', 'kiyoh-customerreview') ?></strong></p>
                </div>
            <?php } ?>
            <form method="post" action="options.php" id="kiyohform">
                <?php settings_fields('kiyoh-settings-group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php echo __('Module Version', 'kiyoh-customerreview'); ?></th>
                        <td>
                            <p>1.0.32</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo __('Enable', 'kiyoh-customerreview'); ?></th>
                        <td>
                            <select name="kiyoh_option_enable">
                                <option value="Yes" <?php selected(kiyoh_getOption('kiyoh_option_enable'), 'Yes'); ?>>
                                    Yes
                                </option>
                                <option value="No" <?php selected(kiyoh_getOption('kiyoh_option_enable'), 'No'); ?>>No
                                </option>
                            </select>
                            <p><?php echo __('Recommended Value is Yes. On setting it to NO, module ll stop sending email invites to customers.', 'kiyoh-customerreview'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo __('Email send method', 'kiyoh-customerreview'); ?></th>
                        <td>
                            <select name="kiyoh_option_send_method" required class="required">
                                <option
                                    value="" <?php selected(kiyoh_getOption('kiyoh_option_send_method'), false); ?>></option>
                                <option
                                    value="my" <?php selected(kiyoh_getOption('kiyoh_option_send_method'), 'my'); ?>><?php echo __('Send emails from my server', 'kiyoh-customerreview'); ?>
                                </option>
                                <option
                                    value="kiyoh" <?php selected(kiyoh_getOption('kiyoh_option_send_method'), 'kiyoh'); ?>>
                                    <?php echo __('Send emails from Kiyoh server', 'kiyoh-customerreview'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top" class="myserver">
                        <th scope="row"><?php echo __('Company Name', 'kiyoh-customerreview'); ?></th>
                        <td><input type="text" name="kiyoh_option_company_name"
                                   value="<?php echo kiyoh_getOption('kiyoh_option_company_name'); ?>"/></td>
                    </tr>
                    <tr valign="top" class="myserver">
                        <th scope="row"><?php echo __('Link rate', 'kiyoh-customerreview'); ?></th>
                        <td><input type="text" name="kiyoh_option_link"
                                   value="<?php echo kiyoh_getOption('kiyoh_option_link'); ?>"/>
                            <p><?php echo __('Enter here the link to the review (Easy Invite Link). Please contact Kiyoh and they provide you the correct link.', 'kiyoh-customerreview'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top" class="myserver">
                        <th scope="row"><?php echo __('Sender Email', 'kiyoh-customerreview'); ?></th>
                        <td><input type="email" name="kiyoh_option_email"
                                   value="<?php echo kiyoh_getOption('kiyoh_option_email'); ?>"/></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo __('Enter delay', 'kiyoh-customerreview'); ?></th>
                        <td><input type="text" name="kiyoh_option_delay"
                                   value="<?php echo kiyoh_getOption('kiyoh_option_delay'); ?>"/>
                            <p><?php echo __('Enter here the delay(number of days) after which you would like to send review invite option). You may enter 0 to send review invite email immediately after customer event. Cron should be configured for values>0', 'kiyoh-customerreview'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo __('Select Event')?></th>
                        <td>
                            <select name="kiyoh_option_event">
                                <option value="" <?php selected(kiyoh_getOption('kiyoh_option_event'), ''); ?>></option>
                                <option value="Purchase" <?php selected(kiyoh_getOption('kiyoh_option_event'), 'Purchase'); ?>><?php echo __('Purchase')?></option>
                                <option value="Orderstatus" <?php selected(kiyoh_getOption('kiyoh_option_event'), 'Orderstatus'); ?>><?php echo __('Order status change')?></option>
                            </select>
                            <p><?php echo __('Enter here the event after which you would like to send review invite email to your customer.');?></p>
                        </td>
                    </tr>
                    <tr valign="top" id="status">
                        <th scope="row"><?php echo __('Order Status', 'kiyoh-customerreview'); ?></th>
                        <td>
                            <select name="kiyoh_option_order_status[]" multiple>
                                <?php
                                $statuses = kiyoh_getOption('kiyoh_option_order_status');
                                if (!$statuses) {
                                    $statuses = array();
                                }
                                ?>
                                <option value="pending" <?php if (in_array('pending', $statuses)) echo " selected"; ?>>
                                    <?php _e('Pending Payment'); ?>
                                </option>
                                <option
                                    value="processing" <?php if (in_array('processing', $statuses)) echo ' selected'; ?>>
                                    <?php _e('Processing'); ?>
                                </option>
                                <option value="on-hold" <?php if (in_array('on-hold', $statuses)) echo ' selected'; ?>>
                                    <?php _e('On Hold'); ?>
                                </option>
                                <option
                                    value="completed" <?php if (in_array('completed', $statuses)) echo ' selected'; ?>>
                                    <?php _e('Completed'); ?>
                                </option>
                                <option
                                    value="cancelled" <?php if (in_array('cancelled', $statuses)) echo ' selected'; ?>>
                                    <?php _e('Cancelled'); ?>
                                </option>
                                <option
                                    value="refunded" <?php if (in_array('refunded', $statuses)) echo ' selected'; ?>>
                                    <?php _e('Refunded'); ?>
                                </option>
                                <option value="failed" <?php if (in_array('failed', $statuses)) echo ' selected'; ?>>
                                    <?php _e('Failed'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo __('Select Server', 'kiyoh-customerreview'); ?></th>
                        <td>
                            <select name="kiyoh_option_server">
                                <option
                                    value="klantenvertellen.nl" <?php selected(kiyoh_getOption('kiyoh_option_server'), 'klantenvertellen.nl'); ?>>
                                    <?php _e('New Klantenvertellen.nl'); ?>
                                </option>
                                <option
                                    value="newkiyoh.com" <?php selected(kiyoh_getOption('kiyoh_option_server'), 'newkiyoh.com'); ?>>
                                    <?php _e('New Kiyoh.com'); ?>
                                </option>
                                <option
                                    value="kiyoh.nl" <?php selected(kiyoh_getOption('kiyoh_option_server'), 'kiyoh.nl'); ?>>
                                    <?php _e('Old Kiyoh Netherlands(kiyoh.nl)'); ?>
                                </option>
                                <option
                                    value="kiyoh.com" <?php selected(kiyoh_getOption('kiyoh_option_server'), 'kiyoh.com'); ?>>
                                    <?php _e('Old Kiyoh International(kiyoh.com)'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top" class="kiyohserver dependsonkiyoh">
                        <th scope="row"><?php echo __('Enter Connector', 'kiyoh-customerreview'); ?></th>
                        <td>
                            <p><input type="text" name="kiyoh_option_connector"
                                      value="<?php echo kiyoh_getOption('kiyoh_option_connector'); ?>" required class="required"/></p>
                            <p><?php echo __('Enter here the Kiyoh Connector Code from your Kiyoh Account.', 'kiyoh-customerreview'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top" class="kiyohserver dependsonkiyoh">
                        <th scope="row"><?php echo __('Company Email', 'kiyoh-customerreview'); ?></th>
                        <td>
                            <p><input type="text" name="kiyoh_option_custom_user"
                                      value="<?php echo kiyoh_getOption('kiyoh_option_custom_user'); ?>" required class="required"/></p>
                            <p><?php echo __('Enter here your "company email address" as registered in your KiyOh account. Not the "user email address"!', 'kiyoh-customerreview'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top" class="Klantenvertellenserver dependsonKlantenvertellenserver">
                        <th scope="row"><?php echo __('Enter hash', 'Klantenvertellen-customerreview'); ?></th>
                        <td>
                            <p><input type="text" name="Klantenvertellen_option_hash"
                                      value="<?php echo kiyoh_getOption('Klantenvertellen_option_hash'); ?>" required class="required"/>
                            </p>
                            <p><?php echo __('Enter here the Hash Code from your Klantenvertellen Account.', 'Klantenvertellen-customerreview'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top" class="Klantenvertellenserver dependsonKlantenvertellenserver">
                        <th scope="row"><?php echo __('Enter Location Id', 'Klantenvertellen-customerreview'); ?></th>
                        <td>
                            <p><input type="text" name="Klantenvertellen_option_locationId"
                                      value="<?php echo kiyoh_getOption('Klantenvertellen_option_locationId'); ?>"
                                      required class="required"/></p>
                            <p><?php echo __('Enter here the Location Id from your Klantenvertellen Account.', 'Klantenvertellen-customerreview'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top" class="Klantenvertellenserver dependsonKlantenvertellenserver">
                        <th scope="row"><?php echo __('Language email template', 'Klantenvertellen-customerreview'); ?></th>
                        <td>
                            <p><input type="text" name="Klantenvertellen_option_email_template_language"
                                      value="<?php echo kiyoh_getOption('Klantenvertellen_option_email_template_language'); ?>"
                                      required class="required"/></p>
                            <p><?php echo __('Language', 'Klantenvertellen-customerreview'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top" class="kiyohserver dependsonkiyohserver dependsonkiyoh">
                        <th scope="row"><?php echo __('Language email template', 'kiyoh-customerreview'); ?></th>
                        <td>
                            <select name="kiyoh_option_email_template_language">
                                <option
                                    value="" <?php selected(kiyoh_getOption('kiyoh_option_email_template_language'), ''); ?>></option>
                                <?php $languges = array(
                                    '' => '',
                                    '1' => ('Dutch (BE)'),
                                    '2' => ('French'),
                                    '3' => ('German'),
                                    '4' => ('English'),
                                    '5' => ('Netherlands'),
                                    '6' => ('Danish'),
                                    '7' => ('Hungarian'),
                                    '8' => ('Bulgarian'),
                                    '9' => ('Romanian'),
                                    '10' => ('Croatian'),
                                    '11' => ('Japanese'),
                                    '12' => ('Spanish'),
                                    '13' => ('Italian'),
                                    '14' => ('Portuguese'),
                                    '15' => ('Turkish'),
                                    '16' => ('Norwegian'),
                                    '17' => ('Swedish'),
                                    '18' => ('Finnish'),
                                    '20' => ('Brazilian Portuguese'),
                                    '21' => ('Polish'),
                                    '22' => ('Slovenian'),
                                    '23' => ('Chinese'),
                                    '24' => ('Russian'),
                                    '25' => ('Greek'),
                                    '26' => ('Czech'),
                                    '29' => ('Estonian'),
                                    '31' => ('Lithuanian'),
                                    '33' => ('Latvian'),
                                    '35' => ('Slovak')
                                );
                                foreach ($languges as $lang_id => $languge):?>
                                    <option
                                        value="<?php echo $lang_id; ?>" <?php selected(kiyoh_getOption('kiyoh_option_email_template_language'), $lang_id); ?>><?php echo $languge; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <?php if (kiyoh_checkExistsTable('groups_group') && is_plugin_active('groups/groups.php')) : ?>
                        <tr valign="top">
                            <th scope="row"><?php echo __('Exclude customer groups', 'kiyoh-customerreview'); ?></th>
                            <td><?php kiyoh_selectExculeGroups(); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr valign="top" class="myserver">
                        <th scope="row"><?php echo __('Email template (English)', 'kiyoh-customerreview'); ?></th>
                        <td>
                            <?php wp_editor(str_replace("\n", '<br />', kiyoh_getOption('kiyoh_option_tmpl_en')), 'kiyoh_option_tmpl_en', array('media_buttons' => true, 'quicktags' => false)); ?>
                        </td>
                    </tr>
                    <tr valign="top" class="myserver">
                        <th scope="row"><?php echo __('Email template (Dutch)', 'kiyoh-customerreview'); ?></th>
                        <td><?php wp_editor(str_replace("\n", '<br />', kiyoh_getOption('kiyoh_option_tmpl_du')), 'kiyoh_option_tmpl_du', array('media_buttons' => true, 'quicktags' => false, 'editor_css' => true)); ?></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        <?php else: ?>
            <h2><?php echo __('You need install and activate WooCommerce plugin', 'kiyoh-customerreview'); ?></h2>
        <?php endif; ?>
    </div>
    <?php
}

add_action('receiveDataCron_event', 'receiveDataCron', 10, 3);
//do_action('receiveDataCron_event');
function receiveDataCron($instance=null)
{
    $widget = new \kiyoh_review();
    $widget->receiveDataNow($instance);
}

function register_kiyoh_review()
{
    register_widget('kiyoh_review');
    $locale = apply_filters('plugin_locale', get_locale(), 'kiyoh-customerreview');
    load_textdomain('kiyoh-customerreview', WP_LANG_DIR . '/plugins/kiyoh-customerreview-' . $locale . '.mo');
    load_plugin_textdomain('kiyoh-customerreview', false, plugin_basename(dirname(__FILE__)) . '/i18n/languages');
}
add_action('widgets_init', 'register_kiyoh_review');

function kiyoh_kiyoh_block_init() {
    register_block_type( __DIR__ . '/build' );
}
add_action( 'init', 'kiyoh_kiyoh_block_init' );