<?php if($total_batches>0 && isset($data_query_batches)): ?>

<br />
<div class="pages"><?php echo $pager_batches; ?></div>

<table id="ordersTable" class="order-table" width="99%">
  <col width="3%" />

  <col width="3%" />
  <col width="10%" />
  <col width="10%" />
  <col width="15%" />
  <col width="15%" />
  <col width="3%" />
  <thead>
  <tr>
    <th><input type="checkbox" value=0 onchange="checkAll(this);"></th>

    <th>ID</th>
    <th>Партия</th>
    <th>Дата<br />отправки</th>
    <th>Статус</th>
    <th style="padding:2px 40px;"></th>
	<th></th>
  </tr>
  </thead>
  <tfoot>
  <tr>
    <th colspan="7"></th>
    </tr>
  </tfoot>
  <tbody>

<?php
    $num = 1;
    while ($data = mysql_fetch_array($data_query_batches)):
	$type_text="";
	$types=explode(',',$data["type"]);
	if(!empty($types)){
		foreach($types as $i => $type)
			switch($type){
				case 20:
					$type_text.=",Заказные посылки";
				break;
				case 21:
					$type_text.=",Заказные бандероли";
				break;
				case 4:
					$type_text.=",Обыкновенные";
				break;
				case 5:
					$type_text.=",Наложки";
				break;
				case 6:
					$type_text.=",EMS";
				break;
				default:
			}
		$type_text='('.substr($type_text,1).')';
	}
?>
 
  <tr id="batch<?php echo $data["id"]; ?>">
    <td align="center"><input type="checkbox" class="checkit" name="check[]" value="<?php echo $data["id"]; ?>" /></td>
    <td align="center"><b><?php echo $data['id']; ?></b></td>
    <td style="text-align: center;">
      <?=$data["name"];?> <?=$type_text;?>
	  <br/>
	  <a class="iframe" href="<?php echo $mod_page; ?>&action=get_BO&batchName=<?php echo $data["name"]; ?>"><?php echo $langTxt['description']; ?></a><br/>
    </td>
    <td style="text-align: center;">
	<?
	switch($data["status"]){
		case 'CREATED':
			echo '<input type="date" id="date" name="'.$data["name"].'" class="changeBD" value="'.$data["date"].'"/>';
		break;
		case 'CHEKIN':
		case 'ARHIVE':
			echo $data["date"];
		break;
	}
	?>
      
    </td>
    <td id="status_info<?=$data["id"];?>" style="font-size:14px;">
	<?=$data["status"];?>
	</td>
    <td align="center">
	<?
	switch($data["status"]){
		case 'CREATED':
			echo '<a href="'.$mod_page.'&action=send&tab=2&batchName='.$data["name"].'">Отправить партию</a>';
		break;
		case 'CHEKIN':
			echo '<a href="'.$mod_page.'&action=zip&tab=2&batchName='.$data["name"].'">Получить документы партии</a>';
			echo '<br/>';
			echo '<a href="'.$mod_page.'&action=archive&tab=2&batchName='.$data["name"].'">Перевод партии в архив</a>';
		break;
		case 'ARHIVE':
		break;
	}
	?>
	</td>
	<td>
	<?if($data["status"]=="CREATED"):?>
	<a href="<?=$mod_page.'&action=delete_B&tab=2&batchName='.$data["name"];?>" onclick="if(confirm('Вы уверены?'))return true; else return false;"><img src="../assets/snippets/shopkeeper/style/default/img/m_delete.gif" align="absmiddle"></a>
	<?endif;?>
	</td>
 </tr>
  

<?php $num++; endwhile; ?>


</tbody>
</table>

<div class="pages"><?php echo $pager_batches; ?></div>

<?php else: ?>

<div style="clear:both; text-align:center; line-height:70px;"><i>Нет партий</i></div>

<?php endif;?>

<br />