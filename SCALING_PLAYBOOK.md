# Scaling Playbook

Date: 2026-03-20
Owner: Platform/Architecture
Status: Active

## Purpose
This playbook defines how we scale as customer count and ticket volume grow. It answers:
- What signals indicate we are nearing current architecture limits.
- Which implementation to schedule next.
- When to consider Kafka, microservices, and non-Laravel components.

This is a staged path. Do not jump to the next stage without meeting trigger thresholds and readiness checks.

## North Star
- Keep customer-facing ticket operations fast and reliable during growth.
- Preserve delivery velocity and developer productivity.
- Introduce complexity only when it clearly reduces business risk or unit cost.

## Core Principles
1. Optimize before rewriting.
2. Measure with real indicators, not intuition.
3. Use event contracts and idempotency everywhere for cross-boundary flows.
4. Split by domain boundaries, not by technical layers.
5. Prefer a hybrid architecture over big-bang migrations.

## Stage Model (Default Path)
1. Stage A: Harden and scale the Laravel modular monolith.
2. Stage B: Add managed streaming/event backbone for high-value async flows.
3. Stage C: Extract selective microservices for high-change or high-throughput domains.
4. Stage D: Introduce non-Laravel components only for proven bottlenecks.

---

## Stage A - Scale Current Laravel Architecture First

### Goal
Push the existing modular Laravel architecture to its efficient limits.

### What to implement
- For non-Docker deployments: move production queue workloads from database queue to Redis-backed queue.
- For Docker deployments: keep Redis queues as baseline and focus on worker autoscaling, queue partitioning, and lag/throughput SLOs.
- Introduce Horizon-based queue autoscaling and queue-specific worker pools.
- Enforce after-commit dispatch for any event/job dependent on committed DB state.
- Add strict queue SLO dashboards: wait time, job age, retries, failed jobs.
- Add query and endpoint performance budgets with p95/p99 alerts.
- Add read replicas for read-heavy paths and validate sticky read-your-writes behavior.
- Ensure object storage and stateless app nodes for horizontal web scaling.

### Deployment note
- Docker production profile already provisions Redis and dedicated queue workers (for example `queue` and `queue-billing` services). Treat Redis queue adoption as done for Docker and optimize around capacity, isolation, and observability.

### Triggers to schedule Stage A work
Schedule when any 2 or more are true for 2 consecutive weeks:
- p95 API latency above 2.0s on core ticket endpoints.
- Queue wait time above 30s for default queue during business hours.
- Failed jobs above 0.1% of total processed jobs.
- Worker CPU sustained above 70% during peak for more than 4h/day.
- DB CPU sustained above 70% during peak for more than 4h/day.

### Lead time
- 2 to 6 weeks depending on current infra maturity.

### Exit criteria
- p95 ticket endpoints under 1.5s at current peak.
- Queue wait p95 under 10s for default/billing/sync queues.
- Failed job rate below 0.05%.

---

## Stage B - Add Kafka for Targeted Event Streams (Hybrid)

### Goal
Use Kafka where durable replay and high-fanout async processing provide clear value.

### What Kafka is for in this platform
- Cross-domain integration events (billing, alerts, external sync pipelines).
- Replayable event history for backfills and incident recovery.
- Decoupled consumers for analytics, automation, and downstream processors.

### What Kafka is not for (initially)
- Simple in-process domain events that stay within one request lifecycle.
- Low-volume notifications that current queues handle with low latency.

### Prerequisites
- Transactional outbox for producer reliability.
- Standard event envelope: event_id, event_type, version, occurred_at, correlation_id, payload.
- Consumer idempotency and dead-letter strategy.
- Lag/error/retry dashboards and on-call runbooks.

### Triggers to schedule Stage B work
Schedule when any 2 or more are true for 4 consecutive weeks:
- 3+ independent consumers needed for the same event family.
- Replays/backfills requested at least 2 times per quarter.
- Queue backlog spikes repeatedly after external sync bursts.
- Need to onboard a new downstream consumer without touching producer code.
- Compliance/audit requires retained immutable integration event stream.

### Lead time
- Foundation: 6 to 10 weeks.
- First production event family: +2 to 4 weeks.

### Exit criteria
- At least one event family running via outbox -> Kafka -> consumer successfully.
- Replay drill completed and documented with recovery time under target.
- No increase in duplicate business side effects after migration.

---

## Stage C - Selective Microservices by Domain

### Goal
Extract only domains where independent scaling and release cadence produce measurable gains.

### Candidate domains in this system
- AI/Case processing pipeline.
- Sync/ingestion pipelines (Action1, GoogleAdmin, external systems).
- Billing execution engine if billing load diverges from ticket load.

### Service extraction criteria (all should be true)
- Domain has clear ownership and API/event contract boundaries.
- Independent scale profile from the rest of the app.
- Independent release cadence needed (at least weekly changes causing contention).
- Can be migrated with strangler pattern and no big-bang cutover.

