<?
#Скрипт для отбора товаров из инфоблока и копирования их свойств в другой инфоблок. Полезно при переезде.
$_SERVER['DOCUMENT_ROOT'] = '/home/bitrix/ext_www/backup.somebox.ru';
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$original_iblock = 8; //ИБ откуда копируем
$original_offers_iblock = 10;  //ИБ предложений откуда копируем
$remote_iblock=23; //ИБ откуда копируем
$remote_offers_iblock=26; //ИБ предложений куда копируем

//Массив со свойствами которые не копируем

function logger($message){
	echo $message;
	echo "\n";
	ob_flush();
    flush();
}

function copy_property($prop_id, $from_iblock_id, $to_iblock_id){
	$res = CIBlockProperty::GetByID($prop_id, $from_iblock_id);
	$prop_code_ignore=array(
		'IN_STOCK',
		'CONTENT_READY',
		'sales_include',
		'HIT',
		'DATA_OBNOVLENIYA',
		'OTBOR_SLUZHEBNOE_POLE',
		'STATUS_VNESHNIY',
		'SALES_NOTES',
		'SALES_NOTES_MSK',
		'SALES_NOTES_SPB',
		'SALES_NOTES_EKB',
		'SALES_NOTES_KRD',
		'SET_DISCOUNT',
		'SAMOVIVOZ_YM',
		'COPABILITY',
		'DAILY_PRODUCT',
		'FORUM_TOPIC_ID',
		'FORUM_MESSAGE_CNT',
		'Y_MARKET_CATEGORY',
		'Y_MARKET_TYPEPREFIX',
		'SEARCH_ID',
		'SAYT',
		'MERLION_CODE',
		'sync_id',
		'MERLION_BRAND',
		'MERLION_MIN_PACKAGED',
		'api_category',
		'DOKUMENTSBIS_STATUS',
		'TEST',
		'PRICE_DOST_MSK',
		'PRICE_DOST_SPB',
		'DOST_MSK',
		'DOST_SPB',
		'SBIS_SPOSOBDOSTAVKI',
		'vote_counts',
		'vote_sums',
		'ratings',
		'vote_count_2',
		'vote_sum_2',
		'rating',
		'vote_count',
		'vote_sum',
		'PHOTOS_FOR_VK_8',
		'USER_ID',
		'BLOG_COMMENTS_CNT',
		'BLOG_POST_ID',
		'MARKER',
		'SALE',
		'POPULAR',
		'NEW',
		'MARKER_PHOTO',
		'RATING_2',
		'VOTE_COUNT22',
		'VOTE_SUM22',
		'DELIVERY_DESC',
		'DELIVERY',
		'PICKUP',
		'AKZII',
		'SEO_DESCRIPTION',
		'SEO_TITLE',
		'NAME_ROD_PADEJ'
	);
	if($ar_res = $res->GetNext(false,false)){
		if (in_array($ar_res['CODE'], $prop_code_ignore)){
			logger('Свойство '.$ar_res['CODE']." в игнор листе. Пропускаем");
			return;
		}
		unset($ar_res["ID"]);
		unset($ar_res["~ID"]);
		$ar_newprop=$ar_res;

	  }
	if ($ar_newprop['PROPERTY_TYPE'] == 'L'){
		$prop_enum=CIBlockProperty::GetPropertyEnum($prop_id,array(),array());
		$i=0;
		while ($ar_prop=$prop_enum->GetNext()){
			unset($ar_prop["ID"]);
			unset($ar_prop["PROPERTY_ID"]);
			unset($ar_prop["TMP_ID"]);
			unset($ar_prop["TMP_ID"]);
			$ar_newprop["VALUES"][$i]= $ar_prop;
			$i++;
		}
	}
	foreach ($ar_newprop as $key => $value) {
		if ($value==Null){
			$ar_newprop[$key]='';
		}
	}
	$ar_newprop['IBLOCK_ID'] = $to_iblock_id;
	return $ar_newprop;

}

