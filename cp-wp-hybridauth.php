<?php




function cp_hybridauth_style_frontend()
{
    wp_enqueue_style('cp_hybridauth_style_frontend', CP_HYBRIDAUTH_PLUGIN_DIR_URL . 'includes/style.css');
}

add_action('wp_enqueue_scripts', 'cp_hybridauth_style_frontend');
add_action('login_enqueue_scripts', 'cp_hybridauth_style_frontend');

add_action('login_form', 'cp_krugi_socials_login_form');

function cp_krugi_socials_login_form()
{
    $setting_name = 'cp_hybridauth_config_data';
    $setting_value = get_option($setting_name);
    if (!is_array($setting_value)) $setting_value = array();
    if (empty($setting_value)) return false;

    $facebook = (isset($setting_value['providers']['Facebook']['enabled']) && $setting_value['providers']['Facebook']['enabled'] == 'true') ? 'true' : 'false';
    $google = (isset($setting_value['providers']['Google']['enabled']) && $setting_value['providers']['Google']['enabled'] == 'true') ? 'true' : 'false';
    $vkontakte = (isset($setting_value['providers']['Vkontakte']['enabled']) && $setting_value['providers']['Vkontakte']['enabled'] == 'true') ? 'true' : 'false';

    if ($facebook != 'true' && $google != 'true' && $vkontakte != 'true') return false;

    echo '<div style="overflow: auto; margin-bottom: 12px;">';
    echo '<span style="color: #777; font-size: 14px; display: block; margin-bottom: 4px;">' . __('Авторизоваться с помощью:') . '</span>';
    if ($facebook == 'true')
        echo do_shortcode('[btn-hybridauth provider_id="Facebook" text="' . __('Фейсбук') . '"]');
    if ($google == 'true')
        echo do_shortcode('[btn-hybridauth provider_id="Google" text="' . __('Google') . '"]');
    if ($vkontakte == 'true')
        echo do_shortcode('[btn-hybridauth provider_id="Vkontakte" text="' . __('ВКонтакте') . '"]');
    echo '</div>';
}



