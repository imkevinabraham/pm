<?php


class App{
    function load_language(){}

    static function get_config(){}

    static function log($data){
        if(class_exists('ChromePhp')){
            ChromePhp::log($data);
        }
    }

    static function get_latest_version(){


        function get_version($post_url){
            $ch = curl_init();

            //set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $post_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, array());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            //todo:bad idea?
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);

            //close connection
            curl_close($ch);


            return $response = json_decode($response);
        }

        //we'll try https first and if it fails we'll try http. This is just to account for any future changes
        //in the .htaccess config at duetapp.com
        $https = 'https://www.duetapp.com/admin/get_latest_version';
        $http = 'http://www.duetapp.com/admin/get_latest_version';

        $version = get_version($https);

        if(!isset($version))
            $version = get_version($http);

        if(!isset($version))
            $version =  array('version' => 'error');
        //open connection


        return $version;
    }


}

