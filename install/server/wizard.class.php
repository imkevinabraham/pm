<?php

class Wizard
{
    public $step_number;
    public $variables;
    public $steps;

    function __construct()
    {
        require_once('installer.class.php');
        $this->installer = new Installer();
        $this->init_steps();
//        $_POST = $this->clean($_POST);
//        $_GET = $this->clean($_GET);
    }

    function init_steps()
    {
        $this->steps = array(
            'introduction',
            'server_compatibility',
            'license_key',
            'folder_permissions',
            'database',
            'main_config',
            'client_access',
            'payments',
            'finish'
        );
    }

    function start()
    {
        $step_number = isset($_GET['step']) ? $_GET['step'] : 1;

        $this->step_number = $step_number;
        $step_name = $this->steps[$step_number - 1];
        $this->$step_name();
    }

    function next_step_url()
    {
        echo $this->get_next_step_url();
    }

    function get_next_step_url()
    {
        return 'index.php?step=' . ((int)$this->step_number + 1);
    }

    function go_to_next_step()
    {
        header('Location:' . $this->get_next_step_url());
    }

    function step_number($step_name)
    {
        return array_search($step_name, $this->steps) + 1;
    }

    static function clean($str)
    {
        $str = is_array($str) ? array_map(array('Wizard', 'clean'), $str)
            : str_replace('\\', '\\\\', strip_tags(trim(htmlspecialchars((get_magic_quotes_gpc()
                ? stripslashes($str) : $str), ENT_QUOTES))));

        return $str;
    }

    function get_param($param_name)
    {
        if (isset($_POST[$param_name]) && !empty($_POST[$param_name]))
            return $_POST[$param_name];
        else return false;
    }


    function load_view($name)
    {
        $this->set('step_number', $this->step_number);

        @extract($this->variables);

        ob_start();

        require_once('templates/header.php');
        require_once("templates/$name.php");
        require_once('templates/footer.php');

        ob_end_flush();

    }

    function set($name, $value)
    {
        $this->variables[$name] = $value;
    }


    function introduction()
    {
        $title = 'Introduction';

        if($this->installer->is_upgrade())
            $title .= ' - UPGRADE TO VERSION ' . $this->installer->version();

        $this->set('step_title', $title);
        $this->load_view('introduction');
    }

    function server_compatibility()
    {
        $this->set('step_title', 'Server Compatibility');

        $response = $this->installer->check_compatibility();
        $this->set('compatibility', $response);
        //check php version
        //
        $this->load_view('server_compatibility');
    }

    function license_key()
    {
        $this->verify_requirements(__FUNCTION__);

        if ($purchase_code = $this->get_param('purchase_code')) {

            $result = $this->installer->is_valid_purchase_code($purchase_code);

            if ($result == true) {
                $this->set('verification_result', true);
            } else $this->set('verification_result', false);
        }

        $this->set('step_title', 'Purchase Verification');
        $this->load_view('license_key');
    }

    function folder_permissions()
    {
        $this->verify_requirements(__FUNCTION__);

        if ($check_permissions = $this->get_param('check_permissions')) {
            $result = $this->installer->check_folder_permissions();
            $result = $result->get_data();

            $this->set('permissions_result', $result['result'] == true);
            $this->set('config_folder_result', $result['config_folder']);
            $this->set('file_folder_result', $result['file_folder']);
            $this->set('tmp_folder_result', $result['tmp_folder']);
            $this->set('language_combined_result', $result['language_combined_result']);
            $this->set('language_folder_result', $result['language_folder_result']);
            $this->set('build_folder_result', $result['build_folder_result']);
            $this->set('build_file_result', $result['build_file_result']);
        }

        $this->set('step_title', 'Folder Permissions');
        $this->load_view('folder_permissions');
    }

    function database()
    {
        $this->verify_requirements(__FUNCTION__);

        $db_name = $this->get_param('database_name');
        $db_host = $this->get_param('database_hostname');
        $db_user = $this->get_param('database_username');
        $db_pass = $this->get_param('database_password');

        //no need to check the db_pass variable because it can be empty - i.e. default wamp install
        if ($db_name != false && $db_host != false && $db_user != false) {

            //if no password was supplied then assume the user's password is the empty string.
            if($db_pass == false)
                $db_pass = '';

            $response = $this->installer->connect_to_database($db_name, $db_host, $db_user, $db_pass);

            //make sure we can connect to the databse
            if (!$response->ok()) {
                //we were unable to connect
                $this->set('result', 'error');
                $this->set('result_data', $response->get_data());
            } else {

                if (!$this->installer->is_upgrade()) //the connection succeeded so let's create the database
                    $response = $this->installer->create_database();
                else $response = $this->installer->update_database();

                //check to see if the database was created ok
                if ($response->ok()) {
                    $this->set('result', 'success');
                    $this->set('result_data', null);
                } else {
                    //there was an error creating the database
                    $this->set('result', 'error');
                    $this->set('result_data', $response->get_data());
                }
            }
        }

        $this->set('step_title', 'Create Database');
        $this->load_view('database');
    }


