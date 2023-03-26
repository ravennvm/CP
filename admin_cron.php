<?php
/**
 * Game Control Panel v2
 * Copyright (c) www.intrepid-web.net
 *
 * The use of this product is subject to a license agreement
 * which can be found at http://www.intrepid-web.net/rf-game-cp-v2/license-agreement/
 */
define('THIS_SCRIPT', 'gamecp');
define('CONFIRM_PAGE', true);
define('ODP_RECHECK_7Z_ENCRYPT75469009373', true);
$out = '';
include($_SERVER['DOCUMENT_ROOT'] . '/gamecp_common.php');
if (!empty($setmodules)) {
    $file = basename(__FILE__);
	$module[_l('Manager Package')][_l('<span style="color:gold"><strong>*Generator AutoPay Package</span></strong>')] = $file;
    return;
}

$lefttitle = _l('Admin Confirm');
$time = date('F j Y G:i');
global $config;
if (isset($config['addon_admin_key_license']) && $config['addon_admin_key_license'] == $keyGenerate) 
{
	//SET USERNAME DAN PASSWORD BCA DISINI
	#$username_bca = 'elida6202';
	#$password_bca = '888815';
	$username_bca = $config['bca_user_id'];
	$password_bca = $config['bca_user_pw'];
	
	$data = file_get_contents('http://' . $_SERVER['HTTP_HOST'] . '/bca_mutasi.php?username=' . $username_bca . '&password=' . $password_bca);
	$data = str_replace("<br>",'',$data);
	$data = explode(';;',$data);
	#unset($data[count($data) - 1]);
	$countData = count($data);
	#print_r($data);
	if (isset($config['addon_license_bca']) && $config['addon_license_bca'] == '1') 
	{
		#------------------------------------------------------------#
		#SCRIPT PART.1 = PARSE DARI BCA DAN INSERT KE gamecp_scrapped#
		#------------------------------------------------------------#
		
		$date = '2018-03-08';
		connectgamecpdb();
		
		//DATA ADALAH ARRAY DARI KUMPULAN2 FULL STRING BERISIKAN(NAMAPENGIRIM,JUMLAH,TANGGAL)
		if (count($data)) 
		{
			//LOOPING FOREACH
			foreach($data as $fulldata) 
			{
				//$FULL DATA SUDAH BERUPA ARRAY BERISIKAN NAMAPENGIRIM,JUMLAH,TANGGAL
				$fulldata = explode('::',$fulldata);
				//ECHO DATA DATA.
				#echo 'pengirim : ' . $fulldata[1] . ', amount : ' . $fulldata[2] . ', tanggal : ' . $fulldata[0] . '<br>';
				//DECLARE $PENGIRIM = ARRAY_FULLDATA INDEX 1 -> NAMAPENGIRIM
				$pengirim = $fulldata[1];
							
				//CHECK APAKAH NAMAPENGIRIM BUKAN BUNGA. LANJUT JIKA BENAR, SALAH SKIP
				if (strpos($pengirim, 'BUNGA') !== true) {
					//PARSE TANGGAL,JUMLAH
					$tanggal = $fulldata[0];
					$jumlah = $fulldata[2];
					
					//DECLARE STATUS AWAL
					$status = 'pending';
					
					//$FACT = BOOLEAN YANG MENJELASKAN APAKAH DATA DENGAN NAMAPENGIRIM,JUMLAH,TANGGAL SUDAH ADA DI TABLE gamecp_scrapped
					$fact = false;
					
					//LAKUKAN PENGECEKAN DATA | $QUERY2 = QUERY UTK CHECK | HASILNYA MENGECEK DATA2 APAKAH TERDAPAT PADA TABLE gamecp_scrapped2 -> INI SEMUA HARUS YANG BERSTATUS PENDING SAJA
					$query2 = mssql_query("SELECT * FROM gamecp_scrapped2 WHERE amount='$jumlah' AND sender_name='$pengirim' AND status='$status'");
							
					//JIKA TERNYATA DATA DITEMUKAN DI TABLE SESUAI SYARAT" DAN STATUSNYA = PENDING, MAKA UBAH $fact = TRUE, ARTINYA DITEMUKAN
					if(mssql_num_rows($query2)) 
					{
						$fact = true;
					}
					
					//JIKA $fact = FALSE, BERARTI DATA TAK DITEMUKAN, INSERT KE gamecp_scrapped
					if (!$fact && $pengirim != '') 
					{
						#echo $tanggal . ':' . $pengirim . ':' . $jumlah;
						$query = mssql_query("INSERT INTO gamecp_scrapped2 (amount,sender_name,date,status) VALUES('$jumlah','$pengirim','$tanggal','$status')");
						#echo $query;
						@mssql_free_result($select_result);
					}
				}
			}
		}

		#------------------------------------------------------------#
		# SCRIPT PART.2 = RUN EXPIRED MODE                           #
		#------------------------------------------------------------#
		
		//INSERTING
		date_default_timezone_set('Asia/Jakarta');
		$time_now =  date("h:i:sa");
				
		//PART SCRIPT UNTUK MERUBAH $time_now MENJADI POLOS. CONTOH $time_now = 01:03:14:PM, SETELAH SCRIPT INI BERJALAN
		//$time_now AKAN MENJADI 010314. (PART SCRIPT SELESAI SAMPAI IF DAN ELSE SELESAI)
		if(strpos($time_now,'am') > 0) 
		{
			$time_now = str_replace('am','',$time_now);
			$time_now = str_replace(':','',$time_now);
		}
		else if(strpos($time_now,'pm') > 0) 
		{
			$time_now = str_replace('pm','',$time_now);
			$time_now = str_replace(':','',$time_now);
			$time_now = $time_now + 120000;
		}
		//PART SCRIPT SELESAI

		//DEKLARASI $date_now SEBAGAI TANGGAL HARI INI DALAM FORMAT Y-m-d
		$date_now = date("Y-m-d");
		
		//DEKLARASI $date_now2 SEBAGAI $date_now YANG BISA DI CUSTOM
		$date_now2 = strtotime($date_now);
		
		//$date_now2 MENJADI TANGGAL HARI KEMARIN DARI HARI INI.
		$date_now2      = $date_now2 - (3600*24);
		
		//$date_deadline ADALAH $date_now2 DALAM FORMAT Y-m-d
		$date_deadline = date("Y-m-d", $date_now2);
		
		//DEKLARASI $status
		$status = 'pending';
		
		//DEKLARASI $date_nows SEBAGAI $date_now YANG DIBUAT POLOS (REPLACE SIMBOL -)
		$date_nows = str_replace('-','',$date_now);
		
		//DEKLARASI $failed
		$failed = 'failed';
				
		//MULAI MODE EXPIRING
		$query = mssql_query("SELECT * FROM gamecp_donation_item_request WHERE status='$status'"); 
		
		//HANYA LAKUKAN JIKA $query MEMILIKI LEBIH DARI 0 BARIS
		if(mssql_num_rows($query) > 0) 
		{
			//LOOPING WHILE UNTUK MENGECEK TIAP TIAP BARIS
			while ($row = mssql_fetch_array($query)) 
			{
				$requester_name = $row['requester_name'];
				$amount = $row['amount'];

				$time_deadline = $row['time_deadline'];
						
				//PART SCRIPT UNTUK MERUBAH $time_now MENJADI POLOS. CONTOH $time_now = 01:03:14:PM, SETELAH SCRIPT INI BERJALAN
				//$time_now AKAN MENJADI 010314. (PART SCRIPT SELESAI SAMPAI IF DAN ELSE SELESAI)
				if(strpos($time_deadline,'am') > 0) 
				{
					$time_deadline = str_replace('am','',$time_deadline);
					$time_deadline = str_replace(':','',$time_deadline);
				} 
				else if(strpos($time_deadline,'pm') > 0) 
				{
					$time_deadline = str_replace('pm','',$time_deadline);
					$time_deadline = str_replace(':','',$time_deadline);
					$time_deadline = $time_deadline + 120000;
				}
				//PART SCRIPT SELESAI
					
				$time_deadline = str_replace('pm','',$time_deadline);
				$time_deadline = str_replace(':','',$time_deadline);
				$date_deadline = $row['date_deadline'];
				$date_deadline_2 = str_replace('-','',$date_deadline);
				
				//JIKA TANGGAL HARI INI MELEBIHI DEADLINE DI TABLE, MAKAN SET STATUS MENJADI FAILED
				if($date_nows >= $date_deadline_2 ) 
				{
					$query2 = mssql_query("UPDATE gamecp_donation_item_request SET status='$failed' WHERE requester_name='$requester_name' AND amount='$amount' AND status='$status'");
				}
			}
		}
		
		//FREE RESULT
		@mssql_free_result($query);
		@mssql_free_result($query2);
				
		#------------------------------------------------------------#
		# SCRIPT PART.3 = EXECUTE YANG PENDING DAN UBAH JADI CLAIMED #
		#------------------------------------------------------------#
		
		//connect ke db dan tbl
		connectgamecpdb();
		$username = $userdata['username'];
		$query = mssql_query("SELECT * FROM gamecp_scrapped2 WHERE status='$status'"); //query select dari scrapped table
				
		//DECLARE $everSuccess YAITU STRING YNG MENYATAKAN MINIMAL ADA SATU YANG BERHASIL DI KONFIRMASI
		$everSuccess = 0;
		
		//DECLARE $out VARIABLES
		$out_execute = '';
		$out_find = '';
		
		$tempData = array();
		
		$i=0;
		
		while($query_results = mssql_fetch_assoc($query))
		{
			$iamArray = false;
			$goProccess = false;
			foreach($query_results as $index=>$data)
			{
				if($index == 'sender_name' AND preg_match("/[a-z]/i", $data))
				{
					#DEBUGecho 'data_' . $data . '_' . $index . '_' . '<br>';
					$goProccess = true;
				}
				if(in_array($query_results,'') == false && $goProccess)
				{
					$tempData[$i][$index] = $data;
					$iamArray = true;
				}
			}
			if($iamArray)
			{
			$i++;
			}
		}
		#print_r($tempData);
		$countData = count($tempData);
		
		//$query_result ADALAH ARRAY BERISI DATA (NAMAPENGIRIM,JUMLAH,TANGGAL,STATUS) DARI gamecp_scrapped2. LAKUKAN PENGECEKAN
		for($i=0;$i<count($tempData);$i++)
		{
			//DEKLARASI $amount YAITU JUMLAH DI gamecp_scrapped2
			$amount = $tempData[$i]['amount'];
			
			//DEKLARASI $sender_name YAITU NAMAPENGIRIM DI gamecp_scrapped2
			$sender_name = $tempData[$i]['sender_name'];
			
			//SOME DEBUGGING LINE
			/**
			#$out .= 'Sekarang mengevaluasi sender name ' . $tempData[$i]['sender_name'] . ',dengan amount ' . $amount . '<br>';
			*/
			
			//CHECK APAKAH DATA $query_result ADA DI gamecp_donation_item_request DAN BER STATUS PENDING
			$query2 = mssql_query("SELECT * FROM gamecp_donation_item_request WHERE amount='$amount' AND status='$status'");
			
			//JALANKAN JIKA $query2 BERHASIL
			if(mssql_num_rows($query2))
			{
				//DEKLARASI $claimed DAN VARIABLE $out LAIN
				$claimed = 'claimed';
				
				//AMBIL DATA DATA DARI gamecp_donation_item_request YANG SESUAI DENGAN gamecp_scrapped
				$result_query2 = mssql_fetch_assoc($query2);
				#print_r($result_query2);
				//DEKLARASI $requester_name YAITU REQUESTER NAME DI gamecp_donation_item_request
				$requester_name = antiject($result_query2['requester_name']);
				
				//DEKLARASI $requester_char YAITU REQUESTER CHAR DI gamecp_donation_item_request
				$requester_char = antiject($result_query2['requester_char']);
				
				//AMBIL PACKET ID
				$packet_id = antiject($result_query2['effect']);
				
				//LAKUKAN EFFECT
				$cmd = ($requester_char != '') ? donateItem($requester_char,$packet_id) : false;
				
				connectgamecpdb();
				
				//CPT,DLL,
				$query = mssql_query("SELECT * FROM gamecp_donate_items WHERE packet_id ='$packet_id'");
				$result_query_packet_details = mssql_fetch_assoc($query);
				$pvpcash = antiject($result_query_packet_details['pvpcash']);
				$premium = antiject($result_query_packet_details['premium']);
				$cpt = antiject($result_query_packet_details['cpt']);
				$cashcoin = antiject($result_query_packet_details['cashcoin']);
				$claimed  = 'claimed';
				$not_pvpcash = true;
				$not_premium = true;
				$not_cpt = true;
				$not_cashcoin = true;
				$has_pvpcash = false;
				$has_premium = false;
				$has_cpt = false;
				$has_cashcoin = false;
				
				if($requester_name != '' && $requester_char != '')
				{
					if ($pvpcash != '')
					{
						$not_pvpcash = false;
						$has_pvpcash = true;
						if (effect_exe($requester_name,1,$pvpcash))
						{
							$not_pvpcash = true;
						}
							
					}
						
					if ($premium != '')
					{
						$not_premium = false;
						$has_premium = true;
						if(effect_exe($requester_name,2,$premium))
						{
							$not_premium = true;
						}
					}
						
					if ($cpt != '')
					{
						$not_cpt = false;
						$has_cpt = true;
						if(tambahCpt($requester_char,$cpt))
						{
							$not_cpt = true;
						}
					}
						
					if ($cashcoin != '')
					{
						$not_cashcoin = false;
						$has_cashcoin = true;
						if(tambahCashcoin($requester_char,$cashcoin))
						{
						$not_cashcoin = true;
						}
					}
				}

				//JIKA $cmd,$query3,$query4, RETURN TRUE. LANJUT JIKA BENAR.
				if($cmd == true && $not_pvpcash == true && $not_premium == true && $not_cashcoin == true && $not_cpt == true && $requester_name != '') 
				{
					//CONNECT BACK TO GAMECP DATABASE
					connectgamecpdb();
					
					//UPDATE SET STATUS = CLAIMED
					$query3 = mssql_query("UPDATE gamecp_scrapped2 SET status='$claimed' WHERE sender_name='$sender_name' AND amount='$amount' AND status='$status' ");
					$query4 = mssql_query("UPDATE gamecp_donation_item_request SET status='$claimed', date_approved='$date_now' WHERE amount='$amount' AND status='$status'");
					
					$out_confirm .= 'Username : ';
					$out_confirm .= $requester_name;
					$out_confirm .= ' ,dengan nominal Rp. ';
					$out_confirm .= number_format($amount);
					$out_confirm .= ' berhasil dikonfirmasi.';
					$out_confirm .= "<br>";
					$everSuccess++;
				}
				else if($requester_name != '')
				{
					$out_execute .= '#DEBUG ALREADY TRYING TO EXECUTE,BUT STILL FAILED. DATA =>';
					$out_execute .= '$cmd : ' . $cmd . ',';
					$out_execute .= '$query3 : ' . $query3 . ',';
					$out_execute .= '$query4 : ' . $query4 . ',';
					$out_execute .= '$not_pvpcash : ' . $not_pvpcash. ',';
					$out_execute .= '$not_premium : ' . $not_premium . ',';
					$out_execute .= '$not_cpt : ' . $not_cpt . ',';
					$out_execute .= '$not_cashcoin : ' . $not_cashcoin . ',';
					$out_execute .= '$requester_name : ' . $requester_name . ',';
					$out_execute .= '$requester_char : ' . $requester_char  . ',';
					$out_execute .= '$not_premium : ' . $not_premium . '<br>';
				}
				else 
				{
					$out_execute .= '#DEBUG ALREADY TRYING TO EXECUTE,BUT STILL FAILED. DATA =>';							$out_execute .= '$amount : ' . $amount . ',';
					$out_execute .= '$cmd : ' . $cmd . ',';
					$out_execute .= '$query3 : ' . $query3 . ',';
					$out_execute .= '$query4 : ' . $query4 . ',';
					$out_execute .= '$not_pvpcash : ' . $not_pvpcash. ',';
					$out_execute .= '$not_premium : ' . $not_premium . ',';
					$out_execute .= '$not_cpt : ' . $not_cpt . ',';
					$out_execute .= '$not_cashcoin : ' . $not_cashcoin . ',';
					$out_execute .= '$requester_name : ' . $requester_name . ',';
					$out_execute .= '$requester_char : ' . $requester_char  . ',';
					$out_execute .= '$not_premium : ' . $not_premium . '<br>';
				}	
			
				//WRITE KE GAMECP_LOG
				gamecp_log(4, $requester_name, "BCA AUTO - UPDATED - Account ID: " . $requester_name . " | Jumlah + : Rp " . number_format($amount) ."", 0);
			}
			else
			{
				$out_find .= '#DEBUG TRYING TO FIND THIS DATA, BUT FAILED. DATA =>';
				$out_find .= '$sender_name : ' . $sender_name  . ', ';
				$out_find .= '$amount : ' . $amount . '<br>';
			}
		} 

		gamecp_log(4, 'CRON-JOB', "CRON JOB RAN", 0);
//MULAI DARI SINI KE BAWAH, TIDAK BERGUNA UNTUK PAGE INI
$always = true;
if ($always) {
	
    if ($always) {

        # Main variables

        $update = (isset($_POST['update'])) ? $_POST['update'] : "";
        $page_gen = (isset($_GET['page_gen'])) ? $_GET['page_gen'] : "1";
        $search_fun = (isset($_POST['search_fun'])) ? $_POST['search_fun'] : "";
        $query_p2 = "";
        $search_query = "";
		$pending = 'pending';
        $exit_process = 0;
        $account_name = (isset($_POST['account_name'])) ? antiject($_POST['account_name']) : '';
		connectuserdb();

		$out .= '<table style="text-align:center;">' . "\n";
		if($countData == 0)
		{
			$out .= '<p style="text-align: center; font-weight: bold; color:red;">API BCA gagal atau kosong</p>';
		}
		else
		{
			$out .= '<p style="text-align: center; font-weight: bold; color:green;">API BCA Berhasil. DATA : ' . $countData .  ' buah</p>';
		}
		if($everSuccess == 0)
		{
			$out .= '<p style="text-align: center; font-weight: bold; color:red;">Tidak ada transfer yang cocok dengan request</p>';
		}
		else
		{
			$out .= '<p style="text-align: center; font-weight: bold; color:green;">UPDATE gamecp_donation_item_request Berhasil</p>';
		}
		$out .= ($out_confirm != '') ? '<center>DATA(s) FOUNDED!</center>' : '';
		$out .= $out_confirm;
		$out .= ($out_find != '') ? '<center>LOOKING FOR THESE DATA(s), BUT COULD' . "'" . 'T FIND THEM</center>' : '';
		$out .= $out_find;
		$out .= ($out_execute != '') ? '<center>EXECUTE THESE DATA(s), BUT WENT WRONG</center>' : '';
		$out .= $out_execute;
		$out .= '</table>';
        $out .= '<br/>' . "\n";

        # Searched?
        if ($search_fun != "") {

            if ($account_name == '') {
               $page = $_SERVER['PHP_SELF'];
				$sec = "0";
                $exit_process = 1;
                $out .= '<meta http-equiv="refresh" content="'. $sec .';URL='. $page .'?do=admin_request_donation"><p style="text-align: center; font-weight: bold;">Please fill in a account name</p>';
            } else if($account_name == $userdata['username']) {
				
			} else {
				$out .= '<table class="table table-bordered" align="center">' . "\n";
				$out .= '	<tr>' . "\n";
                $out .= '		<td class="alt1" style="text-align: center;">Username Error! Contact Admin! </td>' . "\n";
                $out .= '	</tr>' . "\n";
                $out .= '</table>' . "\n";
			}
			
            $out .= '</table>' . "\n";
            $out .= '</form>' . "\n";
			//Writing an admin log :D
            

        } else {
            $query_p2 .= " WHERE ";
        }

        # May we proceed with displaying the dat?
        if ($exit_process == 0) {

            
        }

    } else {
        $out .= _l('no_permission');
    }

} else {
    $out .= _l('invalid_page_load');
}
} else {
        $out .= message(_l('Sorry, this addon has been disabled by the administrator.'), _l('Module Status'), 'warning');
		#$out .= '<p style="text-align: center; font-weight: bold;">This Module has Disabled By Admin.</p>' ;
		}
		} else {
		$out .= message(_l('[ Your Key = <abbr title="This is your License Key">'.$config['addon_admin_key_license'].'</abbr> ] Key License has Incorrect ! For Activated This Addon (BCA AutoPay) Ask to <a href="/">Dev</a>'), _l('License Status Error'), 'warning');
		#$out .= '<p style="text-align: center; font-weight: bold;">Your Key :: <abbr title="This is your License Key">'.$config['addon_admin_key_license'].'</abbr>  | Key License has Incorrect !. For Activated This Addon (Claim Events) Ask to <a href="/">Administrator</a> For Get Key License.</p>' ;
		}
		echo $out;
?>