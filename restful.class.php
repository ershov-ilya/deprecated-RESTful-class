<?php
/**
 * Created by PhpStorm.
 * User: ershov-ilya
 * Website: ershov.pw
 * GitHub : https://github.com/ershov-ilya
 * Date: 25.01.2015
 * Time: 12:52
 */
class RESTful {
    private $headers;
    private $private_scope;
    private $scope;
    private static $filter;

    function __construct($ACTION='', $filter=0, $arrSanitize=array()){
        defined('ACTION') or define('ACTION', $ACTION);

        if($filter!==array()){
            if($filter===0) $filter=array();
            RESTful::$filter = $filter;
            $default_filter = array('ACTION', 'METHOD', 'id');
            RESTful::$filter = array_merge($default_filter, $filter);
        }

        // Define METHOD type
        if(isset($_SERVER['argc'])) defined('METHOD') or define('METHOD', 'CONSOLE');//$this->private_name='CONSOLE';
        elseif(isset($_SERVER['REQUEST_METHOD']))  defined('METHOD') or define('METHOD', $_SERVER['REQUEST_METHOD']); //$this->private_name=$_SERVER['REQUEST_METHOD'];
        else{
            defined('METHOD') or define('METHOD', 'UNKNOWN');
        }

        // Combine parameters
        $this->private_scope = array();
        if(METHOD=='CONSOLE') {
            //$this->private_scope = array_merge($this->private_scope, $_SERVER['argv']);
            $this->private_scope = array_merge($this->private_scope, getOptions());
        }
        else{
            $this->headers = RESTful::parseRequestHeaders();
            $this->private_scope = array_merge($this->private_scope, $this->headers);
            $this->private_scope = array_merge($this->private_scope, $_REQUEST);
        }


        $this->private_scope['ACTION']=$ACTION;
        $this->private_scope['METHOD']=METHOD;
        // Для дебага, возможность переопределять метод
        if(DEBUG && isset($_GET['METHOD'])) $this->private_scope['METHOD']=$_GET['METHOD'];

        // Filter
        if(empty($filter)){
            $this->scope = $this->private_scope;
        }
        else{
            $this->scope = $this->filtrateScope();
        }

        // Sanitize
        $this->scope = $this->sanitize($this->scope, $arrSanitize);
        return $this->private_scope;
    }

    function get($selector){
        switch($selector){
            case 'scope':
                return $this->scope;
                break;
            case 'filter':
                return RESTful::$filter;
                break;
            case 'method':
                return $this->scope['METHOD'];
                break;
            case 'action':
                return $this->scope['ACTION'];
                break;
            case 'raw':
                return $this->private_scope;
                break;
            case 'headers':
                return $this->headers;
                break;
            default:
                return array();
        }
    }

    static private function parseRequestHeaders() {
        $headers = array();
        foreach($_SERVER as $key => $value) {
            if (substr($key, 0, 5) <> 'HTTP_') {
                continue;
            }
            $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $headers[$header] = $value;
        }
        return $headers;
    }

    function sanitize($arr, $filter=array()){
        $out=array();
        foreach($arr as $key => $val) {
            if(isset($filter[$key])){
                $out[$key] = filter_var($val, $filter[$key]);
                continue;
            }
            switch ($key) {
                case 'sc':
                case 'user':
                case 'login':
                    // Only ALLOWED SYMBOLS
                    $out[$key] = preg_replace('/[^a-zA-Z0-9\-_\.]+/i', '', $val);
                    break;
                case 'METHOD':
                case 'ACTION':
                    $out[$key] = preg_replace('/[^a-zA-Z\-_\.]+/i', '', $val);
                    break;
//                case 'phone':
//                    $out[$key] = preg_replace('/[^0-9\s\(\)\-_\.,\+]+/i', '', $val);
//                    break;
                case 'pass':
                case 'name':
                case 'surname':
                    $out[$key] = filter_var($val, FILTER_SANITIZE_STRING);
                    break;
                case 'phone':
                case 'id':
                    $out[$key] = filter_var($val, FILTER_SANITIZE_NUMBER_INT);
                    break;
                case 'email':
                    $out[$key] = filter_var($val, FILTER_SANITIZE_EMAIL);
                    break;
                default:
                    //$out[$key] = $val;
                    $out[$key] = filter_var($val, FILTER_SANITIZE_STRING);
            }
        }
        return $out;
    }

    function filtrate($arr, $filter=NULL){
        if($filter==NULL) $filter=RESTful::$filter;
//        if(DEBUG) {
//            if (is_array($filter)) print "Filter type Array\n";
//            if (is_string($filter)) print "Filter type String\n";
//        }
        if (is_string($filter)){
            $filter=explode(',',$filter);
        }
//        if(DEBUG) print_r($filter);

        $res=array();
        foreach($filter as $el){
            $el_cropspace=preg_replace('/ /','',$el);
            if(isset($arr[$el_cropspace])) $res[$el_cropspace]=$arr[$el_cropspace]; // Вырезаем пробелы из имён параметров
            if(isset($arr[$el])) $res[$el]=$arr[$el];
        }
        return $res;
    }

    function filtrateScope($filter=NULL){
        return $this->filtrate($this->private_scope,$filter);
    }

} // class RESTful

function getOptions($shortopts="", $longopts  = array("id:","action:")){
    // Скрипт example.php
//    $shortopts  = "";
//    $shortopts .= "a:";  // Обязательное значение
//    $shortopts .= "v::"; // Необязательное значение
//    $shortopts .= "abc"; // Эти параметры не принимают никаких значений

//    $longopts  = array(
//        "id:",     // Обязательное значение
//        "action:"
//        "action::",    // Необязательное значение
//        "option",        // Нет значения
//        "opt",           // Нет значения
//    );
    $options = getopt($shortopts, $longopts);
    return $options;
}