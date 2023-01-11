<?php

/**
 * Created by Nguyen Ngoc Nam.
 * User: Nguyen Ngoc Nam
 * Website: https://github.com/namtenten
 * Date: 2018/10/16
 * Time: 09:50
 */


namespace wizarphics\wizarframework\utilities\debugger;
class Functions
{
	public function getDevice($ua)
	{
		if (strpos($ua, 'iPhone') !== false) {
			//iPhone
			return 'iPhone';
		} elseif (strpos($ua, 'iPad') !== false) {
			//iPad
			return 'iPad';
		} elseif ((strpos($ua, 'Android') !== false) && (strpos($ua, 'Mobile') !== false)) {
			//Android
			return 'Android Mobile';
		} elseif (strpos($ua, 'Android') !== false) {
			//Android
			return 'Android Tablet';
		} else {
			return 'PC';
		}
	}

	//ログ
	public function writeLog($data)
	{
		if (!defined("DEBUG_LOG_PATH")) {
			define("DEBUG_LOG_PATH", "/tmp/");
		}

		file_put_contents(DEBUG_LOG_PATH . "app.log", $data, FILE_APPEND);
	}

	public function utf8Encode(string $value)
	{
		$original_encode = mb_detect_encoding($value, 'SJIS,SJIS-win,ASCII,EUC-JP,UTF-8');
		$value = mb_convert_encoding($value, 'UTF-8', $original_encode);

		return $value;
	}

	public function hr($color = "#000000")
	{
		$style = " style=\"border:1px solid " . $color . ";\"";
		$html = '<hr ' . $style . '>';
		echo $html . PHP_EOL;
	}

	public function br()
	{
		$html = '<br>';
		echo $html . PHP_EOL;
	}

	//現在のタイムスタンプ
	public function getYYMMDDHHMMSSUU()
	{
		//microtimeを.で分割
		$arrTime = explode('.', microtime(true));
		return date('ymdHis', $arrTime[0]) . substr($arrTime[1], 0, 2);
	}

	public function e($value = '') // laravel - e function
	{
		$value = htmlentities($value);
		return $value;
	}

	public function htmlEcho($value = '', $echo = true)
	{
		$value = nl2br($this->e($value));
		if ($echo) {
			echo $value;
		}
		return $value;
	}

	public function current_url()
	{
		return $_SERVER['PHP_SELF'];
	}

	public function json($data)
	{
		header('content-type: application/json; charset=utf-8');
		$json = json_encode($data);
		echo $json;
		exit;
	}

