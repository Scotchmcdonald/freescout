{{-- Conversation Statistics Dashboard: Analytics and metrics for conversations --}}
@extends('layouts.app')

@section('title_full', __('Conversation Statistics'))

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu_view')
@endsection

@section('content')
    @include('partials/flash_messages')
    
    <div class="container-fluid">
        <div class="row margin-top">
            <div class="col-xs-12">
                <h1 class="page-title">
                    <i class="glyphicon glyphicon-stats"></i>
                    {{ __('Conversation Statistics') }}
                </h1>
            </div>
        </div>
        
        {{-- Filter Controls --}}
        <div class="row margin-top">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <form method="GET" action="{{ route('conversations.statistics') }}" class="form-inline">
                            <div class="form-group">
                                <label for="mailbox_filter">{{ __('Mailbox') }}</label>
                                <select name="mailbox_id" id="mailbox_filter" class="form-control">
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
                                <label for="period_filter">{{ __('Period') }}</label>
                                <select name="period" id="period_filter" class="form-control">
                                    <option value="today" @if (request('period', 'today') == 'today') selected @endif>{{ __('Today') }}</option>
                                    <option value="yesterday" @if (request('period') == 'yesterday') selected @endif>{{ __('Yesterday') }}</option>
                                    <option value="week" @if (request('period') == 'week') selected @endif>{{ __('This Week') }}</option>
                                    <option value="month" @if (request('period') == 'month') selected @endif>{{ __('This Month') }}</option>
                                    <option value="year" @if (request('period') == 'year') selected @endif>{{ __('This Year') }}</option>
                                    <option value="custom" @if (request('period') == 'custom') selected @endif>{{ __('Custom Range') }}</option>
                                </select>
                            </div>
                            
                            <div class="form-group custom-date-range" style="display: none;">
                                <label for="date_from">{{ __('From') }}</label>
                                <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                            </div>
                            
                            <div class="form-group custom-date-range" style="display: none;">
                                <label for="date_to">{{ __('To') }}</label>
                                <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="glyphicon glyphicon-filter"></i>
                                {{ __('Apply Filters') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Summary Statistics Cards --}}
        <div class="row margin-top">
            <div class="col-md-3 col-sm-6">
                <div class="panel panel-default stats-card">
                    <div class="panel-body text-center">
                        <div class="stats-icon text-primary">
                            <i class="glyphicon glyphicon-inbox"></i>
                        </div>
                        <h3 class="stats-value">{{ $stats['total_conversations'] ?? 0 }}</h3>
                        <p class="stats-label">{{ __('Total Conversations') }}</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="panel panel-default stats-card">
                    <div class="panel-body text-center">
                        <div class="stats-icon text-success">
                            <i class="glyphicon glyphicon-ok-circle"></i>
                        </div>
                        <h3 class="stats-value">{{ $stats['closed_conversations'] ?? 0 }}</h3>
                        <p class="stats-label">{{ __('Closed Conversations') }}</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="panel panel-default stats-card">
                    <div class="panel-body text-center">
                        <div class="stats-icon text-warning">
                            <i class="glyphicon glyphicon-time"></i>
                        </div>
                        <h3 class="stats-value">{{ $stats['open_conversations'] ?? 0 }}</h3>
                        <p class="stats-label">{{ __('Open Conversations') }}</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="panel panel-default stats-card">
                    <div class="panel-body text-center">
                        <div class="stats-icon text-info">
                            <i class="glyphicon glyphicon-hourglass"></i>
                        </div>
                        <h3 class="stats-value">{{ $stats['avg_response_time'] ?? '-' }}</h3>
                        <p class="stats-label">{{ __('Avg Response Time') }}</p>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Status Breakdown --}}
        <div class="row margin-top">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">{{ __('Conversations by Status') }}</h3>
                    </div>
                    <div class="panel-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('Status') }}</th>
                                    <th class="text-right">{{ __('Count') }}</th>
                                    <th class="text-right">{{ __('Percentage') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (!empty($stats['by_status']))
                                    @foreach ($stats['by_status'] as $status => $count)
                                        <tr>
                                            <td>
                                                <span class="badge badge-{{ App\Conversation::$status_classes[$status] ?? 'default' }}">
                                                    {{ App\Conversation::statusCodeToName($status) }}
                                                </span>
                                            </td>
                                            <td class="text-right">{{ $count }}</td>
                                            <td class="text-right">
                                                {{ $stats['total_conversations'] > 0 ? round($count / $stats['total_conversations'] * 100, 1) : 0 }}%
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">{{ __('No data available') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">{{ __('Top Users by Conversations') }}</h3>
                    </div>
                    <div class="panel-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('User') }}</th>
                                    <th class="text-right">{{ __('Assigned') }}</th>
                                    <th class="text-right">{{ __('Closed') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (!empty($stats['by_user']))
                                    @foreach ($stats['by_user'] as $user_stat)
                                        <tr>
                                            <td>{{ $user_stat['name'] ?? __('Unknown') }}</td>
                                            <td class="text-right">{{ $user_stat['assigned'] ?? 0 }}</td>
                                            <td class="text-right">{{ $user_stat['closed'] ?? 0 }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">{{ __('No data available') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Performance Metrics --}}
        <div class="row margin-top">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">{{ __('Performance Metrics') }}</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-3 col-sm-6">
                                <div class="metric-item">
                                    <h4>{{ $stats['first_response_time'] ?? '-' }}</h4>
                                    <p class="text-muted">{{ __('First Response Time') }}</p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="metric-item">
                                    <h4>{{ $stats['resolution_time'] ?? '-' }}</h4>
                                    <p class="text-muted">{{ __('Avg Resolution Time') }}</p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="metric-item">
                                    <h4>{{ $stats['replies_per_conversation'] ?? '-' }}</h4>
                                    <p class="text-muted">{{ __('Replies per Conversation') }}</p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="metric-item">
                                    <h4>{{ $stats['satisfaction_rate'] ?? '-' }}</h4>
                                    <p class="text-muted">{{ __('Satisfaction Rate') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        @action('conversations.statistics.after', $stats ?? [])
    </div>
@endsection

@section('stylesheets')
    <style>
        .stats-card .stats-icon {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .stats-card .stats-value {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stats-card .stats-label {
            color: #666;
            margin: 0;
        }
        
        .metric-item {
            text-align: center;
            padding: 15px;
        }
        
        .metric-item h4 {
            font-size: 24px;
            font-weight: bold;
            margin: 0 0 5px 0;
        }
        
        .margin-top {
            margin-top: 20px;
        }
    </style>
@endsection

@section('javascript')
    @parent
    <script>
        $(document).ready(function() {
            // Show/hide custom date range
            $('#period_filter').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('.custom-date-range').show();
                } else {
                    $('.custom-date-range').hide();
                }
            }).trigger('change');
        });
    </script>
@endsection
