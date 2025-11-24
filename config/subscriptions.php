<?php

return [

    /*
    |-------------------
    | Default Notification Subscriptions
    |----------------------
    |
    | This file is for configuring the default notification subscriptions for new users.
    |
    */

    'defaults' => [
        \App\Models\Subscription::MEDIUM_EMAIL => [
            \App\Models\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME,
            \App\Models\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED,
            //\App\Models\Subscription::EVENT_MY_TEAM_MENTIONED,
            \App\Models\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY,
            \App\Models\Subscription::EVENT_USER_REPLIED_TO_MY,
        ],
        \App\Models\Subscription::MEDIUM_BROWSER => [
            \App\Models\Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME,
            \App\Models\Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED,
            //\App\Models\Subscription::EVENT_MY_TEAM_MENTIONED,
            \App\Models\Subscription::EVENT_CUSTOMER_REPLIED_TO_MY,
            \App\Models\Subscription::EVENT_USER_REPLIED_TO_MY,
        ],
    ],

];
