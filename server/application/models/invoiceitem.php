<?php

use phpSweetPDO\SQLHelpers\Basic as Helpers;

Class InvoiceItem extends Model
{
    public $item;
    public $quantity;
    public $rate;
    public $subtotal;
    public $invoice_id;
    public $task;
    public $task_id;

    function validate(){
        $this->validator_tests = array(
            'item' => 'required'
        );

        return parent::validate();
    }

    function get($invoice_id = null){
        //nothing to do if we don't have an invoice item
        if($invoice_id == null)
            return false;

        $sql = "SELECT * FROM invoice_items WHERE invoice_id = " . $invoice_id;
        return parent::get($sql);
    }

    function save(){
        $this->import_parameters();

        $subtotal = round((float)$this->rate * (float)$this->quantity, 2);
        $this->set('subtotal', $subtotal);

        if(!isset($this->rate))
            $this->set('rate', 0);

        if (!isset($this->quantity))
            $this->set('quantity', 0);

        return parent::save();
    }

    function delete(){
        $this->import_parameters();

        //if the id isn't set, then this item hasn't been saved the database yet so there would be nothing to do
        if(isset($this->id)){
            $result = parent::delete();

            //the total will change on this invoice since we're deleting an item
            $invoice = new Invoice($this->invoice_id);
            $invoice->calculate_total(true);

            return $result;
        }
        else return false;
    }

    function set_subtotal(){}
}
 
