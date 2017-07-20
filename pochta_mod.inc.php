<?
defined('IN_MANAGER_MODE') or die();

$dbname = $modx->db->config['dbase'];
$dbprefix = $modx->db->config['table_prefix'];
$theme = $modx->config['manager_theme'];
$charset = $modx->config['modx_charset'];
$site_name = $modx->config['site_name'];
$manager_language = $modx->config['manager_language'];
$rb_base_url = $modx->config['rb_base_url'];
$mod_page = "index.php?a=112&id=".$_GET['id'];
if(isset($_GET["tab"])) $mod_page=$mod_page."&tab=".intval($_GET["tab"]);
$mod_table=$dbprefix."pochta_batch";

$cur_shk_version = '1.1';

define("SHOPKEEPER_PATH","../assets/snippets/shopkeeper/");
if(file_exists(SHOPKEEPER_PATH."module/lang/".$manager_language.".php"))
  $lang = $manager_language;
elseif(file_exists(SHOPKEEPER_PATH."module/lang/russian".$charset.".php"))
  $lang = "russian".$charset;
else
  $lang = "russian";
require_once SHOPKEEPER_PATH."classes/pagination.class.php";
require_once SHOPKEEPER_PATH."module/lang/".$lang.".php";
require_once SHOPKEEPER_PATH."classes/class.shopkeeper.php";
require_once SHOPKEEPER_PATH."classes/class.SHKmanager.php";

$shkm = new SHKmanager($modx);
$shkm->cur_version = $cur_shk_version;
$shkm->langTxt = $langTxt;
$shkm->dbname = $dbname;
$shkm->mod_page = $mod_page;
$shkm->mod_table = $dbprefix."manager_shopkeeper";
$shkm->mod_config_table = $dbprefix."manager_shopkeeper_config";
$shkm->mod_user_table = $dbprefix."web_user_additdata";
$shkm->mod_catalog_table = $dbprefix."catalog";
$shkm->mod_catalog_tv_table = $dbprefix."catalog_tmplvar_contentvalues";
$shkm->tab_eventnames = $dbprefix."system_eventnames";
$shkm->excepDigitGroup = true;
$tmp_config = $shkm->getModConfig();
extract($tmp_config);
$shk_table = $modx->getFullTableName('manager_shopkeeper');

function send_smsc($data,$close_smsc=true){
		if($close_smsc) return;
		$lSMS = '****';
		$pSMS = '****';
		$postdata = http_build_query(
					array(
						'login'=>$lSMS,
						'psw' => $pSMS,
						'phones' => $data["phones"],
						'mes' => $data["mes"],
						'id' => '',
						'sender' => 'Sender',
						'time' => 0,
						'charset' => 'utf-8'
					)
				);

		$opts = array('http' =>
					array(
						'method'  => 'POST',
						'header'  => 'Content-type: application/x-www-form-urlencoded',
						'content' => $postdata
					)
				);
		$context  = stream_context_create($opts);
		$result = file_get_contents('https://smsc.ru/sys/send.php', false, $context);
		return $result;
}
	

$type_id=$_GET["item_type"];	

require_once SHOPKEEPER_PATH."plugins/pochta/pochta_api.class.php";

