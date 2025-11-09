# Test Results Summary

**Date:** Sun Nov  9 18:28:20 UTC 2025

## PHPStan Bodyscan Results

### Error Count Summary

+-------+-------------+-----------+
|     0 |           0 |         - |
|     1 |           0 |         - |
|     2 |           2 |       + 2 |
|     3 |           2 |         - |
|     4 |           7 |       + 5 |
|     5 |           9 |       + 2 |
|     6 |          54 |      + 45 |
|     7 |          54 |         - |
|     8 |          54 |         - |
+-------+-------------+-----------+

### Detailed Errors by Level


**Note:** Bodyscan runs PHPStan at level 9 in bare mode (no config ignores) to show all potential issues.

#### Level 9 - Detailed Errors (Bare Analysis)

```
 ------ ------------------------------------------------------------------------------------------ 
  Line   Http/Controllers/Auth/RegisteredUserController.php                                        
 ------ ------------------------------------------------------------------------------------------ 
  :48    Offset 0 on non-empty-list<string> on left side of ?? always exists and is not nullable.  
         🪪  nullCoalesce.offset                                                                   
 ------ ------------------------------------------------------------------------------------------ 

 ------ ----------------------------------------------------------------------------------------------------------------------- 
  Line   Http/Controllers/ConversationController.php                                                                            
 ------ ----------------------------------------------------------------------------------------------------------------------- 
  :40    PHPDoc tag @var for variable $viewName contains unresolvable type.                                                     
         🪪  varTag.unresolvableType                                                                                            
  :42    Method App\Http\Controllers\ConversationController::index() should return Illuminate\Contracts\View\View but returns   
         Illuminate\Contracts\View\Factory|Illuminate\Contracts\View\View.                                                      
         🪪  return.type                                                                                                        
  :86    PHPDoc tag @var for variable $viewName contains unresolvable type.                                                     
         🪪  varTag.unresolvableType                                                                                            
  :88    Method App\Http\Controllers\ConversationController::show() should return                                               
         Illuminate\Contracts\View\View|Illuminate\Http\RedirectResponse but returns                                            
         Illuminate\Contracts\View\Factory|Illuminate\Contracts\View\View.                                                      
         🪪  return.type                                                                                                        
  :112   PHPDoc tag @var for variable $viewName contains unresolvable type.                                                     
         🪪  varTag.unresolvableType                                                                                            
  :114   Method App\Http\Controllers\ConversationController::create() should return Illuminate\Contracts\View\View but returns  
         Illuminate\Contracts\View\Factory|Illuminate\Contracts\View\View.                                                      
         🪪  return.type                                                                                                        
  :374   No error to ignore is reported on line 374.                                                                            
  :376   No error to ignore is reported on line 376.                                                                            
  :385   PHPDoc tag @var for variable $viewName contains unresolvable type.                                                     
         🪪  varTag.unresolvableType                                                                                            
  :387   Method App\Http\Controllers\ConversationController::search() should return Illuminate\Contracts\View\View but returns  
         Illuminate\Contracts\View\Factory|Illuminate\Contracts\View\View.                                                      
         🪪  return.type                                                                                                        
 ------ ----------------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------- 
  Line   Http/Controllers/CustomerController.php                                                                 
 ------ -------------------------------------------------------------------------------------------------------- 
  :33    No error to ignore is reported on line 33.                                                              
  :40    PHPDoc tag @var for variable $viewName contains unresolvable type.                                      
         🪪  varTag.unresolvableType                                                                             
  :42    Method App\Http\Controllers\CustomerController::index() should return Illuminate\View\View but returns  
         Illuminate\Contracts\View\Factory|Illuminate\Contracts\View\View.                                       
         🪪  return.type                                                                                         
  :77    PHPDoc tag @var for variable $viewName contains unresolvable type.                                      
         🪪  varTag.unresolvableType                                                                             
  :79    Method App\Http\Controllers\CustomerController::show() should return Illuminate\View\View but returns   
         Illuminate\Contracts\View\Factory|Illuminate\Contracts\View\View.                                       
         🪪  return.type                                                                                         
  :87    PHPDoc tag @var for variable $viewName contains unresolvable type.                                      
         🪪  varTag.unresolvableType                                                                             
  :89    Method App\Http\Controllers\CustomerController::edit() should return Illuminate\View\View but returns   
         Illuminate\Contracts\View\Factory|Illuminate\Contracts\View\View.                                       
         🪪  return.type                                                                                         
  :195   No error to ignore is reported on line 195.                                                             
 ------ -------------------------------------------------------------------------------------------------------- 

 ------ --------------------------------------------------------------------------------------------------------- 
  Line   Http/Controllers/DashboardController.php                                                                 
 ------ --------------------------------------------------------------------------------------------------------- 
  :58    PHPDoc tag @var for variable $viewName contains unresolvable type.                                       
         🪪  varTag.unresolvableType                                                                              
  :60    Method App\Http\Controllers\DashboardController::index() should return Illuminate\View\View but returns  
         Illuminate\Contracts\View\Factory|Illuminate\Contracts\View\View.                                        
         🪪  return.type                                                                                          
 ------ --------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   Http/Controllers/MailboxController.php                                                                              
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :30    PHPDoc tag @var for variable $viewName contains unresolvable type.                                                  
         🪪  varTag.unresolvableType                                                                                         
  :32    Method App\Http\Controllers\MailboxController::index() should return Illuminate\View\View but returns               
         Illuminate\Contracts\View\Factory|Illuminate\Contracts\View\View.                                                   
         🪪  return.type                                                                                                     
  :58    PHPDoc tag @var for variable $viewName contains unresolvable type.                                                  
         🪪  varTag.unresolvableType                                                                                         
  :60    Method App\Http\Controllers\MailboxController::show() should return Illuminate\View\View but returns                
         Illuminate\Contracts\View\Factory|Illuminate\Contracts\View\View.                                                   
         🪪  return.type                                                                                                     
  :76    PHPDoc tag @var for variable $viewName contains unresolvable type.                                                  
         🪪  varTag.unresolvableType                                                                                         
  :78    Method App\Http\Controllers\MailboxController::settings() should return Illuminate\View\View but returns            
         Illuminate\Contracts\View\Factory|Illuminate\Contracts\View\View.                                                   
         🪪  return.type                                                                                                     
  :230   PHPDoc tag @var for variable $viewName contains unresolvable type.                                                  
         🪪  varTag.unresolvableType                                                                                         
  :232   Method App\Http\Controllers\MailboxController::connectionIncoming() should return Illuminate\View\View but returns  
         Illuminate\Contracts\View\Factory|Illuminate\Contracts\View\View.                                                   
         🪪  return.type                                                                                                     
  :277   PHPDoc tag @var for variable $viewName contains unresolvable type.                                                  
         🪪  varTag.unresolvableType                                                                                         
  :279   Method App\Http\Controllers\MailboxController::connectionOutgoing() should return Illuminate\View\View but returns  
         Illuminate\Contracts\View\Factory|Illuminate\Contracts\View\View.                                                   
         🪪  return.type                                                                                                     
  :339   PHPDoc tag @var for variable $viewName contains unresolvable type.                                                  
         🪪  varTag.unresolvableType                                                                                         
  :341   Method App\Http\Controllers\MailboxController::permissions() should return Illuminate\View\View but returns         
         Illuminate\Contracts\View\Factory|Illuminate\Contracts\View\View.                                                   
         🪪  return.type                                                                                                     
  :376   PHPDoc tag @var for variable $viewName contains unresolvable type.                                                  
         🪪  varTag.unresolvableType                                                                                         
  :378   Method App\Http\Controllers\MailboxController::autoReply() should return Illuminate\View\View but returns           
         Illuminate\Contracts\View\Factory|Illuminate\Contracts\View\View.                                                   
         🪪  return.type                                                                                                     
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ ------------------------------------------------------------------------------------------------------- 
  Line   Http/Controllers/ModulesController.php                                                                 
 ------ ------------------------------------------------------------------------------------------------------- 
  :36    PHPDoc tag @var for variable $viewName contains unresolvable type.                                     
         🪪  varTag.unresolvableType                                                                            
  :38    Method App\Http\Controllers\ModulesController::index() should return Illuminate\View\View but returns  
         Illuminate\Contracts\View\Factory|Illuminate\Contracts\View\View.                                      
         🪪  return.type                                                                                        
 ------ ------------------------------------------------------------------------------------------------------- 

 ------ ---------------------------------------------------------------------------------------------------------------- 
  Line   Http/Controllers/ProfileController.php                                                                          
 ------ ---------------------------------------------------------------------------------------------------------------- 
  :19    PHPDoc tag @var for variable $viewName contains unresolvable type.                                              
         🪪  varTag.unresolvableType                                                                                     
  :21    Method App\Http\Controllers\ProfileController::edit() should return Illuminate\Contracts\View\View but returns  
         Illuminate\Contracts\View\Factory|Illuminate\Contracts\View\View.                                               
         🪪  return.type                                                                                                 
 ------ ---------------------------------------------------------------------------------------------------------------- 

 ------ --------------------------------------------------------------------------------------------------------- 
  Line   Http/Controllers/SettingsController.php                                                                  
 ------ --------------------------------------------------------------------------------------------------------- 
  :27    PHPDoc tag @var for variable $viewName contains unresolvable type.                                       
         🪪  varTag.unresolvableType                                                                              
  :29    Method App\Http\Controllers\SettingsController::index() should return Illuminate\View\View but returns   
         Illuminate\Contracts\View\Factory|Illuminate\Contracts\View\View.                                        
         🪪  return.type                                                                                          
  :75    PHPDoc tag @var for variable $viewName contains unresolvable type.                                       
         🪪  varTag.unresolvableType                                                                              
  :77    Method App\Http\Controllers\SettingsController::email() should return Illuminate\View\View but returns   
         Illuminate\Contracts\View\Factory|Illuminate\Contracts\View\View.                                        
         🪪  return.type                                                                                          
  :127   PHPDoc tag @var for variable $viewName contains unresolvable type.                                       
         🪪  varTag.unresolvableType                                                                              
  :129   Method App\Http\Controllers\SettingsController::system() should return Illuminate\View\View but returns  
         Illuminate\Contracts\View\Factory|Illuminate\Contracts\View\View.                                        
         🪪  return.type                                                                                          
  :279   Part $value (mixed) of encapsed string cannot be cast to string.                                         
         🪪  encapsedStringPart.nonString                                                                         
  :281   Part $value (mixed) of encapsed string cannot be cast to string.                                         
         🪪  encapsedStringPart.nonString                                                                         
 ------ --------------------------------------------------------------------------------------------------------- 
```


