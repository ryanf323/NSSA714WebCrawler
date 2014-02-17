<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Web Crawler - Results</title>
</head>

<?php
include_once('simple_html_dom.php');
$pagesCounted = 0;
$date = date("Y-m-d");
$target_url = $_POST['url'];
$searched_urls = array();
$not_yet_searched_urls = array($target_url);
echo "Results:<p>";
echo $date . " <br />";

while(count($not_yet_searched_urls) > 0 && $pagesCounted < 25)
{
	search(&$target_url,&$searched_urls,&$not_yet_searched_urls, &$pagesCounted, $date);
	$target_url = array_shift($not_yet_searched_urls);
}

function search($target_url,&$searched_urls,&$not_yet_searched_urls, &$pagesCounted, $date){

	$searched_urls[] = $target_url;
	echo "<a href=\"". $target_url."\">". $target_url ."</a>  ";
	$html = new simple_html_dom();
	$html -> load_file($target_url);
	
	$pattern = '/(.*sustain.*)|(.*environmental.*)/i';

        if (preg_match($pattern, $html))
		{
                echo "<b>Match Found!</b><br />";
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
		}else{
        	//no action
			echo "<br />";
		}
	
	$pagesCounted++;
	

	//define regex for edu links
	$edu = '/(?<=http).*\.edu.*/i';
	foreach($html -> find('a') as $link)
	{
		if(preg_match($edu, $link) && !already_checked($link, $searched_urls))
		{
			//add qualifying urls to the search list
			$not_yet_searched_urls[]=$link -> href;
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

echo "<b>Pages counted: ". $pagesCounted ."</b>";
?>