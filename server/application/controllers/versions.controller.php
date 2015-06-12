<?php


class VersionsController extends Controller{
    function get_latest(){
        $latest_version = App::get_latest_version();

        Response($latest_version);
    }

}