## PHPStan Analyse Results

✅ PHPStan Analyse completed with no errors.
```
 [OK] No errors                                                                 
```

## PHP Artisan Test Results

### Test Summary

```
  Tests:    45 failed, 1 skipped, 1282 passed (2997 assertions)
```

### ❌ Failed Tests Details

**Total Failures: 45**

```
FAILED  Tests\Unit\Controllers\Auth\RegisteredUserControllerTest > store…    
FAILED  Tests\Unit\Controllers\ProfileControllerTest > up…  QueryException   
FAILED  Tests\Unit\Controllers\ProfileControllerTest > up…  QueryException   
FAILED  Tests\Unit\Controllers\UserControllerTest…  RouteNotFoundException   
FAILED  Tests\Unit\Controllers\UserControllerTest…  RouteNotFoundException   
FAILED  Tests\Unit\Controllers\UserControllerTest…  RouteNotFoundException   
FAILED  Tests\Unit\Controllers\UserControllerTest > create accessible by…    
FAILED  Tests\Unit\Controllers\UserControllerTest > store creates user wi…   
FAILED  Tests\Unit\Controllers\UserControllerTest > updat…  QueryException   
FAILED  Tests\Unit\EdgeCases\ModelEdgeCasesTest > custome…  QueryException   
FAILED  Tests\Unit\EdgeCases\ModelEdgeCasesTest > user wi…  QueryException   
FAILED  Tests\Unit\EdgeCases\ModelEdgeCasesTest > custome…  QueryException   
FAILED  Tests\Unit\Events\EventEdgeCasesTest > new me…  ArgumentCountError   
FAILED  Tests\Unit\Events\EventEdgeCasesTest > new me…  ArgumentCountError   
FAILED  Tests\Unit\Events\EventEdgeCasesTest > user viewing co…  TypeError   
FAILED  Tests\Unit\Events\EventEdgeCasesTest > event…   ArgumentCountError   
FAILED  Tests\Unit\Events\EventEdgeCasesTest > events…  ArgumentCountError   
FAILED  Tests\Unit\Events\EventEdgeCasesTest > multip…  ArgumentCountError   
FAILED  Tests\Unit\Events\EventEdgeCasesTest > event…   ArgumentCountError   
FAILED  Tests\Unit\Jobs\SendAutoReplyComprehensiveTest > job store…  Error   
FAILED  Tests\Unit\Jobs\SendAutoReplyComprehensiveTest >…   QueryException   
FAILED  Tests\Unit\Jobs\SendConversationReplyComprehensiveTest…  TypeError   
FAILED  Tests\Unit\Jobs\SendConversationReplyComprehensiveTest…  TypeError   
FAILED  Tests\Unit\Jobs\SendConversationReplyComprehensiveTest…  TypeError   
FAILED  Tests\Unit\Jobs\SendConversationReplyComprehensiveTest…  TypeError   
FAILED  Tests\Unit\Jobs\SendConversationReplyComprehensiveTest…  TypeError   
FAILED  Tests\Unit\Jobs\SendConversationReplyComprehensiveTest…  TypeError   
FAILED  Tests\Unit\Jobs\SendConversationReplyComprehensiveTest…  TypeError   
FAILED  Tests\Unit\Jobs\SendConversationReplyComprehensiveTest…  TypeError   
FAILED  Tests\Unit\Models\CustomerComprehensiveTest > cus…  QueryException   
FAILED  Tests\Unit\Models\CustomerComprehensiveTest > cus…  QueryException   
FAILED  Tests\Unit\Models\CustomerComprehensiveTest > customer can be sof…   
FAILED  Tests\Unit\Models\CustomerComprehensiveTest > cus…  QueryException   
FAILED  Tests\Unit\Models\CustomerComprehensiveTest > customer first name…   
FAILED  Tests\Unit\Models\CustomerComprehensiveTest > customer email is r…   
FAILED  Tests\Unit\Models\CustomerComprehensiveTest > cus…  QueryException   
FAILED  Tests\Unit\Models\CustomerComprehensiveTest > cus…  QueryException   
FAILED  Tests\Unit\Policies\AdvancedPolicyTest > guest cannot…   TypeError   
FAILED  Tests\Feature\Commands\ConfigureGmailMailbo…  AssertionFailedError   
FAILED  Tests\Feature\ConversationAdvancedTest > search finds by subject     
FAILED  Tests\Feature\ConversationAdvancedTest > search finds by customer…   
FAILED  Tests\Feature\ConversationAdvancedTest > search only shows author…   
FAILED  Tests\Feature\ConversationAdvancedTest > admin search shows all m…   
FAILED  Tests\Feature\ConversationAdvancedTest > search paginates results    
FAILED  Tests\Feature\PaginationAndFilteringTest > conversation search wi…   
```

