# CLAUDE.md - AI Assistant Guide

## Project Overview

This is a **Laravel 12** learning application deployed on **AWS ECS** with comprehensive infrastructure-as-code setup. The project serves as a learning environment for AWS infrastructure deployment and Laravel development.

**Project Name**: learning-aws-app
**Framework**: Laravel 12 (PHP 8.4)
**Database**: PostgreSQL 16
**Frontend**: Vite + Tailwind CSS 4.0
**Infrastructure**: AWS (ECS, RDS, VPC, ALB, Route53)
**Environment**: Learning/Staging (cost-optimized)

---

## Repository Structure

```
learning-aws-app/
├── app/                      # Laravel application code
│   ├── Http/                 # Controllers, Middleware, Requests
│   ├── Models/               # Eloquent models
│   └── Providers/            # Service providers
├── bootstrap/                # Laravel bootstrap files
├── config/                   # Configuration files
├── database/
│   ├── factories/            # Model factories for testing
│   ├── migrations/           # Database migrations
│   └── seeders/              # Database seeders
├── docker/                   # Docker configuration
│   ├── nginx/                # Nginx configs (local & ECS)
│   ├── php/                  # PHP-FPM configuration
│   ├── scripts/              # Container entrypoint scripts
│   └── supervisor/           # Supervisor configs for ECS
├── docs/                     # Project documentation
│   └── aws/                  # AWS infrastructure documentation
│       ├── infrastructure-setup/  # Step-by-step AWS setup guides
│       └── knowledge/        # AWS knowledge base
├── public/                   # Public web root
├── resources/
│   ├── css/                  # Stylesheets (Tailwind)
│   ├── js/                   # JavaScript files
│   └── views/                # Blade templates
├── routes/                   # Route definitions
│   ├── web.php              # Web routes
│   └── console.php          # Console commands
├── scripts/                  # Deployment/utility scripts
├── storage/                  # Storage (logs, cache, sessions)
├── tests/                    # PHPUnit tests
│   ├── Feature/             # Feature tests
│   └── Unit/                # Unit tests
├── .cursorrules             # AI assistant guidelines (Japanese)
├── composer.json            # PHP dependencies
├── package.json             # NPM dependencies
├── Dockerfile               # Local development Docker image
├── Dockerfile.ecs           # ECS production Docker image
├── docker-compose.yml       # Local development environment
├── Makefile                 # AWS deployment automation
└── vite.config.js           # Vite build configuration
```

---

## Development Environments

### Local Development (Docker Compose)

**Services**:
- `app`: PHP 8.4-FPM (Laravel application)
- `webserver`: Nginx (port 8080)
- `postgres`: PostgreSQL 16 (port 5432)

**Quick Start**:
```bash
# Copy environment file
cp .env.example .env

# Start containers
docker-compose up -d

# Install dependencies
docker-compose exec app composer install
docker-compose exec app npm install

# Generate application key
docker-compose exec app php artisan key:generate

# Run migrations
docker-compose exec app php artisan migrate

# Build frontend assets
docker-compose exec app npm run build

# Access application
open http://localhost:8080
```

**Composer Scripts**:
```bash
# Complete setup (first time)
composer run setup

# Development mode (runs server, queue, logs, vite)
composer run dev

# Run tests
composer run test
```

### AWS Deployment (ECS)

**Architecture**:
- **Compute**: AWS ECS Fargate
- **Database**: RDS PostgreSQL
- **Networking**: VPC with public/private subnets across 2 AZs
- **Load Balancer**: Application Load Balancer
- **DNS**: Route53
- **Container Registry**: ECR
- **Access**: Bastion host for DB access

**Environment**: `stg` (staging/learning)
**Region**: `ap-northeast-1`
**Availability Zones**: `1a`, `1c`

---

## Key Technologies & Dependencies

### Backend (PHP)
- **Laravel Framework**: 12.x
- **PHP Version**: 8.4
- **Database**: PostgreSQL (via `pdo_pgsql`)
- **Queue Driver**: Database
- **Cache Driver**: Database
- **Session Driver**: Database

