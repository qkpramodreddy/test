<?php

function chunk_playlist($m3u8,$stream)
{
    $url_base = parse_url($m3u8);
    $scheme = $url_base['scheme'];
    $host = $url_base['host'];
    $base = pathinfo($url_base['path']);
    $base_path = $base['dirname'];	
    $base_url = $scheme."://".$host.$base_path."/";
    $data = file_get_contents($m3u8); //read the file
    $convert = explode("\n", $data); //create array separate by new line
    $i = 0;
    foreach ($convert as $value){
    if((strstr($value,".key") || strstr($value,".aac") || strstr($value,".ts")) && ($i < 180))
    {
      //echo $value;
      //print "\n";
       if(strstr($value,".key"))
       {
  	  if(preg_match('/URI="([^"]*)",IV=/', $value, $matches)) {
		$key = $matches[1];
  	  }
  	  if(preg_match('/IV=0x(.*)/', $value, $matches)) {
		$hexkey = $matches[1];
  	  }
       }
       if(strstr($value,".ts"))
       {
          $last_chunk = trim($base_url.$value);
          $ts_fname_path = $value;
       }
       elseif(strstr($value,".aac"))
       {
          $last_chunk = trim($base_url.$value);
          $ts_fname_path = $value;
       }
    #echo $key;
    #echo $hexkey;
    #echo $last_chunk;
    $url_base_key = parse_url($key);
    $base_key = pathinfo($url_base_key['path']);
    $key_fname = $base_key['filename'].".key";
    $command = "curl -sO $key";
    $command = "echo  $command >> download_key";
    shell_exec($command);
  
    if(strstr($ts_fname_path,".ts") || strstr($ts_fname_path,".aac"))
    {
    	$base_ts = pathinfo($ts_fname_path);
    	$concat_file_list = $stream.".sh";
    	$concat_file_ff = $stream.".txt";
        if(strstr($ts_fname_path,".ts"))
    	   $ts_fname = $base_ts['filename'].".ts";
        elseif(strstr($ts_fname_path,".aac"))
           $ts_fname = $base_ts['filename'].".aac";
    
	$command = "curl -sO $last_chunk";
	//echo $command;
	$command = "echo  $command >> $concat_file_list";
	//print "\n";
    	shell_exec($command);
    	$clear_ts_fname = "clear".$ts_fname;
    	$command = "/bin/sh decrypt.sh $key_fname $ts_fname $clear_ts_fname $hexkey";
    	//shell_exec($command);
    	
	//o$command = "rm -f $key_fname $ts_fname ";
	$command = "echo  $command >> $concat_file_list";
    	shell_exec($command);
        $sub_command = "'>>' ".$stream.".ts";
	$command = "echo  cat $clear_ts_fname $sub_command >> $concat_file_list";
    	shell_exec($command);
	$command = "echo file  $clear_ts_fname >> $concat_file_ff";
        shell_exec($command);
        #$command = "/bin/sh $concat_file_list";
        #shell_exec($command);
        $ts_fname_path = '';
        $i++;
     }
    }
  }
}
function variant_playlist($master_m3u8)
{
    $url_base = parse_url($master_m3u8);
    $scheme = $url_base['scheme'];
    $host = $url_base['host'];
    $base = pathinfo($url_base['path']);
    $base_path = $base['dirname'];	
    $base_url = $scheme."://".$host.$base_path."/";
    $data = file_get_contents($master_m3u8); //read the file
    $convert = explode("\n", $data); //create array separate by new line
    $child_playlist = array();

    foreach($convert as $value){
       if(strstr($value,"m3u8"))
       {
          $child_playlist[] = trim($base_url.$value);
       }
    }
    return $child_playlist;
}
$master_m3u8 = $argv[1];
$var_plist = variant_playlist($master_m3u8);
$stream = 1;
foreach($var_plist as $m3u8){
chunk_playlist($m3u8,$stream);
$stream++;
//$last_chunk = chunk_playlist($m3u8);
//echo $last_chunk;
}
?>
