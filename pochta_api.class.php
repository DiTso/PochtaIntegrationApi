<?
class Batch{
	var $name;
	var $date;
	var $type;
	var $status;
	var $mod_table = "modx_pochta_batch";
	
	function __construct(&$modx,$name){
		$this->modx = $modx;
		if(!empty($name))
			$this->name=$name;
	}
	/*private function getField($field){
		if(!isset($this->$field)) return null;
		return $this->$field;
	}*/
	public function fillData($data_array,$type){
		$this->name=$data_array["batch-name"];
		$this->date=$data_array["list-number-date"];
		$this->status=$data_array["batch-status"];
		$this->type=$type;	
	}
	public function save(){
		$this->modx->db->query("INSERT INTO $this->mod_table (`name`, `date`, `type`, `status`) VALUES ('".$this->name."', '".$this->date."', '".$this->type."', '".$this->status."')");
	}
	
	public function update($field=""){
		if(!isset($field) || empty($field))
			$this->modx->db->query("UPDATE $this->mod_table SET `date`='".$this->date."', `status`='".$this->status."' WHERE `name`=".$this->name);
		else
			$this->modx->db->query("UPDATE $this->mod_table SET `".$field."`='".$this->$field."' WHERE `name`=".$this->name);
	}
	public function deleteDB($name=""){
		if(isset($name) && !empty($name))
			$this->name=$name;
		$this->modx->db->query("DELETE FROM $this->mod_table WHERE `name`=".$this->name);
	}
}

class connectorToPochta{
	static $token = '*****';
	static $key = '****';
	static $apiurl = 'https://otpravka-api.pochta.ru/1.0/';
	static $ADDR_qualityCodes = array('GOOD','POSTAL_BOX','ON_DEMAND','UNDEF_05');
	static $ADDR_qualityCodesInfo = array(
		'GOOD'=>'Пригоден для почтовой рассылки',
		'ON_DEMAND'=>'До востребования',
		'POSTAL_BOX'=>'Абонентский ящик',
		'UNDEF_01'=>'Не определен регион',
		'UNDEF_02'=>'Не определен город или населенный пункт',
		'UNDEF_03'=>'Не определена улица',
		'UNDEF_04'=>'Не определен номер дома',
		'UNDEF_05'=>'Не определена квартира/офис',
		'UNDEF_06'=>'Не определен',
		'UNDEF_07'=>'Иностранный адрес');
	static $ADDR_validationCodes = array('VALIDATED','OVERRIDDEN','CONFIRMED_MANUALLY');
	static $ADDR_validationCodesInfo = array(
		'CONFIRMED_MANUALLY'=>'Подтверждено контролером',
		'VALIDATED'=>'Уверенное распознавание',
		'OVERRIDDEN'=>'Распознан: адрес был перезаписал в стпавочнике',
		'NOT_VALIDATED_HAS_UNPARSED_PARTS'=>'На проверку, неразобранные части',
		'NOT_VALIDATED_HAS_ASSUMPTION'=>'На проверку, предположение',
		'NOT_VALIDATED_HAS_NO_MAIN_POINTS'=>'На проверку, нет основных частей',
		'NOT_VALIDATED_HAS_NUMBER_STREET_ASSUMPTION'=>'На проверку, предположение по улице',
		'NOT_VALIDATED_HAS_NO_KLADR_RECORD'=>'На проверку, нет в КЛАДР',
		'NOT_VALIDATED_HOUSE_WITHOUT_STREET_OR_NP'=>'На проверку, нет улицы или населенного пункта',
		'NOT_VALIDATED_HOUSE_EXTENSION_WITHOUT_HOUSE'=>'На проверку, нет дома',
		'NOT_VALIDATED_HAS_AMBI'=>'На проверку, неоднозначность',
		'NOT_VALIDATED_EXCEDED_HOUSE_NUMBER'=>'На проверку, большой номер дома',
		'NOT_VALIDATED_INCORRECT_HOUSE'=>'На проверку, некорректный дом',
		'NOT_VALIDATED_INCORRECT_HOUSE_EXTENSION'=>'На проверку, некорректное расширение дома',
		'NOT_VALIDATED_FOREIGN'=>'Иностранный адрес',
		'NOT_VALIDATED_DICTIONARY'=>'На проверку, не по справочнику');
	static $FIO_qualityCodes = array('CONFIRMED_MANUALLY','EDITED','NOT_SURE');
	static $batchName = '';
	