function property_list($cat_id, $iblock_id,$goods_table){
	$arProps=[];
	$arGoods=CIBlockElement::GetList(array('timestamp_x'=>'ASC'),array('ACTIVE'=>'Y', 'IBLOCK_ID'=>$iblock_id, 'SECTION_ID'=>$cat_id),false,false,array('ID','DETAIL_PAGE_URL','NAME','ACTIVE', 'TIMESTAMP_X','PROPERTY_ARTICLE'));
	$arGoods2 = $arGoods;
	// $arGoods2= CIBlockElement::GetList(array('timestamp_x'=>'ASC'),array('ACTIVE'=>'Y', 'IBLOCK_ID'=>$iblock_id, 'SECTION_ID'=>$cat_id),false,false,array('ID','DETAIL_PAGE_URL','NAME','ACTIVE', 'TIMESTAMP_X','PROPERTY_ARTICLE'));
	// $arGoods=CIBlockElement::GetList(array('timestamp_x'=>'ASC'),array('ACTIVE'=>'N', 'IBLOCK_ID'=>8),false,false,array('ID','DETAIL_PAGE_URL','NAME','ACTIVE', 'TIMESTAMP_X','PROPERTY_ARTICLE')); //фильтр для неактивных элементов
	if ($arGoods->SelectedRowsCount() == 0)
		return;
	while ($good=$arGoods->GetNext()){
		// logger($good['NAME']);
		$db_props = CIBlockElement::GetProperty($iblock_id, $good['ID'], array("sort" => "asc"), Array());
		while ($ar_props = $db_props->GetNext()){
			if ($ar_props['VALUE'] =='')
				continue;
			$arProps[$ar_props['ID']]=$ar_props;
		}
	}
	// print_r($arGoods2);
	$arFinished=[];
	// foreach ($arProps as $key => $value) {
	// }
	if ($goods_table){
		while ($good = $arGoods2->GetNext()){
			$db_props = CIBlockElement::GetProperty($iblock_id, $good['ID'], array("sort" => "asc"), Array());
			while ($ar_props = $db_props->GetNext()){
				if ($ar_props['VALUE'] =='')
					continue;
				$arProps2[$ar_props['ID']]=$ar_props;
			}		
			foreach ($arProps as $key => $value) {
				if ($value['PROPERTY_TYPE'] == 'L'){
					$val=$arProps2[$value['ID']]['VALUE_ENUM'];
				}
				else {
					$val=$arProps2[$value['ID']]['VALUE'];
				}

				$arFinished[$good['ID']][$value['NAME']]=$val;
				$arFinished[$good['ID']]['NAME']=$good['NAME'];
			}
		}
	}
	else{
		$arFinished=$arProps;
	}	
	return $arFinished;
}

function sections_list($iblock_id){
	$arResult=CIBlockElement::GetList(array('timestamp_x'=>'ASC'),array('ACTIVE'=>'Y', 'IBLOCK_ID'=>$iblock_id),false,false,array('ID','DETAIL_PAGE_URL','NAME','ACTIVE', 'TIMESTAMP_X','PROPERTY_ARTICLE','SECTION_ID'));
	$arSections=[];
	while($good=$arResult->GetNext()){
		// print_r($good);
		if (!in_array($good['IBLOCK_SECTION_ID'], $arSections)){
			array_push($arSections, $good['IBLOCK_SECTION_ID']);
		}
	}
	foreach ($arSections as $section) {
		$res=CIBlockSection::GetByID($section);
		if ($ar_res=$res->GetNext()){
			echo $ar_res['ID'].' - '.$ar_res['NAME'].'<br>';
		}
	}
	// echo "<pre>";
	// print_r ($arSections);
	// echo "</pre>";
}