$action=$_GET["action"];
switch($action){
	case "create":
		$where=isset($_POST["ids"])?'id IN('.$_POST["ids"].')':'';//"status = 8 AND is_zakazn=$type_id";
		if(empty($where)) break;
		$shk_result = $modx->db->query("SELECT id, short_txt, content, allowed, addit, price, delivery_price, currency, date, sentdate, status, email, phone, payment, userid,nalozh,weight,is_zakazn FROM $shk_table WHERE $where ORDER BY id ASC");
		$orders=array();
		while($shk_row = $modx->db->getRow( $shk_result )) {
			$orders[$shk_row["id"]]=$shk_row;
		};
		$orders_addresses=array();
		$orders_fio=array();
		foreach($orders as $i => $data){
			$fulInfo=explode('<br />',$data["short_txt"]);
			$fio=explode(':',$fulInfo[0]);
			$fio=str_replace('&nbsp;','',htmlentities($fio[1]));
			$orders_fio[]=array("id"=>$i,"original-fio"=>$fio);
			$address=explode(':',$fulInfo[1]);
			$address=str_replace('"','',mb_substr($address[1],1));
			$orders_addresses[]=array("id"=>$i,"original-address"=>$address);
		}
		//Проверяем адреса и ФИО для отправки
		$orders_addresses=connectorToPochta::cleanAddress($orders_addresses);
		$orders_addresses_bad=$orders_addresses[1];
		$orders_addresses=$orders_addresses[0];
		$orders_fio=connectorToPochta::cleanFio($orders_fio);
		$orders_fio_bad=$orders_fio[1];
		$orders_fio=$orders_fio[0];
		$no_createOrders=array();
		foreach($orders as $i => $data){
			if(!isset($orders_addresses[$i]))
				$no_createOrders[$i][]="Ошибка в адресе ".print_r($orders_addresses_bad[$i],1);
			else if(!isset($orders_fio[$i]))
				$no_createOrders[$i][]="Ошибка в ФИО ".print_r($orders_fio_bad[$i],1);
		}
		$error_text="";
		if(!empty($no_createOrders)){
			$error_text.='Партия не создана. В следующих заказах обнаружены ошибки:
			<ul>';
			foreach($no_createOrders as $i => $data){
				$error_text.='<li>'.$i.' '.implode(', ',$data).'</li>';
			}
			$error_text.='</ul>';
			break;
		}
		$createOrders=connectorToPochta::createBacklog($orders,$orders_addresses,$orders_fio);
		if(!isset($createOrders["result-ids"]) || empty($createOrders["result-ids"])){
			$error_text.='Не создано заказов';
			$error_text.=print_r($createOrders,1);
			break;
		} 
		/*
		$result_type=array();
		foreach($createOrders["types"] as $n => $type){
			switch($type[0]){
				case "POSTAL_PARCEL":
					if($type[1]=="ORDINARY")
						$result_type[4]=4;
					else if($type[1]=="WITH_DECLARED_VALUE_AND_CASH_ON_DELIVERY")
						$result_type[5]=5;
				break;
				case "EMS":
					$result_type[6]=6;
				break;
				case "LETTER":
					$result_type[20]=20;
				break;
				case "BANDEROL":
					$result_type[21]=21;
				break;
				
			}
		}
		$result_type=implode(',',$result_type);
		$type_id=$result_type;
		*/
		$Batchdate=null;
		if(isset($_POST["date"]) && !empty($_POST["date"])) $Batchdate=$_POST["date"];
		$createBatches=connectorToPochta::createBatch($createOrders["result-ids"],$Batchdate);
		
		foreach($createBatches["batches"] as $i => $batch){
			$newbatch=new Batch($modx,$batch["batch-name"]);
			$type_id=0;
			switch($batch["mail-type"]){
				case "POSTAL_PARCEL":
					if($batch["mail-category"]=="ORDINARY")
						$type_id=4;
					else if($batch["mail-category"]=="WITH_DECLARED_VALUE_AND_CASH_ON_DELIVERY")
						$type_id=5;
				break;
				case "EMS":
					$type_id=6;
				break;
				case "LETTER":
					$type_id=20;
				break;
				case "BANDEROL":
					$type_id=21;
				break;
				
			}
			$newbatch->fillData($batch,$type_id);
			$newbatch->save();
		}
		$modx->sendRedirect($mod_page."&tab=2",0,"REDIRECT_HEADER");	
	break;
	case "change_BD":
		$batchName=$_GET["batchName"];
		$Batchdate=date("Y-m-d");
		if(isset($_GET["date"]) && !empty($_GET["date"])) $Batchdate=$_GET["date"];
		$infoBatch=connectorToPochta::changeBatchDate($batchName,$Batchdate);
		if(empty($infoBatch)){
			$newbatch=new Batch($modx,$batchName);
			$newbatch->date=date("Y-m-d",strtotime($Batchdate));
			$newbatch->update("date");
		}
		exit();
	break;
	case "delete_B":
		$batchName=$_GET["batchName"];
		$infoBatch=connectorToPochta::deleteBatch($batchName);
		if(!empty($infoBatch["errors"])){
			$error_text=print_r($infoBatch["errors"],1);
		}else{
			$newbatch=new Batch($modx,$batchName);
			$newbatch->deleteDB();
			$error_text="Партия ".$batchName." успешно удалена!";
		} 
	break;
	case "send":
		$batchName=$_GET["batchName"];
		$infoBatch=connectorToPochta::sendBatch($batchName);
		if(!isset($infoBatch["error-code"]) || empty($infoBatch["error-code"])){
			//Операция проставления треков по заказам
			$infoBatch=connectorToPochta::getBatchOrders($batchName);
			//Список заказов
			$ids_array=array();
			foreach($infoBatch as $item_id => $order)
				$ids_arrays[]=$item_id;
			$ids_arrays=implode(',',$ids_arrays);
			$sms_template= '@FILE:assets/snippets/shopkeeper/module/templates/sms_shipped.tpl';
			$mail_template = '@FILE:assets/snippets/shopkeeper/module/templates/mail_shipped.tpl';
			$shk_result=$modx->db->query("SELECT id, short_txt, content, allowed, addit, price, delivery_price, currency, date, sentdate, status, email, phone, payment, tracking_num, tracking_flag, userid,status_info FROM $shk_table WHERE id IN (".$ids_arrays.") ORDER BY id ASC");
			$status=3;
			while( $data = $modx->db->getRow( $shk_result ) ) {
				if($data['status']!=8) continue;
				$item_id=$data["id"];
				$item_tracking=$infoBatch[$item_id]["barcode"];
				$update_arr = array("status" => $status,'tracking_num'=>$item_tracking);
				$data['purchases'] = unserialize($data['content']);
				$data['addit'] = unserialize($data['addit']);
				$modx_webuser = $data['userid']!=0 ? $modx->getWebUserInfo($data['userid']) : false;
				$data['status'] = $status;
				$data['tracking_num'] = ($data['tracking_num'] == '' || !empty($item_tracking))?$item_tracking:$data['tracking_num'];
				$max_add_delivery=0;
				foreach($data['purchases'] as $i => $dataArray){
					list($id, $count, $price, $name) = $dataArray;
					$add_delivery= $modx->getTemplateVar('89','*',$id);	 
					if($max_add_delivery<intval($add_delivery['value']))
						$max_add_delivery=intval($add_delivery['value']);
				}
				$data['max_add_deliver']=$max_add_delivery;

				$user_purchase_query = $modx->db->select("setting_value", $shkm->mod_user_table, "webuser = ".$data['userid']." AND setting_name = 'count_purchase'", "", "");

				if($data['userid']){
					if($modx->db->getRecordCount($user_purchase_query)>0){
					  $cur_p_stat = explode('/',$modx->db->getValue($user_purchase_query));
					  $new_p_stat = count($cur_p_stat)>1 ? ($cur_p_stat[0]+1)."/".($cur_p_stat[1]+$data['price']) : ($cur_p_stat[0]+1)."/".$data['price'] ;
					  $p_result = $modx->db->update("setting_value = '$new_p_stat'", $shkm->mod_user_table, "webuser = ".$data['userid']." AND setting_name = 'count_purchase'");
					}else{
					  $new_p_stat = "1/".$data['price'];
					  $sql_p = "INSERT INTO `$shkm->mod_user_table` VALUES (NULL, '".$data['userid']."','count_purchase','$new_p_stat')";
					  $modx->db->query($sql_p);
					}
					
					  $u_table = $modx->getFullTableName( 'web_users' );
					  $u_attr_table = $modx->getFullTableName( 'web_user_attributes' );
					  $result = $modx->db->query( "SELECT $u_table.`username`, $u_attr_table.`phone`
						FROM $u_table LEFT JOIN $u_attr_table ON($u_table.`id` = $u_attr_table.`internalKey`)
						WHERE $u_table.`id` = '$data[userid]'" );

					  $u_name = $modx->db->makeArray( $result );
					  $p_table = $modx->getFullTableName('web_user_additdata');
					  $addit_result = $modx->db->select("count(*)", $p_table, "webuser = '$data[userid]' AND setting_name = 'sms__checkbox' AND setting_value = '1'");
					  $count = $modx->db->getValue( $addit_result );
				}
				$p_allowed = $shkm->allowedArray($data['allowed'],$data['purchases']);
				$shkm->updateInventory($data['purchases'],$p_allowed,$conf_inventory);
				$modx->clearCache();
				$update_arr = array_merge($update_arr,array("sentdate"=>date('Y-m-d H:i:s')));
				$email = !empty($data['email']) ? $data['email'] : ($modx_webuser!=false ? $modx_webuser['email'] : false);
				if(!isset($count)) $count=1;
				if (isset($sms_template) && $count && !$close_smsc){
					if(!isset($modx->placeholders))
						$modx->placeholders = array();
					$chunkArr = array('orderID' => $data['id'],'tracking_num' => $data['tracking_num']);
					$mainChunk = $shkm->fetchTpl($sms_template);
					$chunk = $mainChunk;
					  foreach (array_merge($chunkArr,$modx->placeholders) as $key => $value){
						$chunk = str_replace("[+".$key."+]", $value, $chunk);
					  }
					  //$client = new SoapClient('http://smsc.ru/sys/soap.php?wsdl');
					  $lSMS = 'strunki';
					  $pSMS = 'varvara0511';
					  $mSMS = ucfirst($chunk);
					  $vowels = array("(", ")"," ","+","-");
					  $pattern = "#^(\+7|8|7)[\d\(\)\ -]{4,14}\d$#";
					  if($data['userid'] && isset($u_name[0]['phone']) && $u_name[0]['phone']!=''){
						$tSMS = $u_name[0]['phone'];
					  }
					  else{
						$fulInfo=explode('<br />',$data["short_txt"]);
						$phone=explode(':',$fulInfo[3]);
						$phone=trim(str_replace('&nbsp;','',htmlentities($phone[1])));
						if(preg_match($pattern, $phone)){
							$tSMS=$phone;
						}
					  }
				 
					  if(isset($tSMS) && preg_match($pattern, $tSMS)){
						$tSMS = trim(str_replace($vowels, '', $tSMS));
						$ret = send_smsc(array('phones'=>$tSMS, 'mes'=>trim($mSMS)),$close_smsc);
					  }
				}
				if($email!==false){
					$info_status=$langTxt['phase'.$status];
					$shkm->status_sendMail($site_name.". ".$langTxt['mail_subject']." ".$info_status,$email,$data,$mail_template);

				}
				$data["status_info"].=date('d.m.Y H:i:s').' <b>'.$langTxt['phase'.$status].'</b> - '.$fam.'<br/>';
				$update_arr = array_merge($update_arr,array("status_info"=>$data["status_info"]));
				$update_arr = array_merge($update_arr,array("sentdate"=>date('Y-m-d H:i:s')));
				$change_status = $modx->db->update($update_arr, $shkm->mod_table, "id = $item_id");

				$evtOut = $modx->invokeEvent('OnSHKChangeStatus',array('order_id'=>$item_id,'status'=>$status,'tracking_num'=>$item_tracking));
				if (is_array($evtOut)) echo implode('', $evtOut);
				unset($evtOut);
			}
			$newbatch=new Batch($modx,$batchName);
			$newbatch->status="CHEKIN";
			$newbatch->update("status");
			$zip_name=connectorToPochta::getBatchZipDocs($batchName);
			if(file_exists($zip_name))
			{
				$file_name=explode('/',$zip_name);
				$file_name=$file_name[count($file_name)-1];
				// отдаём файл на скачивание
				header('Content-type: application/zip');
				header('Content-Disposition: attachment; filename="'.date("d.m.Y").'_'.$file_name.'"');
				readfile($zip_name);
				// удаляем zip файл если он существует
				unlink($zip_name);
				exit();
			}
			$modx->sendRedirect($mod_page,0,"REDIRECT_HEADER");
		}else{
			var_dump($infoBatch["error-code"]);
			exit();
		}
	break;
	case "get_BO":
		$batchName=$_GET["batchName"];
		$infoBatch=connectorToPochta::getBatchOrders($batchName);
		echo 'Заказы в партии:
		<ul>';
		foreach($infoBatch as $id => $order){
			echo '<li>'.$id.'</li>';
		};
		echo '</ul>';
		exit();
	break;
	case "get_O":
		$orderID=$_GET["orderID"];
		$infoOrder=connectorToPochta::getOrder($orderID);
		var_dump($infoOrder);
	break;
	case "batch":
		$batchName=$_GET["batchName"];
		$infoBatch=connectorToPochta::getBatch($batchName);
		echo '<br/>';
		var_dump($infoBatch);
	break;
	case "archive":
		$batchName=$_GET["batchName"];
		$infoBatch=connectorToPochta::archiveBatch($batchName);
		foreach($infoBatch as $i => $batch){
			$newbatch=new Batch($modx,$batch["batch-name"]);
			$newbatch->status="ARHIVE";
			$newbatch->update("status");
		}
	break;
	case "zip":
		$batchName=$_GET["batchName"];
		$zip_name=connectorToPochta::getBatchZipDocs($batchName);
		if(file_exists($zip_name))
		{
			$file_name=explode('/',$zip_name);
			$file_name=$file_name[count($file_name)-1];
			// отдаём файл на скачивание
			header('Content-type: application/zip');
			header('Content-Disposition: attachment; filename="'.date("d.m.Y").'_'.$file_name.'"');
			readfile($zip_name);
			// удаляем zip файл если он существует
			unlink($zip_name);
			exit();
		}
	break;
	default:
}

//Фильтрация заказов по статусу
$fcount="status = 8";

  include "templates/header.tpl.php";	
  //echo "SELECT COUNT(*) FROM $shkm->mod_table WHERE ".$fcount;
	$count_query = mysql_query("SELECT COUNT(*) FROM $shkm->mod_table WHERE ".$fcount);
    $total_orders = mysql_result($count_query, 0);
	
	
	$count_query = mysql_query("SELECT COUNT(*) FROM $mod_table");
    $total_batches = mysql_result($count_query, 0);
    
    //top buttons
    echo '
      <div style="width:100%;">
        <div style="width:200px;float:left;">
            <ul class="actionButtons">
				<li><a href="'.$mod_page.'"><img src="'.SHOPKEEPER_PATH.'style/default/img/refresh.png" alt="">&nbsp; '.$langTxt['refresh'].'</a></li>
			</ul> 
        </div>
        <div align="right">
    ';
/*
    $top_nav_plugin = '';
    $evtOut = $modx->invokeEvent('OnSHKmodRenderTopLinks');
    if (is_array($evtOut)) $top_nav_plugin = implode('', $evtOut);
    unset($evtOut);

    echo $shkm->renderButtons($mod_links,$top_nav_plugin);
*/
    echo '
        </div>
      </div>
    ';

    echo '<div style="clear:both"></div>
	';
    if($total_orders>0){
        //pagination
        $page = isset($_GET['pageo']) && $total_orders!=$conf_perpage ? $_GET['pageo'] : 1;
        $pnum = $conf_perpage;
        $start = (($page-1)*$pnum+1)-1;

        $pg = new pagination;
        $pg->Items($total_orders);
        $pg->limit($pnum);
        $pg->target($mod_page);
        $pg->currentPage($page);
		$pg->parameterName("pageo");
        $pg->nextT = ' <a href="[+link+]">'.$langTxt['next'].'</a> ';
        $pg->prevT = ' <a href="[+link+]">'.$langTxt['prev'].'</a> ';
        $pager = $pg->getOutput();
        //Orders data
        $data_query = $modx->db->select("id,content, short_txt, price, delivery_price, currency, note, status, tracking_num, userid, DATE_FORMAT(date,'%d.%m.%Y %k:%i') AS date, DATE_FORMAT(sentdate,'%d.%m.%Y %k:%i') AS sentdate_info,nalozh,payment,status_info,is_zakazn,weight,email", $shkm->mod_table, $fcount, $sort_list, "$start,$pnum");

        //Get user data
        $userData_query = $modx->db->select("DISTINCT wu.id, wu.username", $dbprefix."web_users wu, $shkm->mod_table shk", "wu.id = shk.userid", "", "");
        $users_id_list = "0";
        while ($userData = mysql_fetch_row($userData_query)){
          $userName[$userData[0]] = $userData[1];
          $users_id_list .= ",".$userData[0];
        }
        //Number of customers orders
        $users_all_purchase = array();
        $user_purchase_query = $modx->db->select("webuser, setting_value", $shkm->mod_user_table, "setting_name = 'count_purchase' AND webuser in($users_id_list)", "", "");
        while ($user_purchase = mysql_fetch_row($user_purchase_query)){
          $users_all_purchase[$user_purchase[0]] = strpos($user_purchase[1],'/')!== false ? explode('/',$user_purchase[1]) : array($user_purchase[1],0);
        }

    }
    if($total_batches>0){
        //pagination
        $page_batches = isset($_GET['pageb']) && $total_batches!=$conf_perpage ? $_GET['pageb'] : 1;
        $pnum = $conf_perpage;
        $start = (($page_batches-1)*$pnum+1)-1;

        $pg_b = new pagination;
        $pg_b->Items($total_batches);
        $pg_b->limit($pnum);
		$target=$mod_page;
		if(!isset($_GET["tab"])){
			$target=$mod_page."&tab=2";
		}
        $pg_b->target($target);
        $pg_b->currentPage($page_batches);
		$pg_b->parameterName("pageb");
        $pg_b->nextT = ' <a href="[+link+]">'.$langTxt['next'].'</a> ';
        $pg_b->prevT = ' <a href="[+link+]">'.$langTxt['prev'].'</a> ';
        $pager_batches = $pg_b->getOutput();
        //Batches data
        $data_query_batches = $modx->db->select("*", $mod_table, "", "ID DESC", "$start,$pnum");
    }
if(!empty($error_text))
	echo $error_text;
?>


<div class="dynamic-tab-pane-control" id="tabs">

<div class="tab-row">
  <h2 class="tab <?if($_GET["tab"]!=2):?>selected<?endif;?>"><span>Заказы</span></h2>
  <h2 class="tab <?if($_GET["tab"]==2):?>selected<?endif;?>"><span>Партии</span></h2>
</div>

<!-- \\\tab content 1\\\ -->
<div class="tab-page" <?if($_GET["tab"]==2):?>style="display:none;"<?endif;?>>
	<div style="padding:20px;">
        <ul class="actionButtons">
				<li><a href="javascript://" onclick="postFormCreate()";>Создать партию</a> <input type="date" id="sdate" value="<?=date("Y-m-d",time()+24*60*60);?>"/></li>
        </ul>   
        <br/>
        <br/>
		<?include "templates/orderpage.tpl.php";?>
	</div>
</div>
<!-- ///tab content 1/// -->

<!-- \\\tab content 2\\\ -->
<div class="tab-page" <?if($_GET["tab"]!=2):?>style="display:none;"<?endif;?>>
	<div style="padding:20px;">
		<?include "templates/batchpage.tpl.php";?>
	</div>
</div>
<!-- ///tab content 2/// -->
</div>