<?php
/*
 * Shortcode of Social Authentication
 */
class HyblidAuthButtonShortcode {
	function __construct() {
		add_shortcode ( 'btn-hybridauth', array (
				__CLASS__,
				'shortcode' 
		) );
	}
	/*
	 * public functions
	 */
	public static function shortcode($atts, $content = "") {
		if (is_user_logged_in () && $connect == false)
			return;
		extract ( shortcode_atts ( self::getDefaultAttrebuts (), $atts, 'btn-hybridauth' ) );
		
		if (! empty ( $content ))
			$text = $content;
		
		if (self::isHaveProfile ( $provider_id )) {
			$class_html = strtolower ( $provider_id );
			$url = add_query_arg ( array (
					'cp-aa' => $provider_id 
			) );
		} else {
			$class_html = strtolower ( $provider_id ) . ' cp_delete';
			$url = add_query_arg ( array (
					'cp-aa-delete' => $provider_id 
			) );
		}
		return getHTML ( $text, $url, $class_html );
	}
	public static function deleteProfile() {
		$provider_id = ( string ) $_REQUEST ['cp-aa-delete'];
		
		// Проверяем есть ли удаляемый профайл. Если нет, то возвращаем URL уведомления отсутствия профиля. Иначе удаляем профиль.
		$profile = get_user_meta ( get_current_user_id (), $meta_key = 'cp_hybridauth_' . strtolower ( $provider_id ) . '_identifier', true );
		if (empty ( $profile )) {
			add_query_arg ( array (
					'result' => 'not_found_profile' 
			) );
			exit ();
		} else {
			$meta_key = 'cp_hybridauth_' . strtolower ( $provider_id ) . '_identifier';
			delete_user_meta ( get_current_user_id (), $meta_key );
			global $wp;
			wp_redirect ( add_query_arg ( array (
					'cp_result_delete_profile' => 'success' 
			), home_url ( $wp->request ) ) );
			exit ();
		}
	}
	
	/*
	 * private functions
	 */
	private static function getDefaultAttrebuts() {
		return array (
				'provider_id' => 'Facebook',
				'img' => 'default baz',
				'connect' => false,
				'text' => 'Facebook' 
		);
	}
	private static function isHaveProfile($provider_id) {
		$meta_key = 'cp_hybridauth_' . strtolower ( $provider_id ) . '_identifier';
		$profile = get_user_meta ( get_current_user_id (), $meta_key, true );
		return empty ( $profile );
	}
	private static function getHTML($text, $url, $class_html) {
		$class = apply_filters ( "cp_hybridauth_btn_class", $class_html );
		return '<div class="cp-btn-hybridauth ' . $class . '"><a href="' . $url . '" title="">' . $text . '</a></div>';
	}
}
?>