function copy_good($id,$iblock_from, $iblock_to,$parent_id,$cat_id){
	$arResult = CIBlockElement::GetList(array(),array("ID"=>$id),false,false,array());
	if ($good = $arResult->Fetch()){
		// echo "<pre>";
		// print_r($good);
		// echo "</pre>";
		$good['OLD_ID']=$good['ID'];
		unset($good['ID'],$good['SEARCHABLE_CONTENT'],$good['WF_PARENT_ELEMENT_ID'],$good['SHOW_COUNTER'],$good['USER_NAME'],$good['CREATED_USER_NAME'],$good['LANG_DIR'],$good['LID'],$good['IBLOCK_TYPE_ID'],$good['IBLOCK_CODE'],$good['IBLOCK_NAME'],$good['IBLOCK_EXTERNAL_ID'],$good['DETAIL_PAGE_URL'],$good['LIST_PAGE_URL'],$good['CANONICAL_PAGE_URL'],$good['TIMESTAMP_X'],$good['TIMESTAMP_X_UNIX'],$good['DATE_CREATE'],$good['DATE_CREATE_UNIX'],$good['SHOW_COUNTER_START'],$good['SHOW_COUNTER_START_X'],$good['WF_STATUS_ID']);
		$good['IBLOCK_ID']=$iblock_to;
		if ($cat_id != False){
			$good['cat_code'] = $cat_id;
		}
		$good['PREVIEW_PICTURE'] = intval($good['PREVIEW_PICTURE']);
		if ($good['PREVIEW_PICTURE'] > 0) {
			// $good['PREVIEW_PICTURE'] = CFile::MakeFileArray($good['PREVIEW_PICTURE']);
			$good['PREVIEW_PICTURE']= 'http://backup.somebox.ru'.CFile::GetPath($good['PREVIEW_PICTURE']);
			if (empty($good['PREVIEW_PICTURE'])) {
				$good['PREVIEW_PICTURE'] = false;
			} else {
		         // $good['PREVIEW_PICTURE']['COPY_FILE'] = 'Y';
			}
		} else {
	      $good['PREVIEW_PICTURE'] = false;
	   }
	   $good['DETAIL_PICTURE'] = intval($good['DETAIL_PICTURE']);
	   if ($good['DETAIL_PICTURE'] > 0) {
	      // $good['DETAIL_PICTURE'] = CFile::MakeFileArray($good['DETAIL_PICTURE']);
	   		$good['DETAIL_PICTURE']= 'http://backup.somebox.ru'.CFile::GetPath($good['DETAIL_PICTURE']);
	      if (empty($good['DETAIL_PICTURE'])) {
	         $good['DETAIL_PICTURE'] = false;
	      }
	      else {
	         // $good['DETAIL_PICTURE']['COPY_FILE'] = 'Y';
	      }
	   }
	   else {
	      $good['DETAIL_PICTURE'] = false;
	   }
	   //загрузка свойств
	   $PROP=array();
	   $arProps = CIBlockElement::GetProperty($iblock_from,$id,array(),array());
	   while ($property = $arProps->GetNext()){	   	
	   	if ($property['PROPERTY_TYPE'] =='L'){
			//    if ($property['CODE']=='WIFI_ANTENNA_TYPE'){
			//    print_r($property);
			//    exit();}
			   $val=$property['VALUE_ENUM'];
	   	}
	   	elseif($property['PROPERTY_TYPE'] == 'F'){
	   		//Тут был функционал для копирования файла внутри портала
	   		// if (intval($property['VALUE'])>0){
	   		// 	$val=CFile::MakeFileArray($property['VALUE']);
	   		// 	if (empty($val)){
	   		// 		$val = false;
	   		// 	}
	   		// 	else{
	   		// 		$val['COPY_FILE'] = 'Y';
	   		// 	}	   			
	   		// }
	   		if (intval($property['VALUE'])>0){
	   			
	   			$val='http://backup.somebox.ru'.CFile::GetPath($property['VALUE']);
	   			if (empty($val)){
	   				$val = false;
	   			}	   			
	   		}
	   		else $val=false;
	   	}
	   	else{
	   		$val=$property['VALUE'];
	   	}
	   	if ($val==Null){
	   		continue;
	   	}
	   	//адаптация для цветов ASPRO
	   	if ($property['CODE'] == 'COLOR_LIST'){
	   		$property['CODE'] = 'COLOR_REF';
	   	}
	   	//если у нас множественное свойство - то значение конвертим в массив
	   	//но проверяем, может там уже внутри массив
	   	//и доп проверка - есои переменная приходит в виде массива (для фото и файлов)
	   	if (array_key_exists($property['CODE'], $PROP)){   
	   		if (!is_array($PROP[$property['CODE']])){
		   		$PROP[$property['CODE']]=array($PROP[$property['CODE']]);
		   		array_push($PROP[$property['CODE']],$val);
	   		}
	   		elseif (is_array($val)){
	   			array_push($PROP[$property['CODE']],$val);
	   		}
	   	}
	   	else {
	   		$PROP[$property['CODE']]=$val;

	   	}
	   }
	   if (!$parent_id==False){
	   	$PROP['CML2_LINK']=$parent_id;
	   }
	    $PROP['old_code'] = $good['OLD_ID'];
		$good['PROPERTY_VALUES']=$PROP;

		// $el = new CIBlockElement;
		// if($PRODUCT_ID = $el->Add($good))
		//   logger ("New ID: ".$PRODUCT_ID.'. Товар: '.$good['NAME']);
		// else
		//   logger ("Error: ".$el->LAST_ERROR);
		// return $PRODUCT_ID;
		if (CCatalogSKU::getExistOffers(array($good['OLD_ID']))[$good['OLD_ID']]){
			$good['offers']=1;
			echo 'Да';
		}
		else{
			$good['offers']=0;
			echo "Нет";
		}
	}
	return $good;	
}

