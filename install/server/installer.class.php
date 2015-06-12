<?php

//todo:upgrading should delete old files
//todo:think about a separate upgrade script
class Installer {

    protected $config;
    protected $database_queries;
    protected $database_sql_file;
    protected $database_connection;
    protected $is_upgrade;
    protected $default_database_name;

    public $database_username;
    public $database_password;
    public $database_hostname;
    public $database_name;

    const VERSION = '1.3.3';

    function __construct() {
        require_once('config.class.php');
        require_once('response.class.php');

        @session_start();

        if (!isset($_SESSION['duet_installer']))
            $_SESSION['duet_installer'] = array();

        $this->default_database_name = 'duet_app';

        $this->database_queries = array();
        $this->config = new Config();
        $this->is_upgrade = false;

        $this->load_existing_config();
    }

    function version() {
        return self::VERSION;
    }

    function is_upgrade() {
        return $this->is_upgrade;
    }

    function get_default_database_name() {
        return $this->default_database_name;
    }

    function load_existing_config() {


        if (file_exists('../server/config/config.php')) {
            $this->is_upgrade = true;

            //we will get errors from the loaded config file if the root constant isn't defined
            define('ROOT', '../');
            require_once('../server/config/config.php');

            global $CONFIG;
            $this->set_config($CONFIG);
            //       $this->config->print_config();
        }
    }

    function complete_step($name) {
        $_SESSION['duet_installer'][$name] = true;
    }

    function is_step_completed($name) {
        return isset($_SESSION['duet_installer'][$name])
            && $_SESSION['duet_installer'][$name] == true;
    }


    function check_compatibility() {
        $results = array();

        //clear any previous install data anytime the installer is started over
        $this->config->clear();

        //check php version
        if (version_compare(PHP_VERSION, '5.3') >= 0) {
            //the user has the correct version
            $results['php_version'] = Response(PHP_VERSION);
        } else {
            //the user does not have the minimum php version
            $results['php_version'] = Response()->error(PHP_VERSION);
        }

        //check for pdo
        if (extension_loaded('pdo_mysql')) {
            $results['pdo'] = Response();
        } else $results['pdo'] = Response()->error();

        //check for curl
        if (extension_loaded('curl')) {
            $results['curl'] = Response();
        } else $results['curl'] = Response()->error();

        if ($results['php_version']->ok() && $results['pdo']->ok() && $results['curl']->ok()) {
            $this->complete_step('compatibility_verified');
            return Response($results);
        } else return Response()->error($results);

    }

    function is_valid_purchase_code($purchase_code) {
        /**************** IMPORTANT NOTE *******************
        Hi :) How are you?

        Please don't steal my software. I worked very hard
        on this application and like you, I have bills to
        pay. If you absolutely MUST have the software, and
        you can't afford it, please send me an email and
        I'm sure we can work something out.

        Thank you for your understanding.
        /***************************************************/

        $data = array(
            'purchase_code' => $purchase_code,
            'item_name' => 'Duet - Professional Project Management'
        );
        //todo:put the purchase code in teh config file. I can use it later


        //access token?
        $postUrl = 'http://www.plumtheory.com/gumroad/purchase_verifier.php';


        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        //close connection
        curl_close($ch);

        $response = json_decode($response);

        if ($this->is_valid_purchase_code_response($response)) {
            $this->config->set('purchase_code', $purchase_code);
            $this->complete_step('license_verified');
            return true;
        } else return false;
    }

    function is_valid_purchase_code_response($response) {
        return $response->code == 200 && $response->data == 'valid';
    }

    function check_folder_permissions() {
        //todo: these paths will be wrong
        $language_folder_result = is_writable('../server/application/language');
        $config_folder_result = is_writable('../server/config');
        $file_folder_result = is_writable('../server/files-folder');
        $tmp_folder_result = is_writable('../server/tmp');

        //if the build path already exists, we need it to be writable
        $build_path = '../server/application/language/en/build'; //todo:this shouldn't be hardcoded
        if (is_dir($build_path)) {
            $build_path_result = is_writable($build_path);
        } else $build_path_result = true;

        $build_file = $build_path . '/all.php';
        if (file_exists($build_file)) {
            $build_file_result = is_writable($build_file);
        } else $build_file_result = true;

        $language_combined_result = $language_folder_result && $build_path_result && $build_file_result;

        $response_data = array(
            'result' => $language_combined_result && $config_folder_result && $file_folder_result && $tmp_folder_result,
            'language_combined_result' => $language_combined_result,
            'language_folder_result' => $language_folder_result,
            'build_folder_result' => $build_path_result,
            'build_file_result' => $build_file_result,
            'config_folder' => $config_folder_result,
            'file_folder' => $file_folder_result,
            'tmp_folder' => $tmp_folder_result,
        );

        if (($language_combined_result && $config_folder_result && $file_folder_result && $tmp_folder_result) == true) {
            $this->complete_step('folder_permissions');
        }

        return Response($response_data);
    }

    function set_sql_file($file) {
        $this->database_sql_file = $file;
    }

