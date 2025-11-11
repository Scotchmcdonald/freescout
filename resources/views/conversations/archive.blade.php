{{-- Conversation Archive: View for archived/closed conversations --}}
@extends('layouts.app')

@section('title_full', __('Archived Conversations'))

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu_view')
@endsection

@section('content')
    @include('partials/flash_messages')
    
    <div class="container-fluid">
        <div class="row">
            <div class="col-xs-12">
                <h1 class="page-title">
                    <i class="glyphicon glyphicon-folder-close"></i>
                    {{ __('Archived Conversations') }}
                </h1>
                <p class="text-muted">
                    {{ __('View and manage closed conversations from the archive.') }}
                </p>
            </div>
        </div>
        
        {{-- Archive Filters --}}
        <div class="row margin-top">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <form method="GET" action="{{ route('conversations.archive') }}" class="form-inline">
                            <div class="form-group">
                                <label for="archive_mailbox">{{ __('Mailbox') }}</label>
                                <select name="mailbox_id" id="archive_mailbox" class="form-control">
                                    <option value="">{{ __('All Mailboxes') }}</option>
                                    @if (!empty($mailboxes))
                                        @foreach ($mailboxes as $mailbox)
                                            <option value="{{ $mailbox->id }}" @if (request('mailbox_id') == $mailbox->id) selected @endif>
                                                {{ $mailbox->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="archive_period">{{ __('Closed') }}</label>
                                <select name="period" id="archive_period" class="form-control">
                                    <option value="week" @if (request('period', 'week') == 'week') selected @endif>{{ __('Last 7 days') }}</option>
                                    <option value="month" @if (request('period') == 'month') selected @endif>{{ __('Last 30 days') }}</option>
                                    <option value="quarter" @if (request('period') == 'quarter') selected @endif>{{ __('Last 90 days') }}</option>
                                    <option value="year" @if (request('period') == 'year') selected @endif>{{ __('Last year') }}</option>
                                    <option value="all" @if (request('period') == 'all') selected @endif>{{ __('All time') }}</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="archive_user">{{ __('Closed by') }}</label>
                                <select name="user_id" id="archive_user" class="form-control">
                                    <option value="">{{ __('Anyone') }}</option>
                                    @if (!empty($users))
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}" @if (request('user_id') == $user->id) selected @endif>
                                                {{ $user->getFullName() }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="archive_search">{{ __('Search') }}</label>
                                <input type="text" 
                                       name="q" 
                                       id="archive_search" 
                                       class="form-control" 
                                       placeholder="{{ __('Search...') }}"
                                       value="{{ request('q') }}">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="glyphicon glyphicon-filter"></i>
                                {{ __('Filter') }}
                            </button>
                            
                            @if (request()->hasAny(['mailbox_id', 'period', 'user_id', 'q']))
                                <a href="{{ route('conversations.archive') }}" class="btn btn-default">
                                    <i class="glyphicon glyphicon-remove"></i>
                                    {{ __('Clear Filters') }}
                                </a>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Archive Statistics --}}
        <div class="row margin-top">
            <div class="col-sm-4">
                <div class="panel panel-default">
                    <div class="panel-body text-center">
                        <h3>{{ $archive_stats['total'] ?? 0 }}</h3>
                        <p class="text-muted">{{ __('Total Archived') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="panel panel-default">
                    <div class="panel-body text-center">
                        <h3>{{ $archive_stats['this_month'] ?? 0 }}</h3>
                        <p class="text-muted">{{ __('This Month') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="panel panel-default">
                    <div class="panel-body text-center">
                        <h3>{{ $archive_stats['avg_resolution_time'] ?? '-' }}</h3>
                        <p class="text-muted">{{ __('Avg Resolution Time') }}</p>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Archived Conversations List --}}
        <div class="row margin-top">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-sm-6">
                                <h3 class="panel-title">
                                    {{ __('Archived Conversations') }}
                                    @if (!empty($conversations))
                                        <span class="badge">{{ $conversations->total() }}</span>
                                    @endif
                                </h3>
                            </div>
                            <div class="col-sm-6 text-right">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                        <i class="glyphicon glyphicon-cog"></i>
                                        {{ __('Actions') }} <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                        <li>
                                            <a href="#" id="export-archive">
                                                <i class="glyphicon glyphicon-export"></i>
                                                {{ __('Export Archive') }}
                                            </a>
                                        </li>
                                        @if (Auth::user()->isAdmin())
                                            <li class="divider"></li>
                                            <li>
                                                <a href="#" id="cleanup-archive" class="text-danger">
                                                    <i class="glyphicon glyphicon-trash"></i>
                                                    {{ __('Cleanup Old Archives') }}
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    @if (!empty($conversations) && $conversations->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="50">#</th>
                                        <th>{{ __('Subject') }}</th>
                                        <th>{{ __('Customer') }}</th>
                                        <th>{{ __('Mailbox') }}</th>
                                        <th>{{ __('Closed By') }}</th>
                                        <th>{{ __('Closed At') }}</th>
                                        <th width="100">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($conversations as $conversation)
                                        <tr>
                                            <td>
                                                <a href="{{ route('conversations.view', ['id' => $conversation->id]) }}">
                                                    #{{ $conversation->number }}
                                                </a>
                                            </td>
                                            <td>
                                                <a href="{{ route('conversations.view', ['id' => $conversation->id]) }}" class="conv-subject">
                                                    {{ $conversation->getSubject() }}
                                                </a>
                                            </td>
                                            <td>
                                                @if ($conversation->customer)
                                                    {{ $conversation->customer->getFullName(true) }}
                                                @else
                                                    {{ $conversation->customer_email }}
                                                @endif
                                            </td>
                                            <td>
                                                @if ($conversation->mailbox)
                                                    {{ $conversation->mailbox->name }}
                                                @endif
                                            </td>
                                            <td>
                                                @if ($conversation->closed_by_user)
                                                    {{ $conversation->closed_by_user->getFullName() }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                <span data-toggle="tooltip" title="{{ App\User::dateFormat($conversation->closed_at) }}">
                                                    {{ App\User::dateDiffForHumans($conversation->closed_at) }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-xs">
                                                    <a href="{{ route('conversations.view', ['id' => $conversation->id]) }}" 
                                                       class="btn btn-default" 
                                                       title="{{ __('View') }}"
                                                       data-toggle="tooltip">
                                                        <i class="glyphicon glyphicon-eye-open"></i>
                                                    </a>
                                                    <a href="{{ route('conversations.view', ['id' => $conversation->id, 'print' => 1]) }}" 
                                                       class="btn btn-default" 
                                                       title="{{ __('Print') }}"
                                                       data-toggle="tooltip"
                                                       target="_blank">
                                                        <i class="glyphicon glyphicon-print"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        {{-- Pagination --}}
                        @if ($conversations->hasPages())
                            <div class="panel-footer">
                                @include('partials/pagination', ['paginator' => $conversations, 'show_results_summary' => true])
                            </div>
                        @endif
                    @else
                        <div class="panel-body">
                            <div class="alert alert-info">
                                <i class="glyphicon glyphicon-info-sign"></i>
                                {{ __('No archived conversations found matching your criteria.') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('stylesheets')
    <style>
        .margin-top {
            margin-top: 20px;
        }
        
        .conv-subject {
            font-weight: 500;
        }
        
        .table > tbody > tr > td {
            vertical-align: middle;
        }
    </style>
@endsection

@section('javascript')
    @parent
    <script>
        $(document).ready(function() {
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
            
            // Export archive handler
            $('#export-archive').on('click', function(e) {
                e.preventDefault();
                alert('{{ __('Export functionality would be implemented here') }}');
            });
            
            // Cleanup archive handler
            $('#cleanup-archive').on('click', function(e) {
                e.preventDefault();
                if (confirm('{{ __('Are you sure you want to cleanup old archives? This action cannot be undone.') }}')) {
                    alert('{{ __('Cleanup functionality would be implemented here') }}');
                }
            });
        });
    </script>
@endsection
