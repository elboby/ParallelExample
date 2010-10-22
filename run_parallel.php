<?php
require('./include.php');

$array_response = array();

//callback for responses
function process_responses($msg)
{
  global $ch_response, $array_response;
  
  //acknowledge the message
  $ch_response->basic_ack($msg->delivery_info['delivery_tag']);
    
  //processing reply
  $array = unserialize($msg->body);
  if(is_array($array) && isset($array['type']) && isset($array['data']))
  {
    $array_response[ $array['type'] ] = $array['data'];
  }
}

//create the channel where the response will be sent. this channel is unique for this execution of the php script
$queue_response = "PHPPROCESS_".getmypid();
$ch_response = createMQChannel($queue_response);

//start the timer
$start_time = microtime(true);
echo date('H:i:s')." : ".$queue_response." : waiting for responses...\n";

//create the 2 channels where the consumers will do the task in parallel
$ch_a = createMQChannel($QUEUE."_A");
$ch_b = createMQChannel($QUEUE."_B");

//send the tasks to both consumers
publishMessage($ch_a, $QUEUE."_A", serialize(array('data'=>'10', 'qresponse'=>$queue_response)));
publishMessage($ch_b, $QUEUE."_B", serialize(array('data'=>'5', 'qresponse'=>$queue_response)));


//collect the responses sent by both consumers
$ch_response->basic_consume($queue_response, $CONSUMER_TAG, false, false, false, false, 'process_responses');
while(count($ch_response->callbacks)) 
{
  //when we got our 2 responses we go out
  if(count($array_response) == 2) break;
  $ch_response->wait();
}

//here is our response collected from both consumers
var_dump($array_response);

//timer ends
$time = microtime(true) - $start_time;
echo date('H:i:s')." : ".$queue_response." : done in $time seconds\n";

//clean up opened channels
$ch_a->close();
$ch_b->close();
$ch_response->close();
$conn->close();