	// Function to get the client IP address
	public function getClientIp()
	{
		$ipaddress = '';
		if (isset($_SERVER['HTTP_CLIENT_IP']))
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else if (isset($_SERVER['HTTP_X_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		else if (isset($_SERVER['HTTP_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		else if (isset($_SERVER['REMOTE_ADDR']))
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		else
			$ipaddress = 'UNKNOWN';
		return $ipaddress;
	}

	public function startWith(string $haystack, string $needle)
	{
		$length = mb_strlen($needle);
		return (mb_substr($haystack, 0, $length) === $needle);
	}

	public function endWith(string $haystack, string $needle)
	{
		$length = mb_strlen($needle);
		if ($length == 0) {
			return true;
		}

		return (mb_substr($haystack, -$length) === $needle);
	}

	public function color(string $text = '', string $color = '#000000')
	{
		echo '<font color="' . $color . '">' . $text . '</font>';
	}

	public function delete(string $path)
	{
		array_map('unlink', array_filter((array) glob($path)));
	}

	public function toJpNengoDateTime(string $date = NULL, bool $hasTime = false)
	{
		if (!isset($date) || (empty($date))) {
			return '';
		}

		// https://www.conservapedia.com/Japanese_dates
		$japaneseEras = array(
			701  => '大宝',
			704  => '慶雲',
			708  => '和銅',
			715  => '霊亀',
			717  => '養老',
			724  => '神亀',
			729  => '天平',
			749  => '天平感宝',
			757  => '天平宝字',
			765  => '天平神護',
			767  => '神護景雲',
			770  => '宝亀',
			781  => '天応',
			782  => '延暦',
			806  => '大同',
			810  => '弘仁',
			824  => '天長',
			834  => '承和',
			848  => '嘉祥',
			851  => '仁寿',
			854  => '斉衡',
			857  => '天安',
			859  => '貞観',
			877  => '元慶',
			885  => '仁和',
			889  => '寛平',
			898  => '昌泰',
			901  => '延喜',
			923  => '延長',
			931  => '承平',
			938  => '天慶',
			947  => '天暦',
			957  => '天徳',
			961  => '応和',
			964  => '康保',
			968  => '安和',
			970  => '天禄',
			973  => '天延',
			976  => '貞元',
			978  => '天元',
			983  => '永観',
			985  => '寛和',
			987  => '永延',
			988  => '永祚',
			990  => '正暦',
			995  => '長徳',
			999  => '長保',
			1004  => '寛弘',
			1012  => '長和',
			1017  => '寛仁',
			1021  => '治安',
			1024  => '万寿',
			1028  => '長元',
			1037  => '長暦',
			1040  => '長久',
			1044  => '寛徳',
			1046  => '永承',
			1053  => '天喜',
			1058  => '康平',
			1065  => '治暦',
			1069  => '延久',
			1074  => '承保',
			1077  => '承暦',
			1081  => '永保',
			1084  => '応徳',
			1087  => '寛治',
			1094  => '嘉保',
			1096  => '永長',
			1097  => '承徳',
			1099  => '康和',
			1104  => '長治',
			1106  => '嘉承',
			1108  => '天仁',
			1110  => '天永',
			1113  => '永久',
			1118  => '元永',
			1120  => '保安',
			1124  => '天治',
			1126  => '大治',
			1131  => '天承',
			1132  => '長承',
			1135  => '保延',
			1141  => '永治',
			1142  => '康治',
			1144  => '天養',
			1145  => '久安',
			1151  => '仁平',
			1154  => '久寿',
			1156  => '保元',
			1159  => '平治',
			1160  => '永暦',
			1161  => '応保',
			1163  => '長寛',
			1165  => '永万',
			1166  => '仁安',
			1169  => '嘉応',
			1171  => '承安',
			1175  => '安元',
			1177  => '治承',
			1181  => '養和',
			1182  => '寿永',
			1184  => '元暦',
			1185  => '文治',
			1190  => '建久',
			1199  => '正治',
			1201  => '建仁',
			1204  => '元久',
			1206  => '建永',
			1207  => '承元',
			1211  => '建暦',
			1213  => '建保',
			1219  => '承久',
			1222  => '貞応',
			1224  => '元仁',
			1225  => '嘉禄',
			1227  => '安貞',
			1229  => '寛喜',
			1232  => '貞永',
			1233  => '天福',
			1234  => '文暦',
			1235  => '嘉禎',
			1238  => '暦仁',
			1239  => '延応',
			1240  => '仁治',
			1243  => '寛元',
			1247  => '宝治',
			1249  => '建長',
			1256  => '康元',
			1257  => '正嘉',
			1259  => '正元',
			1260  => '文応',
			1261  => '弘長',
			1264  => '文永',
			1275  => '建治',
			1278  => '弘安',
			1288  => '正応',
			1293  => '永仁',
			1299  => '正安',
			1302  => '乾元',
			1303  => '嘉元',
			1306  => '徳治',
			1308  => '延慶',
			1311  => '応長',
			1312  => '正和',
			1317  => '文保',
			1319  => '元応',
			1321  => '元亨',
			1324  => '正中',
			1326  => '嘉暦',
			1329  => '元徳',
			1331  => '元弘',
			1334  => '建武',
			1336  => '延元',
			1340  => '興国',
			1346  => '正平',
			1370  => '建徳',
			1372  => '文中',
			1375  => '天授',
			1381  => '弘和',
			1384  => '元中',
			1338  => '暦応',
			1342  => '康永',
			1345  => '貞和',
			1350  => '観応',
			1352  => '文和',
			1356  => '延文',
			1361  => '康安',
			1362  => '貞治',
			1368  => '応安',
			1379  => '康暦',
			1387  => '嘉慶',
			1389  => '康応',
			1390  => '明徳',
			1394  => '応永',
			1428  => '正長',
			1429  => '永享',
			1441  => '嘉吉',
			1444  => '文安',
			1449  => '宝徳',
			1452  => '享徳',
			1455  => '康正',
			1457  => '長禄',
			1460  => '寛正',
			1466  => '文正',
			1467  => '応仁',
			1469  => '文明',
			1487  => '長享',
			1489  => '延徳',
			1492  => '明応',
			1501  => '文亀',
			1504  => '永正',
			1521  => '大永',
			1528  => '享禄',
			1532  => '天文',
			1555  => '弘治',
			1558  => '永禄',
			1570  => '元亀',
			1573  => '天正',
			1592  => '文禄',
			1596  => '慶長',
			1615  => '元和',
			1624  => '寛永',
			1644  => '正保',
			1648  => '慶安',
			1652  => '承応',
			1655  => '明暦',
			1658  => '万治',
			1661  => '寛文',
			1673  => '延宝',
			1681  => '天和',
			1684  => '貞享',
			1688  => '元禄',
			1704  => '宝永',
			1711  => '正徳',
			1716  => '享保',
			1736  => '元文',
			1741  => '寛保',
			1744  => '延享',
			1748  => '寛延',
			1751  => '宝暦',
			1764  => '明和',
			1772  => '安永',
			1781  => '天明',
			1789  => '寛政',
			1801  => '享和',
			1804  => '文化',
			1818  => '文政',
			1830  => '天保',
			1844  => '弘化',
			1848  => '嘉永',
			1858  => '安政',
			1860  => '万延',
			1861  => '文久',
			1864  => '元治',
			1865  => '慶応',
			1868  => '明治',
			1912  => '大正',
			1926  => '昭和',
			1989  => '平成',
		);

		$dates = date_parse($date);
		$year = $dates['year'];

		$eraYear = 0;
		$era = '';
		foreach ($japaneseEras as $eraYearKey => $eraValue) {
			if ($eraYearKey < $year) {
				$eraYear = $eraYearKey;
				$era = $eraValue;
			} else {
				break;
			}
		}

		$eraYear = $year - $eraYear + 1;

		$dates['year'] = $era . $eraYear;

		$japaneseDate = $dates['year'] . '年' . $dates['month'] . '月' . $dates['day'] . '日';
		if ($hasTime) {
			$japaneseDate .= ' ' . $dates['hour'] . ':' . $dates['minute'] . ':' . $dates['second'];
		}

		return $japaneseDate;
	}
}
