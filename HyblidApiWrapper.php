<?php
class HyblidApiWrapper {
	function __construct() {
	}
	public function startSession() {
		$provider_name = $_REQUEST ['cp-aa'];
		
		require_once (plugin_dir_path ( __FILE__ ) . '/hybridauth/Hybrid/Auth.php');
		$config = get_option ( 'cp_hybridauth_config_data' );
		
		// получаем текущий URL для дальнейшей работы
		global $wp;
		$redirect_url = home_url ( $wp->request );
		
		try {
			$hybridauth = new Hybrid_Auth ( $config );
			
			// automatically try to login with Twitter
			$provider = $hybridauth->authenticate ( $provider_name );
			
			// return TRUE or False <= generally will be used to check if the user is connected to twitter before getting user profile, posting stuffs, etc..
			$is_user_logged_in = $provider->isUserConnected ();
			
			// get the user profile
			$user_profile = $provider->getUserProfile ();
			
			// проводим авторизацию и аутентификацию. если пользователь получается то возвращаем ID
			$user_id = cp_login_authenticate_wp_user ( $user_profile, $provider->id );
			
			// Если функция авторизации вернула ложь, то добавить в URL параметр ошибки
			if ($user_id == false)
				$redirect_url = add_query_arg ( array (
						'h-auth' => 'fail' 
				), $redirect_url );
				
				// проверка на временный email
			$user = get_userdata ( $user_id );
			error_log ( 'substr - ' . substr ( $user->user_email, - 3 ) );
			if (substr ( $user->user_email, - 3 ) == 'tmp') {
				wp_redirect ( add_query_arg ( array (
						'get_email' => '1' 
				), $redirect_url ) );
				exit ();
			}
			
			wp_redirect ( $redirect_url );
			exit ();
		} catch ( Exception $e ) {
			echo self::errorByType ( $e->getCode () );
			echo "<br /><br /><b>Original error message:</b> " . $e->getMessage ();
			echo "<hr /><h3>Trace</h3> <pre>" . $e->getTraceAsString () . "</pre>";
		}
	}
	/*
	 * private functions
	 */
	private function errorByType($type) {
		switch ($type) {
			case 0 :
				echo "Unspecified error.";
				break;
			case 1 :
				echo "Hybridauth configuration error.";
				break;
			case 2 :
				echo "Provider not properly configured.";
				break;
			case 3 :
				echo "Unknown or disabled provider.";
				break;
			case 4 :
				echo "Missing provider application credentials.";
				break;
			case 5 :
				echo "Authentication failed. The user has canceled the authentication or the provider refused the connection.";
				break;
			case 6 :
				echo "User profile request failed. Most likely the user is not connected to the provider and he should to authenticate again.";
				$provider->logout ();
				break;
			case 7 :
				echo "User not connected to the provider.";
				$provider->logout ();
				break;
			case 8 :
				echo "Provider does not support this feature.";
				break;
		}
	}
}



/*
 Эта функция получает данные профиля из соц сети и проверяет есть ли связанные профили пользователя WP по идентификаторам или email.
 Если есть то выполняет аутентификацию.
 Если нет, то создает нового пользователя на основе данных соц сети.
 */