### Frontend
- **Build Tool**: Vite 7.x
- **CSS Framework**: Tailwind CSS 4.0
- **HTTP Client**: Axios 1.11

### Development Tools
- **Testing**: PHPUnit 11.5
- **Code Style**: Laravel Pint 1.24
- **Local Dev**: Laravel Sail 1.41
- **Logging**: Laravel Pail 1.2

### Docker
- **Base Image**: `php:8.4-fpm`
- **Local**: Multi-container (app, nginx, postgres)
- **ECS**: Single container with Nginx + PHP-FPM + Supervisor

---

## Deployment Workflows

### Using Makefile (Recommended)

The `Makefile` provides automated deployment commands:

```bash
# Show all available commands
make help

# View current configuration
make info ENV=stg

# Build and push Docker image to ECR
make release-image ENV=stg

# Deploy to ECS (build + push + update service)
make deploy-full ENV=stg

# Deploy with database migrations
make deploy-full-with-migrate ENV=stg

# Check ECS service status
make ecs-status ENV=stg

# Check RDS status
make db-status
```

### Manual Deployment Steps

1. **Build Docker Image**:
   ```bash
   docker build --platform=linux/amd64 -t <ECR_URI>:<TAG> -f Dockerfile.ecs .
   ```

2. **Push to ECR**:
   ```bash
   aws ecr get-login-password --region ap-northeast-1 | docker login --username AWS --password-stdin <ECR_URI>
   docker push <ECR_URI>:<TAG>
   ```

3. **Update ECS Service**:
   - Register new task definition with updated image
   - Update ECS service to use new task definition
   - Force new deployment

4. **Run Migrations** (if needed):
   ```bash
   make migrate ENV=stg
   ```

### Deployment Variables

Set these in `Makefile` or as environment variables:
- `ENV`: Environment name (default: `stg`)
- `AWS_REGION`: AWS region (default: `ap-northeast-1`)
- `ECR_NAME`: ECR repository name (default: `learning-app`)
- `CLUSTER_NAME`: ECS cluster name
- `SERVICE_NAME`: ECS service name
- `TASK_DEFINITION_FAMILY`: ECS task definition family

---

## Database Management

### Local Development

**Access PostgreSQL**:
```bash
docker-compose exec postgres psql -U postgres -d laravel
```

### AWS RDS

**Via Bastion Host**:
1. SSH into bastion server
2. Connect to RDS endpoint (private subnet)
3. See `docs/aws/infrastructure-setup/11_bastion-host.md` for setup

**Running Migrations on ECS**:
```bash
# Using Makefile
make migrate ENV=stg

# Manual via ECS Exec
aws ecs execute-command \
  --cluster learning-cluster-stg \
  --task <TASK_ID> \
  --container app \
  --interactive \
  --command "cd /var/www && php artisan migrate --force"
```

### Migrations

```bash
# Create migration
php artisan make:migration create_example_table

# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Reset database
php artisan migrate:fresh

# With seeding
php artisan migrate:fresh --seed
```

---

## Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter ExampleTest

# Run with coverage (requires Xdebug)
php artisan test --coverage

