<?php 
session_start();

//include_once('cadi_response_parser.php');
include_once('packet_processor.php');
$btd_state_text = '';


if (isset($_GET['action'])) {
	$action = $_GET['action'];
}
else {
	$action = $_POST['action'];
}


switch ($action) {
	case 'bt_connect':
		$toput = "release,".$_POST['rfcomm'].", ";
		file_put_contents('daemon_cmd', $toput);
		sleep(4);
		file_put_contents('daemon_cmd', $toput);
		sleep(4);
		$toput = "bind,".$_POST['rfcomm'].",".$_POST['mac'];
		file_put_contents('daemon_cmd', $toput);
		sleep(4);
		$toput = "stream,".$_POST['rfcomm'].", ";
		file_put_contents('daemon_cmd', $toput);
		break;
	case 'bt_disconnect':
		$toput = "release,".$_POST['rfcomm'].", ";
		echo $toput;
		file_put_contents('daemon_cmd', $toput);
		sleep(1);
		break;
	case 'btd_stream_start':
		$toput = "stream_status,".$_POST['status'].", ";
		file_put_contents('daemon_cmd', $toput);
		break;
	case 'reboot':
		$toput = "reboot, , ";
		echo $toput;
		file_put_contents('daemon_cmd', $toput);
		sleep(1);
		break;
	case 'cadi_reset':
		bt_tx('cadi', $cadi_packet);
		break;
	case 'bt_restart':
		$toput = "restart,";
		echo $toput;
		file_put_contents('daemon_cmd', $toput);
		sleep(1);
		break;
	case 'rx_ee':
		$toput = "rx_ee,cadi,".$_POST['addr'].", ".$_POST['number'].", ";
		include_once('cadi_settings.php');
		file_put_contents('daemon_cmd', $toput);
		sleep(1);
		break;
	case 'tx':
		bt_tx('cadi', $cadi_packet);
		print_r($cadi_packet);
		break;
	case 'tx_packet':
//		$cadi_packet = "Test Cadi Packet";
		bt_tx('cadi', $cadi_packet);
		break;
	case 'rfcomm_scan':
		rfcomm_scan();
		break;
	case 'rfcomm_list_binded':
		rfcomm_list_binded();
		break;
	case 'stop_serial_read':
		stop_serial_read($_POST['process']);
		break;
	case 'tail_serial_log':
		tail_serial_log($_POST['amount']);
		break;
	case 'command_send':
		command_send($_POST['command'], $_POST['mac']);
		break;
	case 'get_status':
//		include_once('cadi_status.php');
		include_once('status_view_1.php');
		echo '---socalledseparator---';
		include_once('status_view_2.php');
		break;
	case 'get_status_csv':
		$csv = file_get_contents('cadi_status.csv');
		echo $csv;
		break;
	case 'get_ip':
		array($output);
		exec('/sbin/ifconfig -a', $output);
		print_r($output);
		echo $out;
		echo 'Should be IFCONFIG output';
		break;
	case 'change_video':
		change_video($_POST['new_video']);
		break;
	case 'btd_apply_settings':		// save settings to file
		btd_apply_settings($_POST['settings']);
		break;
	case 'svg_apply_settings':		// save settings to file
		svg_apply_settings($_POST['settings']);
		break;
	case 'cadiweb_update':		// save settings to file
		$toput = "cadiweb_update, , ";
		echo $toput;
		file_put_contents('daemon_cmd', $toput);
		sleep(1);
		break;
	case 'redraw_update_log':		// save settings to file
		redraw_log();
		break;
	case 'tx_str':
		$tx_str = 'tx,cadi,'.$_POST['str'].',';
		file_put_contents('daemon_cmd', $tx_str);
		break;
	case 'upload_csx':
		// parse $params into array of values
		$params = array();
		parse_str($_POST['csx_data'], $params);
		upload_csx($params);
		break;
	case 'download_csx':
		file_put_contents('daemon_cmd', 'ee2server,1500,200,');
		break;
	case 'get_btd_state':

		$fp = fopen("btds/btd_state", "r");

		if (flock($fp, LOCK_SH | LOCK_NB)) { // read lock
		    $btd_state = fread($fp, 1);
		    flock($fp, LOCK_UN); // release the lock
		} else {
		    echo "Couldn't lock the file !";
		}

		fclose($fp);

		switch ($btd_state) {
			case '1':
				$btd_state_text='1<font color="green">BTD: Idle</font>';
			break;
			case '2':
				$btd_state_text='2<font color="yellow">BTD: CSX DL</font>';
			break;
			case '3':
				$btd_state_text='3<font color="yellow">BTD: CSX UL</font>';
			break;
			default:
				$btd_state_text='1<font color="green">N/A</font>';
			break;
		}
		
		echo $btd_state_text;
		break;
}

