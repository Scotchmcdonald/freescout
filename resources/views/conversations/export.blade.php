{{-- Conversation Export View: Interface for exporting conversations in various formats --}}
@extends('layouts.app')

@section('title_full', __('Export Conversation') . ' #' . $conversation->number)

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu_view')
@endsection

@section('content')
    @include('partials/flash_messages')
    
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="glyphicon glyphicon-export"></i>
                            {{ __('Export Conversation') }} #{{ $conversation->number }}
                        </h3>
                    </div>
                    
                    <div class="panel-body">
                        <div class="export-info">
                            <h4>{{ $conversation->getSubject() }}</h4>
                            <p class="text-muted">
                                {{ __('Status') }}: {{ $conversation->getStatusName() }} | 
                                {{ __('Created') }}: {{ App\User::dateFormat($conversation->created_at) }}
                            </p>
                        </div>
                        
                        <hr>
                        
                        <form method="POST" action="{{ route('conversations.export', ['id' => $conversation->id]) }}" class="form-horizontal">
                            {{ csrf_field() }}
                            
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{ __('Export Format') }}</label>
                                <div class="col-sm-9">
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="format" value="pdf" checked>
                                            <strong>PDF</strong> - {{ __('Portable Document Format (recommended)') }}
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="format" value="html">
                                            <strong>HTML</strong> - {{ __('Web page format') }}
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="format" value="txt">
                                            <strong>TXT</strong> - {{ __('Plain text format') }}
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="format" value="json">
                                            <strong>JSON</strong> - {{ __('Machine-readable format') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{ __('Include') }}</label>
                                <div class="col-sm-9">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="include_attachments" value="1" checked>
                                            {{ __('Attachments') }}
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="include_notes" value="1" checked>
                                            {{ __('Internal Notes') }}
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="include_metadata" value="1" checked>
                                            {{ __('Conversation Metadata') }}
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="include_history" value="1">
                                            {{ __('Full Email Headers') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{ __('Thread Filter') }}</label>
                                <div class="col-sm-9">
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="thread_filter" value="all" checked>
                                            {{ __('All threads') }}
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="thread_filter" value="messages_only">
                                            {{ __('Messages only (exclude notes and system events)') }}
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="thread_filter" value="customer_only">
                                            {{ __('Customer messages only') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{ __('Date Range') }}</label>
                                <div class="col-sm-9">
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="date_range" value="all" checked>
                                            {{ __('All dates') }}
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="date_range" value="custom">
                                            {{ __('Custom range') }}
                                        </label>
                                    </div>
                                    <div class="date-range-inputs" style="margin-top: 10px; display: none;">
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <label>{{ __('From') }}</label>
                                                <input type="date" name="date_from" class="form-control">
                                            </div>
                                            <div class="col-sm-6">
                                                <label>{{ __('To') }}</label>
                                                <input type="date" name="date_to" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            @action('conversation.export_form', $conversation)
                            
                            <div class="form-group">
                                <div class="col-sm-9 col-sm-offset-3">
                                    <button type="submit" class="btn btn-primary" data-loading-text="{{ __('Exporting') }}...">
                                        <i class="glyphicon glyphicon-export"></i>
                                        {{ __('Export Conversation') }}
                                    </button>
                                    <a href="{{ route('conversations.view', ['id' => $conversation->id]) }}" class="btn btn-default">
                                        {{ __('Cancel') }}
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="alert alert-info" role="alert">
                    <i class="glyphicon glyphicon-info-sign"></i>
                    <strong>{{ __('Note') }}:</strong>
                    {{ __('The export will include all selected content from this conversation. Large conversations may take longer to export.') }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    @parent
    <script>
        $(document).ready(function() {
            // Show/hide date range inputs
            $('input[name="date_range"]').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('.date-range-inputs').slideDown();
                } else {
                    $('.date-range-inputs').slideUp();
                }
            });
            
            // Handle form submission
            $('form').on('submit', function() {
                var $btn = $(this).find('button[type="submit"]');
                $btn.button('loading');
            });
        });
    </script>
@endsection
