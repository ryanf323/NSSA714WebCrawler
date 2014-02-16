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

echo "Results:<p>";
//First Function call
echo $date . " <br />";
search($target_url, $pagesCounted);

function search($target_url,&$pagesCounted,&$date){
        echo $target_url."   ";
        $html = new simple_html_dom();
        $html -> load_file($target_url);
		$run_limit = 5;
        $pattern = '/(.*sustain.*)|(.*environmental.*)/i';

        if (preg_match($pattern, $html)){
                echo "<b>Match Found!</b><br />";
                //write URL to database
                $con=mysqli_connect("localhost","db_user","database_name","password");
                // Check connection
                if (mysqli_connect_errno())
                {
                echo "Failed to connect to MySQL: " . mysqli_connect_error();
                }

                mysqli_query($con,"INSERT INTO results (id, url, date) VALUES (NULL, $target_url , $date)");

                mysqli_close($con);
        }else{
                //no action
                echo "<br />";
        }
		$pagesCounted++;
        if ($pagesCounted > $run_limit){
                return;
        }

        $edu = '/(?<=http).*\.edu.*/i';
        foreach($html -> find('a') as $link)
        {
                if(preg_match($edu, $link)){
                        search($link -> href, $pagesCounted, $date);
                }
        }
}

	echo "<b>Pages counted: ". $pagesCounted ."</b>";
?>