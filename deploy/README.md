# ARDOCCO deployment (Docker)

## 1) DNS
- Point these domains to your server IP:
  - `ardocco.com`
  - `api.ardocco.com`
  - `ai.ardocco.com`

## 2) Start services
```bash
chmod +x ./deploy.sh ./deploy/ssl-init.sh ./deploy/ssl-renew.sh
./deploy.sh
```

This creates `deploy/.env` (if missing), generates secure passwords/keys, and starts the stack.

## 3) Enable SSL (first time)
Update `deploy/.env` with a real `LETSENCRYPT_EMAIL`, then:
```bash
./deploy/ssl-init.sh
```

## Renew SSL
```bash
./deploy/ssl-renew.sh
```

## Logs
```bash
docker compose -f deploy/docker-compose.yml --env-file deploy/.env logs -f --tail 200
```