function post_data($type,$data){
	$post['type'] = $type;
	$post['data'] = $data;
	$ch = curl_init('http://dev.somebox.ru/include/tools/goods_receiver.php');	
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	curl_close($ch);
	return $response;	
}

// print_r(copy_good(195,$original_iblock,$remote_iblock,false,59));
// exit;
function start_goods($good_id,$cat_code){
	global $original_iblock,$original_offers_iblock,$remote_iblock,$remote_offers_iblock;
	// print_r(copy_good($good_id,$original_iblock,$remote_iblock,false,$cat_code));
	$reply = post_data('goods',copy_good($good_id,$original_iblock,$remote_iblock,false,$cat_code));
	$g = json_decode($reply,true);
	logger($reply);
	if ($g['need_offers']==1){
		echo 'Offers:';
		$offers = CCatalogSKU::getOffersList(array($g['old_id']),0,array(),array(),array());
		foreach ($offers[$g['old_id']] as $offer) {
			$of_id = post_data('goods',copy_good($offer['ID'],$original_offers_iblock,$remote_offers_iblock,$g['new_id'],false));
			logger ($of_id);
		}
	}
}

function start_props($cat_id,$iblock_id, $cat_code){
	global $original_iblock,$original_offers_iblock,$remote_iblock,$remote_offers_iblock;
	$arProps=property_list($cat_id,$iblock_id,false);
	foreach ($arProps as $value) {
		$ar_prop = copy_property($value['ID'],$original_iblock,$remote_iblock);
		$ar_prop['CAT_CODE'] = $cat_code;				
		$reply = post_data('props', $ar_prop);
		$g = json_decode($reply,true);
		if ($g['status']=='ok'){
			logger('Свойство '.$g['name'].' скопировано с кодом '.$g['id']);
		}
		if ($g['status']=='exist'){
			logger('Свойство '.$g['name'].'уже существует');
		}
		if ($g['status']=='error'){
			logger('Ошибка добавления свойства '.$g['name'].'. Причина: '.$g['message']);
		}
	}
}


