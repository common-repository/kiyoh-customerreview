<?php
function kiyoh_getOption($option = null, $forceLang = null)
{
    $kiyoh_options = array();
    $translated = json_decode(get_option('kiyoh_options'), true);
    $lang = 'all';
    if (is_array($translated)) {
        if (defined('ICL_SITEPRESS_VERSION')) {
            $lang = kiyohGetCurrentLanguage();
            if ($forceLang) {
                $lang = $forceLang;
            }
            if (!isset($translated[$lang])) {
                $lang = 'all';
            }
        }
        $kiyoh_options = $translated[$lang];
    } else {
        $kiyoh_options['enable'] = get_option('kiyoh_option_enable');
        $kiyoh_options['send_method'] = get_option('kiyoh_option_send_method');
        $kiyoh_options['connector'] = get_option('kiyoh_option_connector');
        $kiyoh_options['custom_user'] = get_option('kiyoh_option_custom_user');
        $kiyoh_options['email_template_language'] = get_option('kiyoh_option_email_template_language');
        $kiyoh_options['enable_microdata'] = get_option('kiyoh_option_enable_microdata');
        $kiyoh_options['company_id'] = get_option('kiyoh_option_company_id');
        $kiyoh_options['link'] = get_option('kiyoh_option_link');
        $kiyoh_options['email'] = get_option('kiyoh_option_email');
        $kiyoh_options['delay'] = (int)get_option('kiyoh_option_delay');
        $kiyoh_options['event'] = get_option('kiyoh_option_event');
        $kiyoh_options['order_status'] = get_option('kiyoh_option_order_status');
        $kiyoh_options['server'] = get_option('kiyoh_option_server');
        $kiyoh_options['excule_groups'] = kiyoh_getValExculeGroups();
        $kiyoh_options['tmpl_en'] = get_option('kiyoh_option_tmpl_en');
        $kiyoh_options['tmpl_en'] = str_replace("\n", '<br />', $kiyoh_options['tmpl_en']);
        $kiyoh_options['tmpl_du'] = get_option('kiyoh_option_tmpl_du');
        $kiyoh_options['tmpl_du'] = str_replace("\n", '<br />', $kiyoh_options['tmpl_du']);
        $kiyoh_options['company_name'] = get_option('kiyoh_option_company_name');
        $kiyoh_options['hash'] = get_option('Klantenvertellen_option_hash');
        $kiyoh_options['locationId'] = get_option('Klantenvertellen_option_locationId');
        $kiyoh_options['language1'] = get_option('Klantenvertellen_option_email_template_language');


        if ($kiyoh_options['enable'] == 'Yes' && $kiyoh_options['send_method'] == 'kiyoh' && !function_exists('curl_version')) {
            update_option('kiyoh_option_send_method', 'my');
            $kiyoh_options['send_method'] = 'my';
            add_action('admin_notices', 'kiyoh_curlproblem_admin_notice');
        }
        $translated = [$lang => $kiyoh_options];
        update_option('kiyoh_options', json_encode($translated));
    }
    if ($option) {
        $tmp = kiyoh_getShortOptionName($option);

        if (isset($kiyoh_options[$option])) {
            return $kiyoh_options[$option];
        } elseif (isset($kiyoh_options[$tmp])) {
            return $kiyoh_options[$tmp];
        } else {
            return '';
        }
    }

    return $kiyoh_options;
}

/**
 * @return string
 */
function kiyohGetCurrentLanguage()
{
    if (isset($_REQUEST['lang']) && is_admin()) {
        return $_REQUEST['lang'];
    }
    $lang = apply_filters('wpml_current_language', NULL);
    if (!$lang) {
        $lang = 'all';
    }
    return $lang;
}

/**
 * depricated
 */
function kiyoh_curlproblem_admin_notice()
{
    ?>
    <div class="notice notice-error">
        <p><?php _e('Kiyoh: php extension Curl is not installed', 'kiyoh-customerreview'); ?></p>
    </div>
    <?php
}

function kiyoh_getValExculeGroups()
{
    $result = array();
    if (is_plugin_active('groups/groups.php')) {
        $options = kiyoh_getOption('kiyoh_option_excule');
        if (kiyoh_checkExistsTable('groups_group')) {
            global $table_prefix;
            global $wpdb;
            $groups = $wpdb->get_results("SELECT group_id, name FROM `{$table_prefix}groups_group`");
            if (count($groups) > 0) {
                $arr_group = array();
                foreach ($groups as $group) {
                    $arr_group[$group->group_id] = $group->group_id;
                }
            }
            ksort($arr_group);
            foreach ($arr_group as $key => $group) {
                if ($options[$key] == 1) {
                    $result[$key] = 'on';
                }
            }
        }
    }
    return $result;
}

function kiyoh_set_html_content_type()
{
    return 'text/html';
}

