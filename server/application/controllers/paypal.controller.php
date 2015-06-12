<?php

class PaypalController extends Controller{
    function get_button(){

        $invoice = new Invoice($_POST['invoice_id']);

        $this->check_authorization('read', $invoice);

        //todo:make sure user is owner of invoice;
        $paypal = new PaypalPayment();
        $paypal->amount = $_POST['amount'];
        $button = $paypal->generate_submission_button($invoice);

        Response($button);
    }

    function ipn_listener(){
        //todo:public
        $paypal = new PaypalPayment();
        $paypal->process_ipn();
    }
}