### Error Analysis

**Error Types:**
```
     12 QueryException
     10 TypeError
      6 ArgumentCountError
      3 RouteNotFoundException
      1 AssertionFailedError
```

### Sample Error Details

First error detail from log:
```
   FAILED  Tests\Unit\Controllers\ProfileControllerTest > up…  QueryException   
  SQLSTATE[HY000]: General error: 1 table users has no column named name (Connection: sqlite, SQL: insert into "users" ("first_name", "last_name", "email", "email_verified_at", "password", "remember_token", "role", "timezone", "photo_url", "type", "status", "invite_state", "locale", "job_title", "phone", "time_format", "enable_kb_shortcuts", "name", "updated_at", "created_at") values (Bethany, Rogahn, old@example.com, 2025-11-09 18:24:58, $2y$04$sEohEO5sG4BrEU2g6v28ze63cKFXJLtwczzAbVR8Km1zNvGD4zraC, DAieptnzeE, 1, UTC, ?, 1, 1, 1, en, Agricultural Equipment Operator, 470-695-0956, 12, 1, Old Name, 2025-11-09 18:24:58, 2025-11-09 18:24:58))

   FAILED  Tests\Unit\Controllers\ProfileControllerTest > up…  QueryException   
  SQLSTATE[HY000]: General error: 1 table users has no column named name (Connection: sqlite, SQL: insert into "users" ("first_name", "last_name", "email", "email_verified_at", "password", "remember_token", "role", "timezone", "photo_url", "type", "status", "invite_state", "locale", "job_title", "phone", "time_format", "enable_kb_shortcuts", "name", "updated_at", "created_at") values (Carolina, Vandervort, test@example.com, 2025-11-09 18:24:58, $2y$04$sEohEO5sG4BrEU2g6v28ze63cKFXJLtwczzAbVR8Km1zNvGD4zraC, GiSXLSQawu, 1, America/Chicago, ?, 1, 1, 1, en, Typesetter, 878.286.1203, 12, 1, Test User, 2025-11-09 18:24:58, 2025-11-09 18:24:58))

   FAILED  Tests\Unit\Controllers\UserControllerTest > updat…  QueryException   
  SQLSTATE[HY000]: General error: 1 table users has no column named name (Connection: sqlite, SQL: insert into "users" ("first_name", "last_name", "email", "email_verified_at", "password", "remember_token", "role", "timezone", "photo_url", "type", "status", "invite_state", "locale", "job_title", "phone", "time_format", "enable_kb_shortcuts", "name", "updated_at", "created_at") values (Samir, Hagenes, zolson@example.com, 2025-11-09 18:24:59, $2y$04$sEohEO5sG4BrEU2g6v28ze63cKFXJLtwczzAbVR8Km1zNvGD4zraC, VV3vLEiJRi, 1, America/Los_Angeles, ?, 1, 1, 1, en, Heating Equipment Operator, 1-351-582-5750, 12, 1, Original Name, 2025-11-09 18:24:59, 2025-11-09 18:24:59))

   FAILED  Tests\Unit\EdgeCases\ModelEdgeCasesTest > custome…  QueryException   
  SQLSTATE[HY000]: General error: 1 table customers has no column named email (Connection: sqlite, SQL: insert into "customers" ("first_name", "last_name", "company", "job_title", "photo_url", "photo_type", "channel", "channel_id", "phones", "websites", "social_profiles", "address", "city", "state", "zip", "country", "notes", "email", "updated_at", "created_at") values (John, ?, ?, ?, ?, 1, 1, ?, [{"type":"work","value":"+1-302-968-2749"}], ?, ?, ?, ?, kj, ?, CG, ?, john@example.com, 2025-11-09 18:25:00, 2025-11-09 18:25:00))

   FAILED  Tests\Unit\EdgeCases\ModelEdgeCasesTest > user wi…  QueryException   
  SQLSTATE[HY000]: General error: 1 table users has no column named name (Connection: sqlite, SQL: insert into "users" ("first_name", "last_name", "email", "email_verified_at", "password", "remember_token", "role", "timezone", "photo_url", "type", "status", "invite_state", "locale", "job_title", "phone", "time_format", "enable_kb_shortcuts", "name", "updated_at", "created_at") values (Aletha, Hartmann, longname@example.com, 2025-11-09 18:25:00, $2y$04$sEohEO5sG4BrEU2g6v28ze63cKFXJLtwczzAbVR8Km1zNvGD4zraC, Ms6gtOjYcK, 1, America/Los_Angeles, ?, 1, 1, 1, en, Camera Repairer, (970) 572-8506, 12, 1, xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx, 2025-11-09 18:25:00, 2025-11-09 18:25:00))

   FAILED  Tests\Unit\EdgeCases\ModelEdgeCasesTest > custome…  QueryException   
  SQLSTATE[HY000]: General error: 1 table customers has no column named email (Connection: sqlite, SQL: insert into "customers" ("first_name", "last_name", "company", "job_title", "photo_url", "photo_type", "channel", "channel_id", "phones", "websites", "social_profiles", "address", "city", "state", "zip", "country", "notes", "email", "updated_at", "created_at") values (Allen, Hegmann, Zboncak, Beer and Weimann, ?, ?, 1, 1, ?, [{"type":"work","value":"+15747447395"}], ?, ?, 235 Carlo Branch, ?, dd, ?, ?, ?, unique@example.com, 2025-11-09 18:25:00, 2025-11-09 18:25:00))

   FAILED  Tests\Unit\Jobs\SendAutoReplyComprehensiveTest >…   QueryException   
  SQLSTATE[HY000]: General error: 1 table customers has no column named email (Connection: sqlite, SQL: insert into "customers" ("first_name", "last_name", "company", "job_title", "photo_url", "photo_type", "channel", "channel_id", "phones", "websites", "social_profiles", "address", "city", "state", "zip", "country", "notes", "email", "updated_at", "created_at") values (Tara, Balistreri, Kris, O'Reilly and Brakus, Personal Care Worker, ?, 1, 1, ?, [{"type":"work","value":"623-672-0671"}], [{"value":"http:\/\/www.sanford.com\/ex-consequatur-nemo-dolorem.html"}], ?, ?, North Antoinettetown, io, 05244, IQ, ?, customer@example.com, 2025-11-09 18:25:03, 2025-11-09 18:25:03))
```


