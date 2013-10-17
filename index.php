<?php

require(dirname(__FILE__).'/../config/config.inc.php');

function extractArray($file)
{
        $contents = file_get_contents($file);
        $m        = array();
        if(preg_match('/\$(\w+)\s*=\s*array\(\);/', $contents, $m))
        {
                $arr = $m[1];
                if(isset($$arr))
                {
                	unset($$arr);
                }

                include($file);
                return array('name' => $arr, 'data' => $$arr);
        }
        else return null;
}

$data = json_decode(file_get_contents("php://input"), true);

if($data['category'] === 'Tabs')
{
	// TODO! requires DB update
	exit;
}

$path = dirname(__FILE__).'/../'.str_replace("[iso]", $data['language_code'], $data['path']);

if($data['method'] === 'ARRAY')
{
	$fp    = fopen($path, "r+");
	if(flock($fp, LOCK_EX))
	{
		$array = extractArray($path);

		$array['data'][$data['mkey']] = $data['translation'];

		ftruncate($fp, 0);

		fwrite($fp, "<?php\n\n");
		fwrite($fp, "global ".$data['custom'].";\n");
		fwrite($fp, $data['custom']." = array();\n\n");

		foreach($array['data'] as $key => $value)
		{
			fwrite($fp, $data['custom']."['".str_replace("'", "\'", $key)."'] = '".str_replace("'", "\'", $value)."';\n");
		}

		fflush($fp);
		flock($fp, LOCK_UN);
	}
	fclose($fp);
}
else if($data['method'] == 'FILE')
{
	file_put_contents($path, $data['translation']);
}

