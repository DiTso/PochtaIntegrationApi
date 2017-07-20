<?php if($total_orders>0 && isset($data_query)): ?>

<br />
<div class="pages"><?php echo $pager; ?></div>

<table id="ordersTable" class="order-table" width="99%">
  <col width="3%" />

  <col width="3%" />
  <col width="23%" />
  <col width="5%" />
  <col width="5%" />
  <col width="15%" />
  <col width="16%" />
  <col width="3%" />
  <thead>
  <tr>
    <th><input type="checkbox" value=0 onchange="checkAll(this);"></th>

    <th>ID</th>
    <th>Контакт инфо</th>
    <th>Общая<br />стоимость</th>
    <th>Стоимость<br />доставки</th>
    <th><?php echo $langTxt['note']; ?></th>
    <th style="padding:2px 40px;"><?php echo $langTxt['dateTime']; ?></th>
    <th><?php echo $langTxt['user']; ?></th>
  </tr>
  </thead>
  <tfoot>
  <tr>
    <th colspan="10"></th>
    </tr>
  </tfoot>
  <tbody>

<?php
/*
 <th><input type="checkbox" name="check_all" value="" onclick="checkAll(this)" /></th>
   
<th>
    <select onchange="if(confirm('<?php echo $langTxt['confirm'] ?>')){postForm('status_all',null,this.value)}else{this.value='0'};">
      <option value="0"></option>
      <!--<option value="1"><?php echo $langTxt['phase1']; ?></option>-->
      <option value="2"><?php echo $langTxt['phase2']; ?></option>
      <option value="6"><?php echo $langTxt['phase6']; ?></option>
      <option value="3"><?php echo $langTxt['phase3']; ?></option>
      <option value="4"><?php echo $langTxt['phase4']; ?></option>
      <!--<option value="5"><?php echo $langTxt['phase5']; ?></option>-->
      
   </select>
   </th>
    <th></th>
    <th><a href="#" title="<?php echo $langTxt['deleteChecked']; ?>" onclick="if(confirm('<?php echo $langTxt['confirm']; ?>')){postForm('delgroup',null,null)};return false"><img src="<?php echo SHOPKEEPER_PATH; ?>style/default/img/m_delete.gif" align="absmiddle" /></a></th>
  */
    $num = 1;
    while ($data = mysql_fetch_array($data_query)):
      $pos = $total_orders-($total_orders-($start+$num));
      $user_id = $data['userid'];
?>
 
  <tr id="zak<?php echo $data["id"]; ?>" style="background-color:<?php
     switch($data['status']){
     case 8: $phColor=6; break;
       
     case 12: $phColor=7; break;
      case 13: $phColor=8; break;
      case 14: $phColor=9; break;
      case 15: $phColor=10; break;
      case 16: $phColor=11; break;
      case 17: $phColor=12; break;
     default:
       $phColor=$data['status']-1;
     };
      echo $phaseColor[$phColor]; ?>">
    <td align="center"><input type="checkbox" class="checkit" name="check[]" value="<?php echo $data["id"]; ?>" /></td>
    <!--<td align="center"><small><?php echo $pos; ?></small></td>-->
    <td align="center"><b><?php echo $data['id']; ?></b></td>
    <td>
      <?php echo $data["short_txt"]; ?>
    </td>
    <td style="text-align: center;">
      <?php if(!empty($data['price'])): ?>
        <b><?php echo $shkm->numberFormat($data['price']); ?></b> <?php echo $data['currency']; ?>
      <?php endif; ?>
      <?php if($data["payment"]=="Наложенный платеж"){
            $all_cost=$data['price']+$data['delivery_price'];
            /*OLD
			if($all_cost<1000){
        	  $dop_nal=round(25+0.05*$all_cost);
        	}else if($all_cost<5000){
        	  $dop_nal=round(75+0.04*$all_cost);
        	}else if($all_cos<20000){
        	  $dop_nal=round(235+0.02*$all_cost);
        	}else{
        	  $dop_nal=round(0.04*$all_cost);
        	}*/
			$dop_nal=$all_cost*2/100;
			if($dop_nal<50) $dop_nal=50;
        	echo "<br/>+<br/><b>".$dop_nal."</b> ".$data['currency'];
      }
      ?>
       <?php if($data["nalozh"]==1) echo '<br/><b style="color:#be3d3c;">Оплачен</b>'; ?>
    </td>
    <td style="text-align: center;">
      <?php if(!empty($data['delivery_price'])): ?>
        <b><?php echo $shkm->numberFormat($data['delivery_price']); ?></b> <?php echo $data['currency']; ?>
      <?php endif; ?>
	   <?if($max_add_delivery!=0){?>
			<br/>+<br/><b>Надбавка: <?php echo $max_add_delivery; ?> руб.</b>
			<? echo $add_delivery_info;?>
		<?}?>
    </td>
    <td>
      <textarea onblur="postForm('note',<?php echo $data["id"]; ?>,<?php echo $data['status'];?>,'<? echo $data['tracking_num']; ?>',$(this).parent().find('textarea').val());" name="note" cols="40" rows="5"  style="height:70px;width:160px;"><?php echo $data["note"]; ?></textarea><br/>
      
      <!--
      <?php if($data["note"]): ?><br /><?php endif; ?>
      <?php if(isset($users_all_purchase[$user_id])): ?>
        <?php echo $langTxt['count_purchase']; ?>: <?php echo $users_all_purchase[$user_id][0]; ?> <?php echo $langTxt['times']; ?>
        <br />
        <?php echo $langTxt['sumTotal']; ?>: <?php echo $users_all_purchase[$user_id][1]; ?> <?php echo $conf_currency; ?>
      <?php endif; ?>
      -->
      <!--<input type="button" value="ОК" onclick="postForm('note',<?php echo $data["id"]; ?>,<?php echo $data['status'];?>,'<? echo $data['tracking_num']; ?>',$(this).parent().find('textarea').val());" />-->
    
