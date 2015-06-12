<?php

Class InvoicesController extends Controller{
    function pdf(){

        $invoice = new Invoice();

        $result = $invoice->pdf();

        Response($result);
        //we check access in the upload function

    }

    function download($name){
        $invoice = new Invoice();

        $result = $invoice->download($name);
    }


    function force_delete(){
        if(!current_user()->is('admin'))
            Response()->not_authorized();

        $invoice = new Invoice();
        $result = $invoice->force_delete(Request::param('invoice_number'));

        Response($result);
    }


}

 
