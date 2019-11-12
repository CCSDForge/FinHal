<?php

/*
 * Macau;Z01.639.610
 * 
 */
$handle = fopen('mtrees2015.bin', 'r');

if ($handle)
{
	$trad = array ();
	$json = array ();
	
	while (!feof($handle))
	{
		$buffer = explode (";", fgets($handle));
		
		if (count ($buffer) > 1) {
			
			$buffer[0] = trim($buffer[0]);
			$buffer[1] = trim($buffer[1]);
			
			$trad['mesh_' . $buffer[1]] = $buffer[0];	
			
			$code = explode ('.', $buffer[1]);
	
			
			$j = 1;
			$b = "";
			$prev = "";
	
			
			foreach ($code as $c) {
				$c = trim($c);
				if ($b) 
					$b .= '.';
				$b .= $c;
				$prev .= "a:1:{s:" . strlen($b) . ":\"$b\";";
				$j++;
			}
			
			$prev .= "a:0:{";
			
			for ($i=0; $i < substr_count ($prev, '{'); $i++)
				$prev .= "}";
			
			$prev = unserialize($prev);
			
			$json = array_merge($json, $prev);
		}
	}
	
	file_put_contents ("./mesh.php", "<?php\rreturn array (\r");
	foreach ($trad as $key => $val) {
		file_put_contents ("./mesh.php", "\"$key\" => \"" . addslashes($val) . "\",\r" , FILE_APPEND);
	}
	file_put_contents ("./mesh.php", ");" , FILE_APPEND);
	
	file_put_contents ("./mesh.json", json_encode ($json));
	
	fclose($handle);
}
?>