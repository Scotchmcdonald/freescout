# Test Results Summary

**Date:** Sat Nov  8 21:26:07 UTC 2025

## PHPStan Bodyscan Results

### Error Count Summary

+-------+-------------+-----------+
|     0 |           0 |         - |
|     1 |           1 |       + 1 |
|     2 |           5 |       + 4 |
|     3 |           9 |       + 4 |
|     4 |          20 |      + 11 |
|     5 |          28 |       + 8 |
|     6 |          85 |      + 57 |
|     7 |          85 |         - |
|     8 |          85 |         - |
+-------+-------------+-----------+

### Detailed Errors by Level


**Note:** Bodyscan runs PHPStan in bare mode (no config ignores) to show all potential issues.

#### Level 6 - Detailed Errors (Bare Analysis)

```
 ------ ---------------------------------------------------------------- 
  Line   Console/Commands/ConfigureGmailMailbox.php                      
 ------ ---------------------------------------------------------------- 
  :31    Call to an undefined static method App\Models\Mailbox::find().  
         🪪  staticMethod.notFound                                       
 ------ ---------------------------------------------------------------- 

 ------ ------------------------------------------------------------------------ 
  Line   Console/Commands/FetchEmails.php                                        
 ------ ------------------------------------------------------------------------ 
  :39    Call to an undefined static method App\Models\Mailbox::where().         
         🪪  staticMethod.notFound                                               
  :40    Call to an undefined static method App\Models\Mailbox::whereNotNull().  
         🪪  staticMethod.notFound                                               
 ------ ------------------------------------------------------------------------ 

 ------ ------------------------------------------------------------------------------------------------------------------------ 
  Line   Events/ConversationUpdated.php                                                                                          
 ------ ------------------------------------------------------------------------------------------------------------------------ 
  :20    Method App\Events\ConversationUpdated::__construct() has parameter $meta with no value type specified in iterable type  
         array.                                                                                                                  
         🪪  missingType.iterableValue                                                                                           
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                              
 ------ ------------------------------------------------------------------------------------------------------------------------ 

 ------ --------------------------------------------------------------------------------------- 
  Line   Events/UserViewingConversation.php                                                     
 ------ --------------------------------------------------------------------------------------- 
  :56    Access to an undefined property App\Models\User::$id.                                  
         🪪  property.notFound                                                                  
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property  
  :58    Access to an undefined property App\Models\User::$email.                               
         🪪  property.notFound                                                                  
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property  
 ------ --------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------------- 
  Line   Http/Controllers/Auth/AuthenticatedSessionController.php                                                                  
 ------ -------------------------------------------------------------------------------------------------------------------------- 
  :19    Method App\Http\Controllers\Auth\AuthenticatedSessionController::create() should return Illuminate\View\View but returns  
         Illuminate\Contracts\View\View.                                                                                           
         🪪  return.type                                                                                                           
  :39    Call to an undefined method Illuminate\Contracts\Auth\Guard::logout().                                                    
         🪪  method.notFound                                                                                                       
 ------ -------------------------------------------------------------------------------------------------------------------------- 

 ------ ----------------------------------------------------------------------------------------------------------------------- 
  Line   Http/Controllers/Auth/ConfirmablePasswordController.php                                                                
 ------ ----------------------------------------------------------------------------------------------------------------------- 
  :19    Method App\Http\Controllers\Auth\ConfirmablePasswordController::show() should return Illuminate\View\View but returns  
         Illuminate\Contracts\View\View.                                                                                        
         🪪  return.type                                                                                                        
  :31    Access to an undefined property App\Models\User::$email.                                                               
         🪪  property.notFound                                                                                                  
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                                  
  :32    Access to an undefined property Illuminate\Http\Request::$password.                                                    
         🪪  property.notFound                                                                                                  
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                                  
 ------ ----------------------------------------------------------------------------------------------------------------------- 

 ------ ----------------------------------------------------------------------------------------------------------------- 
  Line   Http/Controllers/Auth/NewPasswordController.php                                                                  
 ------ ----------------------------------------------------------------------------------------------------------------- 
  :23    Method App\Http\Controllers\Auth\NewPasswordController::create() should return Illuminate\View\View but returns  
         Illuminate\Contracts\View\View.                                                                                  
         🪪  return.type                                                                                                  
 ------ ----------------------------------------------------------------------------------------------------------------- 

 ------ ----------------------------------------------------------------------------------------------------------------------- 
  Line   Http/Controllers/Auth/PasswordResetLinkController.php                                                                  
 ------ ----------------------------------------------------------------------------------------------------------------------- 
  :18    Method App\Http\Controllers\Auth\PasswordResetLinkController::create() should return Illuminate\View\View but returns  
         Illuminate\Contracts\View\View.                                                                                        
         🪪  return.type                                                                                                        
 ------ ----------------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   Http/Controllers/Auth/RegisteredUserController.php                                                                  
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :22    Method App\Http\Controllers\Auth\RegisteredUserController::create() should return Illuminate\View\View but returns  
         Illuminate\Contracts\View\View.                                                                                     
         🪪  return.type                                                                                                     
  :43    Call to an undefined static method App\Models\User::create().                                                       
         🪪  staticMethod.notFound                                                                                           
  :46    Access to an undefined property Illuminate\Http\Request::$email.                                                    
         🪪  property.notFound                                                                                               
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                               
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ ---------------------------------------------------------------------------------------------------------------------------- 
  Line   Http/Controllers/Auth/VerifyEmailController.php                                                                             
 ------ ---------------------------------------------------------------------------------------------------------------------------- 
  :25    Parameter #1 $user of class Illuminate\Auth\Events\Verified constructor expects Illuminate\Contracts\Auth\MustVerifyEmail,  
         App\Models\User given.                                                                                                      
         🪪  argument.type                                                                                                           
 ------ ---------------------------------------------------------------------------------------------------------------------------- 

 ------ ------------------------------------------------------------------------------------------------------------- 
  Line   Http/Controllers/ConversationController.php                                                                  
 ------ ------------------------------------------------------------------------------------------------------------- 
  :30    Access to an undefined property App\Models\User::$mailboxes.                                                 
         🪪  property.notFound                                                                                        
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                        
  :40    Method App\Http\Controllers\ConversationController::index() should return Illuminate\View\View but returns   
         Illuminate\Contracts\View\View.                                                                              
         🪪  return.type                                                                                              
  :52    Access to an undefined property App\Models\User::$mailboxes.                                                 
         🪪  property.notFound                                                                                        
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                        
  :80    No error to ignore is reported on line 80.                                                                   
  :86    Method App\Http\Controllers\ConversationController::show() should return                                     
         Illuminate\Http\RedirectResponse|Illuminate\View\View but returns Illuminate\Contracts\View\View.            
         🪪  return.type                                                                                              
  :98    Access to an undefined property App\Models\User::$mailboxes.                                                 
         🪪  property.notFound                                                                                        
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                        
  :106   No error to ignore is reported on line 106.                                                                  
  :112   Method App\Http\Controllers\ConversationController::create() should return Illuminate\View\View but returns  
         Illuminate\Contracts\View\View.                                                                              
         🪪  return.type                                                                                              
  :124   Access to an undefined property App\Models\User::$mailboxes.                                                 
         🪪  property.notFound                                                                                        
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                        
  :147   Call to an undefined static method App\Models\Customer::findOrFail().                                        
         🪪  staticMethod.notFound                                                                                    
  :168   No error to ignore is reported on line 168.                                                                  
  :175   Call to an undefined static method App\Models\Conversation::create().                                        
         🪪  staticMethod.notFound                                                                                    
  :178   Access to an undefined property App\Models\Folder::$id.                                                      
         🪪  property.notFound                                                                                        
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                        
  :189   Access to an undefined property App\Models\User::$id.                                                        
         🪪  property.notFound                                                                                        
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                        
  :194   Call to an undefined static method App\Models\Thread::create().                                              
         🪪  staticMethod.notFound                                                                                    
  :196   Access to an undefined property App\Models\User::$id.                                                        
         🪪  property.notFound                                                                                        
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                        
  :234   Access to an undefined property App\Models\User::$mailboxes.                                                 
         🪪  property.notFound                                                                                        
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                        
  :267   Access to an undefined property App\Models\User::$mailboxes.                                                 
         🪪  property.notFound                                                                                        
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                        
  :281   Call to an undefined static method App\Models\Thread::create().                                              
         🪪  staticMethod.notFound                                                                                    
  :283   Access to an undefined property App\Models\User::$id.                                                        
         🪪  property.notFound                                                                                        
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                        
```

