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

Нужны живые воркеры/релей, иначе очередь будет только расти:

```bash
# примеры — как у вас заведены процессы в compose/supervisor
docker compose exec frankenphp php bin/console app:outbox:relay
docker compose exec frankenphp php bin/console messenger:consume async -vv
```

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

Установка [k6](https://k6.io/docs/get-started/installation/) на хосте, затем:

```bash
k6 run -e BASE_URL=http://localhost:8000 docs/load/k6-http-traffic.js
```

Опционально:

```bash
k6 run -e BASE_URL=http://localhost:8000 -e PASSWORD=password docs/load/k6-http-traffic.js
```

Логины фикстур: `user_0@example.com` … `user_399@example.com` / `password`.

---

## Рекомендация

1. Открой Grafana (обычно `http://localhost:3000`).
2. В одном терминале — `app:simulate-traffic` (очередь/БД/spikes).
3. В другом — `k6 run …` (HTTP RED + error rate).
4. На графиках ищи: рост RPS, spike ×5, ненулевой error rate (~5% намеренных 4xx), лаги outbox/consumer.
