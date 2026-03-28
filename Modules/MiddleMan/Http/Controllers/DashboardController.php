<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MiddleMan\Models\MiddleManAuditEntry;
use Modules\MiddleMan\Models\MiddleManIntercept;
use Modules\MiddleMan\Models\MiddleManLog;
use Modules\MiddleMan\Services\RuleEngine;

class DashboardController extends Controller
{
    public function index(RuleEngine $ruleEngine)
    {
        $rules = $ruleEngine->getRules();

        $metrics = [
            'total_logs'           => MiddleManLog::count(),
            'logs_last_hour'       => MiddleManLog::recent(60)->count(),
            'pending_intercepts'   => MiddleManIntercept::pending()->count(),
            'fired_intercepts'     => MiddleManIntercept::where('status', MiddleManIntercept::STATUS_FIRED)->count(),
            'discarded_intercepts' => MiddleManIntercept::where('status', MiddleManIntercept::STATUS_DISCARDED)->count(),
            'unique_event_types'   => MiddleManLog::distinct('event_class')->count('event_class'),
        ];

        $recentAudit = MiddleManAuditEntry::with('user')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $loggingActive   = $ruleEngine->isLoggingActive();
        $interceptActive = $ruleEngine->isInterceptActive();
        $moduleEnabled   = (bool) config('middleman.enabled');

        return view('middleman::dashboard.index', compact(
            'metrics',
            'rules',
            'recentAudit',
            'loggingActive',
            'interceptActive',
            'moduleEnabled',
        ));
    }
}
