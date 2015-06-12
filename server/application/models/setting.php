<?php

class Setting extends Model{

    function get($criteria = null){

        //todo:lang file
        $settings['debugging'] = array(
            'enable_debugging' => Language::get('settingsOverview.enableDebugging')
        );

        $settings['general'] =  array(
            'base_url' => Language::get('settingsOverview.baseUrl'),
            'language' => Language::get('settingsOverview.language'),
            'datepicker_format' => Language::get('settingsOverview.datepickerFormat'),
            'invoice_date_format' => Language::get('settingsOverview.invoiceDateFormat')
        );

        $settings['Company Details'] = array(
            'company.name' => Language::get('settingsOverview.companyName'),
            'company.address1' => Language::get('settingsOverview.companyAddress1'),
            'company.address2' => Language::get('settingsOverview.companyAddress2'),
            'company.email' => Language::get('settingsOverview.companyEmail'),
            'company.phone' => Language::get('settingsOverview.companyPhone'),
            'company.website' => Language::get('settingsOverview.companyWebsite'),
            'company.logo' => Language::get('settingsOverview.companyLogo')
        );

        $settings['Email Settings'] = array(
            'email.use_smtp' => Language::get('settingsOverview.emailUseSmtp'),
            'email.host' => Language::get('settingsOverview.emailHost'),
            'email.port' => Language::get('settingsOverview.emailPort'),
            'email.enable_authentication' => Language::get('settingsOverview.emailEnableAuthentication'),
            'email.username' => Language::get('settingsOverview.emailUsername'),
            'email.password' => Language::get('settingsOverview.emailPassword'),
            'email.enable_encryption' => Language::get('settingsOverview.emailEnableEncryption')
        );

        $settings['Invoice Settings'] = array(
            'invoice.base_invoice_number' => Language::get('settingsOverview.invoiceBaseInvoiceNumber'),
            'invoice.tax_rate' => Language::get('settingsOverview.invoiceTaxRate')
        );

        $settings['Upload Settings'] = array(
            'uploads.allow_client_uploads' => Language::get('settingsOverview.uploadsAllowClientUploads')
        );

        $settings['Currency & Payments'] = array(
               'currency_symbol' => Language::get('settingsOverview.currencySymbol'),
               'payments.method' => Language::get('settingsOverview.paymentsMethod'),
               'payments.is_sandbox' => Language::get('settingsOverview.paymentsIsSandbox')
        );

        $settings['Client Access'] = array(
            'disable_client_access' => Language::get('settingsOverview.disableClientAccess'),
            'email.send_client_emails' => Language::get('settingsOverview.emailSendClientEmails')
        );





        $settings_array = array();
        foreach($settings as $setting_section_name => $setting_section){

            $values = array();
            foreach($setting_section as $setting_key => $setting_description){
                $value = get_config($setting_key);

                //handle booleans
                if($value === true)
                    $value = 'Yes';
                if($value === false)
                    $value = 'No';

                $values[] = array(
                    'description' => $setting_description,
                    'value' => $value
                );
            }

            $settings_array[] = array(
                'section_name' => $setting_section_name,
                'values' => $values
            );
        }


        return array('settings'=>$settings_array);
    }
}