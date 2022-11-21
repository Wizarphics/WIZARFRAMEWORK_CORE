<?php

/**
 * Created by Nguyen Ngoc Nam.
 * User: Nguyen Ngoc Nam
 * Website: https://github.com/namtenten
 * Date: 2018/10/16
 * Time: 09:50
 */

/**
 *
 */

namespace wizarphics\wizarframework\utilities\debugger;

defined('NAM_DEBUG_MODE') or define('NAM_DEBUG_MODE', TRUE);

class Debug
{

	public function d($data = NULL, $is_output = true, $is_html_encode = true)
	{
		if (NAM_DEBUG_MODE === FALSE) return '';

		if (is_null($data)) {
			$str = "<font color='red'><i>NULL</i></font>";
		} elseif ($data === "") {
			$str = "<font color='red'><i>Empty string</i></font>";
		} elseif ($data instanceof \Exception) {
			$td1 = "<td style=\"background-color:#00AA00;color:#FFF;border-top:1px solid #000;border-right:1px solid #000;padding-left:5px;padding-right:5px;\">";
			$td2 = "<td style=\"border-top:1px solid #000;padding:5px;\">";

			$str = "<font color='red'><b>Exception:</b></font><br>";

			$str .= "<table style=\"border-bottom:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;\" cellpadding=\"0\" cellspacing=\"0\">";
			$str 	.= "<tr>" . $td1 . "code</td>" . $td2 . $data->getCode() . "</td></tr>";
			$str 	.= "<tr>" . $td1 . "message</td>" . $td2 . $data->getMessage() . "</td></tr>";
			$str 	.= "<tr>" . $td1 . "file</td>" . $td2 . $data->getFile() . "</td></tr>";
			$str 	.= "<tr>" . $td1 . "line</td>" . $td2 . $data->getLine() . "</td></tr>";
			// $str 	.= "<tr>" . $td1 . "trace</td>" . $td2 . d($data->getTrace(), false) . "</td></tr>";
			$str 	.= "<tr>" . $td1 . "previous</td>" . $td2 . $data->getPrevious() . "</td></tr>";
			// $str 	.= "<tr>" . $td1 . "traceAsString</td>" . $td2 . $data->getTraceAsString() . "</td></tr>";
			$str 	.= "<tr>" . $td1 . "details</td>" . $td2 . $data->__toString() . "</td></tr>";
			$str .= "</table>";
		} elseif (is_array($data)) {
			if (count($data) === 0) {
				$str = "<font color='red'><i>Empty array.</i></font>";
			} else {
				$str = "<table style=\"border-bottom:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;\" cellpadding=\"0\" cellspacing=\"0\">";
				foreach ($data as $key => $value) {
					$str .= "<tr><td style=\"background-color:#00AA00;color:#FFF;border-top:1px solid #000;border-right:1px solid #000;padding-left:5px;padding-right:5px;\">";

					if (is_object($key)) {
						$str .= '<font color="#ffff00;" title="Object variable">';
					}
					$str .= $key;
					if (is_object($key)) {
						$str .= "</font>";
					}
					$str .= "</td><td style=\"border-top:1px solid #000;padding:5px;\">" . $this->d($value, false) . "</td></tr>";
				}
				$str .= "</table>";
			}
		} elseif (is_resource($data)) {
			/**
			 * @var resource|\mysqli_result $data
			 */
			$data_array = mysqli_fetch_all($data);
			$str = $this->d($data_array, false);
		} elseif (is_object($data)) {

			$reflect 					= new \ReflectionClass($data);
			$className 					= $reflect->getName();

			$arr["FullClassPathName"] 		= $className;
			$arr["Namespace"] 				= $reflect->getNamespaceName();
			$arr["ShortClassName"] 			= $reflect->getShortName();
			if (in_array($arr["ShortClassName"], array('mysql_result', 'mysqli_result'))) {
				unset($arr["FullClassPathName"]);

				$arr["fetch_all"] 			= $data->fetch_all();
			} else {
				$arr["Attributes"] 			= get_object_vars($data);
				$arr["Methods"] 			= get_class_methods($className);
			}

			if (empty($arr["Namespace"])) {
				unset($arr["Namespace"]);
			}
			if (empty($arr["Methods"])) {
				unset($arr["Methods"]);
			}

			$str = $this->d($arr, false);
		} elseif (is_numeric($data) && (gettype($data) !== "string")) {
			$str = "<font color='red'><i>" . $data . "</i></font>";
		} elseif (is_bool($data) && ($data === true || $data === false)) {
			$str = "<font color='red'><i>" . (($data === true) ? "True" : "False") . "</i></font>";
		} else {
			$str = $data;
			if ($is_html_encode) {
				$str = htmlspecialchars($str);
			}

			// $original_encode = mb_detect_encoding($str, 'SJIS,SJIS-win,ASCII,EUC-JP,UTF-8');
			// $str = mb_convert_encoding($str, 'UTF-8', $original_encode);

			$str = preg_replace("/(\r|\n)/", "<br>" . PHP_EOL, $str);
		}

		if ($is_output) {
			echo $str;
		}
		return $str;
	}

	public function dn($data = NULL, $is_html_encode = true)
	{
		$this->d($data, true, $is_html_encode);
		echo "<br>" . PHP_EOL;
	}

	public function dd($data = NULL, $is_html_encode = true)
	{
		$this->dn($data, $is_html_encode);
		exit;
	}

	public function dt($message = "")
	{
		$this->dn("[" . date("Y/m/d H:i:s") . "]" . $message);
	}

	public function djson($json = NULL, $isExited = false)
	{
		if (is_string($json)) {
			$json = json_decode($json);
		}

		$this->dn($json);

		if ($isExited) {
			exit;
		}
	}

	public function ddjson($json = NULL)
	{
		$this->djson($json, true);
	}

	public function debugMessage($message)
	{
		$this->dt($message);
	}
}