    function main_config()
    {
        $this->verify_requirements(__FUNCTION__);

        $base_url = $this->get_param('base_url');
        $name = $this->get_param('name');
        $email = $this->get_param('email');
        $address1 = $this->get_param('address1');
        $address2 = $this->get_param('address2');
        $phone = $this->get_param('phone');
        $website = $this->get_param('website');

        if ($base_url != false && $name != false && $email != false) {
            $this->installer->set_config(array(
                'base_url' => $base_url,
                'company_name' => $name,
                'company_email' => $email,
                'company_address1' => $address1,
                'company_address2' => $address2,
                'company_phone' => $phone,
                'company_website' => $website
            ));


            $next_step = $this->get_param('next_step');
            $this->installer->complete_step('main_config');

            $this->go_to_next_step();
        }

        $this->set('step_title', 'Company Info');
        $this->load_view('main_config');
    }


    function yes_no_to_boolean($yes_no)
    {
        if ($yes_no == 'yes')
            return 'true';
        else return 'false';
    }

    function get_database_name()
    {
        $name = $this->installer->get_config('database.name');

        if (!$name){
            $name = $this->installer->get_config('database_name');

            if(!$name)
                $name = $this->installer->get_default_database_name();
        }

        echo $name;
    }

    function get_currency_symbol(){
        $symbol = $this->installer->get_config('currency_symbol');
        if(!$symbol)
            $symbol = '$';

        echo $symbol;
    }

    function get_config($name)
    {
        $val = $this->installer->get_config($name);
        echo $val !== false ? $val : '';
    }

    function client_access()
    {
        $this->verify_requirements(__FUNCTION__);
        $enable_client_access = $this->get_param('enable_client_access');
        $send_client_emails = $this->get_param('send_client_emails');



        if (isset($_POST['enable_client_access']) && $enable_client_access != false) {

            //we have a value for whether or not we should enable client access, but we need a value for
            //disable client acces, the opposite. The value is currently in yes/no format, so we can't use !$enable_client_access
            //to get the opposite. Let's switch it now
            $disable_client_access = $enable_client_access == 'yes' ? 'no' : 'yes';

            //convert the yes, no values to booleans (the string representation of a boolean) i.e. the text 'true'
            $disable_client_access = $this->yes_no_to_boolean($disable_client_access);

            $send_client_emails = $this->yes_no_to_boolean($send_client_emails);

            $this->installer->set_config('disable_client_access', $disable_client_access);
            $this->installer->set_config('send_client_emails', $send_client_emails);
            $this->installer->complete_step('enable_client_access');

            $this->go_to_next_step();
        }

        $this->set('step_title', 'Client Access');
        $this->load_view('client_access');
    }

    function advanced_config()
    {
        $this->verify_requirements(__FUNCTION__);


        $this->set('step_title', 'Advanced Configuration');
        $this->load_view('advanced_config');
    }

    function payments()
    {
        $this->verify_requirements(__FUNCTION__);

        $is_submitted = $this->get_param('is_submitted');

        if ($is_submitted == 1) {

            $currency_symbol = $this->get_param('currency_symbol');
            $payment_method = $this->get_param('payment_method');

            $this->installer->set_config('currency_symbol', $currency_symbol);
            $this->installer->set_config('payment_method', $payment_method);

            if ($payment_method != 'none') {
                if ($payment_method == 'paypal') {
                    $business_email = $this->get_param('business_email');
                    $currency_code = $this->get_param('currency_code');
                    $language_code = $this->get_param('language_code');

                    $this->installer->set_config('business_email', $business_email);
                    $this->installer->set_config('currency_code', $currency_code);
                    $this->installer->set_config('language_code', $language_code);
                } else if ($payment_method == 'stripe') {
                    $secret_key = $this->get_param('secret_key');
                    $publishable_key = $this->get_param('publishable_key');

                    $this->installer->set_config('secret_key', $secret_key);
                    $this->installer->set_config('publishable_key', $publishable_key);
                }

            }


            $this->go_to_next_step();
        }

        $this->set('step_title', 'Payments');
        $this->load_view('payments');
    }

    function finish()
    {
        $this->verify_requirements(__FUNCTION__);

        $this->installer->build_config();
        $this->installer->build_language_file();

        $this->set('step_title', 'Installation Complete');
        $this->load_view('finish');
    }


    function please_complete_step($step_number, $step_name)
    {
        $this->set('step_title', $step_name);
        $this->set('step_name', $step_name);
        $this->set('step_to_complete', $step_number);
        $this->load_view('please-complete-step');
        exit;
    }

    function verify_requirements($step_name)
    {
        $for_this_step = $this->step_number($step_name);


        if ($for_this_step > 2) {
            if (!$this->installer->is_step_completed('compatibility_verified')) {
                $this->please_complete_step(2, 'Server Compatibility');
            }
        }

        if ($for_this_step > 3) {
            if (!$this->installer->is_step_completed('license_verified')) {
                $this->please_complete_step(3, 'License Key');
            }
        }

        if ($for_this_step > 4) {
            if (!$this->installer->is_step_completed('folder_permissions')) {
                $this->please_complete_step(4, 'Folder Permissions');
            }
        }

        if ($for_this_step > 5) {
            if (!$this->installer->is_step_completed('database_created')) {
                $this->please_complete_step(5, 'Database');
            }
        }

        if ($for_this_step > 6) {
            if (!$this->installer->is_step_completed('main_config')) {
                $this->please_complete_step(6, 'Main Config');
            }
        }
    }


//    function step3()
//    {
//        $installer = new Installer();
//        $installer->set_sql_file('resources/duet.sql');
//        $installer->load_sql_file();
//        $installer->database_hostname = 'localhost';
//        $installer->database_user = 'root';
//        $installer->database_password = 'fL0und3R';
//        $installer->database_name = 'duet_test';
//        $response = $installer->run_sql_queries();
//    }

}