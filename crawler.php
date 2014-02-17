<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Web Crawler - Results</title>
</head>

<?php
ini_set('max_execution_time', 1800); //30 minutes
$time_start = microtime(true); 
include_once('simple_html_dom.php');
$pagesCounted = 0;
$date = date("Y-m-d");
$target_url = $_POST['url'];
$domain_pattern = '/\w+.edu/i';
preg_match($domain_pattern, $target_url, $matches);
$domain = $matches[0];
$match_count = 0;
$searched_urls = array();
$not_yet_searched_urls = array($target_url);
echo "The " . $domain . " Search Results:<p>";
echo $date . " <br />";

while(count($not_yet_searched_urls) > 0 && $pagesCounted < 100 )
{
	search(&$target_url,&$searched_urls,&$not_yet_searched_urls, &$pagesCounted, $date, $domain, &$match_count);
	$target_url = array_shift($not_yet_searched_urls);
}

function search($target_url,&$searched_urls,&$not_yet_searched_urls, &$pagesCounted, $date, $domain, &$match_count){

	$searched_urls[] = $target_url;
	$html = new simple_html_dom();
	$html -> load_file($target_url);
	
	$pattern = '/(.*sustain.*)|(.*environmental.*)/i';

        if (preg_match($pattern, $html))
		{
                echo "<a href=\"". $target_url."\">". $target_url ."</a><br />";
                /*write URL to database
                $con=mysqli_connect("localhost","db_user","database_name","password");
                // Check connection
                if (mysqli_connect_errno())
                {
					echo "Failed to connect to MySQL: " . mysqli_connect_error();
                }
                mysqli_query($con,"INSERT INTO results (id, url, date) VALUES (NULL, $target_url , $date)");
                mysqli_close($con);
				*/
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
			//If URL has a / on the end, remove it
			
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

echo "<b>Pages counted: ". $pagesCounted ."<br />";
echo "Pages left in queue: " . count($not_yet_searched_urls) . "<br />";
echo "Number of matches: " . $match_count . "</b><br />";

$time_end = microtime(true);
$execution_time = ($time_end - $time_start);
echo '<b>Total Execution Time:</b> '.(int)($execution_time/60) .' minutes and '.($execution_time%60).' seconds';
?>
