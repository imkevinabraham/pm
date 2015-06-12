<?php


class Config{
    protected $params;
    protected $name;

    function __construct(){
        @session_start();

        $this->params = array();

        $this->name = 'duet_config';

        //we're storing the config values in a session because the values will be collected over several steps,
        //and therefore several different calls to this script
        if(!isset($_SESSION[$this->name]))
            $_SESSION[$this->name] = array();
    }

    function set($key, $value){
        $_SESSION[$this->name][$key] = $value;
    }



    function print_config(){;
        pre($_SESSION[$this->name]);
    }

    function import_params(){
        $this->params = $_SESSION[$this->name];
    }

    function get($name)
    {
        $parts = explode('.', $name);

        if($parts[0] == 'database'){
            return  $this->get_database_property($parts[1]);
        }
        else{
            $val = $_SESSION[$this->name];

            foreach ($parts as $part) {
                if (isset($val[$part]))
                    $val = $val[$part];
                else {
                    $val = false;
                    break;
                }
            }

            return $val;
        }

    }

    function get_database_property($property){

        switch($property){
            case 'name':
                $val =  defined('DB_NAME') ? DB_NAME : false;
                break;
            case 'hostname':
                $val = defined('DB_HOST') ? DB_HOST : false;
                break;
            case 'user':
                $val= defined('DB_USER') ? DB_USER : false;
                break;
            case 'password':
                $val = defined('DB_PASSWORD') ? DB_PASSWORD : false;
                break;

        }

        return $val;
    }

    function clear(){
        $_SESSION[$this->name] = array();
        $this->params = array();
    }


    function write(){

        //todo:try 666 permissions
        $template = file_get_contents('resources/config.template.txt');


        $this->import_params();
        $tags = $this->params;

        $template = preg_replace_callback('/\\{\\{([^{}]+)\}\\}/',
            function($matches) use ($tags)
            {
                $key = $matches[1];

                return array_key_exists($key, $tags)
                    ? $tags[$key]
                    : '';
            }
            , $template);


        $fp = fopen('../server/config/config.php', 'w');


       // fwrite($fp, "<?php \n\n\n");
        fwrite($fp, $template);
        fclose($fp);
    }
}