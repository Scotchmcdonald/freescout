<?php

// @intelephense-ignore-file
// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string|null $log_name
 * @property string $description
 * @property int|null $subject_id
 * @property string|null $subject_type
 * @property string|null $event
 * @property int|null $causer_id
 * @property string|null $causer_type
 * @property array<array-key, mixed>|null $properties
 * @property string|null $batch_uuid
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|null $causer
 * @property-read \Illuminate\Support\Collection $changes
 * @property-read \Illuminate\Database\Eloquent\Model|null $subject
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog causedBy(\Illuminate\Database\Eloquent\Model $causer)
 * @method static \Database\Factories\ActivityLogFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog forBatch(string $batchUuid)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog forEvent(string $event)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog forSubject(\Illuminate\Database\Eloquent\Model $subject)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog hasBatch()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog inLog(...$logNames)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereBatchUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereCauserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereCauserType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereLogName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereProperties($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereSubjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereSubjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereUpdatedAt($value)
 */
	class ActivityLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $thread_id
 * @property int|null $conversation_id
 * @property string $file_name
 * @property string $file_dir
 * @property int $file_size
 * @property string|null $mime_type
 * @property bool $embedded
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Thread $thread
 * @property-read mixed $full_path
 * @property-read string $human_file_size
 * @method static \Database\Factories\AttachmentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereConversationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereEmbedded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereFileDir($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereThreadId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereUpdatedAt($value)
 */
	class Attachment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $type
 * @property array<string, mixed>|null $settings
 * @property bool $active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Customer> $customers
 * @property-read int|null $customers_count
 * @method static \Database\Factories\ChannelFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel whereUpdatedAt($value)
 */
	class Channel extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $number
 * @property int $threads_count
 * @property int $type
 * @property int $folder_id
 * @property int $mailbox_id
 * @property int|null $user_id
 * @property int|null $customer_id
 * @property int $status
 * @property int $state
 * @property string $subject
 * @property string $customer_email
 * @property array<int, string>|null $cc
 * @property array<int, string>|null $bcc
 * @property string|null $preview
 * @property bool $imported
 * @property bool $has_attachments
 * @property int|null $created_by_user_id
 * @property int|null $created_by_customer_id
 * @property int|null $source_via
 * @property int|null $source_type
 * @property int|null $channel
 * @property int|null $closed_by_user_id
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property \Illuminate\Support\Carbon|null $follow_up_date
 * @property \Illuminate\Support\Carbon|null $follow_up_reminded_at
 * @property \Illuminate\Support\Carbon|null $user_updated_at
 * @property \Illuminate\Support\Carbon|null $last_reply
 * @property \Illuminate\Support\Carbon|null $last_reply_at
 * @property int|null $last_reply_from
 * @property bool $read_by_user
 * @property array<string, mixed>|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Mailbox|null $mailbox
 * @property-read \App\Models\Customer|null $customer
 * @property-read \App\Models\User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Thread> $threads
 * @method static \Illuminate\Database\Eloquent\Builder<Conversation>|Conversation create(array<string, mixed> $attributes = [])
 * @mixin \Illuminate\Database\Eloquent\Builder<Conversation>
 * @property string|null $sender_email
 * @property string|null $sender_name
 * @property int|null $client_user_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $closedByUser
 * @property-read \App\Models\User|null $createdByUser
 * @property-read \App\Models\Folder $folder
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Folder> $folders
 * @property-read int|null $folders_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $followers
 * @property-read int|null $followers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $starredByUsers
 * @property-read int|null $starred_by_users_count
 * @method static \Database\Factories\ConversationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereBcc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereCc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereClientUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereClosedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereClosedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereCreatedByCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereCreatedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereCustomerEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereFolderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereFollowUpDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereFollowUpRemindedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereHasAttachments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereImported($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereLastReplyAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereLastReplyFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereMailboxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation wherePreview($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereReadByUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereSenderEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereSenderName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereSourceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereSourceVia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereThreadsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereUserUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation withoutTrashed()
 */
	class Conversation extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $company
 * @property string|null $job_title
 * @property string|null $photo_url
 * @property int|null $photo_type
 * @property int|null $channel
 * @property string|null $channel_id
 * @property array<string, mixed>|null $phones
 * @property array<string, mixed>|null $websites
 * @property array<string, mixed>|null $social_profiles
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip
 * @property string|null $country
 * @property string|null $notes
 * @property bool $is_non_profit
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Email> $emails
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Conversation> $conversations
 * @method static \Illuminate\Database\Eloquent\Builder<Customer>|Customer findOrFail(int $id, array<int, string> $columns = ['*'])
 * @mixin \Illuminate\Database\Eloquent\Builder<Customer>
 * @property int|null $company_id
 * @property string|null $age
 * @property int|null $gender
 * @property string|null $chats
 * @property string|null $background
 * @property string|null $meta
 * @property float|int|null $default_hourly_rate
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Channel> $channels
 * @property-read int|null $channels_count
 * @property-read \Modules\Crm\Models\Company|null $companyRel
 * @property-read int|null $conversations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CustomerChannel> $customerChannels
 * @property-read int|null $customer_channels_count
 * @property-read int|null $emails_count
 * @property-read mixed $full_name
 * @property-read string|null $primary_email
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Thread> $threads
 * @property-read int|null $threads_count
 * @method static \Database\Factories\CustomerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereAge($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereBackground($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereChannelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereChats($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereDefaultHourlyRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereIsNonProfit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereJobTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer wherePhones($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer wherePhotoType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer wherePhotoUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereSocialProfiles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereWebsites($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereZip($value)
 */
	class Customer extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $customer_id
 * @property int $channel
 * @property string $channel_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer $customer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerChannel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerChannel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerChannel query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerChannel whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerChannel whereChannelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerChannel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerChannel whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerChannel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerChannel whereUpdatedAt($value)
 */
	class CustomerChannel extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $customer_id
 * @property string $email
 * @property int $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer $customer
 * @method static \Database\Factories\EmailFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereUpdatedAt($value)
 */
	class Email extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $mailbox_id
 * @property int|null $user_id
 * @property int $type
 * @property string $name
 * @property int $total_count
 * @property int $active_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Mailbox $mailbox
 * @property-read \App\Models\User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Conversation> $conversations
 * @property string|null $meta
 * @property-read int|null $conversations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Conversation> $conversationsViaFolder
 * @property-read int|null $conversations_via_folder_count
 * @method static \Database\Factories\FolderFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Folder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Folder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Folder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Folder whereActiveCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Folder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Folder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Folder whereMailboxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Folder whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Folder whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Folder whereTotalCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Folder whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Folder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Folder whereUserId($value)
 */
	class Folder extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $resource_type
 * @property string $resource_id
 * @property string $channel_id
 * @property string|null $token
 * @property string $webhook_url
 * @property Carbon $expiration_time
 * @property bool $is_active
 * @property Carbon|null $last_notification_at
 * @property int $notification_count
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $client_id
 * @property string $expiration_at
 * @property-read string $expires_in
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GooglePushChannel active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GooglePushChannel expired()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GooglePushChannel expiringSoon()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GooglePushChannel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GooglePushChannel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GooglePushChannel query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GooglePushChannel whereChannelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GooglePushChannel whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GooglePushChannel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GooglePushChannel whereExpirationAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GooglePushChannel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GooglePushChannel whereResourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GooglePushChannel whereResourceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GooglePushChannel whereUpdatedAt($value)
 */
	class GooglePushChannel extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property bool $is_default
 * @property int $status
 * @property string|array<int, string>|null $aliases
 * @property bool $aliases_reply
 * @property string|null $from_name
 * @property string|null $from_name_custom
 * @property int $ticket_status
 * @property int $ticket_assignee
 * @property string|null $template
 * @property string|null $signature
 * @property string|null $before_reply
 * @property int $out_method
 * @property string|null $out_server
 * @property int|null $out_port
 * @property string|null $out_username
 * @property string|null $out_password
 * @property string|null $out_encryption
 * @property string|null $in_server
 * @property int|null $in_port
 * @property string|null $in_username
 * @property string|null $in_password
 * @property string|null $in_protocol
 * @property string|null $in_encryption
 * @property bool $in_validate_cert
 * @property string|array<int, string>|null $in_imap_folders
 * @property string|null $imap_sent_folder
 * @property string|null $auto_bcc
 * @property bool $auto_reply_enabled
 * @property string|null $auto_reply_subject
 * @property string|null $auto_reply_message
 * @property bool $office_hours_enabled
 * @property bool $ratings
 * @property array<string, mixed>|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Conversation> $conversations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Folder> $folders
 * @method static \Illuminate\Database\Eloquent\Builder<Mailbox>|Mailbox find(int $id, array<int, string> $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder<Mailbox>|Mailbox where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder<Mailbox>|Mailbox whereNotNull(string $column, string $boolean = 'and')
 * @mixin \Illuminate\Database\Eloquent\Builder<Mailbox>
 * @property int $ratings_placement
 * @property string|null $ratings_text
 * @property-read int|null $conversations_count
 * @property-read int|null $folders_count
 * @property-read \App\Models\MailboxUser|null $pivot
 * @property-read int|null $users_count
 * @method static \Database\Factories\MailboxFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereAliases($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereAliasesReply($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereAutoBcc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereAutoReplyEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereAutoReplyMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereAutoReplySubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereBeforeReply($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereFromName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereFromNameCustom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereImapSentFolder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereInEncryption($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereInImapFolders($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereInPassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereInPort($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereInProtocol($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereInServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereInUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereInValidateCert($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereOfficeHoursEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereOutEncryption($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereOutMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereOutPassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereOutPort($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereOutServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereOutUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereRatings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereRatingsPlacement($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereRatingsText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereSignature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereTemplate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereTicketAssignee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereTicketStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mailbox whereUpdatedAt($value)
 */
	class Mailbox extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $mailbox_id
 * @property int $user_id
 * @property int $access
 * @property bool $after_send
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailboxUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailboxUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailboxUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailboxUser whereAccess($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailboxUser whereAfterSend($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailboxUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailboxUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailboxUser whereMailboxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailboxUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MailboxUser whereUserId($value)
 */
	class MailboxUser extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $alias
 * @property string $name
 * @property string|null $description
 * @property string $version
 * @property string|null $author
 * @property bool $active
 * @property array<array-key, mixed>|null $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\ModuleFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereAlias($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereAuthor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereVersion($value)
 */
	class Module extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $user_id
 * @property string $module_name
 * @property string $action
 * @property array<array-key, mixed>|null $metadata
 * @property string|null $ip_address
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleActivityLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleActivityLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleActivityLog ofAction(string $action)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleActivityLog ofModule(string $moduleName)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleActivityLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleActivityLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleActivityLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleActivityLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleActivityLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleActivityLog whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleActivityLog whereModuleName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleActivityLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleActivityLog whereUserId($value)
 */
	class ModuleActivityLog extends \Eloquent {}
}

namespace App\Models\Modules\Crm\Models{
/**
 * @property int $id
 * @property string $name
 * @property float|int $base_price
 * @property string $billing_frequency
 * @property string|null $description
 * @property string|null $features
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\Modules\Crm\Models\BillingTemplateFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingTemplate whereBasePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingTemplate whereBillingFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingTemplate whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingTemplate whereFeatures($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingTemplate whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingTemplate whereUpdatedAt($value)
 */
	class BillingTemplate extends \Eloquent {}
}

namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<Option> whereIn(string $column, mixed $values)
 * @method static Option updateOrCreate(array<string, mixed> $attributes, array<string, mixed> $values = [])
 * @property string $name
 * @property string|null $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\OptionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Option newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Option newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Option query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Option whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Option whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Option whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Option whereValue($value)
 */
	class Option extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $label
 * @property string|null $module
 * @property string|null $group
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Role> $roles
 * @property-read int|null $roles_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereModule($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereUpdatedAt($value)
 */
	class Permission extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $label
 * @property bool $is_super_admin
 * @property string $scope
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Permission> $permissions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 * @property-read int|null $permissions_count
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereIsSuperAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereScope($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedAt($value)
 */
	class Role extends \Eloquent {}
}

namespace App\Models{
/**
 * SavedSearch Model
 *
 * Stores user-saved search queries for quick access.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $query
 * @property array<string, mixed>|null $filters
 * @property bool $is_default
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\SavedSearchFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedSearch forUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedSearch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedSearch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedSearch ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedSearch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedSearch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedSearch whereFilters($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedSearch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedSearch whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedSearch whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedSearch whereQuery($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedSearch whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedSearch whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedSearch whereUserId($value)
 */
	class SavedSearch extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $thread_id
 * @property int|null $customer_id
 * @property int|null $user_id
 * @property string|null $message_id
 * @property string $email
 * @property int $mail_type
 * @property int $status
 * @property string|null $status_message
 * @property int $opens
 * @property int $clicks
 * @property \Illuminate\Support\Carbon|null $opened_at
 * @property \Illuminate\Support\Carbon|null $clicked_at
 * @property array<string, mixed>|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Thread $thread
 * @property-read \App\Models\Customer|null $customer
 * @property-read \App\Models\User|null $user
 * @mixin \Illuminate\Database\Eloquent\Builder<SendLog>
 * @property string|null $subject
 * @property string|null $smtp_queue_id
 * @method static \Database\Factories\SendLogFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SendLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SendLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SendLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SendLog whereClickedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SendLog whereClicks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SendLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SendLog whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SendLog whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SendLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SendLog whereMailType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SendLog whereMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SendLog whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SendLog whereOpenedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SendLog whereOpens($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SendLog whereSmtpQueueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SendLog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SendLog whereStatusMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SendLog whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SendLog whereThreadId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SendLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SendLog whereUserId($value)
 */
	class SendLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $medium
 * @property int $event
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\SubscriptionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereMedium($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereUserId($value)
 */
	class Subscription extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $operation_type
 * @property string $source
 * @property string $status
 * @property int $total_items
 * @property int $processed_items
 * @property int $failed_items
 * @property int $success_items
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $last_progress_at
 * @property string|null $error_message
 * @property array<array-key, mixed>|null $checkpoint_data
 * @property array<array-key, mixed>|null $failures
 * @property float|int|null $items_per_second
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $estimated_time_remaining
 * @property-read int $progress_percentage
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SyncOperation active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SyncOperation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SyncOperation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SyncOperation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SyncOperation recent(int $hours = 24)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SyncOperation whereCheckpointData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SyncOperation whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SyncOperation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SyncOperation whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SyncOperation whereFailedItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SyncOperation whereFailures($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SyncOperation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SyncOperation whereItemsPerSecond($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SyncOperation whereLastProgressAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SyncOperation whereOperationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SyncOperation whereProcessedItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SyncOperation whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SyncOperation whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SyncOperation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SyncOperation whereSuccessItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SyncOperation whereTotalItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SyncOperation whereUpdatedAt($value)
 */
	class SyncOperation extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $title
 * @property array<array-key, mixed> $config
 * @property bool $is_system
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Theme newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Theme newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Theme query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Theme whereConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Theme whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Theme whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Theme whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Theme whereIsSystem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Theme whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Theme whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Theme whereUpdatedAt($value)
 */
	class Theme extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $conversation_id
 * @property int|null $user_id
 * @property int|null $customer_id
 * @property int|null $created_by_user_id
 * @property int|null $created_by_customer_id
 * @property int|null $edited_by_user_id
 * @property \Illuminate\Support\Carbon|null $edited_at
 * @property int $type
 * @property int $status
 * @property int $state
 * @property int|null $action_type
 * @property int|null $source_via
 * @property int|null $source_type
 * @property string|null $body
 * @property array<int, string>|null $to
 * @property array<int, string>|null $cc
 * @property array<int, string>|null $bcc
 * @property string|null $from
 * @property string|array<string, mixed>|null $headers
 * @property string|null $message_id
 * @property \Illuminate\Support\Carbon|null $opened_at
 * @property array<string, mixed>|null $meta
 * @property bool $first
 * @property bool $has_attachments
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Conversation $conversation
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\Customer|null $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Attachment> $attachments
 * @method static \Illuminate\Database\Eloquent\Builder<Thread>|Thread create(array<string, mixed> $attributes = [])
 * @mixin \Illuminate\Database\Eloquent\Builder<Thread>
 * @property string|null $action_data
 * @property string|null $body_original
 * @property int|null $saved_reply_id
 * @property int|null $send_status
 * @property string|null $send_status_data
 * @property string|null $meta_subtype
 * @property int|null $meta_id
 * @property bool $imported
 * @property string|null $sender_email
 * @property string|null $sender_name
 * @property int|null $client_user_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read int|null $attachments_count
 * @property-read \App\Models\User|null $createdByUser
 * @property-read \App\Models\User|null $editedByUser
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SendLog> $sendLogs
 * @property-read int|null $send_logs_count
 * @method static \Database\Factories\ThreadFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereActionData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereActionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereBcc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereBodyOriginal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereCc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereClientUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereConversationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereCreatedByCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereCreatedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereEditedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereEditedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereFirst($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereHasAttachments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereHeaders($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereImported($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereMetaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereMetaSubtype($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereOpenedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereSavedReplyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereSendStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereSendStatusData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereSenderEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereSenderName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereSourceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereSourceVia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Thread withoutTrashed()
 */
	class Thread extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $password
 * @property int $role
 * @property int $status
 * @property string|null $remember_token
 * @property string|null $timezone
 * @property string|null $photo_url
 * @property int|null $type
 * @property int|null $invite_state
 * @property string|null $locale
 * @property string|null $theme
 * @property string|null $job_title
 * @property string|null $phone
 * @property int|null $time_format
 * @property bool $enable_kb_shortcuts
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Mailbox> $mailboxes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Folder> $folders
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Conversation> $conversations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Thread> $threads
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @method static \Illuminate\Database\Eloquent\Builder<User>|User create(array<string, mixed> $attributes = [])
 * @mixin \Illuminate\Database\Eloquent\Builder<User>
 * @property string|null $google_id
 * @property string|null $avatar
 * @property string|null $invite_hash
 * @property string|null $emails
 * @property int $locked
 * @property bool $dark_mode
 * @property array<array-key, mixed>|null $permissions
 * @property bool $is_demo
 * @property string|null $sender_email
 * @property string|null $sender_name
 * @property int|null $client_user_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Crm\Models\Company> $companies
 * @property-read int|null $companies_count
 * @property-read int|null $conversations_count
 * @property-read int|null $folders_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Conversation> $followedConversations
 * @property-read int|null $followed_conversations_count
 * @property-read mixed $full_name
 * @property-read mixed $client_id
 * @property-read mixed $company_id
 * @property-read \App\Models\MailboxUser|null $pivot
 * @property-read int|null $mailboxes_count
 * @property-read mixed $name
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SavedSearch> $savedSearches
 * @property-read int|null $saved_searches_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @property-read int|null $threads_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\KnowledgeBase\Models\UserTourProgress> $tourProgress
 * @property-read int|null $tour_progress_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User nonDeleted()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereClientUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDarkMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEnableKbShortcuts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereGoogleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereInviteHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereInviteState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsDemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereJobTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePermissions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhotoUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSenderEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSenderName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTheme($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTimeFormat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent implements \Illuminate\Contracts\Auth\MustVerifyEmail {
		public function hasVerifiedEmail()
		{
			return true;
		}

		public function getEmailForVerification()
		{
			return '';
		}

		public function markEmailAsVerified()
		{
			return true;
		}

		public function sendEmailVerificationNotification()
		{
		}
	}
}

