<?php
//Ryan Flynn
include_once('simple_html_dom.php');

//Initialize Variables
$execution_limit = 5000;
$time_start = microtime(true); 
$pagesCounted = 0;
$date = date("Y-m-d\,h:i:s A");
$match_count = 0;
$searched_urls = array();
$prev_url = "";
$max_queue_size = 0;

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

//Create Inital Log entry
$logEntry = $date . "," .$domain.",";
$file = 'node_log.txt';
file_put_contents($file, $logEntry, FILE_APPEND | LOCK_EX);

//-----Main program loop-----
while(count($not_yet_searched_urls) > 0 && $pagesCounted < $execution_limit){
	if($prev_url != $target_url){
		try{
			search(&$target_url,&$searched_urls,&$not_yet_searched_urls, &$pagesCounted, $date, $domain, &$match_count);
		}
		catch (Exeception $e)
		{
			echo 'Failed to search target page\n';
		}
	}
	$prev_url = $target_url;
	$target_url = array_shift($not_yet_searched_urls);	
	
	//Debugging Output
	echo "Target URL: " . $target_url . "\n";
	$queue_size = count($not_yet_searched_urls);
	echo "Queue: ". $queue_size . " Pages Loaded: ". $pagesCounted ."\n";	
	
	if ($queue_size > $max_queue_size){
		$max_queue_size = $queue_size;
	}
	
	//if ($pagesCounted % 10 == 0){
		echo "\n--------------------------------\n";
		echo "Memory Useage: ".number_format((memory_get_usage()/1000000),2,'.','') . " MB \n";
        	$cpu = exec('top -b -d1 -n1|grep -i "Cpu(s)"|head -c21|cut -d \' \' -f3|cut -d \'%\' -f1');
        	echo "CPU Useage:    ".$cpu."%\n\n";  
	//}
}

//Post Crawl Output
$time_end = microtime(true);
$execution_time = ($time_end - $time_start);
echo "Pages scanned: ". $pagesCounted ."\n";
echo "Pages left in queue: " . count($not_yet_searched_urls) . "\n";
echo "Max Queue Size was:  " . $max_queue_size . "\n";
echo "Number of matches: " . $match_count . "\n";
echo 'Total Execution Time: '.(int)($execution_time/60) .' minutes and '.($execution_time%60)." seconds\n";
//Post Crawl Logging
//Create Inital Log entry
$logEntry = $pagesCounted . ",".count($not_yet_searched_urls). ",".$match_count . "," .$execution_time."\n";
$file = 'node_log.txt';
file_put_contents($file, $logEntry, FILE_APPEND | LOCK_EX);

//-----Search Function-----//
function search($target_url,&$searched_urls,&$not_yet_searched_urls, &$pagesCounted, $date, $domain, &$match_count){
	
    	$target_url = follow_url($target_url);	
	$searched_urls[] = $target_url;

        $html = file_get_html($target_url);
	
	//sometimes pages fail to load, if so exit function
	if(gettype($html) == "boolean"){
		unset($html);
		return false;
	}
	
	$pattern = '/(.*sustain.*)|(.*environmental.*)/i';
    if (preg_match($pattern, $html)){
		//write URL to database
		$mysqli = new mysqli("localhost","user","password","database");
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
	$edu = '/(?<=http:).*' . $domain . '.*/i';
	$avoid = '/(\.jpg|\.gif|\.png|\.pdf|\.zip|\.tar|\.rar|\.msi|\.esp|\.exe|\.sh|\.pl)$/i';	
	if (is_bool($html)===false){
		
		foreach($html->find('a') as $link){
			$url = $link -> href;
			$url = follow_url($url);
			if(preg_match($edu, $url) && !(preg_match($avoid, $url)) && already_checked($url,$not_yet_searched_urls) && already_checked($url, $searched_urls)){
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
	if(array_search($reference, $array, false)=== false){
		return true;
	}else{
		return false;
	}

} 

function follow_url($url){
	//Use curl to follow redirects and find effective URL
        $ch = curl_init();
        $timeout = 0;
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        //$data = curl_exec($ch);
        $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

	return $url;
}
?>