function copy_sections($iblock_from,$iblock_to){
	$result=Array();
	$arResult=CIBlockSection::GetList(Array('depth_level'=>'ASC'),Array('HAS_ELEMENT','IBLOCK_ID'=>$iblock_from));
	while ($res=$arResult->GetNext()){
		$res['IBLOCK_ID'] = $iblock_to;
		$res['DETAIL_PICTURE'] = intval($res['DETAIL_PICTURE']);
		$res['PICTURE'] = intval($res['PICTURE']);
		if ($res['DETAIL_PICTURE']>0)
			$res['DETAIL_PICTURE']= 'http://backup.somebox.ru'.CFile::GetPath($res['DETAIL_PICTURE']);
		if ($res['PICTURE']>0)
			$res['PICTURE']= 'http://backup.somebox.ru'.CFile::GetPath($res['PICTURE']);
		array_push($result,$res);

	}
		// echo "<pre>";
		// print_r ($result);
		// echo "</pre>";
		//починить картинки!!
	return $result;	
}

function full_transfer(){
	global $original_iblock,$original_offers_iblock,$remote_iblock,$remote_offers_iblock;
	$skip = True;
	$skip2 = True;
	$arResult=CIBlockSection::GetList(Array('depth_level'=>'ASC'),Array('HAS_ELEMENT','IBLOCK_ID'=>$iblock_from, 'ID'=>360));
	while ($res=$arResult->GetNext(false,false)){
		// // Участок кода для восстановление прерванного процесса
		// if ($res['NAME']=='Радиоуправляемые машины')
		// 	$skip=False;
		// if ($skip){
		// 	logger ($res['NAME'].' пропускаем');
		// 	continue;
		// }	
		// logger('Копируем свойства для категории: '.$res['NAME']);
		// start_props($res['ID'],$original_iblock, $res['CODE']);
		logger('Поиск товаров в категории: '.$res['NAME']);
		$goods = CIBlockElement::GetList(array(),array('IBLOCK_ID'=>$original_iblock,'IBLOCK_SECTION_ID'=>$res['ID']));
		while ($good = $goods->GetNext()){
			// if ($good['NAME'] =='Аккумулятор nVision Li-pol 4200 mAh, 60c, 3s1p, Deans')
			// 	$skip2 = False;
			// if ($skip2){
			// 	logger ("пропускаем товар ".$good['NAME']);
			// 	continue;
			// }
			logger('Копируем товар: '.$good['NAME']);
			start_goods($good['ID'],$res['CODE']);
			// exit();

		}
	}

}
//функция для вывода всех свойств в json в файл в разрезе разделов, для более удобной работы с ними.
function props_info(){
	global $original_iblock,$original_offers_iblock,$remote_iblock,$remote_offers_iblock;
	$skip = True;
	$arResult=CIBlockSection::GetList(Array('depth_level'=>'ASC'),Array('HAS_ELEMENT','IBLOCK_ID'=>8));
	$count = 1;
	while ($res=$arResult->GetNext()){
		$arr_prop_name = array();
		// Участок кода для восстановление прерванного процесса

		// if ($res['NAME']=='Внешние аккумуляторы Power Bank')
		// 	$skip=False;
		// if ($skip){
		// 	logger ($res['NAME'].' пропускаем');
		// 	continue;
		// }	

		logger('Копируем свойства для категории: '.$res['NAME']);
		// if ($count == 15)
		// 	break;
		// // logger($res['ID']);
		$props_list = property_list($res['ID'],8,False);
		// print_r($props_list);
		foreach ($props_list as $prop) {
			// logger ($res['CODE'].' '.$prop['NAME']);
			array_push($arr_prop_name,$prop['NAME']);
		}
		$arr_prop[$res['NAME']] = $arr_prop_name;
		logger ('Раздел: '.$res['NAME']);
		// echo json_encode($arr_prop);
		
		$count=$count+1;



	}
	
	$results = json_encode($arr_prop, JSON_UNESCAPED_UNICODE);
	file_put_contents('/home/bitrix/ext_www/somebox.ru/upload/test.txt', $results);

}