function kiyoh_sendMail($options)
{
    $kiyoh_options = $options['option'];
    $send_mail = $kiyoh_options['email'];
    $email = $options['email'];
    if ($kiyoh_options['send_method'] == 'kiyoh') {
        $kiyoh_server = $kiyoh_options['server'];
        $kiyoh_user = $kiyoh_options['custom_user'];
        $kiyoh_connector = $kiyoh_options['connector'];
        $kiyoh_action = 'sendInvitation';
        $kiyoh_delay = $kiyoh_options['delay'];
        $kiyoh_lang = $kiyoh_options['email_template_language'];
        if (in_array($kiyoh_server, array('kiyoh.nl', 'kiyoh.com'))) {

            $request = http_build_query(array(
                'user' => $kiyoh_user,
                'connector' => $kiyoh_connector,
                'action' => $kiyoh_action,
                'targetMail' => $email,
                'delay' => $kiyoh_delay,
                'language' => $kiyoh_lang
            ));
            $url = 'https://www.' . $kiyoh_server . '/set.php?' . $request;
            $response = wp_remote_get($url, array('timeout' => 2));
        } elseif ($kiyoh_server == 'klantenvertellen.nl' || $kiyoh_server == 'newkiyoh.com') {
            $hash = $kiyoh_options['hash'];
            $location_id = $kiyoh_options['locationId'];
            $language_1 = $kiyoh_options['language1'];
            $first_name = $options['firstname'];
            $last_name = $options['lastname'];
            $server = 'klantenvertellen.nl';
            if ($kiyoh_server == 'newkiyoh.com') {
                $server = 'kiyoh.com';
            }
            $request = http_build_query(array(
                'hash' => $hash,
                'location_id' => $location_id,
                'invite_email' => $email,
                'delay' => $kiyoh_delay,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'language' => $language_1
            ));
            $url = "https://{$server}/v1/invite/external?" . $request;
            $response = wp_remote_get($url, array('timeout' => 2));
        }
        if (is_array($response) && isset($response['body'])) {
            $logdata = date("Y-m-d H:i:s ") . var_export($response['body'],true). "\n";
            @file_put_contents(ABSPATH.'wp-content/kiyoh.log', $logdata, FILE_APPEND);
        }
        if ($response instanceof WP_Error) {
            $logdata = date("Y-m-d H:i:s ") . $response->get_error_message(). "\n";
            @file_put_contents(ABSPATH.'wp-content/kiyoh.log', $logdata, FILE_APPEND);
        }
    } else {
        add_filter('wp_mail_content_type', 'kiyoh_set_html_content_type');

        $content_email = ($kiyoh_options['server'] == 'kiyoh.com') ? $kiyoh_options['tmpl_en'] : $kiyoh_options['tmpl_du'];
        $link = $kiyoh_options['link'];
        $subject = ($kiyoh_options['server'] == 'kiyoh.com') ? 'Review ' : 'Beoordeel ';
        $subject .= $kiyoh_options['company_name'];
        $content_email = str_replace('[COMPANY_NAME]', $kiyoh_options['company_name'], $content_email);
        $link = '<a href="' . $link . '">' . $link . '</a>';
        $content_email = str_replace('[LINK]', $link, $content_email);
        $message = $content_email;
        $headers = 'From: ' . $send_mail;
        $attachments = '';
        wp_mail($email, $subject, $message, $headers, $attachments);

        //echo $headers;die();
        remove_filter('wp_mail_content_type', 'kiyoh_set_html_content_type');
    }
}

function kiyoh_checkExculeGroups($excule_groups, $user_id)
{
    //return true or false
    $flag = true;
    if (is_array($excule_groups) && count($excule_groups) > 0 && kiyoh_checkExistsTable('groups_user_group') && kiyoh_checkExistsTable('groups_group')) {
        if ($user_id > 0) {
            global $table_prefix;
            global $wpdb;
            $groups = $wpdb->get_results("SELECT group_id FROM `{$table_prefix}groups_user_group` WHERE user_id=" . $user_id);
            if (count($groups) > 0) {
                if (count($groups) == 1) {
                    $groups = current($groups);
                    $group_id = $groups->group_id;
                    if (array_key_exists($group_id, $excule_groups)) {
                        $flag = false;
                    }
                } else {
                    $arr_group = array();
                    foreach ($groups as $key => $group) {
                        $arr_group[$group->group_id] = 0;
                    }
                    foreach ($excule_groups as $id => $group) {
                        if (array_key_exists($id, $arr_group)) {
                            $flag = false;
                            break;
                        }
                    }
                }
            }
        }
    }
    return $flag;
}

function kiyoh_checkExistsTable($table_name)
{
    $flag = true;
    global $table_prefix;
    global $wpdb;
    $table_name = $table_prefix . $table_name;
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $flag = false;
    }
    return $flag;
}