*(Showing first ~40 errors of 85 total - run full bodyscan for complete list)*


## PHPStan Analyse Results

✅ PHPStan Analyse completed with no errors.
```
 [OK] No errors                                                                 
```

## PHP Artisan Test Results

### Test Summary

```
  Tests:    1 skipped, 973 passed (2415 assertions)
```



## Code Coverage Summary

### Overall Metrics

| Metric    | Coverage      | Covered / Total |
|-----------|---------------|-----------------|
| Lines     | **59.00%**    | 1656/2807          |
| Functions | **70.37%**|       |
| Classes   | **%**  |         |

[View Full Code Coverage Report](coverage-report/index.html)

### 🚨 Top Classes at Risk (Low Coverage)

| Class | Coverage |
|-------|----------|
| Http.Controllers.Auth.EmailVerificationNotificationController | 0% |
| Listeners.SendAutoReply | 0% |
| Providers.AppServiceProvider | 0% |
| Jobs.SendAutoReply | 1% |
| Events.NewMessageReceived | 4% |
| Services.ImapService | 6% |
| Mail.AutoReply | 14% |
| Events.ConversationUpdated | 30% |
| Events.UserViewingConversation | 30% |
| Mail.ConversationReplyNotification | 40% |
| Services.SmtpService | 40% |
| Http.Controllers.ModulesController | 45% |
| Models.Attachment | 60% |
| Policies.MailboxPolicy | 60% |
| Http.Controllers.UserController | 61% |