<br/>
	<select  id="rad<?=$data["id"];?>" name="change_type<?=$data["id"];?>"  onchange="postForm('change_type',<?php echo $data["id"]; ?>,this.value,'<? echo $data['tracking_num']; ?>');" style="width: 150px;">
		<option value="-1"/>Не выбрана</option>  
		<option value="4"  <?if($data["is_zakazn"]==4) echo "selected";?>/>Обыкновенная посылка</option>  
		<option value="2"  <?if($data["is_zakazn"]==2) echo "selected";?>/>Заказная</option>  
		<option value="3"  <?if($data["is_zakazn"]==3) echo "selected";?>/>Заказная бандероль 1-го класса</option>  
		<!--<option value="1"  <?if($data["is_zakazn"]==1) echo "selected";?>/>Ценная</option>  -->
		<option value="5"  <?if($data["is_zakazn"]==5) echo "selected";?>/>Посылка с наложенным платежом</option>  
	</select>
	<br/>
	Вес: <input name="change_weight<?=$data["id"];?>" id="change_weight<?=$data["id"];?>" type="text" value="<?=$data["weight"];?>"  onchange="postForm('change_weight',<?php echo $data["id"]; ?>,this.value,'');"/>
	<br/>
     
    </td>
    <td id="status_info<?=$data["id"];?>" style="font-size:9px;"><i style="display:none;"><?php if(isset($data["sentdate_info"])) echo strtotime($data["sentdate_info"]); else echo strtotime($data["date"]); ?></i><?php echo $data["date"]; ?> <?php if(isset($data["sentdate_info"])) echo " - ".$data["sentdate_info"]; ?><br/><hr/><?=$data['status_info'];?></td>
    <td align="center">
    
    <?php if(isset($data['userid']) && isset($userName[$user_id])): ?>
      
        <a class="iframe" href="index.php?a=88&id=<?php echo $user_id; ?>" title="<?php echo $langTxt["userData"]; ?>"><?php echo $userName[$user_id]; ?></a>
        
    <?php else:?>
        
        <span title="<?php echo $langTxt["unregistered"]; ?>">&mdash;</span>
        
    <?php endif; ?>
	</td>
 </tr>
  

<?php $num++; endwhile; ?>


</tbody>
</table>

<div class="pages"><?php echo $pager; ?></div>

<?php else: ?>

<div style="clear:both; text-align:center; line-height:70px;"><i><?php echo $langTxt['noOrders']; ?></i></div>

<?php endif;?>

<br />

<form action="<?=$mod_page;?>&action=create" name="batchCreate" method="post">
	<input name="ids" type="hidden" value="" />
	<input name="date" type="hidden" value="" />
	<input name="item_type" type="hidden" value="<?=$_GET["item_type"];?>" />
</form>