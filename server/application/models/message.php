<?php
use phpSweetPDO\SQLHelpers\Basic as Helpers;

Class Message extends Model {
    public $message;
    public $reference_object;
    public $reference_id;
    public $project_id;
    public $client_id;
    protected $user_id;
    protected $created_date;

    //not saved to the db
    public $linked_object_title;
    public $linked_object;

    function validate() {
        $this->validator_tests = array(
            'message' => 'required',
            'reference_object' => 'required',
            'reference_id' => 'required'
        );

        return parent::validate();
    }

    function save() {

        $this->import_parameters();

        $this->load_library('htmlpurifier-4.5.0-lite/library/HTMLPurifier.auto');

        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);

        $message = $purifier->purify(html_entity_decode($this->message));


        $this->set('message', $message);

        $reference_object = new $this->reference_object($this->reference_id);

        //if the message is being created for an object other than a project, then the project id will be retrieved from
        //the actual object
        //if the message is being posted on a project, then the project id is the messages reference_id
        if ($this->reference_object != 'project')
            $project_id = isset($reference_object->project_id) ? $reference_object->project_id : false;
        else $project_id = $this->reference_id;

        if ($project_id)
            $this->set('project_id', $project_id);

        if (isset($reference_object->client_id))
            $this->set('client_id', $reference_object->client_id);


        $current_user = current_user();
        $this->set('user_id', $current_user->id);

        //these two parameters shouldn't be set yet (they are set when we log activity which happens after the save),
        //but let's just make sure
        $this->unset_param('linked_object');
        $this->unset_param('linked_object_title');

        $result = parent::save();

        ActivityManager::message_created($this);

        $email = new AppEmail();
        $email->send_message_notification(array_merge($this->to_array(), array(
            'linked_object_title' => $this->linked_object_title,
            'linked_object_url' => Request::param('message_url'),
            'linked_object_type' => strtolower(get_class($this->linked_object)),
            'posted_by' => $current_user->first_name

        )));

        return $result;
    }


    function get($reference_object_or_id = null, $reference_id = null) {
        if (is_numeric($reference_object_or_id) && !isset($reference_id))
            return $this->get_specific_message($reference_object_or_id);
        else return $this->get_messages_for_object($reference_object_or_id, $reference_id);

    }

    function get_messages_for_object($reference_object = null, $reference_id = null) {
        //there's nothing to do if we don't have a reference object
        if ($reference_object == null || $reference_id == null)
            return false;

        //make sure the user has access to the model that they are trying to get messages for
        $model = new $reference_object($reference_id);
        if (!current_user()->can_access($model))
            return false;


        $reference_table = $reference_object . "s";
        $name_field = $this->name_field($reference_object);


        $sql = "SELECT messages.*, CONCAT(users.first_name, ' ', users.last_name) AS user_name, $reference_table.$name_field AS entity_name
                FROM messages
                LEFT JOIN users ON messages.user_id = users.id
                LEFT JOIN $reference_table ON messages.reference_id = $reference_table.id
                WHERE reference_object = '$reference_object' AND reference_id = '$reference_id'";

        //if we're looking at the discussion for a project, we also want to view all of the messages that are
        //posted on each of hte items in the project
        if ($reference_object == 'project')
            $sql .= " OR messages.project_id = $reference_id";


        $messages = parent::get($sql);

        User::set_profile_images($messages);

        $this->get_entity_names_for_messages_posted_on_child_objects($messages);

        $this->get_files_attached_to_messages($messages);

        return $messages;
    }


    function get_entity_names_for_messages_posted_on_child_objects($messages) {
        if (count($messages) == 0)
            return;

        //we need the ids for ALL of the messages so we can get all of the files for a discussion in one query
        $messages_on_tasks = array();
        $messages_on_files = array();
        $messages_on_invoices = array();

        $entity_names = array();
        foreach ($messages as $message) {
            if ($message->reference_object == 'task')
                $messages_on_tasks[] = $message['id'];
            if ($message->reference_object == 'file')
                $messages_on_files[] = $message['id'];
            if ($message->reference_object == 'invoice')
                $messages_on_invoices[] = $message['id'];
        }


        if (count($messages_on_tasks) > 0) {
            $sql = "SELECT messages.id, tasks.task AS child_entity_name
                FROM messages
                LEFT JOIN tasks
                  ON messages.reference_id = tasks.id
                WHERE messages.id IN(" . implode(',', $messages_on_tasks) . ")";

            $task_names = $this->select($sql);
            $entity_names = array_merge($entity_names, $task_names);
        }

        if (count($messages_on_files) > 0) {
            $sql = "SELECT messages.id, files.name AS child_entity_name
                FROM messages
                LEFT JOIN files
                  ON messages.reference_id = files.id
                WHERE messages.id IN(" . implode(',', $messages_on_files) . ")";

            $file_names = $this->select($sql);
            $entity_names = array_merge($entity_names, $file_names);
        }

        if (count($messages_on_invoices) > 0) {
            $sql = "SELECT messages.id, invoices.number AS child_entity_name
                FROM messages
                LEFT JOIN invoices
                  ON messages.reference_id = invoices.id
                WHERE messages.id IN(" . implode(',', $messages_on_invoices) . ")";

            $invoice_names = $this->select($sql);
            $entity_names = array_merge($entity_names, $invoice_names);
        }

        //currently we have an array in the form array
        //(0 => Object(id=>52, child_entity_name => 'some name'), 1 => Object(id=>53, child_entity_name => 'some other name'))
        //we need the entity names to be organized by the message id
        //array(52=>'some name', 53=>'some other name')
        $entity_names_by_message_id = array();
        foreach($entity_names as $entity_name){
            $entity_names_by_message_id[$entity_name->id] = $entity_name->child_entity_name;
        }

        //add the child entity name to each message
        foreach($messages as &$message){
            if(isset($entity_names_by_message_id[$message['id']]))
                $message['child_entity_name'] = $entity_names_by_message_id[$message['id']];
        }
    }

    function get_files_attached_to_messages($messages) {

        if (count($messages) == 0)
            return;

        //we need the ids for ALL of the messages so we can get all of the files for a discussion in one query
        $message_ids = array();
        foreach ($messages as $message) {
            $message_ids[] = $message['id'];
        }

        $sql = "SELECT files.id, files.name, files.entity_id AS message_id FROM files WHERE files.entity_type = 'message' AND files.entity_id IN(" . implode(',', $message_ids) . ")";
        $files = $this->select($sql);

        //organize the files by the message id they are associated with. This will result in an array of message_ids. Each
        //message id will have a child array that lists all the files for that message
        //array('26' => array(<files for this message>))
        $files_by_message_id = array();
        foreach ($files as $file) {
            $message_id = $file->message_id;

            if (!isset($files_by_message_id[$message_id]))
                $files_by_message_id[$message_id] = array();

            $files_by_message_id[$message_id][] = $file;
        }

        //loop through each message and attach its files
        foreach ($messages as &$message) {
            if (isset($files_by_message_id[$message->id]))
                $message->files = $files_by_message_id[$message->id];
        }
    }

    function get_specific_message($id) {
        $sql = "SELECT *
                FROM messages
                WHERE id = $id";

        return $this->get_one($sql);
    }


    function name_field($object) {
        if ($object == 'invoice')
            $name = 'number';
        else if ($object == 'task')
            $name = 'task';
        else $name = 'name';

        return $name;
    }

    function current_user_can_access() {
        $user = current_user();

        if ($this->is_new())
            return true;

        if ($user->role == 'admin' || $user->client_id == $this->client_id)
            return true;
        else return false;
    }
}
 