### ⚠️ Top Classes at Risk (High CRAP Scores)

| Class | CRAP Score |
|-------|------------|
| Services.ImapService | 16146 |
| Jobs.SendAutoReply | 202 |
| Services.SmtpService | 197 |
| Listeners.SendAutoReply | 182 |
| Mail.AutoReply | 87 |
| Http.Controllers.SystemController | 79 |
| Http.Controllers.ConversationController | 77 |
| Events.NewMessageReceived | 50 |
| Http.Controllers.ModulesController | 39 |
| Misc.MailHelper | 38 |
| Http.Controllers.UserController | 32 |
| Http.Controllers.SettingsController | 30 |
| Policies.MailboxPolicy | 26 |
| Http.Controllers.CustomerController | 16 |
| Models.User | 15 |

### 🔍 Top Methods at Risk (Low Coverage)

| Method | Coverage |
|--------|----------|
| Events.ConversationUpdated::broadcastAs | 0% |
| Events.ConversationUpdated::broadcastWith | 0% |
| Events.NewMessageReceived::broadcastOn | 0% |
| Events.NewMessageReceived::broadcastAs | 0% |
| Events.NewMessageReceived::broadcastWith | 0% |
| Events.UserViewingConversation::broadcastAs | 0% |
| Events.UserViewingConversation::broadcastWith | 0% |
| Http.Controllers.Auth.EmailVerificationNotificationController::store | 0% |
| Http.Controllers.ConversationController::upload | 0% |
| Http.Controllers.ConversationController::clone | 0% |
| Http.Controllers.SettingsController::validateSmtp | 0% |
| Http.Controllers.UserController::ajax | 0% |
| Jobs.SendAutoReply::handle | 0% |
| Jobs.SendAutoReply::failed | 0% |
| Listeners.SendAutoReply::handle | 0% |
| Mail.AutoReply::content | 0% |
| Mail.AutoReply::build | 0% |
| Mail.ConversationReplyNotification::content | 0% |
| Mail.ConversationReplyNotification::attachments | 0% |
| Misc.MailHelper::generateMessageId | 0% |

### ⚡ Top Methods at Risk (High CRAP Scores)

| Method | CRAP Score |
|--------|------------|
| Services.ImapService::processMessage | 4830 |
| Services.ImapService::getAddressesWithNames | 272 |
| Services.SmtpService::validateSettings | 210 |
| Listeners.SendAutoReply::handle | 182 |
| Services.ImapService::parseAddresses | 182 |
| Jobs.SendAutoReply::handle | 156 |
| Services.ImapService::testConnection | 132 |
| Services.ImapService::fetchEmails | 72 |
| Services.ImapService::separateReply | 72 |
| Http.Controllers.UserController::ajax | 42 |
| Mail.AutoReply::build | 42 |
| Http.Controllers.SystemController::ajax | 26 |
| Events.NewMessageReceived::broadcastOn | 20 |
| Models.Customer::create | 19 |
| Http.Controllers.ConversationController::clone | 12 |
| Models.Attachment::getHumanFileSizeAttribute | 12 |
| Models.Thread::isAutoResponder | 12 |
| Policies.MailboxPolicy::view | 12 |
| Services.ImapService::getOriginalSenderFromFwd | 12 |
| Services.ImapService::createCustomersFromMessage | 12 |

## 📋 Test Priority Recommendations

Based on coverage analysis, prioritize tests for:

### High Priority (Critical & Untested)

- **Services.ImapService** - Coverage: 6%, CRAP: 16146
- **Jobs.SendAutoReply** - Coverage: 1%, CRAP: 202
- **Services.SmtpService** - Coverage: 40%, CRAP: 197

### Medium Priority (Partially Tested)


### Top Methods Needing Tests

- `Services.ImapService::processMessage` - CRAP: 4830
- `Services.ImapService::getAddressesWithNames` - CRAP: 272
- `Services.SmtpService::validateSettings` - CRAP: 210
- `Listeners.SendAutoReply::handle` - CRAP: 182
- `Services.ImapService::parseAddresses` - CRAP: 182