## Code Coverage Summary

### Overall Metrics

| Metric    | Coverage      | Covered / Total |
|-----------|---------------|-----------------|
| Lines     | **63.75%**    | 1820/2855          |
| Functions | **76.30%**|       |
| Classes   | **%**  |         |

[View Full Code Coverage Report](coverage-report/index.html)

### 🚨 Top Classes at Risk (Low Coverage)

| Class | Coverage |
|-------|----------|
| Listeners.SendAutoReply | 0% |
| Jobs.SendAutoReply | 1% |
| Services.ImapService | 9% |
| Http.Controllers.ModulesController | 46% |
| Models.Attachment | 60% |
| Services.SmtpService | 63% |
| Http.Controllers.UserController | 63% |
| Events.NewMessageReceived | 64% |
| Http.Requests.Auth.LoginRequest | 65% |
| Models.Channel | 70% |
| Http.Controllers.SystemController | 70% |
| Http.Controllers.ConversationController | 74% |
| Http.Controllers.Auth.EmailVerificationPromptController | 75% |
| Mail.AutoReply | 80% |
| Policies.MailboxPolicy | 85% |

### ⚠️ Top Classes at Risk (High CRAP Scores)

| Class | CRAP Score |
|-------|------------|
| Services.ImapService | 16612 |
| Listeners.SendAutoReply | 182 |
| Jobs.SendAutoReply | 150 |
| Http.Controllers.ConversationController | 79 |
| Services.SmtpService | 66 |
| Http.Controllers.SystemController | 62 |
| Http.Controllers.ModulesController | 38 |
| Misc.MailHelper | 38 |
| Http.Controllers.UserController | 33 |
| Http.Controllers.SettingsController | 30 |
| Http.Controllers.CustomerController | 17 |
| Models.User | 15 |
| Policies.MailboxPolicy | 14 |
| Models.Thread | 14 |
| Mail.AutoReply | 11 |