function copy_iblock($from_iblock, $to_iblock){
	$arfields = Array('ID','NAME','CODE', 'DATE_CREATE', 'IBLOCK_ID','PREVIEW_PICTURE','PREVIEW_TEXT','PREVIEW_TEXT_TYPE', 'DETAIL_PICTURE',
				'DETAIL_TEXT','DETAIL_TEXT_TYPE','PROPERTY_LINK');
	$arResult = CIBlockElement::GetList(Array(),Array("IBLOCK_ID"=>$from_iblock, 'ACTIVE'=>'Y'),false,false,array());
	while ($arItem = $arResult->GetNextElement(true,false)){
		$item=$arItem->GetFields();
		logger('Копируем '.$item['NAME']);
		if ($item['DETAIL_PICTURE'] > 0) {
			$item['DETAIL_PICTURE']= 'http://backup.somebox.ru'.CFile::GetPath($item['DETAIL_PICTURE']);
			if (empty($item['DETAIL_PICTURE'])) {
			   $item['DETAIL_PICTURE'] = false;
			}
		 }
		 if ($item['PREVIEW_PICTURE'] > 0) {
			$item['PREVIEW_PICTURE']= 'http://backup.somebox.ru'.CFile::GetPath($item['PREVIEW_PICTURE']);
			if (empty($item['PREVIEW_PICTURE'])) {
			   $item['PREVIEW_PICTURE'] = false;
			}
		 }		 
		 $item['IBLOCK_ID'] = $to_iblock;
		 $arProps = $arItem->GetProperties();
		 $item['LINKS'] = $arProps['LINK']['VALUE'];
		//  print_r($item);
		 logger(post_data('content',$item));
		//  exit();
	}

	// 
	// exit();
}

// props_info();
// Копирование структуры каталога
// $reply = post_data('cats',copy_sections($original_iblock,$remote_iblock));
// print_r($reply);


full_transfer();
// start_props();
// copy_sections(8,10);
// copy_iblock(4,18);



/* Скрипт копирования
$arGoods=CIBlockElement::GetList(array(),array('ACTIVE'=>'Y', 'IBLOCK_ID'=>8, 'SECTION_ID'=>362),false,false,array('ID'));
while ($good=$arGoods->GetNext()){
	$new_id = copy_good($good['ID'],8,62,false,1623);
	if (CCatalogSKU::getExistOffers(array($good['ID']))[$good['ID']]){
		$offers = CCatalogSKU::getOffersList(array($good['ID']),0,array(),array(),array());
		foreach ($offers[$good['ID']] as $offer) {
			$of_id = copy_good($offer['ID'],10,65,$new_id,false);
			CCatalogProduct::Add(array('ID'=>$of_id));
		}
	}
	else {
		CCatalogProduct::Add(array('ID'=>$new_id));
	}
}
*/

// echo $g;
// CCatalogProduct::Add(array('ID'=>$g));

// sections_list(8);
// Вывод товаров раздела вместе со свойствами в JSON
// echo json_encode(property_list(360,8,false),JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
// foreach (property_list(360,8,false) as $value) {
// 	echo $value['ID'].';'.$value['NAME'].';'.$value['FILTRABLE'].'<br>';
// }

// $id = copy_good(34901,8,62,false);
// copy_good(34902,10,65,$id);
// echo "<pre>";
// print_r(property_list(360,8,false));
// echo "</pre>";
//Копирование свойств между инфоблоками
// foreach ($arProps as $prop) {
// 	echo $prop['ID'].";".$prop['NAME'].';'.$prop['CODE']."<br>";
// 	copy_property($prop['ID'],8,62);
// 	echo "<br>";
// }

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>
