/**
 * HTTP load for tweet-metrics-lab RED dashboard.
 *
 * Mix (approx):
 *   70% GET  /api/feed
 *   10% POST /api/tweets
 *   15% POST /api/tweets/{id}/like
 *    5% intentional errors (404 on missing tweet + 401 without token)
 *
 * Usage:
 *   k6 run -e BASE_URL=http://localhost:8000 docs/load/k6-http-traffic.js
 */
import http from 'k6/http';
import { check, sleep } from 'k6';
import { SharedArray } from 'k6/data';
import { Trend } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const PASSWORD = __ENV.PASSWORD || 'password';

const feedLatency = new Trend('feed_latency_ms');
const tweetLatency = new Trend('tweet_create_latency_ms');
const likeLatency = new Trend('like_latency_ms');

/** Fixture users 0..399 — emails from UserFixtures */
const users = new SharedArray('users', function () {
  const list = [];
  for (let i = 0; i < 400; i++) {
    list.push({ email: `user_${i}@example.com`, password: PASSWORD });
  }
  return list;
});

export const options = {
  stages: [
    { duration: '1m', target: 50 }, // ramp-up
    { duration: '3m', target: 50 }, // plateau
    { duration: '1m', target: 0 }, // ramp-down
  ],
  thresholds: {
    http_req_failed: ['rate<0.15'], // intentional ~5% errors + occasional domain conflicts
    http_req_duration: ['p(95)<2000'],
  },
};

function login(email, password) {
  const res = http.post(
    `${BASE_URL}/api/login`,
    JSON.stringify({ email, password }),
    { headers: { 'Content-Type': 'application/json' }, tags: { name: 'login' } },
  );

  let token = null;
  try {
    const body = res.json();
    token = body && body.data && body.data.token ? body.data.token : null;
  } catch (e) {
    token = null;
  }

  check(res, {
    'login status 200': (r) => r.status === 200,
    'login has token': () => token !== null,
  });

  return token;
}

function authHeaders(token) {
  return {
    'Content-Type': 'application/json',
    Authorization: `Bearer ${token}`,
  };
}

export function setup() {
  // Warm a small pool of tweet IDs for like traffic
  const seedUser = users[0];
  const token = login(seedUser.email, seedUser.password);
  const tweetIds = [];

  if (token) {
    for (let i = 0; i < 20; i++) {
      const res = http.post(
        `${BASE_URL}/api/tweets`,
        JSON.stringify({ text: `k6 seed tweet ${i} ${Date.now()}` }),
        { headers: authHeaders(token), tags: { name: 'setup_create_tweet' } },
      );
      try {
        const body = res.json();
        if (body && body.data && body.data.id) {
          tweetIds.push(body.data.id);
        }
      } catch (e) {
        // ignore
      }
    }

    const feed = http.get(`${BASE_URL}/api/feed?limit=50`, {
      headers: authHeaders(token),
      tags: { name: 'setup_feed' },
    });
    try {
      const body = feed.json();
      const items = body && body.data && body.data.items ? body.data.items : [];
      for (const item of items) {
        if (item.tweetId) {
          tweetIds.push(item.tweetId);
        }
      }
    } catch (e) {
      // ignore
    }
  }

  return { tweetIds: Array.from(new Set(tweetIds)) };
}

/** Per-VU JWT (login once, then request loop). */
let vuToken = null;
let vuTweetIds = [];

export default function (data) {
  if (!vuToken) {
    const user = users[Math.floor(Math.random() * users.length)];
    vuToken = login(user.email, user.password);
    vuTweetIds = data.tweetIds ? data.tweetIds.slice() : [];
  }

  if (!vuToken) {
    sleep(1);
    return;
  }

  const token = vuToken;
  const roll = Math.random() * 100;

  if (roll < 70) {
    const res = http.get(`${BASE_URL}/api/feed?limit=20`, {
      headers: authHeaders(token),
      tags: { name: 'GET /api/feed' },
    });
    feedLatency.add(res.timings.duration);
    check(res, { 'feed 200': (r) => r.status === 200 });
  } else if (roll < 80) {
    const res = http.post(
      `${BASE_URL}/api/tweets`,
      JSON.stringify({ text: `k6 tweet ${__VU}-${__ITER}-${Date.now()}` }),
      { headers: authHeaders(token), tags: { name: 'POST /api/tweets' } },
    );
    tweetLatency.add(res.timings.duration);
    check(res, { 'create tweet 201': (r) => r.status === 201 });

    try {
      const body = res.json();
      if (body && body.data && body.data.id) {
        vuTweetIds.push(body.data.id);
      }
    } catch (e) {
      // ignore
    }
  } else if (roll < 95) {
    const tweetId =
      vuTweetIds.length > 0
        ? vuTweetIds[Math.floor(Math.random() * vuTweetIds.length)]
        : '00000000-0000-7000-8000-000000000001';

    const res = http.post(`${BASE_URL}/api/tweets/${tweetId}/like`, null, {
      headers: authHeaders(token),
      tags: { name: 'POST /api/tweets/{id}/like' },
    });
    likeLatency.add(res.timings.duration);
    check(res, {
      'like 204 or conflict': (r) => r.status === 204 || r.status === 409 || r.status === 400,
    });
  } else {
    // ~5%: intentional client errors for RED error rate
    if (Math.random() < 0.5) {
      const res = http.get(`${BASE_URL}/api/tweets/999999999`, {
        headers: authHeaders(token),
        tags: { name: 'GET /api/tweets/missing (4xx)' },
      });
      check(res, { 'missing tweet is 4xx': (r) => r.status >= 400 && r.status < 500 });
    } else {
      const res = http.get(`${BASE_URL}/api/feed`, {
        tags: { name: 'GET /api/feed without token (401)' },
      });
      check(res, { 'unauthenticated is 401': (r) => r.status === 401 });
    }
  }

  sleep(0.3 + Math.random() * 0.7);
}
