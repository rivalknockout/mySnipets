<?php

function get_csv_sitedata( $page, $i ){
	
	$rank = $page->rank;
	$name = $page->name;
	$desc = $page->desc;
	$url  = $page->url;
	
	if( is_pdf($url) )
	{
		$content = $desc;
	}
	elseif( $html = file_get_html($url) )
	{
		$name    = enc_utf( $html->find('title')[0]->plaintext );
		$content = enc_utf( $html->plaintext );
		
		$name    = exclude_nrt($name);
		$content = exclude_nrt($content);
	}
	else
	{
		$content = $desc;
	}
	
	return e($rank).','.e($url).','.e($name).','.e($content)."
";// 一番安全な改行のしかた（\r\nとか怖い）
}
