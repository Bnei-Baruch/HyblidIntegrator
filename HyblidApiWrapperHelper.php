<?php
class HyblidApiWrapperHelper{
	function cp_hybridauth_add_email()
	{
		if (!is_user_logged_in()) {
			return;
		}
	
		if (isset($_REQUEST['newuseremail']) && is_user_logged_in()) {
			$new_email = get_user_meta(get_current_user_id(), '_new_email', true);
	
			if ($_REQUEST['newuseremail'] == $new_email['hash']) {
				global $wpdb;
	
				$current_user = wp_get_current_user();
	
				$user_id = $current_user->ID;
	
				if ($wpdb->get_var($wpdb->prepare("SELECT user_login FROM {$wpdb->users} WHERE ID = %d", $user_id)))
					$wpdb->query($wpdb->prepare("UPDATE {$wpdb->users} SET user_email = %s, user_login = %s, user_nicename = %s WHERE ID = %d", $new_email['newemail'], $new_email['newemail'], str_replace(array('@', '.', '!', '_', ' '), "-", $new_email['newemail']), $user_id));
	
				delete_user_meta($user_id, '_new_email');
				$need_data = get_user_meta($user_id, 'need_confirm_data', true);
	
				if (isset($need_data['email']) && !empty($need_data['email'])) {
					unset($need_data['email']);
					update_user_meta($user_id, 'need_confirm_data', $need_data);
	
					if (empty($need_data)) {
						update_user_meta($user_id, 'register_data_check', '0');
	
						if (get_user_meta($user_id, 'cp_hybridauth_facebook_identifier', true)) {
							wp_redirect('/login/?cp-aa=Facebook');
						}
	
						if (get_user_meta($user_id, 'cp_hybridauth_vkontakte_identifier', true)) {
							wp_redirect('/login/?cp-aa=Vkontakte');
						}
	
						if (get_user_meta($user_id, 'cp_hybridauth_google_identifier', true)) {
							wp_redirect('/login/?cp-aa=Google');
						}
	
						exit;
					}
	
					wp_redirect('/');
					exit;
				}
	
			}
		}
	
		if (!isset($_REQUEST['get_data']) || !is_user_logged_in())
			return;
	
		$user_id = get_current_user_id();
	
		if (isset($_POST['get_data'])) {
			$need_data = get_user_meta($user_id, 'need_confirm_data', true);
	
			foreach ($need_data as $key => $val) {
				if (!isset($_POST['get_data'][$key]) || empty($_POST['get_data'][$key])) {
					$flag = true;
				}
			}
			if (!isset($flag)) {
				do_action('bp_init');
	
				if (isset($need_data['country']) && !empty($need_data['country']) && isset($_POST['get_data']['country']) && !empty($_POST['get_data']['country'])) {
					unset($need_data['country']);
					update_user_meta($user_id, 'need_confirm_data', $need_data);
	
					if (function_exists('xprofile_set_field_data')) {
						xprofile_set_field_data(10, $user_id, $_POST['get_data']['country']);
					}
	
					if (empty($need_data)) {
						update_user_meta($user_id, 'register_data_check', '0');
						wp_redirect('/');
					}
				}
	
				if (isset($need_data['city']) && !empty($need_data['city']) && isset($_POST['get_data']['city']) && !empty($_POST['get_data']['city'])) {
					unset($need_data['city']);
					update_user_meta($user_id, 'need_confirm_data', $need_data);
	
					if (function_exists('xprofile_set_field_data')) {
						xprofile_set_field_data(11, $user_id, $_POST['get_data']['city']);
					}
	
					if (empty($need_data)) {
						update_user_meta($user_id, 'register_data_check', '0');
						wp_redirect('/');
					}
				}
	
	
				if (isset($need_data['email']) && is_email($_POST['get_data']['email'])) {
	
					if (!email_exists($_POST['get_data']['email']) && !username_exists($_POST['get_data']['email'])) {
						$current_user = wp_get_current_user();
	
						if ($current_user->user_email != $_POST['get_data']['email']) {
	
							$hash = md5($_POST['get_data']['email'] . time() . mt_rand());
							$new_user_email = array(
									'hash' => $hash,
									'newemail' => $_POST['get_data']['email']
							);
							update_user_meta($current_user->ID, '_new_email', $new_user_email);
	
	
							$content = apply_filters('get_user_email_content', __("Дорогой пользователь,
	
        Для подтверждения адреса электронной почты, пожалуйста, перейдите по ссылке:
        ###ADMIN_URL###
	
        Если вы не хотите уточнять адрес электронной почты, то просто проигнорируйте это письмо.
	
        Это письмо было отправлно на адрес ###EMAIL###
	
        С наилучшими пожеланиями,
        Команда сайта ###SITENAME###"), $new_user_email);
	
							$content = str_replace('###ADMIN_URL###', esc_url(home_url('?newuseremail=' . $hash)), $content);
							$content = str_replace('###EMAIL###', $_POST['get_data']['email'], $content);
							$content = str_replace('###SITENAME###', get_site_option('site_name'), $content);
	
							wp_mail($_POST['get_data']['email'], sprintf(__('[%s] New Email Address'), get_option('blogname')), $content);
							$_POST['get_email'] = $current_user->user_email;
	
	
							wp_redirect(add_query_arg(array('need_activate' => 'true')));
							exit;
						}
					} else {
						$email_exists = true;
					}
	
				}
			}
	
			//wp_redirect('/');
			//exit;
		}
	
		$need_data = get_user_meta($user_id, 'need_confirm_data', true);
	
	
	
		if (empty($need_data)) {
			wp_redirect('/');
		}
	
		$need_data_str = '';
	
		if (isset($need_data['email'])) {
			$need_data_str .= 'email';
		}
	
		if (isset($need_data['country'])) {
			if (!empty($need_data_str)) {
				$need_data_str .= ', ';
			}
			$need_data_str .= 'страна';
		}
	
		if (isset($need_data['city'])) {
			if (!empty($need_data_str)) {
				$need_data_str .= ', ';
			}
			$need_data_str .= 'город';
		}
		exit;
	}
	function cp_krugi_rus2translit($string)
	{
		$converter = array(
				'а' => 'a', 'б' => 'b', 'в' => 'v',
				'г' => 'g', 'д' => 'd', 'е' => 'e',
				'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
				'и' => 'i', 'й' => 'y', 'к' => 'k',
				'л' => 'l', 'м' => 'm', 'н' => 'n',
				'о' => 'o', 'п' => 'p', 'р' => 'r',
				'с' => 's', 'т' => 't', 'у' => 'u',
				'ф' => 'f', 'х' => 'h', 'ц' => 'c',
				'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
				'ь' => "'", 'ы' => 'y', 'ъ' => "'",
				'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
	
				'А' => 'A', 'Б' => 'B', 'В' => 'V',
				'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
				'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z',
				'И' => 'I', 'Й' => 'Y', 'К' => 'K',
				'Л' => 'L', 'М' => 'M', 'Н' => 'N',
				'О' => 'O', 'П' => 'P', 'Р' => 'R',
				'С' => 'S', 'Т' => 'T', 'У' => 'U',
				'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
				'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch',
				'Ь' => "'", 'Ы' => 'Y', 'Ъ' => "'",
				'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
		);
		return strtr($string, $converter);
	}
	
	if (! function_exists ( 'mb_ucfirst' )) {
	function mb_ucfirst($str, $enc = 'utf-8') {
		return mb_strtoupper ( mb_substr ( $str, 0, 1, $enc ), $enc ) . mb_substr ( $str, 1, mb_strlen ( $str, $enc ), $enc );
	}
}
function get_url_mime_type($url) {
	$ch = curl_init ( $url );
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, true );
	curl_setopt ( $ch, CURLOPT_HEADER, 1 );
	curl_setopt ( $ch, CURLOPT_NOBODY, 1 );
	curl_exec ( $ch );
	return curl_getinfo ( $ch, CURLINFO_CONTENT_TYPE );
}
} 
?>