<?php

class Dashboard extends Model
{
    protected $projects;
    protected $activity;
    protected $invoices;

    function get($criteria = null){
        $user = current_user();
        $projects = new Project();
        $activity = new Activity();
        $invoice = new Invoice();

        $this->projects = $projects->get("WHERE projects.is_archived = 0");
        $this->activity = $activity->get();

        $outstanding_invoices = $invoice->get("WHERE invoices.is_paid = 0");
        $overdue_invoices = $invoice->get("WHERE invoices.is_paid = 0 AND invoices.due_date <= " . time());

        $outstanding_total = 0;
        $overdue_total = 0;

        foreach($outstanding_invoices as $invoice){
            $outstanding_total += $invoice['total'];
        }

        foreach($overdue_invoices as $invoice){
            $overdue_total += $invoice['total'];
        }

        $this->invoices = array(
            'outstanding' => $outstanding_invoices,
            'outstanding_total' => $outstanding_total,
            'overdue' => $overdue_invoices,
            'overdue_total' => $overdue_total
        );
    }



    function current_user_can_access(){
        return true;
    }
}