# Using composer
composer run test
```

### Test Structure

- **Feature Tests**: `tests/Feature/` - Test full application features
- **Unit Tests**: `tests/Unit/` - Test individual classes/methods
- **Factories**: `database/factories/` - Create test data
- **Seeders**: `database/seeders/` - Seed test/development data

---

## Code Style & Conventions

### Laravel Best Practices

1. **Follow PSR-12** coding standards
2. **Use Laravel Pint** for code formatting:
   ```bash
   ./vendor/bin/pint
   ```
3. **Type Hints**: Use strict types and return type declarations
4. **Eloquent Models**: Use in `app/Models/`
5. **Controllers**: Keep thin, delegate logic to services/actions
6. **Validation**: Use Form Requests for complex validation
7. **Database**: Always use migrations (never manual schema changes)

### File Naming Conventions

- **Models**: Singular, PascalCase (e.g., `User.php`)
- **Controllers**: PascalCase + `Controller` suffix (e.g., `UserController.php`)
- **Migrations**: Snake_case with timestamp prefix
- **Views**: Kebab-case (e.g., `user-profile.blade.php`)
- **Routes**: Kebab-case URLs (e.g., `/user-profile`)

### Git Workflow

**Current Branch**: `claude/claude-md-mhyv47lz8ynq364c-01DsT15Ac8vaHTAgm5SgKjX4`

**Commit Conventions**:
- Use clear, descriptive commit messages in Japanese
- Reference issue numbers when applicable
- Keep commits atomic and focused

**Recent Commits**:
- `d5f0f94`: GUIでDB接続まで (GUI DB connection setup)
- `ab1a32b`: 踏み台サーバー作成と接続確認まで (Bastion server creation)
- `dd59c92`: deploy
- `5e5ab1e`: Laravelウェルカム画面表示まで (Laravel welcome screen)

---

## AWS Infrastructure

### Complete Documentation

Comprehensive AWS setup guides are in `docs/aws/infrastructure-setup/`:

1. **VPC Setup** (`1_vpc.md`) - Network foundation
2. **RDS Setup** (`2_rds.md`) - PostgreSQL database
3. **Secrets Management** (`3_secrets-management.md`) - Environment variables
4. **ECR Deployment** (`4_laravel-ecr-deployment.md`) - Container registry
5. **ECS Setup** (`5_ecs-setup.md`) - Container orchestration
6. **Task Definitions** (`6_ecs-task-definition.md`) - ECS tasks
7. **ECS Service** (`7_ecs-service.md`) - Service configuration
8. **Route53** (`8_route53.md`) - DNS setup
9. **ALB** (`9_alb.md`) - Load balancer
10. **NAT Instance** (`10_nat-instance.md`) - Cost-optimized NAT
11. **Bastion Host** (`11_bastion-host.md`) - Secure DB access

### Key Infrastructure Points

- **Cost Optimization**: Using NAT instance instead of NAT Gateway
- **High Availability**: Multi-AZ deployment (1a, 1c)
- **Security**: Private subnets for app/DB, public for ALB
- **Scalability**: ECS Fargate for auto-scaling
- **Monitoring**: CloudWatch integration (planned)

---

## Environment Variables

### Required Variables (`.env`)

```bash
# Application
APP_NAME=Laravel
APP_ENV=local|production
APP_KEY=<generated-by-artisan>
APP_DEBUG=true|false
APP_URL=http://localhost:8080

# Database
DB_CONNECTION=pgsql
DB_HOST=postgres|<rds-endpoint>
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=postgres
DB_PASSWORD=<secure-password>

# Cache/Session/Queue
CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

