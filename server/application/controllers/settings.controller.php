<?php

class SettingsController extends Controller{
    function get($id = null){
        $current_user = current_user();

        if(!$current_user->is('admin'))
            Response()->not_authorized();

        $settings = new Setting();

        Response($settings->get());
    }
}
