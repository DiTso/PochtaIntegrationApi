<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"  lang="en" xml:lang="en">
<head>
  <link rel="stylesheet" type="text/css" href="media/style/<?php echo $theme; ?>/style.css" />
  <link rel="stylesheet" type="text/css" href="<?php echo SHOPKEEPER_PATH; ?>module/js/colorpicker/farbtastic.css" />
  <link rel="stylesheet" type="text/css" href="<?php echo SHOPKEEPER_PATH; ?>module/js/colorbox/colorbox.css" />
  <style type="text/css">
    .but-link {padding:2px 0 2px 20px; background-repeat:no-repeat; background-position:left top;}
    .order-table {border-collapse:collapse;}
    .order-table th, .order-table td {padding:2px 5px; border:1px solid #888;}
    .order-table th {background-color:#E4E4E4;}
    .order-table th select, .order-table th input {font-weight:normal;}
    .pages {padding:5px 0;}
    table input {margin:2px 0;}
    li {margin:10px 0 0 0;}
    th.header {background-image: url(<?php echo SHOPKEEPER_PATH; ?>style/default/img/sort.gif); cursor: pointer; font-weight: bold; background-repeat: no-repeat; background-position: center left; padding-left: 15px;}
    th.headerSortUp {background-image: url(<?php echo SHOPKEEPER_PATH; ?>style/default/img/asc.gif); background-color: #D0D0D0;}
    th.headerSortDown {background-image: url(<?php echo SHOPKEEPER_PATH; ?>style/default/img/desc.gif); background-color: #D0D0D0;}
    .colorwell {border: 2px solid #fff; width: 75px; text-align: center; cursor: pointer;}
    
a.button15 {
  display: inline-block;
  font-family: arial,sans-serif;
  font-size: 11px;
  font-weight: bold;
  color: rgb(68,68,68);
  text-decoration: none;
  user-select: none;
  padding: .2em 1.2em;
  outline: none;
  border: 1px solid rgba(0,0,0,.1);
  border-radius: 2px;
  background: rgb(245,245,245) linear-gradient(#f4f4f4, #f1f1f1);
  transition: all .218s ease 0s;
}
a.button15:hover {
  color: rgb(24,24,24);
  border: 1px solid rgb(198,198,198);
  background: #f7f7f7 linear-gradient(#f7f7f7, #f1f1f1);
  box-shadow: 0 1px 2px rgba(0,0,0,.1);
}
a.button15:active {
  color: rgb(51,51,51);
  border: 1px solid rgb(204,204,204);
  background: rgb(238,238,238) linear-gradient(rgb(238,238,238), rgb(224,224,224));
  box-shadow: 0 1px 2px rgba(0,0,0,.1) inset;
}
.maillinks, .maillinks li{
	list-style:none;
	margin:2px 0px;
	font-size:11px;
	text-align:left;
}
  </style>
 
  <script src="//code.jquery.com/jquery-1.10.2.js"></script>
  <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
  <script src="<?php echo SHOPKEEPER_PATH; ?>module/js/jquery.tablesorter.min.js" type="text/javascript"></script>
  <script src="<?php echo SHOPKEEPER_PATH; ?>module/js/colorpicker/farbtastic.js" type="text/javascript"></script>
  <script src="<?php echo SHOPKEEPER_PATH; ?>module/js/colorbox/jquery.colorbox-min.js" type="text/javascript"></script>
  
  <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
  
  <script type="text/javascript">
  var src_action="<?=$mod_page;?>";
  var colorBoxOpt = {iframe:true, innerWidth:700, innerHeight:400, opacity:0.5};
  $.fn.tabs = function(){
    var parent = $(this);
    var tabNav = $('div.tab-row',this);
    var tabContent = $('div.tab-page',this);
    $('h2.tab',tabNav).each(function(i){
      $(this).click(function(){
        $('h2.tab',tabNav).removeClass('selected');
        $('h2.tab',tabNav).eq(i).addClass('selected');
        tabContent.hide();
        tabContent.eq(i).show();
        return false;
      });
    });
  }

	$.fn.shifty = function(o){
		var o = $.extend({
			className:'shifty-select', // название класса по умолчанию
			select:function(){},  // функция при выделении
			unselect:function(){} // функция при снятии выделения
		}, o);
		elems = $(this); // получаем элементы
		last = null;
		var className = o.className; // и название класса
		return $(this).each(function(){
        	    var block = $(this); // работаем с отдельным элементом
          	    $(document).keydown(function(e){ // при нажатии клавиши
        		    if (!e.ctrlKey && !e.shiftKey) return;  // если клавиша не shift и не ctrl выходим из функции
                            this.onselectstart = function(){return false}; // запрет выделения для IE
	        	    block.unbind('click').css({'-moz-user-select':'none','-webkit-user-select':'none','user-select':'none'}); // и для всех остальных браузеров
	        	   
			    if (e.shiftKey) { // если нажата клавиша shift
		                block.click(function(){
		        	    first = elems.index(block); // находим элемент, с которого начнётся выделение
		        	    if (first < last) { // выделяем последующие элементы в зависимости от направления
		        		    elems.filter(':gt(' + (first - 1) + ')').addClass(className);
		        		    elems.filter(':lt(' + first + '),:gt(' + last + ')').removeClass(className);
		        	    } else {
			        	    elems.filter(':gt(' + last + ')').addClass(className);
			        	    elems.filter(':lt(' + last + '),:gt(' + first + ')').removeClass(className);
		        	    }
		        	    //o.unselect(elems);  // снимаем выделение пользовательской функцией
		        	    o.select(elems.filter('.' + className)); // добавляем пользовательскую функцию для элемента
				});
			    }
        	    });
        	    $(document).keyup(function(e){  // когда клавиша отпущена
        	        this.onselectstart = function(){}; // снимаем запрет выделения с IE
        	        block.unbind('click').click(blockClick).css({'-moz-user-select':'','-webkit-user-select':'','user-select':''}); // и с остальных браузеров
        	    });
        	    block.click(blockClick); // устанавливаем обработчик клика
		});
		function blockClick(){ // обработчик простого клика
		    elems.removeClass(className); // снимаем выделение со всех элементов
		    $(this).addClass(className); // добавляем выделение к текущему элементу
		    //o.unselect(elems); // то же самое с пользовательской функцией
		    o.select($(this));
	            last = elems.index($(this));
		}
	};
  $(document).bind('ready',function(){
      $("#ordersTable").tablesorter({sortList: [[1,1]], headers: {0:{sorter: false}, 9:{sorter: false}}});
      $("a.iframe").colorbox(colorBoxOpt);
      $("a.ajax").colorbox({innerWidth:700, innerHeight:400});
      $("#tabs").tabs();
      setTimeout(function(){
        $('#notifyBlock').slideUp(700);
      },5000);
      
	$('.checkit').shifty({
		className:'select', // класс выделенного элемента
		select:function(el){if(el.prop('checked')) el.prop('checked','checked'); else el.removeAttr('checked');}, // выделение
		unselect:function(el){el.removeAttr('checked');} // снятие выделения
	});
    } 
  );
  var tree = false;
  
   function postFormCreate(){
                Order_ids="";
  	            $('input.checkit:checked').each(function(ind,el){
                    Order_ids+=","+$(el).val();
  	            });
  	            
  	            if(!Order_ids){ alert('Не выбрано ни одного заказа'); return false;}
				if($("#sdate").val()!=undefined && $("#sdate").val()!="")
					document.batchCreate.date.value=$("#sdate").val()
				 document.batchCreate.ids.value=Order_ids.substr(1);
				 document.batchCreate.submit();
				return false;
  }
  
  
  
  function checkAll(elem){
    if(elem.checked==true){
      $("input.checkit").prop("checked","checked");
    }else{
      $("input.checkit").removeAttr("checked");
    }
  }
 $(document).ready(function(){
     
    $('.butOK').removeAttr('disabled');
    $('.stat').removeAttr('disabled');
	$('.dis_stat').attr('disabled','disabled');
	
    $('.changeBD').change(function(){
		var newdate=$(this).val();
		var batchName=$(this).attr("name");
		var changeelement=$(this);
        $.ajax({
								url : src_action,
								type: "GET",
								data : {
									action : "",
									batchName : batchName,
									date : newdate
								},
								success : function(data) {
								   changeelement.css('box-shadow','0px 0px 0px 2px #25A93E');
								}
		});
    });
     
 });
  </script>
</head>
<body>

<br />
<div class="sectionHeader">Интернет магазин Strunki.ru <?php //if($action=='catalog'){echo $langTxt['catalog_mod'];}else{echo $langTxt['modTitle'];} ?></div>

<div class="sectionBody" style="min-height:250px;">