# Нагрузка (simulate-traffic + k6)

Два сценария для практики observability: внутренний генератор команд
(очередь / outbox / консьюмеры / БД) и HTTP-нагрузка на RED-дашборд.

Смотри **Grafana параллельно** с запуском — иначе всплески и error rate
проще пропустить.

Типичные панели: HTTP RED (artprima), Messenger/outbox, Postgres, RabbitMQ,
`active_users_5m`, доменные счётчики команд.

Предварительно загрузи фикстуры:

```bash
docker compose exec frankenphp php bin/console doctrine:fixtures:load --no-interaction
```

---

## 1. `app:simulate-traffic` (без HTTP)

Диспатчит `CreateTweetCommand` / `LikeTweetCommand` / `FollowUserCommand`
напрямую через `command.bus` (outbox → RabbitMQ → консьюмеры).

```bash
# базовый RPS=10; раз в 2–3 минуты на 30с RPS ×5 (spike)
docker compose exec frankenphp php bin/console app:simulate-traffic --rps=10
```

Другой baseline:

```bash
docker compose exec frankenphp php bin/console app:simulate-traffic --rps=25
```

Остановка: `Ctrl+C` (SIGINT/SIGTERM).

Воркеры уже в `docker compose up`: сервисы `outbox-relay` и `messenger-worker`.
Проверка: `docker compose ps` / `docker compose logs -f outbox-relay messenger-worker`.

---

## 2. k6 — HTTP-слой (`docs/load/k6-http-traffic.js`)

Виртуальные пользователи логинятся (JWT `data.token`), затем:

| Доля | Запрос                                                   | Зачем              |
|------|----------------------------------------------------------|--------------------|
| ~70% | `GET /api/feed`                                          | чтение / кэш ленты |
| ~10% | `POST /api/tweets`                                       | запись             |
| ~15% | `POST /api/tweets/{id}/like`                             | запись + события   |
| ~5%  | `GET /api/tweets/999999999` и `GET /api/feed` без токена | 4xx / 401 на RED   |

Профиль: ramp-up → 50 VU → плато 3 мин → ramp-down.

### Перед прогоном: лимит логина

Все VU логинятся с одного IP, а `login_ip` по умолчанию — 5 запросов в минуту,
поэтому без поднятого лимита почти все VU получат 429 вместо токена.
Подними лимит (и заодно выключи debug для честных задержек) и пересоздай app:

```bash
APP_DEBUG=0 LOGIN_RATE_LIMIT=10000 docker compose up -d frankenphp
```

Вернуть боевые значения после прогона:

```bash
docker compose up -d frankenphp
```

### Вариант A — в Docker (k6 на хосте не нужен)

Сервис `k6` объявлен в `docker-compose.yml` под профилем `load`, поэтому
вместе со стеком не поднимается:

```bash
docker compose run --rm k6
```

Внутри сети контейнер ходит на `http://frankenphp:80` (не `localhost:8000`).

### Вариант B — k6 установлен на хосте

Установка: [k6.io/docs](https://k6.io/docs/get-started/installation/).

```bash
k6 run -e BASE_URL=http://localhost:8000 docs/load/k6-http-traffic.js
```

Опционально:

```bash
k6 run -e BASE_URL=http://localhost:8000 -e PASSWORD=password docs/load/k6-http-traffic.js
```

Логины фикстур: `user_0@example.com` … `user_399@example.com` / `password`.

### Что смотреть в итоговой сводке

| Метрика                         | Смысл                                |
|---------------------------------|--------------------------------------|
| `http_reqs` (rate)              | суммарный RPS                        |
| `feed_latency_ms` p(95)         | p95 ленты (кэш Redis)                |
| `tweet_create_latency_ms` p(95) | p95 записи                           |
| `http_req_failed`               | доля ошибок (~5% намеренных 4xx/401) |

---

## Рекомендация

1. Открой Grafana (обычно `http://localhost:3000`).
2. В одном терминале — `app:simulate-traffic` (очередь/БД/spikes).
3. В другом — `k6 run …` (HTTP RED + error rate).
4. На графиках ищи: рост RPS, spike ×5, ненулевой error rate (~5% намеренных 4xx), лаги outbox/consumer.