function cp_login_authenticate_wp_user($profile, $provider_id)
{
	global $blog_id;
	global $REGISTRATION_PAGE_ID;

	/*
	 Определяем переменные из профиля
	 */
	$provider_id = strtolower($provider_id);
	$email = $profile->email;
	$displayName = $profile->displayName;
	if (empty($displayName)) $displayName = $profile->firstName;
	$identifier = $profile->identifier;

	if (empty($identifier))
		return false;

	if (!empty($profile->firstName) && !empty($profile->lastName)) {
		$username = mb_ucfirst(cp_krugi_rus2translit($profile->lastName)) . '-' . mb_ucfirst(cp_krugi_rus2translit($profile->firstName));
	} elseif (!empty($profile->firstName)) {
		$username = mb_ucfirst(cp_krugi_rus2translit($profile->firstName));
	} else {
		$username = $profile->identifier;
	}


	/*
	 Запрашиваем идентификатор и провайдера, чтобы понять есть ли пользователи с такими параметрами
	 */
	$user_query = new WP_User_Query(
			array(
					'meta_key' => 'cp_hybridauth_' . $provider_id . '_identifier',
					'meta_value' => $identifier
			)
	);



	if ($user_query->total_users == 1) {

		//Если запрос вернул одного пользователя, то ставим куку и авторизуем
		$users = $user_query->get_results();
		$user_id = $users[0]->ID;
		wp_set_auth_cookie($user_id, 1);
		error_log('Если запрос вернул одного пользователя, то ставим куку и авторизуем');
		return $user_id;

	} elseif ($user_query->total_users > 1) {
		//Если запрос вернул более одного пользователя, то сбрасываем меты. Это маловероятно, но лучше удалить авторизацию.
		error_log('Если запрос вернул много пользователей, то удаляем профиль на всякий случай у всех');
		$users = $user_query->get_results();
		foreach ($users as $user):

		$user_id = $user->ID;

		delete_user_meta(
				$user_id,
				$meta_key = 'cp_hybridauth_' . $provider_id . '_identifier',
				$meta_value = $identifier
		);
		endforeach;
		return false;
	}

	/*
	 Пробуем найти пользователя по эл.почте.
	 Если в профиле есть email, и есть такой пользователь в базе сайта, то добавляем мету и делаем авторизацию.
	 У этого пользователя явно не было ранних подключений к других соц сетям, иначе отработка прошла бы выше.
	 Даже если текущий пользователь авторизован на сайте, то email имеет приоритет и потому произойдет переавторизация.
	 */
	if (!empty($email))
		$user = get_user_by('email', $email);

	//Если не нашли пользователя по email то вернется false и нужно это учесть
	if (!empty($user)) {
		$user_id = $user->ID;
		if ($user_id > 0) {
			error_log('Нашли пользователя по эл.почте.');
			update_user_meta(
					$user_id,
					$meta_key = 'cp_hybridauth_' . $provider_id . '_identifier',
					$meta_value = $identifier);
			wp_set_auth_cookie($user_id, 1);
			return $user_id;
		}
	}

	/*
	 Если пользователь авторизован, то подключить к нему профиль.
	 При этом система не смогла найти аналогичные эл.ящики в базе или аналогичные профили ранее подключенные.
	 */
	if (is_user_logged_in()) {
		$user_id = get_current_user_id();
		error_log('Если пользователь авторизован, то подключить к нему профиль - ' . $user_id);
		update_user_meta(
				$user_id,
				$meta_key = 'cp_hybridauth_' . $provider_id . '_identifier',
				$meta_value = $identifier);
		return $user_id;
	}

	/*
	 Если пользователя нет и нет авторизации, то создать нового и авторизовать
	 */
	$random_password = wp_generate_password($length = 12, $include_standard_special_chars = false);
	//берем реальную почту из профиля или генерируем на лету
	if (!is_email($email))
		$email = $identifier . '@' . $provider_id . '.tmp';

	if (!validate_username($username) || empty($username))
		$username = $email;

	//проверяем имя пользователя и мыло
	error_log('имя пользователя и мыло - ' . $username . ', ' . $email);

	//создаем пользователя
	$user_id = wp_create_user($username, $random_password, $email);

	if (is_wp_error($user_id)) {
		$i = 1;
		while (is_wp_error($user_id)) {
			$ident = $username . '-' . $i;
			$email = $identifier . '@' . $provider_id . '.tmp';
			$user_id = wp_create_user($ident, $random_password, $email);
			$i++;

			print_r($ident . ', ' . $random_password . ', ' . $email);
		}
	}

	error_log('wp error - ' . print_r($user_id, true));

	if (!is_wp_error($user_id)) {
		wp_update_user(array('ID' => $user_id, 'display_name' => $displayName, 'user_nicename' => str_replace(array('@', '.', '!', '_', ' '), "-", $email), 'first_name' => $profile->firstName, 'last_name' => $profile->lastName, 'role' => 'student'));
		update_user_meta(
				$user_id,
				$meta_key = 'cp_hybridauth_' . $provider_id . '_identifier',
				$meta_value = $identifier
		);

		$data_to_check = array();

		do_action('bp_init');

		if (function_exists('xprofile_set_field_data')) {
			xprofile_set_field_data(10, $user_id, $profile->country);
			xprofile_set_field_data('Страна', $user_id, $profile->country);
			xprofile_set_field_data(11, $user_id, $profile->city);
			xprofile_set_field_data('Город', $user_id, $profile->city);
			xprofile_set_field_data(1, $user_id, $profile->firstName);
			xprofile_set_field_data('Имя', $user_id, $profile->firstName);
			xprofile_set_field_data(13, $user_id, $profile->lastName);
			xprofile_set_field_data('Фамилия', $user_id, $profile->lastName);
		}




		if ($blog_id == $REGISTRATION_PAGE_ID) {

			if (empty($profile->country)) {
				$data_to_check['country'] = '1';
			}
			if (empty($profile->city)) {
				$data_to_check['city'] = '1';
			}

		}

		if (substr($email, -3) == 'tmp') {
			$data_to_check['email'] = '1';
		}

		update_user_meta($user_id, 'need_confirm_data', $data_to_check);
		update_user_meta($user_id, 'register_data_check', '1');


		wp_set_auth_cookie($user_id, 1);
		return $user_id;
	}

	/*
	 Если дошли до сюда, то ни одна из схем не сработала. Возвращаем false
	 */
	return false;
}

?>