// uploads Cadi Settings passed from Web Form in Cadiweb into Cadi EEPROM
function upload_csx($csx){

	// create array for values of cadi settings form
	$curaddr = 0;
	$ndx = 0;
	unset($outarr);
	$outarr = array();
	foreach ($csx as $key=>$value) {
		unset($tmparr);
		$tmparr = explode("_", $key);
		$rowaddr = $tmparr[1];
		$subkey = $tmparr[2];
		if ($curaddr != $rowaddr) {
			$curaddr = $rowaddr;
			$ndx++;
		}
		$outarr[$ndx][$subkey] = $value;
		$outarr[$ndx]['addr'] = $rowaddr;	
	}

	// prepare cadi settings config file contents
	unset($outfile);
	foreach ($outarr as $key=>$value) {
		unset($line);
		$line = $value['addr'].','.$value['type'].','.$value['value'].','.$value['text'].','.PHP_EOL;
		$outfile .= $line;
	}
	// put file contents into config file
	file_put_contents('cadi_settings_conf.csv',$outfile);

	// start settings transfer into Cadi EEPROM
//	unset($cmd);
//	$cmd = "rx_ee,cadi,1400,200, ";
//	file_put_contents('daemon_cmd', $toput);
	sleep(1);
	
}

function redraw_log(){
	$logtail = array();
	exec('tail 500 /var/www/cadiweb_update_log', $logtail);
	for ($i=0; $i<sizeof($logtail);$i++){
		echo $logtail[$i].PHP_EOL;
	}
	
}

function btd_apply_settings($settings){
	file_put_contents('btds/btd.conf',$settings);
	file_put_contents('daemon_cmd','reload_settings,');	// force Cadi BTDaemon to reload settings file
	echo $settings;
}

function svg_apply_settings($settings){
	file_put_contents('svg.conf',$settings);
	echo $settings;
}

function command_send($command, $mac){
	$out = array();
	$cmd = 'sudo echo '.time().': '.$command.' >> cadi_input';
	exec($cmd, $out);
	echo $cmd;
}

function change_video($video){
		$toput = "change_video,".$video.", , ";
		echo $toput;
		file_put_contents('daemon_cmd', $toput);
//		sleep(1);
}

function bt_tx($rfcomm, $data){
		$toput = "tx,".$rfcomm.",".$data.", ";
		echo $toput;
		file_put_contents('daemon_cmd', $toput);
		sleep(1);
}

function tail_serial_log($amount){
	$filename = 'serialresp.out';
	if (1) {
		# Processing
		
		$i = 0;
		$arrr = read_file('serialresp.out', 10);
		echo "---separator---";
		$cadi_str_arr = explode(',', $arrr[8]);
		$cadi_time = date('l jS \of F Y h:i:s A', $cadi_str_arr[0]);
		
		
		for ($i=0; $i<3; $i++) {
			$curflag = substr($cadi_str_arr[3], $i, 1);
			if ($curflag == '1'){
				$plugout .= '<b style="background:green; color:white;">&nbsp;'.$i.'&nbsp;</b>';
			}
			else {
                       		$plugout .= '<b style="background:red; color:white;">&nbsp;'.$i.'&nbsp;</b>';
			}
		}

		for ($i=0; $i<3; $i++) {
			$curflag = substr($cadi_str_arr[7], $i, 1);
			if ($curflag == '1'){
				$ctimerout .= '<b class="flgenb">'.$i.'</b>';
			}
			else {
                       		$ctimerout .= '<b class="flgdsb">'.$i.'</b>';
			}
		}
		
		
		$timer_States='';
		echo $arrr[8];
	}
		

}