# AWS (for production)
AWS_ACCESS_KEY_ID=<access-key>
AWS_SECRET_ACCESS_KEY=<secret-key>
AWS_DEFAULT_REGION=ap-northeast-1
AWS_BUCKET=<s3-bucket>

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=debug|info|error
```

### Secrets Management (AWS)

- **Non-sensitive**: Store in S3 bucket
- **Sensitive**: Use AWS Secrets Manager
- See: `docs/aws/infrastructure-setup/3_secrets-management.md`

---

## Common Tasks for AI Assistants

### When Working on Features

1. **Always run tests** after changes:
   ```bash
   php artisan test
   ```

2. **Update migrations** if database schema changes

3. **Follow Laravel conventions** for file placement and naming

4. **Use Laravel Pint** before committing:
   ```bash
   ./vendor/bin/pint
   ```

### When Deploying

1. **Test locally first** with Docker Compose

2. **Build for ECS** using `Dockerfile.ecs`

3. **Use Makefile** for consistent deployments:
   ```bash
   make deploy-full ENV=stg
   ```

4. **Verify deployment**:
   ```bash
   make ecs-status ENV=stg
   ```

5. **Check logs** if issues occur:
   ```bash
   aws logs tail /ecs/learning-app-stg --follow
   ```

### When Debugging

1. **Local logs**: `storage/logs/laravel.log`

2. **Laravel Pail** for real-time logs:
   ```bash
   php artisan pail
   ```

3. **ECS logs**: CloudWatch Logs `/ecs/learning-app-stg`

4. **Database queries**: Enable query logging in config/database.php

---

## Important Notes for AI Assistants

### Language Preferences

From `.cursorrules`:
- **Respond in Japanese** (日本語に変換して返答すること)
- **Ask before executing commands** (コマンド実行時は確認してから)
- **Keep responses concise** unless user requests details
- **Use AWS Documentation MCP tools** for AWS-related work

### Security Considerations

1. **Never commit** `.env` files
2. **Never expose** database credentials or AWS keys
3. **Use Secrets Manager** for sensitive production data
4. **Validate input** in all controllers and requests
5. **Use CSRF protection** (enabled by default)
6. **Sanitize output** in Blade templates (use `{{ }}` not `{!! !!}`)

### Performance Best Practices

1. **Use database indexing** on frequently queried columns
2. **Eager load relationships** to avoid N+1 queries
3. **Cache expensive queries** using Laravel cache
4. **Optimize images** before uploading
5. **Use queue jobs** for long-running tasks

### AWS Cost Optimization

- **Use NAT Instance** instead of NAT Gateway (see docs)
- **Right-size ECS tasks** based on actual usage
- **Stop RDS** during non-working hours (development only)
- **Monitor CloudWatch** for unused resources
- **Use spot instances** where appropriate

---

## Useful Commands Reference

### Artisan Commands

```bash
# Generate files
php artisan make:model Post -mcr        # Model + migration + controller + resource
php artisan make:controller PostController --resource
php artisan make:request StorePostRequest
php artisan make:middleware CheckAge

# Database
php artisan migrate                     # Run migrations
php artisan migrate:fresh --seed        # Fresh DB with seeds
php artisan db:seed                     # Run seeders only

# Cache
php artisan config:cache                # Cache configuration
php artisan route:cache                 # Cache routes
php artisan view:cache                  # Cache views
php artisan cache:clear                 # Clear application cache

# Queue
php artisan queue:work                  # Process queue jobs
php artisan queue:listen                # Listen for queue jobs

# Development
php artisan serve                       # Start dev server
php artisan tinker                      # REPL console
php artisan pail                        # Real-time logs
```

### Docker Commands

```bash
# Local development
docker-compose up -d                    # Start all services
docker-compose down                     # Stop all services
docker-compose logs -f app              # Follow app logs
docker-compose exec app bash            # Enter app container

# ECS deployment
docker build -t app:latest -f Dockerfile.ecs .
docker run -p 80:80 app:latest         # Test ECS image locally
```

### Git Commands

```bash
# Development workflow
git status                              # Check status
git add .                               # Stage changes
git commit -m "descriptive message"     # Commit
git push -u origin <branch-name>        # Push to remote

# Branch management
git checkout -b feature/new-feature     # Create new branch
git branch                              # List branches
git merge main                          # Merge main into current
```

---

## Resources

### Official Documentation

- **Laravel 12**: https://laravel.com/docs/12.x
- **Tailwind CSS 4**: https://tailwindcss.com/docs
- **Vite**: https://vitejs.dev/
- **PostgreSQL**: https://www.postgresql.org/docs/
- **AWS ECS**: https://docs.aws.amazon.com/ecs/
- **AWS RDS**: https://docs.aws.amazon.com/rds/

### Project Documentation

- **AWS Infrastructure Setup**: `docs/aws/infrastructure-setup/README.md`
- **Deployment Guide**: `docs/aws/knowledge/deployment-with-makefile.md`
- **DB Connection**: `docs/aws/knowledge/ecs-task-connection-and-migration.md`
- **Health Checks**: `docs/aws/knowledge/health-check-grace-period.md`

### Getting Help

1. Check `docs/` directory for existing documentation
2. Review Laravel documentation for framework questions
3. Check AWS docs (use MCP tools for current info)
4. Review recent commits for context
5. Ask user for clarification when needed

---

**Last Updated**: 2025-11-14
**Maintained for**: AI assistants (Claude, Cursor, etc.)
**Repository**: learning-aws-app (AWS Learning Environment)
