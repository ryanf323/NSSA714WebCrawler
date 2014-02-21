<?php
//Ryan Flynn
include_once('simple_html_dom.php');

//Initialize Variables
$execution_limit = 1500;
$time_start = microtime(true); 
$pagesCounted = 0;
$date = date("Y-m-d");
$match_count = 0;
$searched_urls = array();

//Get URL from command line argument
if (isset($argv)){
    $target_url = $argv[1];
}else{
	echo "Usage: php -f crawler.php http://website.com\n";
	return 2;
}

//Extract domain from provided url
$domain_pattern = '/\w+.edu/i';
preg_match($domain_pattern, $target_url, $matches);
$domain = $matches[0];

//Put first url into the array
$not_yet_searched_urls = array($target_url);

//Begin output
echo "The " . $domain . " Search Results:\n";
echo $date . "\n";


//-----Main program loop-----
while(count($not_yet_searched_urls) > 0 && $pagesCounted < $execution_limit){
	search(&$target_url,&$searched_urls,&$not_yet_searched_urls, &$pagesCounted, $date, $domain, &$match_count, $output_type);
	$target_url = array_shift($not_yet_searched_urls);
	
	//Debugging Output
	echo "Target URL: " . $target_url . "\n";
	echo "Queue: ". count($not_yet_searched_urls). " Pages Loaded: ". $pagesCounted ."\n";	
}

//Post Crawl Output
$time_end = microtime(true);
$execution_time = ($time_end - $time_start);
echo "Pages scanned: ". $pagesCounted ."\n";
echo "Pages left in queue: " . count($not_yet_searched_urls) . "\n";
echo "Number of matches: " . $match_count . "\n";
echo 'Total Execution Time: '.(int)($execution_time/60) .' minutes and '.($execution_time%60)." seconds\n";


//-----Search Function-----//
function search($target_url,&$searched_urls,&$not_yet_searched_urls, &$pagesCounted, $date, $domain, &$match_count, $output_type){
	$searched_urls[] = $target_url;
	$html = file_get_html($target_url);
	echo "Memory Useage: ".number_format((memory_get_usage()/1000000),2,'.','') . " MB \n\n";
	
	//sometimes pages fail to load, if so exit function
	if(gettype($html) == "boolean"){
		unset($html);
		return false;
	}
	
	$pattern = '/(.*sustainability.*)|(.*environmental.*)/i';
    if (preg_match($pattern, $html)){
       	
       	if ($output_type == "html"){
			echo "<a href=\"". $target_url."\">". $target_url ."</a><br />";
		}else{
			//echo $target_url . "\n";
		}
	
		//write URL to database
		$mysqli = new mysqli("localhost","root","newpwd","crawlerfinds");
		$target_url = $mysqli->real_escape_string($target_url);
		$mysqli -> query("INSERT INTO results (id, url, date) VALUES (NULL, '$target_url' , '$date');");
		$mysqli -> close();
		
		//Count the match
		$match_count++;
		
	}else{
       	//no action...for now
	}
	
	$pagesCounted++;

	//define regex for domain links
	$edu = '/(?<=http).*' . $domain . '.*/i';
	
	if (is_bool($html)===false){
		
		foreach($html->find('a') as $link){
			$url = $link -> href;
			
			if(preg_match($edu, $url) && !already_checked($url, $searched_urls)){
				//add qualifying urls to the search list
				$not_yet_searched_urls[]=$url;
			}
		}
	}
	
unset($html);
return true;
}

//------Function to Check Array-----//
function already_checked($reference,$array){
	foreach($array as $ref){
		if (strstr($reference,$ref)){         
			return true;
		}
    }
    return false;
} 

?>