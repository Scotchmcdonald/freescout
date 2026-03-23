<?php

declare(strict_types=1);

namespace Modules\AppHealth\Services;

use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Schema;
use Modules\AppHealth\Contracts\MetricRecorderContract;
use Modules\AppHealth\Models\ScalingScorecardSnapshot;

class MetricRecorderService implements MetricRecorderContract
{
    private const COUNTER_PREFIX = 'apphealth:counter:';

    private const SUMMARY_PREFIX = 'apphealth:summary:';

    private const HISTOGRAM_PREFIX = 'apphealth:histogram:';

    private const INDEX_KEY = 'apphealth:metric:index';

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly LegacyMetricsCompatibilityAdapter $legacyAdapter
    ) {}

    public function increment(string $name, int|float $value = 1, array $labels = []): void
    {
        $key = self::COUNTER_PREFIX.$this->normalizedMetricKey($name, $labels);
        $current = (float) $this->cache->get($key, 0.0);

        $this->cache->forever($key, $current + (float) $value);
        $this->rememberMetricKey(self::COUNTER_PREFIX, $key);
        $this->legacyAdapter->recordCounter($name, $value, $labels);
    }

    public function observe(string $name, float $value, array $labels = []): void
    {
        $key = self::SUMMARY_PREFIX.$this->normalizedMetricKey($name, $labels);
        $summary = $this->cache->get($key, [
            'count' => 0,
            'sum' => 0.0,
            'last' => 0.0,
        ]);

        if (! is_array($summary)) {
            $summary = ['count' => 0, 'sum' => 0.0, 'last' => 0.0];
        }

        $summary['count'] = (int) ($summary['count'] ?? 0) + 1;
        $summary['sum'] = (float) ($summary['sum'] ?? 0.0) + $value;
        $summary['last'] = $value;

        $this->cache->forever($key, $summary);
        $this->rememberMetricKey(self::SUMMARY_PREFIX, $key);
        $this->legacyAdapter->recordObservation($name, $value, $labels);
    }

    public function timing(string $name, float $milliseconds, array $labels = []): void
    {
        $this->observe($name.'_milliseconds', $milliseconds, $labels);
    }

    public function recordHistogram(string $name, float $value, array $labels = []): void
    {
        $bounds = config('apphealth.histogram_buckets', [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0]);

        if (! is_array($bounds)) {
            $bounds = [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0];
        }

        // Increment count + sum under summary key (reuses existing observe storage)
        $summaryKey = self::SUMMARY_PREFIX.$this->normalizedMetricKey($name, $labels);
        $summary = $this->cache->get($summaryKey, ['count' => 0, 'sum' => 0.0, 'last' => 0.0]);

        if (! is_array($summary)) {
            $summary = ['count' => 0, 'sum' => 0.0, 'last' => 0.0];
        }

        $summary['count'] = (int) ($summary['count'] ?? 0) + 1;
        $summary['sum'] = (float) ($summary['sum'] ?? 0.0) + $value;
        $summary['last'] = $value;
        $this->cache->forever($summaryKey, $summary);
        $this->rememberMetricKey(self::SUMMARY_PREFIX, $summaryKey);

        // Increment each bucket where value <= bound
        foreach ($bounds as $bound) {
            $boundFloat = (float) $bound;
            if ($value <= $boundFloat) {
                $bucketKey = self::HISTOGRAM_PREFIX.$this->normalizedMetricKey($name, array_merge($labels, ['le' => (string) $boundFloat]));
                $current = (int) $this->cache->get($bucketKey, 0);
                $this->cache->forever($bucketKey, $current + 1);
                $this->rememberMetricKey(self::HISTOGRAM_PREFIX, $bucketKey);
            }
        }

        // +Inf bucket always equals count
        $infKey = self::HISTOGRAM_PREFIX.$this->normalizedMetricKey($name, array_merge($labels, ['le' => '+Inf']));
        $infCount = (int) $this->cache->get($infKey, 0);
        $this->cache->forever($infKey, $infCount + 1);
        $this->rememberMetricKey(self::HISTOGRAM_PREFIX, $infKey);

        $this->legacyAdapter->recordObservation($name, $value, $labels);
    }

    public function renderPrometheus(): string
    {
        $lines = [
            '# HELP apphealth_up AppHealth endpoint availability',
            '# TYPE apphealth_up gauge',
            'apphealth_up 1',
        ];

        $lines = array_merge($lines, $this->renderCachedCounters(), $this->renderCachedSummaries(), $this->renderCachedHistograms(), $this->renderScorecardMetrics());

        return implode("\n", $lines)."\n";
    }

    /**
     * @return array<int, string>
     */
    private function renderCachedCounters(): array
    {
        $lines = [];

        foreach ($this->allCacheValuesByPrefix(self::COUNTER_PREFIX) as $key => $value) {
            $metric = str_replace(self::COUNTER_PREFIX, '', $key);
            $lines[] = sprintf('apphealth_counter_total{metric="%s"} %s', $metric, (float) $value);
        }

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    private function renderCachedSummaries(): array
    {
        $lines = [];

        foreach ($this->allCacheValuesByPrefix(self::SUMMARY_PREFIX) as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            $metric = str_replace(self::SUMMARY_PREFIX, '', $key);
            $count = (int) ($value['count'] ?? 0);
            $sum = (float) ($value['sum'] ?? 0.0);
            $last = (float) ($value['last'] ?? 0.0);

            $lines[] = sprintf('apphealth_summary_count{metric="%s"} %d', $metric, $count);
            $lines[] = sprintf('apphealth_summary_sum{metric="%s"} %s', $metric, $sum);
            $lines[] = sprintf('apphealth_summary_last{metric="%s"} %s', $metric, $last);
        }

        return $lines;
    }

    /**
     * Emits Prometheus-native histogram lines: one _bucket per le label, plus _count and _sum.
     * Groups by metric base name (strips the le= from the key).
     *
     * @return array<int, string>
     */
    private function renderCachedHistograms(): array
    {
        $lines = [];
        $emittedHeaders = [];

        foreach ($this->allCacheValuesByPrefix(self::HISTOGRAM_PREFIX) as $key => $bucketCount) {
            // Key format: apphealth:histogram:<name>|le=<bound>[,label=val...]
            $metricPart = str_replace(self::HISTOGRAM_PREFIX, '', $key);

            // Extract le value from label segment
            $pipePos = strpos($metricPart, '|');
            $baseName = $pipePos !== false ? substr($metricPart, 0, $pipePos) : $metricPart;
            $labelSegment = $pipePos !== false ? substr($metricPart, $pipePos + 1) : '';

            // Parse labels back into Prometheus format: {label="value",...}
            $labelParts = [];

            foreach (explode(',', $labelSegment) as $pair) {
                if (str_contains($pair, '=')) {
                    [$lk, $lv] = explode('=', $pair, 2);
                    $labelParts[] = sprintf('%s="%s"', $lk, $lv);
                }
            }

            $labelStr = $labelParts !== [] ? '{'.implode(',', $labelParts).'}' : '';

            if (! isset($emittedHeaders[$baseName])) {
                $lines[] = sprintf('# HELP %s_bucket HTTP request duration histogram', $baseName);
                $lines[] = sprintf('# TYPE %s_bucket histogram', $baseName);
                $emittedHeaders[$baseName] = true;
            }

            $lines[] = sprintf('%s_bucket%s %d', $baseName, $labelStr, (int) $bucketCount);
        }

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    private function renderScorecardMetrics(): array
    {
        try {
            if (! Schema::hasTable('app_health_scaling_scorecard_snapshots')) {
                return [];
            }

            $snapshot = ScalingScorecardSnapshot::query()->latest('snapshot_date')->first();
        } catch (\Throwable) {
            return [];
        }

        if (! $snapshot) {
            return [];
        }

        return [
            sprintf('apphealth_stage_a_breach_count %d', $snapshot->breach_count),
            sprintf('apphealth_stage_a_schedule_recommended %d', $snapshot->recommendation === 'schedule_stage_a_work' ? 1 : 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function allCacheValuesByPrefix(string $prefix): array
    {
        $index = $this->metricIndex();
        $keys = $index[$prefix] ?? [];

        if (! is_array($keys)) {
            return [];
        }

        $out = [];

        foreach ($keys as $key) {
            if (! is_string($key) || ! str_starts_with($key, $prefix)) {
                continue;
            }

            $out[$key] = $this->cache->get($key);
        }

        return $out;
    }

    private function rememberMetricKey(string $prefix, string $key): void
    {
        $index = $this->metricIndex();
        $bucket = $index[$prefix] ?? [];

        if (! is_array($bucket)) {
            $bucket = [];
        }

        if (! in_array($key, $bucket, true)) {
            $bucket[] = $key;
            $index[$prefix] = array_values($bucket);
            $this->cache->forever(self::INDEX_KEY, $index);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function metricIndex(): array
    {
        $index = $this->cache->get(self::INDEX_KEY, []);

        return is_array($index) ? $index : [];
    }

    /**
     * @param  array<string, scalar>  $labels
     */
    private function normalizedMetricKey(string $name, array $labels): string
    {
        ksort($labels);
        $labelParts = [];

        foreach ($labels as $k => $v) {
            $labelParts[] = $k.'='.$v;
        }

        return $name.'|'.implode(',', $labelParts);
    }
}
