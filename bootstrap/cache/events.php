<?php return array (
  'App\\Providers\\EventServiceProvider' => 
  array (
    'App\\Events\\CustomerCreatedConversation' => 
    array (
      0 => 'App\\Listeners\\SendAutoReply',
    ),
    'App\\Events\\CustomerReplied' => 
    array (
    ),
    'App\\Events\\NewMessageReceived' => 
    array (
      0 => 'App\\Listeners\\HandleNewMessage',
    ),
  ),
  'Illuminate\\Foundation\\Support\\Providers\\EventServiceProvider' => 
  array (
    'App\\Events\\NewMessageReceived' => 
    array (
      0 => 'App\\Listeners\\HandleNewMessage@handle',
    ),
    'App\\Events\\CustomerCreatedConversation' => 
    array (
      0 => 'App\\Listeners\\SendAutoReply@handle',
    ),
  ),
);