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

    $num = 1;
    while ($data = mysql_fetch_array($data_query)):
      $pos = $total_orders-($total_orders-($start+$num));
      $user_id = $data['userid'];
?>
 
  <tr id="zak<?php echo $data["id"]; ?>">
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
    </td>
    <td style="text-align: center;">
      <?php if(!empty($data['delivery_price'])): ?>
        <b><?php echo $shkm->numberFormat($data['delivery_price']); ?></b> <?php echo $data['currency']; ?>
      <?php endif; ?>
    </td>
    <td>
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