# SPEC: queue-health.json

Собери дашборд в Grafana UI (datasource **Prometheus**), затем
**Share → Export → Save to file** как `queue-health.json` в эту папку.

Meta: title `Queue health`, uid `queue-health`,
tags `tweet-metrics-lab`, `messenger`, `rabbitmq`, refresh `10s`.

Источники scrape (см. `docker/prometheus/prometheus.yml`):
- job `frankenphp` → tasko/artprima на `/metrics/prometheus`
- job `rabbitmq` → RabbitMQ prometheus plugin `:15692`

---

## Row 1 — Symfony Messenger (tasko-products)

Конфиг: `config/packages/tasko_prometheus.yaml`  
На `event.bus` висит `MessengerEventMiddleware` → counters:

| Metric                     | Labels                    |
|----------------------------|---------------------------|
| `middleware_message`       | `bus`, `message`, `label` |
| `middleware_message_error` | `bus`, `message`, `label` |

Gauges subscribers:

| Metric                                   | Notes         |
|------------------------------------------|---------------|
| `messenger_events_active_workers`        | workers alive |
| `messenger_events_messages_in_transport` | in transport  |

`RetryMessengerEventMiddleware` **не подключён** → `middleware_retry_message`
появится только после добавления retry-middleware на bus. Панель «retried»
можно заготовить заранее (будет пустой).

### Panel: Messages processed / sec by type
- Type: **Time series**
- PromQL:
  ```promql
  sum by (message) (rate(middleware_message[1m]))
  ```
- Legend: `{{message}}`

### Panel: Messages failed / sec by type
- Type: **Time series**
- PromQL:
  ```promql
  sum by (message) (rate(middleware_message_error[1m]))
  ```

### Panel: Messages retried / sec by type (optional / future)
- Type: **Time series**
- PromQL (когда появится метрика):
  ```promql
  sum by (message) (rate(middleware_retry_message[1m]))
  ```

### Panel: Error rate by message type
- Type: **Time series**
- PromQL:
  ```promql
  sum by (message) (rate(middleware_message_error[5m]))
  /
  clamp_min(sum by (message) (rate(middleware_message[5m])), 1e-9)
  ```

### Panel: Active workers
- Type: **Stat** / **Time series**
- PromQL:
  ```promql
  messenger_events_active_workers
  ```
  (если есть label `queue`/`transport` — `sum by (...)` по фактическим labels из Prometheus).

### Panel: Messages in transport
- Type: **Time series**
- PromQL:
  ```promql
  messenger_events_messages_in_transport
  ```

---

## Row 2 — RabbitMQ (management / prometheus plugin)

Job: `rabbitmq`. Типичные имена плагина `rabbitmq_prometheus`
(уточни через Prometheus → Graph → `{job="rabbitmq"}`):

### Panel: Queue depth (ready + unacked, or total)
- Type: **Time series**
- PromQL (предпочтительно per-queue):
  ```promql
  rabbitmq_queue_messages{job="rabbitmq"}
  ```
  или раздельно:
  ```promql
  rabbitmq_queue_messages_ready{job="rabbitmq"}
  ```
  ```promql
  rabbitmq_queue_messages_unacked{job="rabbitmq"}
  ```
- Legend: `{{queue}}` (и `{{vhost}}` если нужно)

### Panel: Unacked messages
- Type: **Time series**
- PromQL:
  ```promql
  rabbitmq_queue_messages_unacked{job="rabbitmq"}
  ```

### Panel: Consumer count
- Type: **Time series** / **Stat**
- PromQL:
  ```promql
  rabbitmq_queue_consumers{job="rabbitmq"}
  ```

### Panel: Publish / deliver rate (optional)
Если есть counters вида `rabbitmq_channel_messages_published_total` /
`rabbitmq_queue_messages_delivered_total` (зависит от версии плагина):
  ```promql
  sum(rate(rabbitmq_channel_messages_published_total{job="rabbitmq"}[1m]))
  ```
  ```promql
  sum(rate(rabbitmq_queue_messages_delivered_total{job="rabbitmq"}[1m]))
  ```

---

## Row 3 — связка с outbox (context)

### Panel: Outbox pending (bridge to business dashboard)
```promql
tweet_metrics_lab_outbox_pending_messages
```

### Panel: Outbox publish rate
```promql
rate(tweet_metrics_lab_outbox_messages_published_total[1m])
```

### Panel: Outbox failures / dead
```promql
rate(tweet_metrics_lab_outbox_messages_failed_total[1m])
```
```promql
rate(tweet_metrics_lab_outbox_messages_dead_total[1m])
```

---

## Проверка имён метрик перед сборкой

```bash
# app / tasko
curl -s http://localhost:8000/metrics/prometheus | rg 'middleware_message|messenger_events_|outbox_'

# rabbitmq plugin
curl -s http://localhost:15692/metrics | rg 'rabbitmq_queue_messages|rabbitmq_queue_consumers'
```

Подставь точные имена/labels из вывода в панели (версии RabbitMQ иногда
добавляют префикс `rabbitmq_detailed_*`).