### 🔍 Top Methods at Risk (Low Coverage)

| Method | Coverage |
|--------|----------|
| Events.NewMessageReceived::broadcastOn | 0% |
| Http.Controllers.ConversationController::upload | 0% |
| Http.Controllers.ConversationController::clone | 0% |
| Http.Controllers.SettingsController::validateSmtp | 0% |
| Http.Controllers.UserController::ajax | 0% |
| Jobs.SendAutoReply::handle | 0% |
| Jobs.SendAutoReply::failed | 0% |
| Listeners.SendAutoReply::handle | 0% |
| Misc.MailHelper::generateMessageId | 0% |
| Misc.MailHelper::getMessageIdHash | 0% |
| Models.Attachment::getFullPathAttribute | 0% |
| Models.Attachment::getHumanFileSizeAttribute | 0% |
| Models.Channel::customers | 0% |
| Models.Channel::isActive | 0% |
| Models.SendLog::wasOpened | 0% |
| Models.SendLog::wasClicked | 0% |
| Models.Thread::isAutoResponder | 0% |
| Models.User::getFirstName | 0% |
| Models.User::getPhotoUrl | 0% |
| Policies.MailboxPolicy::restore | 0% |

### ⚡ Top Methods at Risk (High CRAP Scores)

| Method | CRAP Score |
|--------|------------|
| Services.ImapService::processMessage | 5256 |
| Services.ImapService::getAddressesWithNames | 272 |
| Services.SmtpService::validateSettings | 210 |
| Listeners.SendAutoReply::handle | 182 |
| Services.ImapService::parseAddresses | 182 |
| Jobs.SendAutoReply::handle | 110 |
| Services.ImapService::testConnection | 110 |
| Services.ImapService::fetchEmails | 97 |
| Services.ImapService::separateReply | 72 |
| Http.Controllers.UserController::ajax | 56 |
| Services.ImapService::getOriginalSenderFromFwd | 30 |
| Http.Controllers.SystemController::ajax | 25 |
| Events.NewMessageReceived::broadcastOn | 20 |
| Models.Customer::create | 19 |
| Http.Controllers.ConversationController::clone | 12 |
| Models.Attachment::getHumanFileSizeAttribute | 12 |
| Models.Thread::isAutoResponder | 12 |
| Services.ImapService::createCustomersFromMessage | 12 |
| Http.Controllers.SystemController::diagnostics | 11 |
| Http.Controllers.ModulesController::delete | 9 |

## 📋 Test Priority Recommendations

Based on coverage analysis, prioritize tests for:

### High Priority (Critical & Untested)

- **Services.ImapService** - Coverage: 9%, CRAP: 16612
- **Listeners.SendAutoReply** - Coverage: 0%, CRAP: 182
- **Jobs.SendAutoReply** - Coverage: 1%, CRAP: 150

### Medium Priority (Partially Tested)


### Top Methods Needing Tests

- `Services.ImapService::processMessage` - CRAP: 5256
- `Services.ImapService::getAddressesWithNames` - CRAP: 272
- `Services.SmtpService::validateSettings` - CRAP: 210
- `Listeners.SendAutoReply::handle` - CRAP: 182
- `Services.ImapService::parseAddresses` - CRAP: 182


