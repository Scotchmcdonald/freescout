<?php return array (
  'App\\Providers\\EventServiceProvider' => 
  array (
    'Illuminate\\Auth\\Events\\Registered' => 
    array (
      0 => 'App\\Listeners\\LogRegisteredUser',
    ),
    'Illuminate\\Auth\\Events\\Login' => 
    array (
      0 => 'App\\Listeners\\RememberUserLocale',
      1 => 'App\\Listeners\\LogSuccessfulLogin',
    ),
    'Illuminate\\Auth\\Events\\Failed' => 
    array (
      0 => 'App\\Listeners\\LogFailedLogin',
    ),
    'Illuminate\\Auth\\Events\\Logout' => 
    array (
      0 => 'App\\Listeners\\LogSuccessfulLogout',
    ),
    'Illuminate\\Auth\\Events\\Lockout' => 
    array (
      0 => 'App\\Listeners\\LogLockout',
    ),
    'Illuminate\\Auth\\Events\\PasswordReset' => 
    array (
      0 => 'App\\Listeners\\LogPasswordReset',
      1 => 'App\\Listeners\\SendPasswordChanged',
    ),
    'App\\Events\\UserDeleted' => 
    array (
      0 => 'App\\Listeners\\LogUserDeletion',
    ),
    'App\\Events\\ConversationStatusChanged' => 
    array (
      0 => 'App\\Listeners\\UpdateMailboxCounters',
    ),
    'App\\Events\\ConversationUserChanged' => 
    array (
      0 => 'App\\Listeners\\UpdateMailboxCounters',
      1 => 'App\\Listeners\\SendNotificationToUsers',
    ),
    'App\\Events\\UserReplied' => 
    array (
      0 => 'App\\Listeners\\SendReplyToCustomer',
      1 => 'App\\Listeners\\SendNotificationToUsers',
    ),
    'App\\Events\\CustomerReplied' => 
    array (
      0 => 'App\\Listeners\\SendNotificationToUsers',
    ),
    'App\\Events\\UserCreatedConversation' => 
    array (
      0 => 'App\\Listeners\\SendReplyToCustomer',
      1 => 'App\\Listeners\\SendNotificationToUsers',
    ),
    'App\\Events\\CustomerCreatedConversation' => 
    array (
      0 => 'App\\Listeners\\SendAutoReply',
      1 => 'App\\Listeners\\SendNotificationToUsers',
    ),
    'App\\Events\\UserAddedNote' => 
    array (
      0 => 'App\\Listeners\\SendNotificationToUsers',
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
    'Illuminate\\Auth\\Events\\PasswordReset' => 
    array (
      0 => 'App\\Listeners\\SendPasswordChanged@handle',
      1 => 'App\\Listeners\\LogPasswordReset@handle',
    ),
    'App\\Events\\CustomerCreatedConversation' => 
    array (
      0 => 'App\\Listeners\\SendAutoReply@handle',
      1 => 'App\\Listeners\\SendNotificationToUsers@handle',
    ),
    'Illuminate\\Auth\\Events\\Login' => 
    array (
      0 => 'App\\Listeners\\LogSuccessfulLogin@handle',
      1 => 'App\\Listeners\\RememberUserLocale@handle',
    ),
    'Illuminate\\Auth\\Events\\Failed' => 
    array (
      0 => 'App\\Listeners\\LogFailedLogin@handle',
    ),
    'App\\Events\\UserReplied' => 
    array (
      0 => 'App\\Listeners\\SendNotificationToUsers@handle',
      1 => 'App\\Listeners\\SendReplyToCustomer@handle',
    ),
    'App\\Events\\UserAddedNote' => 
    array (
      0 => 'App\\Listeners\\SendNotificationToUsers@handle',
    ),
    'App\\Events\\UserCreatedConversation' => 
    array (
      0 => 'App\\Listeners\\SendNotificationToUsers@handle',
      1 => 'App\\Listeners\\SendReplyToCustomer@handle',
    ),
    'App\\Events\\ConversationUserChanged' => 
    array (
      0 => 'App\\Listeners\\SendNotificationToUsers@handle',
      1 => 'App\\Listeners\\UpdateMailboxCounters@handle',
    ),
    'App\\Events\\CustomerReplied' => 
    array (
      0 => 'App\\Listeners\\SendNotificationToUsers@handle',
    ),
    'Illuminate\\Auth\\Events\\Logout' => 
    array (
      0 => 'App\\Listeners\\LogSuccessfulLogout@handle',
    ),
    'Illuminate\\Auth\\Events\\Lockout' => 
    array (
      0 => 'App\\Listeners\\LogLockout@handle',
    ),
    'App\\Events\\ConversationStatusChanged' => 
    array (
      0 => 'App\\Listeners\\UpdateMailboxCounters@handle',
    ),
    'App\\Events\\UserDeleted' => 
    array (
      0 => 'App\\Listeners\\LogUserDeletion@handle',
    ),
    'Illuminate\\Auth\\Events\\Registered' => 
    array (
      0 => 'App\\Listeners\\LogRegisteredUser@handle',
    ),
  ),
);