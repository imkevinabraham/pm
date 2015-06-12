<?php

class AppEmail extends Email
{
    protected $user;

    function send($force = false)
    {
        $role = $this->get_role($this->user);

        //clients may not receive emails depending on settings
        if($role == 'client'){

            //if client access has been disabled there is no reason to send ANY emails (even forgot password)
            if(get_config('disable_client_access') == true)
                return false;
            else if (get_config('email.send_client_emails') !== true){
                //clients have access to the system, but client emails are turned off. However, some emails (i.e. forgot
                //password) still need to be sent. The force parameter allows this

                if ($force == true)
                    return parent::send();
                else {
                    return false;
                }
            }
        }
        else return parent::send();


        //determine whether we should send emails to clients
        if ($this->get_role($this->user) != 'admin' && get_config('email.send_client_emails') !== true) {
            //even if client emails are turned off, there are some emails (forgot password) that should be sent to everyone
            if ($force == true)
                return parent::send();
            else {
                return false;
            }
        } else return parent::send();
    }

    function get_role($user)
    {
        if (is_array($user))
            $role = $user['role'];
        else $role = $user->role;

        return $role;
    }

    function set_recipient($user)
    {
        $this->user = $user;

        if (is_array($user))
            $email = $user['email'];
        else $email = $user->email;

        parent::set_recipient($email);
    }

    function get_client_targets($client_id)
    {
        //todo:this gets all users, even admins. It shoudldnt I don't think.
        $current_user = current_user();
        if ($current_user->is('admin') || (current_user()->client_id == $client_id)) {
            $client = new Client($client_id);
            $users = $client->get_users();

            return $users;
        } else return array();
    }

    function get_admins()
    {
        $sql = "SELECT role_user.user_id, roles.name AS role, users.email, users.id
                FROM role_user
                LEFT JOIN users
                  ON users.id = role_user.user_id
                LEFT JOIN roles
                  ON role_user.role_id = roles.id
                WHERE role_user.role_id = 1";

        $result = $this->select($sql);

        return $result;
    }

    function get_agents($project_id){
        $sql = "SELECT role_user.user_id, roles.name AS role, users.email, users.id
                FROM role_user
                LEFT JOIN users
                  ON users.id = role_user.user_id
                LEFT JOIN roles
                  ON role_user.role_id = roles.id
                WHERE role_user.role_id = 3 AND users.id IN(SELECT user_id FROM user_project WHERE project_id = $project_id)";

        $result = $this->select($sql);

        return $result;



    }

    function get_all_other_users($client_id, $project_id)
    {
        $current_user = current_user();
        $clients = $this->get_client_targets($client_id);
        $admins = $this->get_admins();
        $agents = $this->get_agents($project_id);
        $users = array_merge($admins, $clients, $agents);

        foreach ($users as $key => $user) {
            if ($user['id'] == $current_user->id) {
                unset($users[$key]);
            }
        }

        return $users;
    }

    function send_invoice($client_id, $invoice)
    {
        $users = $this->get_client_targets($client_id);

        foreach ($users as $user) {
            //todo: this role id shouldn't be hardcoded, what if they change.
            if ($user['role_id'] == 2) {
                $this->set_recipient($user);
                $this->set_subject('email_subjects.new_invoice');


                $this->generate('new-invoice', get_object_vars($invoice));
                $this->send();
            }
        }

        return true;
    }

    function send_forgot_password($user, $params)
    {
        $this->set_recipient($user);
        $this->set_subject('email_subjects.forgot_password');

        $this->generate('forgot-password', $params);
        return $this->send(true);
    }

    function send_admin_regenerate_password($user, $params){
        $this->set_recipient($user);
        $this->set_subject('email_subjects.admin_send_password');

        $this->generate('admin-send-password', $params);

        return $this->send(true);
    }

    function send_changed_password($user)
    {
        $this->set_recipient($user);
        $this->set_subject('email_subjects.changed_password');
        $this->generate('changed-password');
        return $this->send(true);
    }

    function send_new_user($user, $params)
    {
        $this->set_recipient($user);
        $this->set_subject('email_subjects.new_account');
        $this->generate('new-user', $params);
        return $this->send(true);
    }

    function send_client_payment_notification($user, $params)
    {
        $this->set_recipient($user);
        $this->set_subject('email_subjects.client_payment');
        $this->generate('client-payment', $params);
        return $this->send();
    }

    function send_admin_payment_notifications($admins, $params)
    {
        foreach ($admins as $admin) {
            $this->set_recipient($admin);
            $this->set_subject('email_subjects.admin_payment');
            $this->generate('admin-payment', $params);
            $this->send();
        }
    }

    function send_message_notification($params)
    {
        $users = $this->get_all_other_users($params['client_id'], $params['project_id']);

        foreach ($users as $user) {
            $this->set_recipient($user);
            $this->set_subject('email_subjects.message');
            $this->generate('message', $params);
            $result = $this->send();
        }
    }

    function send_file_upload_notification($project, $files)
    {
        $users = $this->get_all_other_users($project->client_id, $project->id);

        $params = array(
            'project' => $project,
            'files' => $files,
            'base_url' => get_config('base_url')
        );

        foreach ($users as $user) {
            $this->set_recipient($user);
            $this->set_subject('email_subjects.uploaded_file');
            $this->generate('file', $params);
            $result = $this->send();
        }
    }

    function send_payment_notifications($client, $params)
    {
        $admins = $this->get_admins();
        $this->send_client_payment_notification($client, $params);
        $this->send_admin_payment_notifications($admins, $params);
    }

    function send_task_assignment($params)
    {
        $this->set_recipient($params['user']);
        $this->set_subject('email_subjects.task_assignment');
        $this->generate('task-assignment', $params);
        return $this->send();
    }


}