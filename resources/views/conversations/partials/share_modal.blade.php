{{-- Conversation Share Modal: Share conversation with external parties or generate public link --}}
<div class="modal fade" tabindex="-1" role="dialog" id="conv-share-modal" aria-labelledby="conv-share-title">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="conv-share-title">
                    <i class="glyphicon glyphicon-share"></i>
                    {{ __('Share Conversation') }}
                </h4>
            </div>
            <div class="modal-body">
                <form id="share-conversation-form">
                    {{ csrf_field() }}
                    <input type="hidden" name="conversation_id" value="{{ $conversation->id ?? '' }}">
                    
                    <ul class="nav nav-tabs" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#share-email" aria-controls="share-email" role="tab" data-toggle="tab">
                                {{ __('Share via Email') }}
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#share-link" aria-controls="share-link" role="tab" data-toggle="tab">
                                {{ __('Public Link') }}
                            </a>
                        </li>
                    </ul>
                    
                    <div class="tab-content" style="margin-top: 20px;">
                        {{-- Email Sharing Tab --}}
                        <div role="tabpanel" class="tab-pane active" id="share-email">
                            <div class="form-group">
                                <label for="share_email_to">{{ __('Email Address') }}</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="share_email_to" 
                                       name="email_to" 
                                       placeholder="{{ __('Enter email address') }}"
                                       required>
                                <p class="help-block">{{ __('Enter the email address of the person you want to share this conversation with.') }}</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="share_message">{{ __('Message') }} ({{ __('Optional') }})</label>
                                <textarea class="form-control" 
                                          id="share_message" 
                                          name="message" 
                                          rows="3" 
                                          placeholder="{{ __('Add a personal message...') }}"></textarea>
                            </div>
                            
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="include_attachments" value="1" checked>
                                    {{ __('Include attachments') }}
                                </label>
                            </div>
                            
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="hide_notes" value="1" checked>
                                    {{ __('Hide internal notes') }}
                                </label>
                            </div>
                        </div>
                        
                        {{-- Public Link Tab --}}
                        <div role="tabpanel" class="tab-pane" id="share-link">
                            <div class="alert alert-info">
                                <i class="glyphicon glyphicon-info-sign"></i>
                                {{ __('Generate a public link that anyone can use to view this conversation.') }}
                            </div>
                            
                            <div class="form-group">
                                <label>{{ __('Link Expiration') }}</label>
                                <select class="form-control" name="link_expiration">
                                    <option value="1">{{ __('1 hour') }}</option>
                                    <option value="24">{{ __('24 hours') }}</option>
                                    <option value="168" selected>{{ __('7 days') }}</option>
                                    <option value="720">{{ __('30 days') }}</option>
                                    <option value="never">{{ __('Never expires') }}</option>
                                </select>
                            </div>
                            
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="require_password" value="1">
                                    {{ __('Require password') }}
                                </label>
                            </div>
                            
                            <div class="form-group password-group" style="display: none; margin-top: 10px;">
                                <label for="share_password">{{ __('Password') }}</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="share_password" 
                                       name="password" 
                                       placeholder="{{ __('Enter password') }}">
                            </div>
                            
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="allow_replies" value="1">
                                    {{ __('Allow replies via link') }}
                                </label>
                            </div>
                            
                            <div class="form-group" id="generated-link-group" style="display: none; margin-top: 20px;">
                                <label>{{ __('Share Link') }}</label>
                                <div class="input-group">
                                    <input type="text" 
                                           class="form-control" 
                                           id="generated-link" 
                                           readonly>
                                    <span class="input-group-btn">
                                        <button class="btn btn-default" type="button" id="copy-link-btn">
                                            <i class="glyphicon glyphicon-copy"></i>
                                            {{ __('Copy') }}
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    {{ __('Cancel') }}
                </button>
                <button type="button" class="btn btn-primary" id="share-submit-btn">
                    <i class="glyphicon glyphicon-share"></i>
                    <span class="share-btn-text">{{ __('Share') }}</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        // Toggle password field
        $('input[name="require_password"]').on('change', function() {
            if ($(this).is(':checked')) {
                $('.password-group').slideDown();
            } else {
                $('.password-group').slideUp();
            }
        });
        
        // Handle tab change
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            var target = $(e.target).attr('href');
            if (target === '#share-link') {
                $('.share-btn-text').text('{{ __('Generate Link') }}');
            } else {
                $('.share-btn-text').text('{{ __('Share') }}');
            }
        });
        
        // Handle share button click
        $('#share-submit-btn').on('click', function() {
            var $btn = $(this);
            var $form = $('#share-conversation-form');
            var activeTab = $('.tab-pane.active').attr('id');
            
            $btn.prop('disabled', true).html('<i class="glyphicon glyphicon-refresh spinning"></i> {{ __('Processing') }}...');
            
            if (activeTab === 'share-email') {
                // Handle email sharing
                var emailTo = $('#share_email_to').val();
                if (!emailTo) {
                    alert('{{ __('Please enter an email address') }}');
                    $btn.prop('disabled', false).html('<i class="glyphicon glyphicon-share"></i> {{ __('Share') }}');
                    return;
                }
                
                // Would make AJAX call here
                setTimeout(function() {
                    alert('{{ __('Conversation shared successfully!') }}');
                    $('#conv-share-modal').modal('hide');
                    $btn.prop('disabled', false).html('<i class="glyphicon glyphicon-share"></i> {{ __('Share') }}');
                    $form[0].reset();
                }, 1000);
                
            } else {
                // Handle link generation
                // Would make AJAX call here
                setTimeout(function() {
                    var demoLink = window.location.origin + '/public/conversation/' + Math.random().toString(36).substr(2, 9);
                    $('#generated-link').val(demoLink);
                    $('#generated-link-group').slideDown();
                    $btn.prop('disabled', false).html('<i class="glyphicon glyphicon-refresh"></i> {{ __('Regenerate Link') }}');
                }, 1000);
            }
        });
        
        // Copy link to clipboard
        $('#copy-link-btn').on('click', function() {
            var $input = $('#generated-link');
            $input.select();
            document.execCommand('copy');
            
            var $btn = $(this);
            var originalHtml = $btn.html();
            $btn.html('<i class="glyphicon glyphicon-ok"></i> {{ __('Copied!') }}');
            
            setTimeout(function() {
                $btn.html(originalHtml);
            }, 2000);
        });
        
        // Reset form when modal closes
        $('#conv-share-modal').on('hidden.bs.modal', function() {
            $('#share-conversation-form')[0].reset();
            $('#generated-link-group').hide();
            $('.password-group').hide();
            $('.share-btn-text').text('{{ __('Share') }}');
            $('#share-submit-btn').prop('disabled', false);
            // Switch back to first tab
            $('.nav-tabs a:first').tab('show');
        });
    });
</script>

<style>
    .spinning {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .tab-content {
        min-height: 300px;
    }
</style>
