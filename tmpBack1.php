<!DOCTYPE html>
<html lang="ja"><head><meta charset="UTF-8"></head><body>
<?php
/* UTF-8で処理 – 他の文字コード処理するかもしれないからdefineしておく。 */
define("CHAR_SET","UTF-8");
/* 文字化け対策のおまじない的（？）なもの。 */
mb_language("Japanese");

/* スクレイピングライブラリ */
require_once('./simple_html_dom.php');
/* Wordpress関数をビルトイン */
define('WP_USE_THEMES', false);
require('../wp/wp-blog-header.php');



/* sslページを取得できるかテスト */
$url  = 'https://www.infotop.jp/afi/item/list/page/2';
//$html = file_get_html($url);
//print_r($html->plaintext);
$html = str_get_html( get_infotop(9,4) );
echo $html->find('title')[0]->plaintext;


//--------------------------------------------------------------------------------------------
//	関数
//--------------------------------------------------------------------------------------------
/* ファイルに書き出す */
function writefile($filename,$content,$navcnt){
	// ファイルを書き込み専用でオープンします。
	if( $navcnt==1 )
		$fno = fopen($filename, 'w');//ファイルポインタをさいしょに（1-100位）
	else
		$fno = fopen($filename, 'a');//ファイルポインタをさいごに（101-200位以降なので）
	// 文字列を書き出します。
	fwrite( $fno, $content );
	// ファイルをクローズします。
	fclose($fno);	
}
/* Wordpressに記事を投稿する */
/* postToWP( date('Y年m月d日(D) H時i分s秒'), $csv ); */
function postToWP($title,$content){
	$my_post['post_title'] = $title;
	$my_post['post_content'] = $content;
	$my_post['post_status'] = 'publish';
	wp_insert_post( $my_post );
}
/* 文字列のさいごが.pdfの場合trueを返す */
function is_pdf($str){
	return substr($str, -3) == 'pdf' ? true : false;
}
/* エクセル用に文字コードと改行コードを変換（Windowsフォーマット） */
function formatToExel($str){
	$str = mb_convert_encoding($str, "SJIS", "ASCII,JIS,UTF-8,EUC-JP,SJIS,'HTML-ENTITIES'");
	$str = str_replace(array("\r\n","\r","\n"), "\r\n", $str);
	return $str;
}
/* HTMLエンティティにエンコード（未使用） */
function enc_html($str){
	return mb_convert_encoding($str,"HTML-ENTITIES","ASCII,JIS,UTF-8,EUC-JP,SJIS");
}
/* UTF-8にエンコード（文字コードの名前が取得できない場合、文字列がHTMLエンティティの可能性があるのでこれを直す） */
function enc_utf($str){
	if( mb_detect_encoding($str) ){
		$str = mb_convert_encoding($str, "UTF-8", "ASCII,JIS,UTF-8,EUC-JP,SJIS,UTF-7,UCS-2LE,eucjp-win,sjis-win,Windows-1251");
	}else{
		/* ひとつの関数にまとめると無効 */
		$str = mb_convert_encoding($str, "UTF-8", "ASCII,JIS,UTF-8,EUC-JP,SJIS,UTF-7,UCS-2LE,eucjp-win,sjis-win,Windows-1251");
		$str = mb_convert_encoding($str, "UTF-8", "HTML-ENTITIES");
	}
	return $str;
}
/* カンマを除外 */
function e($str){
	$str = str_replace('，','、',$str);
	return str_replace(',','',$str);
}
/* タブ 改行 マークップを除外 */
function exclude_nrt($str){
	/*
	$str = ereg_replace("[\n|\r|\nr|\t]","",$str);//タブと改行コードを削除
	$str = str_replace(array("\r\n","\r","\n"), '', $str);*/
	$str = str_replace(array("\r\n","\r","\n","\t"), '', $str);
	$str = strip_tags($str);//<style>などのマークアップもすべて削除
	return $str;
}
/* エスケープシーケンスをjson_decode()用に除外
   ふつうにエスケープすると\"が"になってjson_decodeでひっかかる */
function exclude_escape($str){
	$str = str_replace('\"','',$str);
	return stripslashes($str);
}
/* 改行を除外（未使用） */
function exclude_newline($str){
	return str_replace(array("\r\n","\r","\n"), '', $str);
}
/* HTMLエンティティを除外（未使用） */
function exclude_html($str){
	return strip_tags($str);
}


?>
<?php
/*
	MEMO：
	$html =file_get_html( $url ); //if($i==44) writefile('test.txt',mb_convert_encoding($html->plaintext, "UTF-8", "HTML-ENTITIES"));
	
	
	
	
	
	
	
	
*/
?>
<?php 

/**
 * 参考：
 * http://web-prog.com/php/curl-login-scraiping/
 * Google検索：スクレイピング php ログイン
 */
function get_infotop( $category_id, $page_num=1 ){
	$params = array( 
	    "pds[id_txt]" => '*****', //login-name  pds[id_txt] 
	    "pds[pw_txt]" => '*****'//, //login-password  pds[pw_txt] 
	    //"submit"  => "ログイン"
	); 
	//対象ページのform内容に合わせる
	
	$params2 = array(
		'pds[category_id_i]' => 9
	);
	//カテゴリのvalueを指定
	 
	/** Hideeeeeaki：
	 *  ログイン後、/afi/item/search上でカテゴリIDをPOSTで指定する（クッキーに保存させる）必要があるため
	 *  そのあとで目的のページ( ~~/page/2 など)に移動する（ file4（=第４段階）の記述 ）
	 */
	$file  = "https://www.*******.jp/afi/auth/login";// https://www.phppro.jp/members/login_page.php
	$file2 = "https://www.*******.jp/afi/loginform/login"; // https://www.phppro.jp/members/login.php
	$file3 = "https://www.*******.jp/afi/item/search"; // https://www.phppro.jp/members/user_edit.php
	$file4 = "https://www.*******.jp/afi/item/list/page/{$page_num}";
	//段階ごとに使うURL。上からクッキー取得用、form要素のaction先URL、ログイン後ページ
	 
	$cookie_file_path = '/home/***********/www/***/ssl/other_scraping/cookie.txt'; // '絶対パス/cookie.txt'
	touch($cookie_file_path);
	//クッキー保存ファイルを作成
	 
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $file);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file_path);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file_path);
	$put = curl_exec($ch) or dir('error ' . curl_error($ch)); 
	curl_close($ch);
	//第１段階
	 
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $file2);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HEADER, TRUE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file_path);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file_path);
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	$output = curl_exec($ch) or dir('error ' . curl_error($ch)); 
	curl_close($ch);
	//第２段階
	 
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $file3);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file_path);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file_path);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params2);
	$output_comp = curl_exec($ch) or dir('error ' . curl_error($ch)); 
	curl_close($ch);
	//第３段階
	 
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $file4);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file_path);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file_path);
	$output_comp = curl_exec($ch) or dir('error ' . curl_error($ch)); 
	curl_close($ch);
	//第４段階
	 
	mb_language("Japanese");
	$complete_source = mb_convert_encoding($output_comp, "UTF-8", "auto");
	//取得ページをUTF-8化
	 
	$data = $complete_source; // echo $complete_source;
	 
	unlink($cookie_file_path);
	//クッキー保存ファイルの削除
	
	return $data;
}



 ?>
</body>
</html>
