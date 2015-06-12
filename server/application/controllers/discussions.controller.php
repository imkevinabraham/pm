<?php


class DiscussionController extends Controller{
    function load($reference_object, $reference_id){


        $entity = new $reference_object($reference_id);

        $this->check_authorization('read', $entity);

        $message = new Message();
        $messages = $message->get($reference_object, $reference_id);

        Response(array(
            'entity' => $entity,
            'messages'=>$messages
        ));
    }
}