function stop_serial_read($psid){
	$out = array();
	exec('kill -9 '.$psid, $out);
	echo 'kill -9 '.$psid;
}

function rfcomm_scan(){
	$out = array();
	$out2 = array();
	exec('hcitool scan', $out);

	// parse 'hcitool scan' output and prepare options to put into <select> html tag
	for ($i=1; $i<sizeof($out); $i++) {
		$out2[$i][0] = substr($out[$i], 1, 17);
	    	$out2[$i][1] = substr($out[$i], 18, strlen($out[$i])-18);
		echo '<option value="'.$out2[$i][0].'">';
		echo $out2[$i][1].' ('.$out2[$i][0].')';
		echo '</option>';
	}
}


function rfcomm_list_binded(){
	$out = array();
	exec('rfcomm -a', $out);
	echo '<ul>';
	for ($i=0; $i<sizeof($out); $i++) {
		$rfend = strpos($out[$i], ':');
		$rfcomm_name = substr($out[$i], 0, $rfend);
		echo '<li>
			'.$out[$i].'&nbsp;&nbsp;&nbsp;
				<div 
					style="display:inline; border:1px solid red;"
					onClick=bt_disconnect("'.$rfcomm_name.'");>
					Disconnect
				</div>
			</li>';
	}
	echo '</ul>';
	unset($out);
	echo '<ul>';

/*	for ($i=0; $i<sizeof($out); $i++) {
		$position = strpos($out[$i], 'rfcomm');
		if (strpos($out[$i], 'oot')>0 && $position==0) {
			$startpos = digit_offset($out[$i]);
			$psid = substr($out[$i], $startpos,5);
			echo '<li>
				'.$out[$i].'
					<div 
						style="border:1px solid red; display:inline;" 
						onClick=stopSerialRead('.$psid.')>
						Kill '.$psid.'</div></li>';
		}
	} */
	echo '</ul>';
}

function digit_offset($text){
    preg_match('/^\D*(?=\d)/', $text, $m);
    return strlen($m[0]);
}


function rfcomm_bind($name, $mac, $channel){
	$out = array();
	$out = exec('rfcomm -r bind /dev/'.$name.' '.$mac.' '.$channel);
}

function rfcomm_release($name){
	$out = array();
	$out = exec('rfcomm release /dev/'.$name);	
}


function rfcomm_connect($rfcomm_name, $mac){
	$out = array();
	$cmd = './bt_expect.exp '.$rfcomm_name.' '.$mac.' > /dev/null &';
	$cmd = 'id';
	$out = exec($cmd);
	echo $cmd.'<br>';
	print_r($out);
}

// kill the process with name "rfcomm"
function rfcomm_kill(){
	$out = array();
	exec('kill -9 $(pidof rfcomm)');
	print_r($out);
}


function read_file($file, $lines) {
       $handle = fopen($file, "r");
       $linecounter = $lines;
       $pos = -2;
       $beginning = false;
       $text = array();
       while ($linecounter > 0) {
         $t = " ";
         while ($t != "\n") {
           if(fseek($handle, $pos, SEEK_END) == -1) {
		$beginning = true;
		break;
	   }
           $t = fgetc($handle);
           $pos --;
         }
         $linecounter --;
         if($beginning) {
		rewind($handle);
	 }
         $text[$lines-$linecounter-1] = fgets($handle);
         if($beginning) {
		break;
	 }
       }
       fclose ($handle);
       return array_reverse($text); // array_reverse is optional: you can also just return the $text array which consists of the file's lines.
}

?>
