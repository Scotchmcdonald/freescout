{{-- Conversation Templates Modal: Quick access to saved reply templates --}}
<div class="modal fade" tabindex="-1" role="dialog" id="conv-templates-modal" aria-labelledby="conv-templates-title">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="conv-templates-title">
                    <i class="glyphicon glyphicon-list-alt"></i>
                    {{ __('Saved Reply Templates') }}
                </h4>
            </div>
            <div class="modal-body">
                <div class="templates-search">
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="glyphicon glyphicon-search"></i>
                        </span>
                        <input type="text" 
                               class="form-control" 
                               id="template-search" 
                               placeholder="{{ __('Search templates...') }}"
                               aria-label="{{ __('Search templates') }}">
                    </div>
                </div>
                
                <div class="templates-list" style="margin-top: 20px;">
                    <div class="list-group templates-items">
                        {{-- Templates will be loaded dynamically or can be passed as variable --}}
                        @if (!empty($saved_replies))
                            @foreach ($saved_replies as $reply)
                                <div class="list-group-item template-item" data-template-id="{{ $reply->id }}">
                                    <div class="template-item-header">
                                        <h5 class="template-item-name">{{ $reply->name }}</h5>
                                        <div class="template-item-actions">
                                            <button type="button" 
                                                    class="btn btn-xs btn-primary template-use-btn" 
                                                    data-template-id="{{ $reply->id }}"
                                                    data-loading-text="{{ __('Loading') }}...">
                                                {{ __('Use Template') }}
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-xs btn-default template-preview-btn" 
                                                    data-toggle="collapse" 
                                                    data-target="#template-preview-{{ $reply->id }}">
                                                {{ __('Preview') }}
                                            </button>
                                        </div>
                                    </div>
                                    <div class="collapse template-item-preview" id="template-preview-{{ $reply->id }}">
                                        <div class="template-preview-content">
                                            {!! $reply->text !!}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="alert alert-info">
                                <i class="glyphicon glyphicon-info-sign"></i>
                                {{ __('No saved reply templates found. Create templates to speed up your responses.') }}
                            </div>
                        @endif
                    </div>
                    
                    <div class="templates-empty hidden">
                        <div class="alert alert-warning">
                            <i class="glyphicon glyphicon-search"></i>
                            {{ __('No templates match your search.') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    {{ __('Close') }}
                </button>
                @if (Auth::user()->isAdmin())
                    <a href="{{ route('saved_replies') }}" class="btn btn-link" target="_blank">
                        {{ __('Manage Templates') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .templates-search {
        margin-bottom: 15px;
    }
    
    .template-item {
        margin-bottom: 10px;
    }
    
    .template-item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .template-item-name {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
    }
    
    .template-item-actions {
        white-space: nowrap;
    }
    
    .template-item-actions .btn {
        margin-left: 5px;
    }
    
    .template-item-preview {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #e0e0e0;
    }
    
    .template-preview-content {
        padding: 10px;
        background: #f9f9f9;
        border-radius: 3px;
        font-size: 13px;
        max-height: 200px;
        overflow-y: auto;
    }
    
    @media (max-width: 767px) {
        .template-item-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .template-item-actions {
            margin-top: 10px;
            width: 100%;
        }
        
        .template-item-actions .btn {
            margin-left: 0;
            margin-right: 5px;
        }
    }
</style>

<script type="text/javascript">
    $(document).ready(function() {
        // Template search functionality
        $('#template-search').on('keyup', function() {
            var searchText = $(this).val().toLowerCase();
            var $items = $('.template-item');
            var visibleCount = 0;
            
            $items.each(function() {
                var itemText = $(this).find('.template-item-name').text().toLowerCase();
                if (itemText.indexOf(searchText) > -1 || searchText === '') {
                    $(this).show();
                    visibleCount++;
                } else {
                    $(this).hide();
                }
            });
            
            // Show/hide empty state
            if (visibleCount === 0) {
                $('.templates-empty').removeClass('hidden');
                $('.templates-items').addClass('hidden');
            } else {
                $('.templates-empty').addClass('hidden');
                $('.templates-items').removeClass('hidden');
            }
        });
        
        // Use template button
        $(document).on('click', '.template-use-btn', function() {
            var $btn = $(this);
            var templateId = $btn.data('template-id');
            
            // Trigger custom event that can be listened to
            $(document).trigger('template-selected', [templateId]);
            
            // Close modal
            $('#conv-templates-modal').modal('hide');
        });
        
        // Clear search on modal close
        $('#conv-templates-modal').on('hidden.bs.modal', function() {
            $('#template-search').val('').trigger('keyup');
        });
    });
</script>
