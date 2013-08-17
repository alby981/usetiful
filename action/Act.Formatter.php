<?php

class Formatter{
    private $pattern_as = '/[ASas]+ [A-Za-z0-9\_`]*,/';
    private $pattern_where = '/where/';
    private $pattern_from = '/from/';
    private $pattern_order_by = '/order by/';
    private $pattern_group_by = '/group by/';
    private $pattern_when = '/when/';
	private $pattern_and = '/and/';
    private $pattern_union = '/union/';
	private $pattern_end = '/end\)/';
    private $conta = 0;
    function __construct(){

    }
    function normalize($key,$query_to_normalize){
        switch($key){
            case 'as':
                $pattern = $this->pattern_as;
            break;
            case 'where':
                $pattern = $this->pattern_where;
            break;
            case 'from':
                $pattern = $this->pattern_from;
            break;
            case 'order_by':
                $pattern = $this->pattern_order_by;
            break;
            case 'group_by':
                $pattern = $this->pattern_group_by;
            break;
			case 'when':
                $pattern = $this->pattern_when;
            break;
            case 'and':
                $pattern = $this->pattern_and;
            break;
            case 'union':
                $pattern = $this->pattern_union;
            break;
			case 'end':
			   $pattern = $this->pattern_end;
            break;
        }
        @preg_match_all($pattern, $query_to_normalize, $matches, PREG_OFFSET_CAPTURE);
        $conta = $this->conta;
		for($i = 0;$i < count($matches[0]);$i++){
			$conta++;
			if($key != "as")
						$offset = ($matches[0][$i][1]);
					else
						$offset = strlen($matches[0][$i][0])+($matches[0][$i][1]);
			if($conta==1){
			$subjects[] = substr( $query_to_normalize ,  0 , $offset );
			}else{
				$subjects[] = substr( $query_to_normalize , $offset_prev , $offset-$offset_prev );
		}
		$offset_prev = $offset;
		}
		$subjects[] = substr( $query_to_normalize , $offset , strlen($query_to_normalize)-$offset);
		foreach($subjects as $result){
			 $query_normalized .= "<br/>".$result;
		}
		return $query_normalized;
    }
}


?>