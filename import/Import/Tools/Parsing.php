<?php

namespace Import\Tools;


trait Parsing{
	public static function parseInt($str){
		return preg_replace("/[^0-9']/",'', $str);
	}

	public static function parseHtml($str){

		$str = str_ireplace(['; '], ';', $str);
		$str = str_ireplace([';</li>'], '</li>', $str);
		$str = str_ireplace(['.</li>'], '</li>', $str);
		$str = str_ireplace([';'], '<br>', $str);
		$str = str_ireplace(['<br></li>'], '</li>', $str);
		$str = str_ireplace(['\n'], '', $str);

		return $str;
	}

	public static function parseValueFromWrap($str, $fItem, $lItem){
		$pattern = "/$fItem(.*?)$lItem/";
		preg_match_all($pattern, $str, $matches);
		return $matches[1];
	}

	public static function parseEnumTF($str, $true = 'Y', $false = 'N'){
		$trueAlias = array('да');
		$falseAlias = array('нет');

		if (in_array(mb_strtolower($str), $trueAlias)){
			return $true;
		}
		elseif (in_array(mb_strtolower($str), $falseAlias)){
			return $false;
		}
		else{
			//TODO error list
		}
	}

    public function prepareOnlyText($str, $escape = false){
        $str = str_ireplace(['\n'], '', $str);
        $str = preg_replace('/[^ a-zа-яё\d]/ui', '',$str );
        $str = trim($str);
        if ($escape == true){
            return \Connection::GetSQLString( $str );
        }
        else{
            return $str;
        }
    }


//Create elements
	public static function createTitles($str, $delimiter, $firstWrap = null, $secondWrap = null){
		$new_str = '';
		if ( empty($firstWrap) ) {
			$firstWrap = 'h4';
		}
		if ( empty($secondWrap) ) {
			$secondWrap = 'p';
		}

		$result = self::parseValueFromWrap($str, '<li>', '<*li*>');

		foreach ($result as $key => $value) {
			$result = explode($delimiter, $value);
			$new_str .= '<li><' . $firstWrap . '>' . $result[0] . '</' . $firstWrap . '><' . $secondWrap . '>' . $result[1] . '</' . $secondWrap . '></li>';
		}
		return '\'<ul>' . $new_str . '<ul>\'';
	}

	public function createYouTubeFrame($str, $delimiter){
		$new_str = '';
		preg_match_all('/<li>(.*?)<\/li>/', $str, $matches);
		foreach ($matches[1] as $key => $value) {
			$result = explode($delimiter, $value);
			$name = $result[0];
			$src = $result[1];

			$result = explode('/', $result[1]);
			$result = array_reverse($result);
			foreach ($result as $key => $value) {
				if ( !empty($value) ) {
					$src = trim($value);
					break;
				}
			}
			unset($result);

			$new_str .=
				'<li>
    			<iframe src="https://www.youtube.com/embed/' . $src . '?rel=0&amp;showinfo=0" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
    			<h5>' . $name . '</h5></li>';
		}
		return '\'<ul>' . $new_str . '<ul>\'';
	}

	public function createList($str, $delimiter){
		$str = preg_replace('/[\']/', '', $str);

		$result = explode($delimiter, $str);

		$new_str = '\'<ul>';
		foreach ($result as $key => $value) {
			$new_str .= '<li>' . $value . '</li>';
		}
		$new_str .= '</ul>\'';
		return $new_str;
	}

	public function getStrForQuery($array){
		$query_items = '';
		$i = 0;
		foreach ($array as $key => $value) {
			$i++;
			if( $i >= count($array) ){
				$query_items .= $value;
			}
			else{
				$query_items .= $value . ',';
			}
		}
		return $query_items;
	}

}