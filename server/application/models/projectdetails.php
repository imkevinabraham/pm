<?php

class ProjectDetails extends Model{
    public $activity;
    public $project;
    public $task_counts;
    public $total_time;
    public $invoice_stats;
    public $people;


    function get($project_id = null){
        //there's nothing to do if we don't have a project id
        if($project_id == null)
            return false;

        $this->project = new Project($project_id);

        //if the project doesn't exist, there's nothing for us to do  here
        if(!$this->project->is_valid())
            return false;

        $total_time = $this->project->get_total_time();

        $invoice_stats = $this->project->get_invoice_stats();

        $activity = new Activity();
        $activity = $activity->get('WHERE project_id = ' . $project_id);

        $task_counts = $this->project->get_task_counts();


        $this->project->update_status($task_counts);

        $project_details = array(
            'project' => $this->project->to_array(),
            'people' => $this->project->get_people(),
            'activity' => $activity,
            'task_counts' => $task_counts,
            'total_time' => $total_time,
            'invoice_stats' => $invoice_stats
        );

        return $project_details;
    }

    //project details is a collection of other models. There is nothing to save, so let's prevent the base model save
    //from being called accidentally
    function save(){}

    function current_user_can_access(){
        $user = current_user();

        //this->project is now an array, because of the call to to_array in the get function
        if($user->role == 'admin')
            return true;
        else if ($user->role == 'client' && $user->client_id == $this->project['client_id'])
            return true;
        else if($user->is('agent')){
            //$this->project currently contains an array (because of the call to $this->project->to_array() in the get function
            //we need the project object
            $project = new Project($this->project);
            return $user->can_access($project);
        }
        else return false;
    }
}