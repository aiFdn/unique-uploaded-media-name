<?php

/**
 * Plugin Name: Unique Uploaded Media Name
 * Plugin URI:  https://github.com/aifdn/unique-uploaded-media-name
 * Description: Unique uploaded media names by adding some extra random string
 * Author: Sazzad Hossain Sharkar
 * Author URI: https://github.com/shsbd
 * Version: 1.0.3
 * License: GPLv3
 */

class UniqueUploadedMediaName {
	public static
	function randomString(
		$type = 'no_zero', $length = 8
	) {
		switch($type) {
			case 'all_string':
				$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;
			case 'alpha':
				$pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;
			case 'capital':
				$pool = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;
			case 'alphabet':
				$pool = 'abcdefghkmnprstuvwyz';
				break;
			case 'hexadecimal':
				$pool = '0123456789abcdef';
				break;
			case 'numeric':
				$pool = '0123456789';
				break;
			case 'no_zero':
				$pool = '123456789';
				break;
			case 'distinct':
				$pool = '2345679acdefhjklmnprstuvwxyz';
				break;
			default:
				$pool = (string) $type;
				break;
		}
		$crypto_rand_secure =
			function($min, $max) {
				$range = $max - $min;
				if($range < 0) {
					return $min;
				} // not so random...
				$log    = log($range, 2);
				$bytes  = (int) ($log / 8) + 1; // length in bytes
				$bits   = (int) $log + 1; // length in bits
				$filter = (int) (1 << $bits) - 1; // set all lower bits to 1
				do {
					$rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
					$rnd = $rnd &= $filter; // discard irrelevant bits
				} while($rnd >= $range);

				return $min + $rnd;
			};
		$token              = '';
		$max                = strlen($pool);
		for($i = 0; $i < $length; $i ++) {
			$token .= $pool[$crypto_rand_secure(0, $max)];
		}

		return $token;
	}

	public static
	function stringTen() {
		$nozero   = self::randomString('numeric', 6);
		$alphabet = self::randomString('all_string', 8);

		return $nozero . '-' . $alphabet;
	}
}

function unique_uploaded_media_name($filename) {
	$sanitized_filename = remove_accents($filename); // Convert to ASCII

	// Standard replacements
	$invalid = [
		'  '  => '-',
		' '   => '-',
		'%20' => '-',
		'_'   => '-',
		'-'   => '-',
	];

	$sanitized_filename = str_replace(array_keys($invalid), array_values($invalid), $sanitized_filename);

	$sanitized_filename = preg_replace('/[^A-Za-z0-9-\. ]/', '', $sanitized_filename);
	$sanitized_filename = preg_replace('/\.(?=.*\.)/', '', $sanitized_filename);
	$sanitized_filename = preg_replace('/-+/', '-', $sanitized_filename);
	$sanitized_filename = str_replace('-.', '.', $sanitized_filename);
	$sanitized_filename = strtolower($sanitized_filename);

	$info = pathinfo($filename);
	$ext  = empty($info['extension']) ? '' : '.' . $info['extension'];
	$name = basename($sanitized_filename, $ext);

	return $name . '-' . UniqueUploadedMediaName::stringTen() . $ext;
}

add_filter('sanitize_file_name', 'unique_uploaded_media_name', 10);
