<?php
require_once('pinyin_table.php');
function get_pinyin_array($string)
{
	global $pinyin_table;
	$flow = array();
	$luoma = array();
	for ($i=0;$i<strlen($string);$i++)
	{
		if (ord($string[$i]) >= 0x81 and ord($string[$i]) <= 0xfe) 
		{

			$h = ord($string[$i]);
			if (isset($string[$i+1])) 
			{
				$i++;
				$l = ord($string[$i]);
				if (isset($pinyin_table[$h][$l])) 
				{
					array_push($flow,$pinyin_table[$h][$l]);
				}
				else 
				{
					//array_push($flow,$h);
					//array_push($flow,$l);
				}
			}
			else 
			{
				//array_push($flow,ord($string[$i]));
			}
		}
		else
		{
			//小写字母
			if(ord($string[$i]) >= 97 && ord($string[$i]) <= 122){
				array_push($flow,$string[$i]);
			//大写字母
			} elseif(ord($string[$i]) >= 65 && ord($string[$i]) <= 90) {
				array_push($flow,$string[$i]);
			//阿拉伯数字
			} elseif(ord($string[$i]) >= 48 && ord($string[$i]) <= 57) {
				array_push($flow,$string[$i]);
			} else {
			
			}
			
		}
	}

	return $flow;
}
