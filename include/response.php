<?php

class GS_ADMIN_SDK_Response{
  public $data = null;

  function __construct($response_data){
    $this->succeeded = $response_data['success'];
    $this->success =& $this->succeeded;
    $this->successful =& $this->succeeded;
    if(!$response_data['success']){
      $this->errors = new stdClass();
      foreach($response_data['errors'] as $name=>$errorset){
        $this->errors->$name = null;
        foreach($errorset as $errname=>$err){
          if(is_array($err)){
            if(!$this->errors->$name)$this->errors->$name = array();
            $err2 = new stdClass();
            $err2->message = $err['message'];
            $err2->code = $err['code'];
            $this->errors->{$name}[] = $err2;
          }else{
            if($errname=='')$errname = 'system';
            if(!$this->errors->$name)$this->errors->$name = new stdClass();
            $this->errors->{$name}->{$errname} = $err;
          }
        }
      }
    }
    elseif(is_array($response_data['data'])){
      $this->data = array();
     foreach($response_data['data'] as $k => $v){
        $this->data[$k] = $v;
      } 
    }
  }
}