### Triggers to schedule Stage C work
Schedule when any 3 or more are true for 1 to 2 quarters:
- Platform deployment frequency drops due to monolith coordination overhead.
- Mean lead time to production rises above 2x team target.
- High-change domain incidents repeatedly impact unrelated domains.
- Peak resource profile of one domain forces global overprovisioning.
- Team topology aligns to domain ownership (dedicated team available).

### Lead time
- Service zero (first extraction): 8 to 16 weeks.
- Subsequent services: 4 to 10 weeks each.

### Exit criteria
- Extracted service meets SLO and does not degrade end-to-end ticket experience.
- Deployment blast radius measurably reduced.
- Platform cost per ticket remains neutral or better.

---

## Stage D - Non-Laravel Components (Polyglot)

### Goal
Introduce non-Laravel runtimes only for specialized workloads with proven ROI.

### Typical justifications
- Stream processing with very high throughput and low-latency requirements.
- CPU-heavy workloads where runtime efficiency materially lowers cost.
- Specialized libraries/ecosystem requirements not practical in PHP.

### Triggers to schedule Stage D work
Schedule only when all are true:
- A measured bottleneck remains after Stage A to C optimizations.
- A benchmark shows at least 30% cost or latency improvement in target workload.
- Team has operational expertise for the new runtime.
- Full observability, security, and on-call ownership are defined.

### Lead time
- Pilot component: 6 to 12 weeks.

### Exit criteria
- Proven workload benefit in production (cost, latency, or reliability).
- No increase in incident rate from operational complexity.

---

## Indicator Catalog and Suggested Thresholds

Track weekly, alert daily.

### Customer and workload indicators
- Active customers growth rate.
- Tickets/day and tickets/min peak.
- Burst ratio (peak 5-min volume vs hourly average).

### API/service indicators
- p95 and p99 latency per critical endpoint.
- Error rate (5xx and domain failures).
- Saturation: CPU, memory, FPM workers, connection pools.

### Queue/event indicators
- Queue wait p95 by queue name.
- Oldest pending job age.
- Retry and failure rates.
- Consumer lag (for Kafka-backed streams).
- Duplicate processing incidents.

### Data/DB indicators
- Slow query count and top offenders.
- Lock contention/deadlock frequency.
- Replica lag and cache hit rates.

### Delivery/organization indicators
- Lead time for changes.
- Deployment frequency.
- MTTR and incident count by domain.
- Cross-team coordination overhead per release.

---

## Decision Matrix: Which Move Next?

Use this matrix every month in architecture review.

1. If user-facing latency and queue delay are rising, but boundaries are still clear:
- Choose Stage A optimization first.

2. If multiple downstream consumers, replay needs, and integration fanout are growing:
- Schedule Stage B Kafka foundation.

3. If one domain dominates incidents/cost and has distinct scale profile:
- Schedule Stage C extraction for that domain.

4. If one extracted workload still cannot meet cost/perf targets in PHP:
- Pilot Stage D non-Laravel component for that workload only.

---

## 12-Month Rolling Implementation Playbook

### Quarter 1
- Build indicator dashboards and thresholds.
- Complete Stage A gaps (Redis queue, Horizon, queue SLOs, after-commit enforcement).
- Create event contract standard and outbox design doc.

### Quarter 2
- Implement outbox and one Kafka event family pilot.
- Run replay and failure drills.
- Keep remaining flows on Laravel queues.

### Quarter 3
- Decide on first microservice candidate based on measured bottlenecks.
- Implement strangler path for one domain if Stage C triggers are met.

### Quarter 4
- Evaluate runtime specialization only if Stage D triggers are met.
- Otherwise deepen Stage B/C reliability and cost optimization.

---

## Scheduling Rules (When to Start Work)

1. Trigger window rule:
- Do not schedule architecture transitions on one-week spikes.
- Require sustained threshold breach over defined windows above.

2. Capacity rule:
- Start only when a dedicated owner team is available.

3. Risk rule:
- Every stage must include rollback and parallel-run plans.

4. Proof rule:
- Move to the next stage only after post-implementation metrics confirm improvement.

---

## Anti-Patterns to Avoid
- Big-bang rewrite away from Laravel.
- Full microservices decomposition without clear domain pain.
- Kafka as default transport for every internal event.
- New runtime adoption without on-call maturity.
- Scaling decisions made without baseline and trend data.

---

## Immediate Next Actions (Next 30 Days)
1. Stand up a weekly architecture scorecard with all indicators above.
2. Define SLOs for top 10 ticket and billing endpoints.
3. Verify queue migration path to Redis + Horizon in production plan.
4. Draft and approve outbox/event envelope standard.
5. Identify one event family for Kafka pilot (integration-heavy, low blast radius).

## Ownership
- Platform Lead: indicators, SLOs, stage-gate decisions.
- Domain Leads: service extraction proposals and readiness.
- SRE/Ops: observability, runbooks, incident drills.
- Product/Engineering Leadership: quarterly go/no-go decisions.
