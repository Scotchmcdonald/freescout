{{-- Bulk Action Confirmation Modal: Confirms bulk operations on conversations --}}
<div class="modal fade" tabindex="-1" role="dialog" id="bulk-action-confirm-modal" aria-labelledby="bulk-action-confirm-title">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="bulk-action-confirm-title">{{ __('Confirm Bulk Action') }}</h4>
            </div>
            <div class="modal-body">
                <div class="bulk-action-message">
                    <p class="bulk-action-description"></p>
                    <div class="alert alert-warning bulk-action-warning hidden" role="alert">
                        <i class="glyphicon glyphicon-warning-sign" aria-hidden="true"></i>
                        <span class="bulk-action-warning-text"></span>
                    </div>
                    <div class="bulk-action-details">
                        <p>
                            <strong>{{ __('Selected conversations') }}:</strong> 
                            <span class="bulk-action-count">0</span>
                        </p>
                        <div class="bulk-action-list hidden">
                            <ul class="list-unstyled bulk-action-items"></ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    {{ __('Cancel') }}
                </button>
                <button type="button" class="btn btn-primary bulk-action-confirm-btn" data-action="" data-loading-text="{{ __('Processing') }}...">
                    {{ __('Confirm') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    /**
     * Initialize bulk action confirmation modal
     * Usage: showBulkActionConfirm(action, count, description, warning)
     */
    window.showBulkActionConfirm = function(action, count, description, warning) {
        var $modal = $('#bulk-action-confirm-modal');
        var actionNames = {
            'delete': '{{ __('Delete') }}',
            'close': '{{ __('Close') }}',
            'open': '{{ __('Open') }}',
            'spam': '{{ __('Mark as Spam') }}',
            'not_spam': '{{ __('Not Spam') }}',
            'move': '{{ __('Move') }}',
            'assign': '{{ __('Assign') }}'
        };
        
        // Set action
        $modal.find('.bulk-action-confirm-btn').attr('data-action', action);
        
        // Set title
        var actionName = actionNames[action] || action;
        $modal.find('.modal-title').text('{{ __('Confirm') }} ' + actionName);
        
        // Set description
        $modal.find('.bulk-action-description').text(description || '{{ __('Are you sure you want to perform this action?') }}');
        
        // Set count
        $modal.find('.bulk-action-count').text(count);
        
        // Set warning if provided
        if (warning) {
            $modal.find('.bulk-action-warning').removeClass('hidden');
            $modal.find('.bulk-action-warning-text').text(warning);
        } else {
            $modal.find('.bulk-action-warning').addClass('hidden');
        }
        
        // Update button text
        if (action === 'delete') {
            $modal.find('.bulk-action-confirm-btn').removeClass('btn-primary').addClass('btn-danger');
        } else {
            $modal.find('.bulk-action-confirm-btn').removeClass('btn-danger').addClass('btn-primary');
        }
        
        // Show modal
        $modal.modal('show');
    };
    
    // Handle confirm button click
    $(document).on('click', '.bulk-action-confirm-btn', function() {
        var $btn = $(this);
        var action = $btn.attr('data-action');
        
        // Trigger custom event that can be listened to
        $(document).trigger('bulk-action-confirmed', [action]);
        
        // Hide modal
        $('#bulk-action-confirm-modal').modal('hide');
    });
</script>