function kiyoh_selectExculeGroups()
{
    if (kiyoh_checkExistsTable('groups_group')) {
        global $table_prefix;
        global $wpdb;
        $groups = $wpdb->get_results("SELECT group_id, name FROM `{$table_prefix}groups_group`");
        if (count($groups) > 0) {
            $arr_group = array();
            foreach ($groups as $group) {
                $arr_group[$group->group_id] = $group->name;
            }
        }
        ksort($arr_group);
        //$arr_group[1] = 'General';
        $options = kiyoh_getOption('kiyoh_option_excule');
        foreach ($arr_group as $key => $group) {
            echo '<fieldset>';
            echo '<label for="kiyoh_option_excule' . $key . '">';
            echo '<input name="kiyoh_option_excule[' . $key . ']" type="checkbox" value="1" ';
            checked($options[$key], 1, true);
            echo ' />' . $group;
            echo '</label>';
            echo '</fieldset>';
        }
    }
}

function kiyoh_createTableKiyoh($table_name = 'kiyoh')
{
    if (!kiyoh_checkExistsTable($table_name)) {
        global $wpdb;
        $table_name = $wpdb->prefix . $table_name;

        $sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			order_id int(11) DEFAULT NULL,
			status varchar(255) NULL,
			UNIQUE KEY id (id)
		);";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        return true;
    } else {
        return false;
    }

}

function kiyoh_checkSendedMail($table_name, $order_id, $status)
{
    global $wpdb;
    $row = $wpdb->get_row('SELECT * FROM ' . $table_name . ' WHERE order_id=' . $order_id . ' AND status="' . $status . '"');
    if ($row) {
        return true;
    } else {
        return false;
    }
}

function kiyoh_insertRow($table_name, $order_id, $status)
{
    global $wpdb;

    $row = $wpdb->get_row('SELECT * FROM ' . $table_name . ' WHERE order_id=' . $order_id);
    if ($row) {
        $wpdb->update(
            $table_name,
            array('status' => $status),
            array('order_id' => $order_id),
            array('%s')
        );
    } else {
        $wpdb->insert(
            $table_name,
            array(
                'order_id' => $order_id,
                'status' => $status
            ),
            array(
                '%d',
                '%s'
            )
        );
    }
}

function kiyohProccessPurchaseAction()
{
    $kiyoh_options = kiyoh_getOption();
    $delay = time() + $kiyoh_options['delay'] * 24 * 3600;
    $url = trim(strip_tags($_SERVER['REQUEST_URI']));
    $order_id = 0;
    if (count($_GET) >= 1) {
        if (strpos($url, 'order-received') == true && strpos($url, 'wc_order') == true) {
            require(ABSPATH . WPINC . '/pluggable.php');
            $current_user = wp_get_current_user();
            if ($current_user) {
                if (isset($current_user->ID)) {
                    $user_id = $current_user->ID;
                }
            }
            if (kiyoh_checkExculeGroups($kiyoh_options['excule_groups'], $user_id) == true) {
                if (strpos($url, 'order-received/') !== false) {
                    $url = explode('order-received/', $url);
                    $url = $url[1];
                    $url = explode("/", $url);
                    $order_id = (int)$url[0];
                } else {
                    if (isset($_GET['order-received'])) {
                        $order_id = strip_tags($_GET['order-received']);
                    }
                }
                if ($order_id > 0) {
                    $order = new WC_Order($order_id);
                    $wpmlLanguage = $order->get_meta('wpml_language');
                    if ($wpmlLanguage) {
                        $kiyoh_options = kiyoh_getOption(null, $wpmlLanguage);
                    }
                    $email = $order->get_billing_email();
                    if (!$email) return;
                    $optionsSendMail = array('option' => $kiyoh_options, 'email' => $email, 'firstname' => $order->get_shipping_first_name(), 'lastname' => $order->get_shipping_last_name());
                    kiyoh_createTableKiyoh();
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'kiyoh';
                    if ($kiyoh_options['send_method'] == 'kiyoh') {
                        kiyoh_sendMail($optionsSendMail);
                    } else if (!kiyoh_checkSendedMail($table_name, $order_id, 'Purchase')) {
                        kiyoh_insertRow($table_name, $order_id, 'Purchase');
                        if ($kiyoh_options['delay'] == 0) {
                            kiyoh_sendMail($optionsSendMail);
                        } else {
                            wp_schedule_single_event($delay, 'kiyoh_sendMail', array('optionsSendMail' => $optionsSendMail));
                        }
                    }
                }
            }
        }
    }
}

function kiyoh_getShortOptionName($option)
{
    $key = false;
    if (substr($option, 0, 13) == 'kiyoh_option_') {
        $key = substr($option, 13);
    } elseif ($option == 'Klantenvertellen_option_email_template_language') {
        $key = 'language1';
    } elseif (substr($option, 0, 24) == 'Klantenvertellen_option_') {
        $key = substr($option, 24);
    } else {
        return false;
    }
    return $key;
}

function kiyoh_update_option($value, $option, $old_value)
{
    $key = kiyoh_getShortOptionName($option);
    if (!$key) {
        return $value;
    }
    $translated = json_decode(get_option('kiyoh_options'), true);
    if (!isset($translated[kiyohGetCurrentLanguage()])) {
        $translated[kiyohGetCurrentLanguage()] = array();
    }
    $translated[kiyohGetCurrentLanguage()][$key] = $value;
    update_option('kiyoh_options', json_encode($translated));
    return $value;
}