	static function cleanAddress($data){
		$res_addr=self::getSslPage('clean/address',$data);
		$res_good_addr=array();
		$res_bad_addr=array();
		foreach($res_addr as $i => $addr){
			if(in_array($addr["quality-code"],self::$ADDR_qualityCodes) && in_array($addr["validation-code"],self::$ADDR_validationCodes)){
				$res_good_addr[$addr["id"]]=$res_addr[$i];
			}
			else{
				$res_bad_addr[$addr["id"]]=array(0=>self::$ADDR_qualityCodesInfo[$addr["quality-code"]],1=>self::$ADDR_validationCodesInfo[$addr["validation-code"]]);
			}
		}
		return array(0=>$res_good_addr,1=>$res_bad_addr);
	}
	static function cleanFio($data){
		$res_fio=self::getSslPage('clean/physical',$data);
		$res_good_fio=array();
		$res_bad_fio=array();
		foreach($res_fio as $i => $fio){
			if(in_array($fio["quality-code"],self::$FIO_qualityCodes)){
				$res_good_fio[$fio["id"]]=$res_fio[$i];
			}
			else{
				$res_bad_fio[$fio["id"]]=$res_fio[$i];
			}
		}
		return array(0=>$res_good_fio,1=>$res_bad_fio);
	}
	static function createBacklog($orders,$orders_addresses,$orders_fio){
		$data=array();
		$types=array();
		foreach($orders as $i => $order){
			if(!isset($orders_addresses[$i]) || !isset($orders_fio[$i])) continue;
			$adres_array=array("area","building","corpus","hotel","house","index","letter","location","num-address-type","place","region","room","slash","street");
			foreach($adres_array as $ii => $vi){
				if(!isset($orders_addresses[$i][$vi])) $orders_addresses[$i][$vi]="";
			}
			if(!isset($orders_addresses[$i]["address-type"])) $orders_addresses[$i]["address-type"]="DEFAULT";
			
			$allcost=$order['price']+$order['delivery_price'];
			$payment=($order["payment"]=="Наложенный платеж")? $allcost*100:0;//сумма наложенного платежа
			$category_mail=($order["payment"]=="Наложенный платеж")?"WITH_DECLARED_VALUE_AND_CASH_ON_DELIVERY":"ORDINARY";// наложка или нет
			$type_mail="POSTAL_PARCEL";
			$indexfrom=443099;
			if($order["is_zakazn"]==2){
				$category_mail="ORDERED";
				if(intval($order["weight"])>=100)
					$type_mail="BANDEROL";
				else if(intval($order["weight"])<100)
					$type_mail="LETTER";
			}else if($order["is_zakazn"]==6){
				$indexfrom=443880;
				$category_mail="ORDINARY";
				$type_mail="EMS";
			}
			$types[$i]=array($type_mail,$category_mail);
			$insr_value=$payment;//сумма объявленной ценности
			$weight=intval($order["weight"]);//вес
			$country=643;
			$data[]=array(
				"address-type-to"=> $orders_addresses[$i]["address-type"],
				"area-to"=> $orders_addresses[$i]["area"],
				"brand-name"=> "NO NAME",
				"building-to"=> $orders_addresses[$i]["building"],
				"corpus-to"=> $orders_addresses[$i]["corpus"],
				"fragile"=> false,
				"given-name"=> $orders_fio[$i]["name"],
				"hotel-to"=> $orders_addresses[$i]["hotel"],
				"house-to"=> $orders_addresses[$i]["house"],
				"index-to"=> $orders_addresses[$i]["index"],
				"insr-value"=> $insr_value,
				"letter-to"=> $orders_addresses[$i]["letter"],
				"location-to"=> $orders_addresses[$i]["location"],
				"mail-category"=> $category_mail,
				"mail-direct"=> $country,
				"mail-type"=> $type_mail,
				"mass"=> $weight,
				"middle-name"=> $orders_fio[$i]["middle-name"],
				"num-address-type-to" => $orders_addresses[$i]["num-address-type"],
				"order-num"=> $i,//номер заказа
				"payment"=> $payment,
				"place-to"=> $orders_addresses[$i]["place"],
				"postoffice-code"=> $indexfrom,
				"region-to"=> $orders_addresses[$i]["region"],
				"room-to"=> $orders_addresses[$i]["room"],
				"slash-to"=> $orders_addresses[$i]["slash"],
				"street-to"=> $orders_addresses[$i]["street"],
				"surname"=> $orders_fio[$i]["surname"],
				"sms-notice-recipient"=> 0
			);
		}
		$res_send=self::getSslPage('user/backlog',$data,"PUT");
		$res_send["types"]=$types;
		//Добавить проверку на ошибки и ошибочные id;
		return $res_send;
	}
	static function getOrder($orderId){
		$res_order=self::getSslPage('backlog/'.$orderId,'',"GET");
		return $res_order;
	}
	static function createBatch($ordersIds,$date=null){
		if(isset($date) && !empty($date))
			$date="?sending-date=".date("Y-m-d",strtotime($date));
		else $date="";
		$res_batch=self::getSslPage('user/shipment'.$date,$ordersIds);
		//Добавить проверку на ошибки и ошибочные id;
		return $res_batch;
	}
	static function getBatchZipDocs($batchName){
		if(empty($batchName)) $batchName=self::$batchName;
		$res_batch_docs=self::getSslPage('forms/'.$batchName.'/zip-all','',"GET",false);
		$file_name = dirname(__FILE__).'/'.$batchName.'.zip';
		file_put_contents($file_name, $res_batch_docs);
		return $file_name;
	}
	static function getBatch($batchName){
		if(empty($batchName)) $batchName=self::$batchName;
		if(empty($batchName)) return;
		$res_batch=self::getSslPage('batch/'.$batchName,'',"GET");
		return $res_batch;
	}
	static function changeBatchDate($batchName,$date){
		$date=date("Y-m-d",strtotime($date));
		$date=explode('-',$date);
		$res_batch=self::getSslPage('batch/'.$batchName.'/sending/'.$date[0].'/'.$date[1].'/'.$date[2],'');
		return $res_batch;
	}
	static function sendBatch($batchName){
		$res_batch=self::getSslPage('batch/'.$batchName.'/checkin?sendEmail=true');
		return $res_batch;
	}
	static function archiveBatch($batchName){
		if(empty($batchName)) $batchName=self::$batchName;
		$res_batch_archive=self::getSslPage('archive',array($batchName),"PUT");
		return $res_batch_archive;
	}
	
	
	static function getBatchOrders($batchName){
		if(empty($batchName)) $batchName=self::$batchName;
		$res_batch=self::getSslPage('batch/'.$batchName.'/shipment','',"GET");
		$order_tracking=array();
		foreach($res_batch as $i => $order){
			$order_tracking[$order["order-num"]]=$order;
		}
		return $order_tracking;
	}
	static function deleteBatch($batchName){
		if(empty($batchName)) $batchName=self::$batchName;
		$res_batch=self::getSslPage('batch/'.$batchName.'/shipment','',"GET");
		$order_del=array();
		if($res_batch["code"]!=1001){
			foreach($res_batch as $i => $order){
				$order_del[]=$order["id"];
			}
		}
		$res_batch=self::getSslPage('/shipment',$order_del,"DELETE");
		return $res_batch;
	}
	static function getSslPage($url,$data='',$type="POST",$decode = true) {
		$data=json_encode($data);
		$ch = curl_init();
		$headers = array('Content-type: application/json;charset=UTF-8', 
							'Accept: application/json;charset=UTF-8', 
							'X-User-Authorization: Basic '.self::$key,
							'Authorization: AccessToken '.self::$token);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		//curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers );
		//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);                                                                     
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);    
		curl_setopt($ch, CURLOPT_URL, self::$apiurl.$url);
		curl_setopt($ch, CURLOPT_REFERER, '');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$result = curl_exec($ch);
		curl_close($ch);
		if($decode)
			$result=json_decode($result, true);
		return $result;
	}
}
?>