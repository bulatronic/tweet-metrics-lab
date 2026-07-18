# SPEC: business-metrics.json

Собери дашборд в Grafana UI (datasource **Prometheus**), затем
**Share → Export → Save to file** как `business-metrics.json` в эту папку.

Рекомендуемые meta: title `Business metrics`, uid `business-metrics`,
tags `tweet-metrics-lab`, `business`, refresh `10s`, time `now-1h`.

Namespace приложения: `tweet_metrics_lab` (см. `config/packages/prometheus_metrics.yaml`).

---

## Row 1 — write throughput (tweets / likes / follows per minute)

### Panel: Tweets created / min
- Type: **Time series**
- Unit: ops/min (или `short` + legend «/min»)
- PromQL:
  ```promql
  rate(tweet_metrics_lab_tweets_created_total[1m]) * 60
  ```

### Panel: Likes / min
- Type: **Time series**
- PromQL:
  ```promql
  rate(tweet_metrics_lab_likes_total[1m]) * 60
  ```
- Optional overlay (unlikes):
  ```promql
  rate(tweet_metrics_lab_unlikes_total[1m]) * 60
  ```

### Panel: Follows / min
- Type: **Time series**
- PromQL:
  ```promql
  rate(tweet_metrics_lab_follows_total[1m]) * 60
  ```
- Optional:
  ```promql
  rate(tweet_metrics_lab_unfollows_total[1m]) * 60
  ```

---

## Row 2 — activity

### Panel: Active users (5m)
- Type: **Stat** + **Time series** (две панели или одна timeseries)
- PromQL:
  ```promql
  tweet_metrics_lab_active_users_5m
  ```
- Примечание: это **агрегатный gauge**, не per-user. Периодически
  обновляется `app:metrics:active-users`.

### Panel: «Топ активных» (proxy — топ команд по rate)
- Per-user labels в метриках **нет** — вместо топа юзеров показываем
  топ command handlers (нагрузка от simulate-traffic / API).
- Type: **Bar gauge** или **Time series** (legend table, calcs: last)
- PromQL:
  ```promql
  topk(10, rate(tweet_metrics_lab_commands_handled_total[5m]))
  ```
- Legend: `{{command}}`

---

## Row 3 — feed cache

### Panel: Feed cache hit ratio
- Type: **Gauge** (0–1) + optional timeseries
- PromQL:
  ```promql
  rate(tweet_metrics_lab_feed_cache_hits_total[5m])
  /
  clamp_min(
    rate(tweet_metrics_lab_feed_cache_hits_total[5m])
    +
    rate(tweet_metrics_lab_feed_cache_misses_total[5m]),
    1e-9
  )
  ```

### Panel: Feed cache hits / misses rate
- Type: **Time series** (две серии)
- PromQL:
  ```promql
  rate(tweet_metrics_lab_feed_cache_hits_total[1m])
  ```
  ```promql
  rate(tweet_metrics_lab_feed_cache_misses_total[1m])
  ```

---

## Row 4 — outbox & projection lag

### Panel: Outbox pending messages
- Type: **Stat** + **Time series**
- PromQL:
  ```promql
  tweet_metrics_lab_outbox_pending_messages
  ```

### Panel: Feed projection lag p95
- Type: **Time series**
- Unit: seconds (`s`)
- PromQL:
  ```promql
  histogram_quantile(
    0.95,
    sum by (le) (rate(tweet_metrics_lab_feed_projection_lag_seconds_bucket[5m]))
  )
  ```
- Optional p50 / p99 рядом:
  ```promql
  histogram_quantile(0.50, sum by (le) (rate(tweet_metrics_lab_feed_projection_lag_seconds_bucket[5m])))
  ```
  ```promql
  histogram_quantile(0.99, sum by (le) (rate(tweet_metrics_lab_feed_projection_lag_seconds_bucket[5m])))
  ```

### Panel (alt): Feed projection lag heatmap
- Type: **Heatmap**
- Data: heatmap from histogram buckets
- PromQL (Grafana heatmap / Prometheus histogram):
  ```promql
  sum by (le) (rate(tweet_metrics_lab_feed_projection_lag_seconds_bucket[5m]))
  ```
- Format: **Heatmap** / «Calculate from data» → **Count** (зависит от версии Grafana; в v10+ удобнее panel type Heatmap + query type Instant/Range по гайду Grafana).

### Bonus (optional): Outbox publish lag p95
```promql
histogram_quantile(
  0.95,
  sum by (le) (rate(tweet_metrics_lab_outbox_message_lag_seconds_bucket[5m]))
)
```
