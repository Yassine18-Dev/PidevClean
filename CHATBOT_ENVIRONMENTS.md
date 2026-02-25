# 🌍 Environment Configuration Guide

Configure the chatbot for different deployment environments.

---

## Development Environment (`.env.local`)

```bash
# Development - Local machine
APP_ENV=dev
APP_DEBUG=1
GEMINI_API_KEY=your-test-api-key-here

# Use faster responses, don't worry about costs
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
```

**Access chatbot at:** `http://localhost/support` → AI Chat

---

## Staging Environment

### Option 1: Using `.env.staging`

```bash
# staging/.env
APP_ENV=prod
APP_DEBUG=0
GEMINI_API_KEY=your-staging-api-key
LOG_LEVEL=notice
```

### Option 2: Using Docker Compose

```yaml
# docker-compose.staging.yml
version: '3.8'
services:
  app:
    image: your-app:latest
    environment:
      APP_ENV: prod
      APP_DEBUG: '0'
      GEMINI_API_KEY: ${GEMINI_API_KEY_STAGING}
    ports:
      - "8080:80"
```

**Run:**
```bash
docker-compose -f docker-compose.staging.yml up
```

---

## Production Environment

### Option 1: Using Environment Variables (Recommended)

**Set in your hosting platform:**

```bash
APP_ENV=prod
APP_DEBUG=0
GEMINI_API_KEY=your-production-api-key
MAILER_DSN=smtp://...
DATABASE_URL=mysql://...
LOG_CHANNEL=syslog
TRUSTED_HOSTS=yourdomain.com
```

### Option 2: Using Heroku

```bash
heroku config:set \
  APP_ENV=prod \
  APP_DEBUG=0 \
  GEMINI_API_KEY=your-prod-key
```

### Option 3: Using AWS ECS

```json
{
  "containerDefinitions": [
    {
      "name": "app",
      "image": "your-registry/your-app:latest",
      "environment": [
        {
          "name": "APP_ENV",
          "value": "prod"
        },
        {
          "name": "GEMINI_API_KEY",
          "valueFrom": "arn:aws:secretsmanager:region:account:secret:gemini-api-key"
        }
      ]
    }
  ]
}
```

### Option 4: Using Docker Secrets

```dockerfile
FROM php:8.1-fpm

# ... other setup ...

# In production, read from Docker secrets
RUN echo 'GEMINI_API_KEY_FILE=/run/secrets/gemini_api_key' >> .env.prod.local
```

**Docker Compose:**
```yaml
version: '3.8'
services:
  app:
    image: your-app:prod
    secrets:
      - gemini_api_key
    environment:
      - APP_ENV=prod

secrets:
  gemini_api_key:
    external: true
```

---

## Environment-Specific Configuration

### Development

```php
# config/packages/dev/monolog.yaml
monolog:
  handlers:
    main:
      type: stream
      path: "%kernel.logs_dir%/%kernel.environment%.log"
      level: debug
```

**Benefits:** Full debugging, see all logs

### Staging

```php
# config/packages/staging/monolog.yaml
monolog:
  handlers:
    main:
      type: stream
      path: "/var/log/symfony/staging.log"
      level: notice
    sentry:
      type: fingers_crossed
      action_level: error
      handler: sentry
    sentry:
      type: sentry
      dsn: "%env(SENTRY_DSN)%"
```

**Benefits:** Error tracking, limited log verbosity

### Production

```php
# config/packages/prod/monolog.yaml
monolog:
  handlers:
    main:
      type: fingers_crossed
      action_level: error
      handler: grouped
    grouped:
      type: group
      members:
        - syslog
        - sentry
    syslog:
      type: syslog
      facility: local0
      level: error
    sentry:
      type: sentry
      dsn: "%env(SENTRY_DSN)%"
      level: warning
```

**Benefits:** Error reporting, performance optimized

---

## API Key Management

### Never Do This ❌

```bash
# ❌ DON'T commit to git
echo "GEMINI_API_KEY=sk-abc123..." >> .env
git add .env
git commit -m "Add API key"
```

### Do This Instead ✅

```bash
# ✅ Add .env to .gitignore
echo ".env" >> .gitignore

# ✅ Use environment variables
export GEMINI_API_KEY="your-key"

# ✅ Or use secrets management
aws secretsmanager create-secret \
  --name gemini-api-key \
  --secret-string "your-key"
```

### Best Practices

1. **Different Keys Per Environment**
   ```
   Dev:     dev-api-key-xxx
   Staging: staging-api-key-xxx
   Prod:    prod-api-key-xxx
   ```

