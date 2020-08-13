<?
$_SERVER['DOCUMENT_ROOT'] = '/home/bitrix/ext_www/dev.somebox.ru';
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
function logger($message){
	echo $message;
	echo "\n";
	ob_flush();
    flush();
}


$json=file_get_contents("php://input");
$request=json_decode($json,true);
// file_put_contents('/home/bitrix/ext_www/dev.somebox.ru/include/tools/test.txt', $json);
// exit();
if ($request['type']=='goods'){
	$good=$request['data'];
	$good['PROPERTY_VALUES']['MORE_PHOTO'] = $good['PROPERTY_VALUES']['MORE_PHOTOS'];
	unset($good['PROPERTY_VALUES']['MORE_PHOTOS']);
	if (isset($good['cat_code'])){
		$new_section = CIBlockSection::GetList(array(),array('CODE'=>$good['cat_code']));
		$new_section = $new_section->GetNext();		
		$good['IBLOCK_SECTION_ID'] = $new_section['ID'];
	}
	unset ($good['SEARCHABLE_CONTENT'],$good['WF_PARENT_ELEMENT_ID']);
	//Обработка изображений и файлов
	if (strlen($good['DETAIL_PICTURE']) > 5) {
	  $good['DETAIL_PICTURE'] = CFile::MakeFileArray($good['DETAIL_PICTURE']);
			// $good['DETAIL_PICTURE']= 'http://backup.somebox.ru'.CFile::GetPath($good['DETAIL_PICTURE']);
	  if (empty($good['DETAIL_PICTURE'])) {
	     $good['DETAIL_PICTURE'] = false;
	  }
	  else {
	     $good['DETAIL_PICTURE']['COPY_FILE'] = 'Y';
	  }
	}
	else {
	  $good['DETAIL_PICTURE'] = false;
	}
	if (strlen($good['PREVIEW_PICTURE']) > 10) {
		$good['PREVIEW_PICTURE'] = CFile::MakeFileArray($good['PREVIEW_PICTURE']);
		// $good['PREVIEW_PICTURE']= 'http://backup.somebox.ru'.CFile::GetPath($good['PREVIEW_PICTURE']);
		if (empty($good['PREVIEW_PICTURE'])) {
			$good['PREVIEW_PICTURE'] = false;
		} else {
	         $good['PREVIEW_PICTURE']['COPY_FILE'] = 'Y';
		}
	} else {
	  $good['PREVIEW_PICTURE'] = false;
	}
	foreach ($good['PROPERTY_VALUES'] as $key => $value) {
		if (is_array($value)){			
			foreach ($value as $id => $link) {
				if (strpos($link,'somebox')>0){
					unset($good['PROPERTY_VALUES'][$key][$id]);
					$photo = CFile::MakeFileArray($link);
					$photo['COPY_FILE'] = 'Y';
					$good['PROPERTY_VALUES'][$key][]=array('VALUE'=>$photo, 'DESCRIPTION'=>'Test');
					
					
				}
				else {		
					$prop_check = CIBlockProperty::GetList(Array(),Array("IBLOCK_ID"=>23,"CODE"=>$key));
					$prop_check = $prop_check->GetNext();
					if ($prop_check['PROPERTY_TYPE'] == "L"){
						$property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>23, "CODE"=>$key, 'VALUE'=>$link));
						$property_enums=$property_enums->GetNext();
						$good['PROPERTY_VALUES'][$key][$id] = $property_enums['ID'];
					}
				}
			}
		}
		else {
			$prop_check = CIBlockProperty::GetList(Array(),Array("IBLOCK_ID"=>23,"CODE"=>$key));
			$prop_check = $prop_check->GetNext();
			// exit();
			if ($prop_check['PROPERTY_TYPE'] == "L"){
				$property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>23, "CODE"=>$key, 'VALUE'=>$value));
				$property_enums=$property_enums->GetNext();
				$good['PROPERTY_VALUES'][$key] = $property_enums['ID'];
			}
		}
		
	}
	$el = new CIBlockElement;
	if($PRODUCT_ID = $el->Add($good)){
		$res['status']=true;
		$res['new_id']=$PRODUCT_ID;
		$res['old_id']=$good['OLD_ID'];
		$res['need_offers']=$good['offers'];
		if ($good['offers']==0){
			CCatalogProduct::Add(array('ID'=>$PRODUCT_ID));
		}
	  logger (json_encode($res));
	}
	else
	  logger ("Error: ".$el->LAST_ERROR);
}
elseif ($request['type']=='props'){
	$ar_newprop=$request['data'];
	//проверка на существование такого символьного кода
	unset($ar_newprop['TIMESTAMP_X']);
	$res['code'] = $ar_newprop['CODE'];
	$res['name'] = $ar_newprop['NAME'];
	//Находим ID раздела по коду
	$catid=CIBlockSection::GetList(Array(),Array('CODE'=>$ar_newprop['CAT_CODE']));
	$catid=$catid->GetNext();
	
	
	$new_prop_check=CIBlockProperty::GetByID($ar_newprop['CODE'], $to_iblock_id);
	if ($prop = $new_prop_check->GetNext()){
		$res['status']='exist';
		CIBlockSectionPropertyLink::Add($catid['ID'], $prop['ID'], $arLink = array());
	}
	else{				
		$ibp = new CIBlockProperty;
		if($res['id'] = $ibp->Add($ar_newprop)){
			CIBlockSectionPropertyLink::Delete(0, $res['id']);
			CIBlockSectionPropertyLink::Add($catid['ID'], $res['id'], $arLink = array());
			$res['status'] = 'ok';
		}
		else{
			$res['status']='error';
			$res['message']=$ibp->LAST_ERROR;
		}
		}			
	

	logger (json_encode($res));	
}
elseif ($request['type']=='cats'){
	logger('Start categories');
	$ar_cats=$request['data'];
	$ar_forest=Array();
	foreach ($ar_cats as $cat) {

		$bs = new CIBlockSection;
		foreach ($cat as $key => $value) {
			// echo $key.' - '. strpos($key,'~').'<br>';
			if (strpos($key,'~')===0){
				// echo $key.'<br>';
				unset ($cat[$key]);
			}
		}
		$old_id = $cat['ID'];
		unset ($cat['ID']);
		$res['name'] = $cat['NAME'];
		if (strlen($cat['DETAIL_PICTURE']) > 5) {
		  $cat['DETAIL_PICTURE'] = CFile::MakeFileArray($cat['DETAIL_PICTURE']);
		  if (empty($cat['DETAIL_PICTURE'])) {
		     $cat['DETAIL_PICTURE'] = false;
		  }
		  else {
		     $cat['DETAIL_PICTURE']['COPY_FILE'] = 'Y';
		  }
		}
		else {
		  $cat['DETAIL_PICTURE'] = false;
		}
		if (strlen($cat['PICTURE']) > 5) {
		  $cat['PICTURE'] = CFile::MakeFileArray($cat['PICTURE']);
		  if (empty($cat['PICTURE'])) {
		     $cat['PICTURE'] = false;
		  }
		  else {
		     $cat['PICTURE']['COPY_FILE'] = 'Y';
		  }
		}
		else {
		  $cat['PICTURE'] = false;
		}		
		if (array_key_exists($cat['IBLOCK_SECTION_ID'], $ar_forest)){
			echo "Замена подкатегории: ".$cat['IBLOCK_SECTION_ID'].' на '.$ar_forest[$cat['IBLOCK_SECTION_ID']].'<br>';
			$cat['IBLOCK_SECTION_ID'] = $ar_forest[$cat['IBLOCK_SECTION_ID']];			
		}

		if($id = $bs->Add($cat))
		{
			$ar_forest[$old_id]=$id;
			$res['status'] = 'ok';
			$res['id'] = $id;
		}
		else
		{
			$res['status'] = 'error';
			$res['message'] = $bs->LAST_ERROR;
		}
		echo "<pre>";
		print_r ($res);
		echo "</pre>";

	}
}
elseif ($request['type']=='content'){
	$item = $request['data'];
	$item['IBLOCK_SECTION_ID'] = 52;
	$ibp = new CIBlockElement;
	if (strlen($item['DETAIL_PICTURE']) > 5) {
		$item['DETAIL_PICTURE'] = CFile::MakeFileArray($item['DETAIL_PICTURE']);
			  // $item['DETAIL_PICTURE']= 'http://backup.somebox.ru'.CFile::GetPath($item['DETAIL_PICTURE']);
		if (empty($item['DETAIL_PICTURE'])) {
		   $item['DETAIL_PICTURE'] = false;
		}
		else {
		   $item['DETAIL_PICTURE']['COPY_FILE'] = 'Y';
		}
	  }
	  else {
		$item['DETAIL_PICTURE'] = false;
	  }
	  if (strlen($item['PREVIEW_PICTURE']) > 10) {
		  $item['PREVIEW_PICTURE'] = CFile::MakeFileArray($item['PREVIEW_PICTURE']);
		  // $item['PREVIEW_PICTURE']= 'http://backup.somebox.ru'.CFile::GetPath($item['PREVIEW_PICTURE']);
		  if (empty($item['PREVIEW_PICTURE'])) {
			  $item['PREVIEW_PICTURE'] = false;
		  } else {
			   $item['PREVIEW_PICTURE']['COPY_FILE'] = 'Y';
		  }
	  } else {
		$item['PREVIEW_PICTURE'] = false;
	  }
	// print_r($item);
	// exit();
	if($content_id = $ibp->Add($item)){
		$res['status']=true;
		$res['new_id']=$content_id;
		$goods_id = $item['LINKS'];
		$goods = CIBlockElement::GetList(array(),array('IBLOCK_ID'=> 23, PROPERTY_old_code =>$goods_id),false,false,array('ID','IBLOCK_ID','NAME'));
		while ($good = $goods->GetNext()) {
			$props[]=$good['ID'];
		}
		CIBlockElement::SetPropertyValueCode($content_id,'LINK_GOODS',$props);
		
	  logger (json_encode($res));
	}
	else
		logger ("Error: ".$el->LAST_ERROR);
}
else{
	logger('Ничего не передано');
}


require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>
