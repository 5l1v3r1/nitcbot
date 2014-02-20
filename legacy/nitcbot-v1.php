<?
/* This stopped working with Twitter API v1.1 when 
   every request to the API was required to be authenticated 
*/
$url = "http://search.twitter.com/search.atom?q=nitc+OR+%22nit+calicut%22+OR+nit+calicut+OR+Nit+Calicut+OR+%22Nit+Calicut%22+OR+%22National+Institute+of+Technology+Calicut%22+OR+tathva+OR+ragam+nit+OR+%22%40NITCalicut%22+OR+%22%40NITcalicut%22+OR+%23tedxnitcalicut+-Iran+-vacancy+-jobs+-job+-position+-positions+-naukri+-interview+-vacancies+-GRE+-sarkari+-nangor+-nitcbot+-http%3A%2F%2Fu.nu%2F3nitc+-RestlessMystic";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$curl_scraped_page = curl_exec($ch);
curl_close($ch);
echo $curl_scraped_page; 
?>