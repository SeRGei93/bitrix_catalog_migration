<?
$_SERVER['DOCUMENT_ROOT'] = '/home/bitrix/ext_www/dev.somebox.ru';
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
function logger($message){
	echo $message;
	echo "\n";
	ob_flush();
    flush();
}

function props_enable(){
    $hiden_props=Array("MINIMUM_PRICE","LINK_REGION","MAXIMUM_PRICE","HIT","BRAND",
                        "IN_STOCK","YM_ELEMENT_ID","LINK_SALE",
                        "ASSOCIATED_FILTER","EXPANDABLES_FILTER",
                        "PROP_2033","EXTENDED_REVIEWS_COUNT","EXTENDED_REVIEWS_RAITING",
                        "vote_count","vote_sum","rating",
                        "VIDEO_YOUTUBE","SERVICES","FORUM_TOPIC_ID",
                        "FORUM_MESSAGE_CNT","CML2_TRAITS","CML2_BASE_UNIT",
                        "CML2_TAXES","FILES","EXPANDABLES","ASSOCIATED",
                        "CML2_ATTRIBUTES","SALE_TEXT","PHOTO_GALLERY","POPUP_VIDEO",
                        "PODBORKI","PROP_2104","FAVORIT_ITEM","BIG_BLOCK",
                        "BIG_BLOCK_PICTURE","HELP_TEXT","LINK_NEWS","LINK_BLOG",
                        "LINK_STAFF","LINK_VACANCY","old_code","CML2_BAR_CODE",
                        "SUPPLIER_ID","MARKETPLACE","SIMILAR_PRODUCT","CODE_BERU",
                        "kanonicheskaya_ssylka","MORE_PROPERTIES","CRITEO"
                    );
    $arProps = CIBlockProperty::GetList(Array(),Array("IBLOCK_ID"=>23));
    $ibp = new CIBlockProperty();
    while ($prop = $arProps->GetNext(false,false)){
        if ($prop['ID']<591)
            continue;
        if (!in_array($prop['CODE'],$hiden_props)){
        \Bitrix\Iblock\Model\PropertyFeature::setFeatures(
		$prop["ID"],[[
			"MODULE_ID"=>"iblock",
			"IS_ENABLED"=>"Y",
			"FEATURE_ID" => "DETAIL_PAGE_SHOW"
			]]
		);
            echo $prop['NAME'].' ok<br>';
        }

    }
}
props_enable();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>