2. **Rotate Keys Regularly**
   - Monthly or quarterly
   - After team member leaves
   - If accidentally exposed

3. **Use Google Cloud IAM**
   ```bash
   gcloud iam service-accounts create chatbot-prod
   gcloud iam roles create custom.chatbot.reader
   gcloud iam service-accounts add-iam-policy-binding ...
   ```

4. **Audit Key Usage**
   - Monitor Gemini API console for unusual activity
   - Set up alerts for high usage
   - Review access logs weekly

---

## Scaling Configuration

### Single Server

```yaml
# docker-compose.yml
services:
  app:
    image: php-app
    environment:
      - GEMINI_API_KEY=${GEMINI_API_KEY}
    ports:
      - "80:80"
```

### Load Balanced (Multiple Servers)

```yaml
version: '3.8'
services:
  app-1:
    image: php-app:latest
    environment:
      - GEMINI_API_KEY=${GEMINI_API_KEY}

  app-2:
    image: php-app:latest
    environment:
      - GEMINI_API_KEY=${GEMINI_API_KEY}

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf:ro
```

**nginx.conf:**
```nginx
upstream app {
  server app-1:80;
  server app-2:80;
}

server {
  listen 80;
  location / {
    proxy_pass http://app;
  }
}
```

---

## Monitoring Configuration

### Sentry (Error Tracking)

1. Create Sentry project: https://sentry.io/
2. Get your DSN
3. Configure:

```bash
# .env
SENTRY_DSN=https://xxx@yyy.ingest.sentry.io/zzz
```

4. In Symfony:

```php
# config/packages/prod/sentry.yaml
sentry:
  dsn: "%env(SENTRY_DSN)%"
  environment: "%kernel.environment%"
  traces_sample_rate: 0.1
```

### DataDog (APM)

```bash
# .env
DD_API_KEY=your-datadog-api-key
DD_APP_KEY=your-datadog-app-key
```

### New Relic (Performance)

```bash
# .env
NEW_RELIC_LICENSE_KEY=your-license-key
NEW_RELIC_CUSTOM_EVENTS_MAX=5000
```

---

## Database Configuration

### SQLite (Development Only)

```bash
# .env
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
```

### MySQL (Recommended for Production)

```bash
# .env
DATABASE_URL="mysql://user:password@hostname:3306/database?serverVersion=8.0&charset=utf8mb4"
```

### PostgreSQL (Great Alternative)

```bash
# .env
DATABASE_URL="postgresql://user:password@localhost/dbname"
```

---

## Cache Configuration

### Development (No Cache)

```yaml
# config/packages/dev/cache.yaml
framework:
  cache:
    app: cache.adapter.null
```

### Production (Redis)

```yaml
# config/packages/prod/cache.yaml
framework:
  cache:
    app: cache.adapter.redis
    default_redis_provider: "redis://%env(REDIS_HOST)%:%env(REDIS_PORT)%"
```

```bash
# .env
REDIS_HOST=localhost
REDIS_PORT=6379
```

---

## SSL/HTTPS Configuration

### Self-Signed (Development Only)

```bash
openssl req -new -newkey rsa:2048 -days 365 \
  -nodes -x509 -keyout server.key -out server.crt
```

### Let's Encrypt (Production)

```bash
# Using Certbot
certbot certonly --standalone -d yourdomain.com
certbot renew --dry-run  # Test auto-renewal
```

### Nginx Configuration

```nginx
server {
    listen 443 ssl http2;
    server_name yourdomain.com;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Redirect HTTP to HTTPS
    error_page 497 https://$server_name$request_uri;
}

server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}
```

---

## Troubleshooting

### API Key Not Recognized

```bash
# Check if variable is set
echo $GEMINI_API_KEY

# Clear Symfony cache
php bin/console cache:clear --env=prod

# Verify in running container
docker exec your-container env | grep GEMINI
```

### Configuration Not Loading

```bash
# Debug environment loading
php bin/console debug:config framework

# Check parsed .env
php bin/console debug:dotenv
```

### Wrong Environment Active

```bash
# Verify current environment
php bin/console debug:container --env-vars | grep APP_ENV

# Force environment
APP_ENV=prod php bin/console cache:clear
```

---

## References

- **Symfony Docs:** https://symfony.com/doc/6.4/configuration.html
- **Gemini API Docs:** https://ai.google.dev/docs
- **Docker Docs:** https://docs.docker.com/
- **Let's Encrypt:** https://letsencrypt.org/

Good luck with your deployment! 🚀
