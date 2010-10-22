<?php
require('./include.php'); 

//type of the queue (a or b)
if((count($argv) != 2) || ($argv[1]!='a' && $argv[1]!='b')) die('to use specify a type a or b like this: "php consume.php a" or "php consume.php b"');
$type = $argv[1];

//callback for responses
function process_message($msg) 
{
    global $ch, $type;
    
    //acknowledge the message
    $ch->basic_ack($msg->delivery_info['delivery_tag']);
    
    //retrieve the message
    $body = unserialize($msg->body);    
    //some logging
    echo date('H:i:s').": ".$type." : ".$body['qresponse']." : start processing...\n";
    
    //simple logic to simulate heavy processing
    $response = "waited ".$body['data']."seconds";
    sleep($body['data']);
    
    //send response back to the sender
    $ch_response = createMQChannel($body['qresponse']);
    publishMessage($ch_response, $body['qresponse'], serialize(array('data'=>$response, 'type'=>$type)));
    $ch_response->close();
    
    //u know u need some logging
    echo date('H:i:s').": ".$type." : ".$body['qresponse']." : done in ".$body['data']." seconds!\n";
}

//consume jobs from the right queue
$queue = $QUEUE."_".strtoupper($type);
$ch = createMQChannel($queue);
$ch->basic_consume($queue, $CONSUMER_TAG, false, false, false, false, 'process_message');

// Loop as long as the channel has callbacks registered
while(count($ch->callbacks)) 
{
    $ch->wait();
}

//clean up open connection
$ch->close();
$conn->close();