    function connect_to_database($database, $hostname, $username, $password) {
        $this->database_name = $database;
        $this->database_hostname = $hostname;
        $this->database_username = $username;
        $this->database_password = $password;

        $connected = false;
        $errors = array();

        //if a value was entered, that's the value we want in the form, not the default
        $this->set_config('database_name', $database);

        //try to connect with the database name supplied
        try {
            $conn = @new PDO("mysql:host=$this->database_hostname;dbname=$this->database_name", $this->database_username, $this->database_password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $connected = true;
        } catch (PDOException $e) {
            $errors[] = 'Attempt 1 - Assume database already exists: ' . $e->getMessage();
        }

        //if the first connection attempt failed, it's possible the credentials are correct but the database hasn't been
        //created yet. Lets try to connect without the database name
        if ($connected == false) {
            try {
                $conn = @new PDO("mysql:host=$this->database_hostname;", $this->database_username, $this->database_password);

                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $connected = true;
            } catch (PDOException $e) {
                $errors[] = 'Attempt 2 - Create database if not exists: ' . $e->getMessage();
            }
        }

        if ($connected != false) {
            $this->database_connection = $conn;

            $this->set_config(array(
                'database_name' => $database,
                'database_hostname' => $hostname,
                'database_username' => $username,
                'database_password' => $password
            ));

            return Response();
        } else {

            return Response()->error(implode(', ', $errors));
        }
    }

    function column_exists($table, $column) {
        $conn = $this->database_connection;
        $stmt = $conn->prepare("SHOW COLUMNS FROM `$table` LIKE '$column'");
        $stmt->execute();
        $result = $stmt->fetchAll();

        return is_array($result) && (count($result) > 0);
    }

    function update_database() {
        $conn = $this->database_connection;

        try {
            $conn->exec("use `$this->database_name`");

            $this->set_sql_file('resources/duet.sql');
            $this->load_sql_file();

            //create the database tables
            //since this is an update, most of hte tables will exist, we just want to add the new tables. We definitely
            //dont want to run any inserts so loop through and find the CREATE statements
            foreach ($this->database_queries as $query) {
                $query_type = explode(' ', $query);
                $query_type = $query_type[0];

                if($query_type == 'CREATE'){
                    $result = $conn->query($query);

                    if ($result == false) {
                        return Response()->error('There was an error creating the database tables');
                    }
                }
            }

            if (!$this->column_exists('projects', 'is_template'))
                $conn->exec("ALTER TABLE projects ADD `is_template` tinyint(4) NOT NULL DEFAULT '0'");

            $conn->exec("ALTER TABLE projects MODIFY COLUMN `status_text`  varchar(20) DEFAULT NULL");

            $this->verify_required_data();

            $this->complete_step('database_created');


            return Response();
        } catch (PDOException $e) {
            return Response()->error($e->getMessage());
        }


    }

    function verify_required_data(){
        $conn = $this->database_connection;
        //make sure all the roles are present
        $sql = "SELECT id FROM roles WHERE name = 'agent'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

        if(!is_array($result) || count($result) == 0){
            $sql = "INSERT into roles (id, name) VALUES(3, 'agent')";
            $conn->exec($sql);
        }
    }

    function create_database() {
        $conn = $this->database_connection;

        //see if the database already exists. If not, create it
        try {
            $conn->exec("use `$this->database_name`");

        } catch (PDOException $e) {

            $conn->exec("CREATE DATABASE `$this->database_name`");
            $conn->exec("use `$this->database_name`");
        }

        $this->set_sql_file('resources/duet.sql');
        $this->load_sql_file();

        //create the database tables
        foreach ($this->database_queries as $query) {
            $result = $conn->query($query);

            if ($result == false) {
                return Response()->error('There was an error creating the database tables');
            }
        }

        $this->complete_step('database_created');
        return Response();
    }


    function load_sql_file($delimiter = ';') {
        //http://stackoverflow.com/questions/1883079/best-practice-import-mysql-file-in-php-split-queries?lq=1
        set_time_limit(0);

        if (is_file($this->database_sql_file) === true) {
            $file = fopen($this->database_sql_file, 'r');

            if (is_resource($file) === true) {
                $query = array();

                while (feof($file) === false) {
                    $query[] = fgets($file);

                    if (preg_match('~' . preg_quote($delimiter, '~') . '\s*$~iS', end($query)) === 1) {
                        $query = trim(implode('', $query));

                        $this->database_queries[] = $query;

                        flush();
                    }

                    if (is_string($query) === true) {
                        $query = array();
                    }
                }

                return fclose($file);
            }
        }

        return false;
    }

    function print_config() {
        $this->config->print_config();
    }

    function get_config($name) {
        return $this->config->get($name);

    }

    function set_config($name, $value = null) {
        if (!is_array($name)) {
            if ($value != false)
                $this->config->set($name, $value);
        } else {
            foreach ($name as $key => $value) {
                if ($value != false)
                    $this->config->set($key, $value);
            }
        }

    }


    function build_config() {
        //import the params so we have access to them with the config->get method
        $this->config->import_params();

        $payment_method = $this->config->get('payment_method');
        $currency_symbol = $this->config->get('currency_symbol');

        //apply defaults to optional parameters
        if ($payment_method == false || !isset($payment_method) || empty($payment_method)) {
            $payment_method = 'none';
            $this->config->set('payment_method', $payment_method);
        }

        if ($currency_symbol == false || !isset($currency_symbol) || empty($currency_symbol)) {
            $currency_symbol = '$';
            $this->config->set('currency_symbol', $currency_symbol);
        }

        $this->config->write();
        $this->complete_step('config_created');
    }

    function build_language_file() {
        define('APP_ROUTE', '../server/application');

        require_once('../server/core/utils.class.php');
        require_once('../server/core/language.class.php');
        $language = new Language();
        $language->build_language_file();
    }


}


