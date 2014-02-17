<?php
//Ryan Flynn
include_once('simple_html_dom.php');
$execution_limit = 100;
ini_set('max_execution_time', 1800); //30 minutes
$time_start = microtime(true); 
$pagesCounted = 0;
$date = date("Y-m-d");

//Get URL from command line argument
if (isset($argv)) {
    $target_url = $argv[1];
    $output_type = "cli";
}
//Or from a web form!
else {
    $target_url = $POST['url'];
    $output_type = "html";
    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Web Crawler - Results</title>
	</head>';
}

//Extract domain form provided url
$domain_pattern = '/\w+.edu/i';
preg_match($domain_pattern, $target_url, $matches);
$domain = $matches[0];

$match_count = 0;
$searched_urls = array();
$not_yet_searched_urls = array($target_url);

//Begin output
if ($output_type == "html")
{
	echo "The <b>" . $domain . "</b> Search Results:<p>";
	echo $date . " <br />";
}else{
	echo "The " . $domain . " Search Results:\n";
	echo $date . "\n";
}

//Main program loop
while(count($not_yet_searched_urls) > 0 && $pagesCounted < $execution_limit)
{
	search(&$target_url,&$searched_urls,&$not_yet_searched_urls, &$pagesCounted, $date, $domain, &$match_count, $output_type);
	$target_url = array_shift($not_yet_searched_urls);
}

//Search Function
function search($target_url,&$searched_urls,&$not_yet_searched_urls, &$pagesCounted, $date, $domain, &$match_count, $output_type){

	$searched_urls[] = $target_url;
	$html = new simple_html_dom();
	$html -> load_file($target_url);
	
	$pattern = '/(.*sustain.*)|(.*environmental.*)/i';

        if (preg_match($pattern, $html))
		{
                if ($output_type == "html")
		{
			echo "<a href=\"". $target_url."\">". $target_url ."</a><br />";
                }else{
			echo $target_url . "\n";
		}
		//write URL to database
                $mysqli = new mysqli("localhost","username","password","database_name");
		$target_url = $mysqli->real_escape_string($target_url);
		$mysqli -> query("INSERT INTO results (id, url, date) VALUES (NULL, '$target_url' , '$date');");
                $mysqli -> close();
				
		$match_count++;
		}else{
        	//no action
		}
$pagesCounted++;
	

	//define regex for edu links
	$edu = '/(?<=http).*' . $domain . '.*/i';
	foreach($html -> find('a') as $link)
	{
		$link = $link -> href;
		if(preg_match($edu, $link) && !already_checked($link, $searched_urls))
		{
			
			//add qualifying urls to the search list
			$not_yet_searched_urls[]=$link;
		}
	}
}

function already_checked($reference,$array)
{
      foreach($array as $ref)
	  {
			if (strstr($reference,$ref))
			{         
				return true;
			}
      }
      return false;
} 

$time_end = microtime(true);
$execution_time = ($time_end - $time_start);
if ($output_type == "html")
{
	echo "<b>Pages scanned: ". $pagesCounted ."<br />";
	echo "Pages left in queue: " . count($not_yet_searched_urls) . "<br />";
	echo "Number of matches: " . $match_count . "</b><br />";
	echo '<b>Total Execution Time:</b> '.(int)($execution_time/60) .' minutes and '.($execution_time%60).' seconds';
}else{
        echo "Pages scanned: ". $pagesCounted ."\n";
        echo "Pages left in queue: " . count($not_yet_searched_urls) . "\n";
        echo "Number of matches: " . $match_count . "\n";
        echo 'Total Execution Time: '.(int)($execution_time/60) .' minutes and '.($execution_time%60)." seconds\n";

}
?>
