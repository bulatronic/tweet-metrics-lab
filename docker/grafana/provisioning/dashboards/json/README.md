# Grafana dashboards (file provisioning)

Provider: `../dashboards.yml` → этот каталог.

| Файл                    | Статус                                                                                                                                              |
|-------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------|
| `symfony-overview.json` | Импорт из artprima `grafana/symfony-app-overview.json` (готово). В UI выставь variable **Namespace** = `tweet_metrics_lab`, **Job** = `frankenphp`. |
| `business-metrics.json` | Собрать в Grafana UI по `business-metrics.SPEC.md`, затем Export → сохранить сюда.                                                                  |
| `queue-health.json`     | Собрать в Grafana UI по `queue-health.SPEC.md`, затем Export → сохранить сюда.                                                                      |

После экспорта перезапуск Grafana не обязателен (`updateIntervalSeconds: 30`).
