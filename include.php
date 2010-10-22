<?php
//include the PHP-AMQP library
require_once('./php-amqplib/amqp.inc');

$HOST = 'localhost';
$PORT = 5672;
$USER = 'guest';
$PASS = 'guest';
$VHOST = '/';
$EXCHANGE = 'router';
$QUEUE = 'msgs';
$CONSUMER_TAG = 'consumer';

//create main connection to rabbitmq server
$conn = new AMQPConnection($HOST, $PORT, $USER, $PASS);

//simple function to create a channel
function createMQChannel($queue)
{
  global $conn, $VHOST, $EXCHANGE;
  
  $ch = $conn->channel();
  $ch->access_request($VHOST, false, false, true, true);
  $ch->exchange_declare($EXCHANGE."_".$queue, 'direct', false, false, false);
  $ch->queue_declare($queue);
  $ch->queue_bind($queue, $EXCHANGE."_".$queue);

  return $ch;
}

//simple function to publish messages
function publishMessage($ch, $queue, $msg_body)
{
  global $EXCHANGE;
  
  $msg = new AMQPMessage($msg_body, array('content_type' => 'text/plain'));
  $ch->basic_publish($msg, $EXCHANGE."_".$queue);
}
