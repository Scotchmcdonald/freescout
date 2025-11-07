<?php
    $is_in_chat_mode = $conversation->isInChatMode();
?>

<?php $__env->startSection('title_full', '#'.$conversation->number.' '.$conversation->getSubject().($customer ? ' - '.$customer->getFullName(true) : '')); ?>

<?php if(app('request')->input('print')): ?>
    <?php $__env->startSection('body_class', 'body-conv print'); ?>
<?php else: ?>
    <?php $__env->startSection('body_class', 'body-conv'.($is_in_chat_mode ? ' chat-mode' : '')); ?>
<?php endif; ?>

<?php $__env->startSection('body_attrs'); ?>
    <?php echo \Illuminate\View\Factory::parentPlaceholder('body_attrs'); ?> 
    data-conversation_id="<?php echo e($conversation->id); ?>"
<?php $__env->stopSection(); ?>

<?php $__env->startSection('sidebar'); ?>
    <?php echo $__env->make('partials/sidebar_menu_toggle', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('mailboxes/sidebar_menu_view', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('partials/flash_messages', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div id="conv-layout" class="flex gap-4 conv-type-<?php echo e(strtolower($conversation->getTypeName())); ?> <?php if($is_following): ?> conv-following <?php endif; ?>" x-data="conversationManager()">
        <div id="conv-layout-header" class="bg-white shadow-sm rounded-lg p-4 mb-4">
            <div id="conv-toolbar" class="flex justify-between items-center mb-4">
                <div class="conv-actions flex items-center gap-2">
                    <?php
                        $actions = \App\Misc\ConversationActionButtons::getActions($conversation, Auth::user(), $mailbox);
                        $toolbar_actions = \App\Misc\ConversationActionButtons::getActionsByLocation($actions, \App\Misc\ConversationActionButtons::LOCATION_TOOLBAR);
                        $dropdown_actions = \App\Misc\ConversationActionButtons::getActionsByLocation($actions, \App\Misc\ConversationActionButtons::LOCATION_DROPDOWN);
                    ?>

                    <?php $__currentLoopData = $toolbar_actions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action_key => $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($action_key === 'delete'): ?>
                            <button type="button" 
                                    class="editor-btn <?php echo e($action['class']); ?>" 
                                    data-toggle="tooltip"
                                    title="<?php echo e($action['label']); ?>"
                                    aria-label="<?php echo e($action['label']); ?>">
                                <i class="glyphicon <?php echo e($action['icon']); ?>"></i>
                            </button>
                        <?php elseif(!empty($action['url'])): ?>
                            <a href="<?php echo e($action['url']($conversation)); ?>"
                               class="editor-btn <?php echo e($action['class']); ?>"
                               <?php if(!empty($action['attrs'])): ?>
                                   <?php $__currentLoopData = $action['attrs']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attr_key => $attr_value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                       <?php echo e($attr_key); ?>="<?php echo e($attr_value); ?>"
                                   <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                               <?php endif; ?>
                               data-toggle="tooltip"
                               title="<?php echo e($action['label']); ?>"
                               aria-label="<?php echo e($action['label']); ?>">
                                <i class="glyphicon <?php echo e($action['icon']); ?>"></i>
                            </a>
                        <?php else: ?>
                            <button type="button"
                                    class="editor-btn <?php echo e($action['class']); ?>"
                                    data-toggle="tooltip"
                                    title="<?php echo e($action['label']); ?>"
                                    aria-label="<?php echo e($action['label']); ?>"
                                    <?php if(!empty($action['attrs'])): ?>
                                        <?php $__currentLoopData = $action['attrs']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attr_key => $attr_value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php echo e($attr_key); ?>="<?php echo e($attr_value); ?>"
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php endif; ?>>
                                <i class="glyphicon <?php echo e($action['icon']); ?>"></i>
                            </button>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    @action('conversation.action_buttons', $conversation, $mailbox)

                    
                    <div class="relative" x-data="{ open: false }">
                        <button type="button" 
                                class="editor-btn" 
                                @click="open = !open"
                                data-toggle="tooltip"
                                title="<?php echo e(__('More Actions')); ?>">
                            <i class="glyphicon glyphicon-option-horizontal"></i>
                        </button>
                        <div x-show="open" 
                             @click.away="open = false"
                             class="dropdown-menu"
                             x-transition>
                            @action('conversation.prepend_action_buttons', $conversation, $mailbox)
                            <?php $__currentLoopData = $dropdown_actions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action_key => $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($action_key === 'delete_mobile'): ?>
                                    <a href="#" class="<?php echo e($action['class']); ?> md:hidden">
                                        <i class="glyphicon <?php echo e($action['icon']); ?>"></i> <?php echo e($action['label']); ?>

                                    </a>
                                <?php else: ?>
                                    <?php if(!empty($action['has_opposite'])): ?>
                                        <a href="#" class="<?php echo e($action['class']); ?> <?php if($is_following): ?> hidden <?php endif; ?>" data-follow-action="follow">
                                            <i class="glyphicon <?php echo e($action['icon']); ?>"></i> <?php echo e($action['label']); ?>

                                        </a>
                                        <a href="#" class="<?php echo e($action['opposite']['class']); ?> <?php if(!$is_following): ?> hidden <?php endif; ?>" data-follow-action="unfollow">
                                            <i class="glyphicon <?php echo e($action['icon']); ?>"></i> <?php echo e($action['opposite']['label']); ?>

                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo e(!empty($action['url']) ? $action['url']($conversation) : '#'); ?>"
                                           class="<?php echo e($action['class']); ?>"
                                           <?php if(!empty($action['attrs'])): ?>
                                               <?php $__currentLoopData = $action['attrs']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attr_key => $attr_value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                   <?php echo e($attr_key); ?>="<?php echo e($attr_value); ?>"
                                               <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                           <?php endif; ?>>
                                            <i class="glyphicon <?php echo e($action['icon']); ?>"></i> <?php echo e($action['label']); ?>

                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            @action('conversation.append_action_buttons', $conversation, $mailbox)
                        </div>
                    </div>
                </div>

                <div class="conv-info flex items-center gap-4">
                    @action('conversation.convinfo.prepend', $conversation, $mailbox)
                    
                    <?php if($conversation->state != App\Conversation::STATE_DELETED): ?>
                        
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" 
                                    @click="open = !open"
                                    class="flex items-center gap-2 px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50"
                                    data-toggle="tooltip"
                                    title="<?php echo e(__('Assignee')); ?>: <?php echo e($conversation->getAssigneeName(true)); ?>">
                                <i class="glyphicon glyphicon-user"></i>
                                <span><?php echo e($conversation->getAssigneeName(true)); ?></span>
                                <i class="glyphicon glyphicon-chevron-down text-xs"></i>
                            </button>
                            <div x-show="open" 
                                 @click.away="open = false"
                                 class="dropdown-menu max-h-64 overflow-y-auto"
                                 x-transition>
                                <a href="#" 
                                   class="<?php if(!$conversation->user_id): ?> bg-blue-50 <?php endif; ?>"
                                   @click.prevent="window.conversationManager.changeAssignee(-1); open = false">
                                    <?php echo e(__("Anyone")); ?>

                                </a>
                                <a href="#" 
                                   class="<?php if($conversation->user_id == Auth::user()->id): ?> bg-blue-50 <?php endif; ?>"
                                   @click.prevent="window.conversationManager.changeAssignee(<?php echo e(Auth::user()->id); ?>); open = false">
                                    <?php echo e(__("Me")); ?>

                                </a>
                                <?php $__currentLoopData = $mailbox->usersAssignable(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php if($user->id != Auth::user()->id): ?>
                                        <a href="#" 
                                           class="<?php if($conversation->user_id == $user->id): ?> bg-blue-50 <?php endif; ?>"
                                           @click.prevent="window.conversationManager.changeAssignee(<?php echo e($user->id); ?>); open = false">
                                            <?php echo e($user->getFullName()); ?>@action('assignee_list.item_append', $user)
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    
                    <div x-data="{ open: false }" class="relative">
                        <?php if($conversation->state != App\Conversation::STATE_DELETED): ?>
                            <button type="button" 
                                    @click="open = !open"
                                    class="flex items-center gap-2 px-3 py-2 rounded <?php echo e('bg-' . App\Conversation::$status_classes[$conversation->getStatus()] . '-100'); ?>"
                                    data-toggle="tooltip"
                                    title="<?php echo e(__('Status')); ?>: <?php echo e($conversation->getStatusName()); ?>">
                                <i class="glyphicon glyphicon-<?php echo e(App\Conversation::$status_icons[$conversation->getStatus()]); ?>"></i>
                                <span><?php echo e($conversation->getStatusName()); ?></span>
                                <i class="glyphicon glyphicon-chevron-down text-xs"></i>
                            </button>
                            <div x-show="open" 
                                 @click.away="open = false"
                                 class="dropdown-menu"
                                 x-transition>
                                <?php if($conversation->status != App\Conversation::STATUS_SPAM): ?>
                                    <?php $__currentLoopData = App\Conversation::$statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status => $dummy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <a href="#" 
                                           class="<?php if($conversation->status == $status): ?> bg-blue-50 <?php endif; ?>"
                                           @click.prevent="window.conversationManager.changeStatus(<?php echo e($status); ?>); open = false">
                                            <?php echo e(App\Conversation::statusCodeToName($status)); ?>

                                        </a>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php else: ?>
                                    <a href="#" @click.prevent="window.conversationManager.changeStatus('not_spam'); open = false">
                                        <?php echo e(__('Not Spam')); ?>

                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <button type="button" class="flex items-center gap-2 px-3 py-2 bg-gray-200 rounded">
                                <i class="glyphicon glyphicon-trash"></i>
                                <span><?php echo e(__('Deleted')); ?></span>
                            </button>
                        <?php endif; ?>
                    </div>

                    @action('conversation.convinfo.before_nav', $conversation, $mailbox)
                    
                    
                    <div class="flex items-center gap-1">
                        <a href="<?php echo e($conversation->urlPrev(App\Conversation::getFolderParam())); ?>" 
                           class="editor-btn"
                           data-toggle="tooltip" 
                           title="<?php echo e(__('Newer')); ?>">
                            <i class="glyphicon glyphicon-chevron-left"></i>
                        </a>
                        <a href="<?php echo e($conversation->urlNext(App\Conversation::getFolderParam())); ?>" 
                           class="editor-btn"
                           data-toggle="tooltip" 
                           title="<?php echo e(__('Older')); ?>">
                            <i class="glyphicon glyphicon-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            
            <div id="conv-subject" class="border-t pt-4">
                <div class="flex items-center justify-between" x-data="{ editing: false }">
                    <div class="flex-1">
                        <h1 class="text-2xl font-semibold" x-show="!editing">
                            <?php echo e($conversation->getSubject()); ?>

                        </h1>
                        <div class="flex items-center gap-2" x-show="editing">
                            <input type="text" 
                                   x-ref="subjectInput"
                                   value="<?php echo e($conversation->getSubject()); ?>" 
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500" />
                            <button type="button" 
                                    @click="window.conversationManager.updateSubject($refs.subjectInput.value); editing = false"
                                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                <i class="glyphicon glyphicon-ok"></i>
                            </button>
                            <button type="button" 
                                    @click="editing = false"
                                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                                <i class="glyphicon glyphicon-remove"></i>
                            </button>
                        </div>
                    </div>
                    
                    <?php if($conversation->isChat() && $conversation->getChannelName()): ?>
                        <div class="flex items-center gap-2">
                            <?php if(\Helper::isChatMode()): ?>
                                <a class="status-badge status-badge-active" 
                                   href="<?php echo e(request()->fullUrlWithQuery(['chat_mode' => '0'])); ?>" 
                                   title="<?php echo e(__('Exit')); ?>"
                                   data-toggle="tooltip">
                                    <i class="glyphicon glyphicon-stop"></i> <?php echo e(__('Chat Mode')); ?>

                                </a>
                            <?php else: ?>
                                <a class="status-badge status-badge-pending" 
                                   href="<?php echo e(request()->fullUrlWithQuery(['chat_mode' => '1'])); ?>">
                                    <i class="glyphicon glyphicon-play"></i> <?php echo e(__('Chat Mode')); ?>

                                </a>
                            <?php endif; ?>
                            <span class="status-badge">
                                <i class="glyphicon glyphicon-phone"></i> <?php echo e($conversation->getChannelName()); ?>

                            </span>
                        </div>
                    <?php endif; ?>
                    
                    @action('conversation.after_subject', $conversation, $mailbox)
                    
                    <div class="flex items-center gap-2">
                        <button type="button" 
                                @click="window.conversationManager.toggleStar()"
                                class="editor-btn"
                                title="<?php if($conversation->isStarredByUser()): ?><?php echo e(__('Unstar Conversation')); ?><?php else: ?><?php echo e(__('Star Conversation')); ?><?php endif; ?>">
                            <i class="glyphicon <?php if($conversation->isStarredByUser()): ?> glyphicon-star <?php else: ?> glyphicon-star-empty <?php endif; ?>"></i>
                        </button>
                        <span class="text-sm text-gray-600">#<strong><?php echo e($conversation->number); ?></strong></span>
                    </div>
                </div>

                
                <div id="conv-viewers" class="mt-4" x-show="$store.viewers && $store.viewers.length > 0">
                    <template x-for="viewer in $store.viewers" :key="viewer.id">
                        <div class="viewer-item">
                            <img :src="viewer.avatar" 
                                 :alt="viewer.name"
                                 class="w-8 h-8 rounded-full" />
                            <span x-text="viewer.replying ? viewer.name + ' <?php echo e(__('is replying')); ?>' : viewer.name + ' <?php echo e(__('is viewing')); ?>'"></span>
                        </div>
                    </template>
                </div>

                <?php if($is_in_chat_mode): ?>
                    <div class="conv-top-chat mt-4 p-4 bg-blue-50 rounded">
                        <?php if($conversation->user_id != Auth::user()->id): ?>
                            <button type="button" 
                                    @click="window.conversationManager.acceptChat()"
                                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                <?php echo e(__('Accept Chat')); ?>

                            </button>
                        <?php elseif(!$conversation->isClosed()): ?>
                            <button type="button" 
                                    @click="window.conversationManager.endChat()"
                                    class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                                <?php echo e(__('End Chat')); ?>

                            </button>
                        <?php endif; ?>
                        <button type="button" 
                                @click="$refs.details.classList.toggle('hidden')"
                                class="ml-2 text-blue-600 hover:underline">
                            <?php echo e(__('Show Details')); ?>

                        </button>
                    </div>
                    <div x-ref="details" class="hidden">
                <?php endif; ?>

                @action('conversation.after_subject_block', $conversation, $mailbox)
                
                <?php if($conversation->isInChatMode()): ?>
                    </div>
                <?php endif; ?>

                
                <div class="conv-action-wrapper mt-4" x-show="$store.showReplyForm">
                    <div class="conv-reply-block bg-white rounded-lg border border-gray-300 p-4">
                        <form class="form-reply" 
                              method="POST" 
                              action="<?php echo e(route('conversations.ajax_html', ['action' => 'send_reply'])); ?>"
                              @submit.prevent="window.conversationManager.submitReply($event)">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="conversation_id" value="<?php echo e($conversation->id); ?>" />
                            <input type="hidden" name="mailbox_id" value="<?php echo e($mailbox->id); ?>" />
                            <input type="hidden" name="saved_reply_id" value="" />
                            <input type="hidden" name="thread_id" value="" />
                            <input type="hidden" name="is_note" value="0" />
                            <input type="hidden" name="subtype" value="" />

                            
                            <?php if(count($from_aliases)): ?>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo e(__('From')); ?></label>
                                    <select name="from_alias" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                                        <?php $__currentLoopData = $from_aliases; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $from_alias_email => $from_alias_name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php if($from_alias_email != $mailbox->email): ?><?php echo e($from_alias_email); ?><?php endif; ?>" <?php if(!empty($from_alias) && $from_alias == $from_alias_email): ?>selected@endif>
                                                <?php if($from_alias_name): ?><?php echo e($from_alias_email); ?> (<?php echo e($from_alias_name); ?>)<?php else: ?><?php echo e($from_alias_email); ?><?php endif; ?>
                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            
                            <?php if(!empty($to_customers)): ?>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo e(__('To')); ?></label>
                                    <select name="to" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                                        <?php $__currentLoopData = $to_customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $to_customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($to_customer['email']); ?>" <?php if($to_customer['email'] == $conversation->customer_email): ?>selected@endif>
                                                <?php echo e($to_customer['customer']->getFullName(true)); ?> &lt;<?php echo e($to_customer['email']); ?>&gt;
                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            
                            <div class="mb-4" x-data="{ showCc: <?php echo e($cc ? 'true' : 'false'); ?>, showBcc: <?php echo e($bcc ? 'true' : 'false'); ?> }">
                                <button type="button" 
                                        @click="showCc = !showCc"
                                        class="text-sm text-blue-600 hover:underline">
                                    <?php echo e(__('Cc/Bcc')); ?>

                                </button>
                                
                                <div x-show="showCc" class="mt-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo e(__('Cc')); ?></label>
                                    <select name="cc[]" multiple class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                                        <?php if($cc): ?>
                                            <?php $__currentLoopData = $cc; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cc_email): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($cc_email); ?>" selected><?php echo e($cc_email); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div x-show="showBcc" class="mt-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo e(__('Bcc')); ?></label>
                                    <select name="bcc[]" multiple class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                                        <?php if($bcc): ?>
                                            <?php $__currentLoopData = $bcc; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bcc_email): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($bcc_email); ?>" selected><?php echo e($bcc_email); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            
                            <?php if(!empty($threads[0]) && $threads[0]->type == App\Thread::TYPE_NOTE && $threads[0]->created_by_user_id != Auth::user()->id && $threads[0]->created_by_user): ?>
                                <div class="alert alert-warning mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                                    <i class="glyphicon glyphicon-exclamation-sign"></i>
                                    <?php echo __('This reply will go to the customer. :%switch_start%Switch to a note:%switch_end% if you are replying to :user_name.', [
                                        '%switch_start%' => '<a href="#" class="text-blue-600 hover:underline switch-to-note">', 
                                        '%switch_end%' => '</a>', 
                                        'user_name' => htmlspecialchars($threads[0]->created_by_user->getFullName())
                                    ]); ?>

                                </div>
                            <?php endif; ?>

                            
                            <div id="dropzone-area" class="mb-4"></div>

                            
                            <div class="mb-4">
                                <div id="editor-container" 
                                     data-placeholder="<?php if($conversation->isInChatMode()): ?><?php echo e(__('Use ENTER to send the message and SHIFT+ENTER for a new line')); ?><?php else: ?><?php echo e(__('Type your reply...')); ?><?php endif; ?>"></div>
                                <textarea name="body" id="body" class="hidden"></textarea>
                                <?php if($errors->has('body')): ?>
                                    <p class="form-error"><?php echo e($errors->first('body')); ?></p>
                                <?php endif; ?>
                            </div>

                            
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                        <?php echo e(__('Send Reply')); ?>

                                    </button>
                                    <button type="button" 
                                            @click="window.conversationManager.createNote()"
                                            class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                                        <?php echo e(__('Add Note')); ?>

                                    </button>
                                    <button type="button" 
                                            @click="window.conversationManager.saveDraft()"
                                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                                        <?php echo e(__('Save Draft')); ?>

                                    </button>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <span x-show="$store.draftSaved"><?php echo e(__('Draft saved')); ?></span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        
        <div id="conv-layout-customer" class="w-80 bg-white shadow-sm rounded-lg p-4">
            <?php echo $__env->make('conversations/partials/customer_sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            @action('conversation.after_customer_sidebar', $conversation)
        </div>

        
        <div id="conv-layout-main" class="flex-1">
            @action('conversation.before_threads', $conversation)
            <?php echo $__env->make('conversations/partials/threads', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            @action('conversation.after_threads', $conversation)
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('body_bottom'); ?>
    <?php echo \Illuminate\View\Factory::parentPlaceholder('body_bottom'); ?>
    <?php echo $__env->make('conversations.partials.settings_modal', ['conversation' => $conversation], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('javascript'); ?>
    <?php echo \Illuminate\View\Factory::parentPlaceholder('javascript'); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize conversation manager
            if (typeof ConversationManager !== 'undefined') {
                window.conversationManager = new ConversationManager({
                    conversationId: <?php echo e($conversation->id); ?>,
                    mailboxId: <?php echo e($mailbox->id); ?>,
                    editorSelector: '#editor-container',
                    uploaderSelector: '#dropzone-area'
                });
            }

            // Alpine.js store for reactive data
            if (typeof Alpine !== 'undefined') {
                Alpine.store('showReplyForm', false);
                Alpine.store('draftSaved', false);
                Alpine.store('viewers', []);
            }
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/runner/work/freescout/freescout/resources/views/conversations/view.blade.php ENDPATH**/ ?>