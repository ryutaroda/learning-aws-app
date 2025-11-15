SHELL := /bin/bash -o pipefail

# 環境変数（デフォルト値）
ENV ?= stg
AWS_REGION ?= ap-northeast-1
AWS_ACCOUNT_ID ?= $(shell aws sts get-caller-identity --query Account --output text)

# ECRリポジトリ名（デフォルトはプロジェクト名）
ECR_NAME ?= learning-app

# ECRのベースURI
IMAGE_REPOSITORY_BASE := ${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com

# ECRのフルURI
IMAGE_REPOSITORY_URI := ${IMAGE_REPOSITORY_BASE}/${ECR_NAME}-${ENV}

# Gitのコミットハッシュを取得
GIT_COMMIT_HASH ?= $(shell git rev-parse --short HEAD)

# ECS関連の変数
CLUSTER_NAME ?= learning-cluster-${ENV}
SERVICE_NAME ?= learning-ecs-service-${ENV}
TASK_DEFINITION_FAMILY ?= learning-app-task-${ENV}
MIGRATE_TASK_DEFINITION_FAMILY ?= learning-db-migrate-task-${ENV}

.PHONY: help docker-login build-image build-image-no-cache push-image push-image-force release-image release-image-no-cache deploy deploy-no-cache deploy-ecs deploy-full deploy-full-with-migrate migrate migrate-task migrate-task-wait deploy-full-with-migrate-task ecs-status clean info .check-env

help: ## ヘルプを表示
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

.check-env:
ifndef ENV
	$(error ENV is required. e.g. make deploy ENV=stg)
endif

# ECRにログイン
# 実行タイミング: イメージをプッシュする前
# 実行コマンド: aws ecr get-login-password --region ${AWS_REGION} | docker login --username AWS --password-stdin ${IMAGE_REPOSITORY_BASE}
docker-login: .check-env ## ECRにログイン
	@echo "Logging in to ECR..."
	aws ecr get-login-password --region ${AWS_REGION} | docker login --username AWS --password-stdin ${IMAGE_REPOSITORY_BASE}

# Dockerイメージをビルド
# 実行タイミング: コード変更後、ECRにプッシュする前
# 実行コマンド: docker build --platform=linux/amd64 -t ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH} -f Dockerfile.ecs .
build-image: .check-env ## Dockerイメージをビルド
	@echo "Building Docker image: ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}"
	docker build --platform=linux/amd64 -t ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH} -f Dockerfile.ecs .

# Dockerイメージをキャッシュなしでビルド
# 実行タイミング: 依存関係の更新後、キャッシュを無視してビルドしたい場合
# 実行コマンド: docker build --no-cache --platform=linux/amd64 -t ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH} -f Dockerfile.ecs .
build-image-no-cache: .check-env ## Dockerイメージをキャッシュなしでビルド
	@echo "Building Docker image (no cache): ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}"
	docker build --no-cache --platform=linux/amd64 -t ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH} -f Dockerfile.ecs .

# ECRにイメージをプッシュ
# 実行タイミング: イメージをビルドした後
# 実行コマンド: docker push ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}
push-image: .check-env ## ECRにプッシュ
	@echo "Pushing to ECR: ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}"
	docker push ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}

# ECRにプッシュ（既存タグを削除してから）
# 実行タイミング: 同じタグで上書きしたい場合
# 実行コマンド: aws ecr batch-delete-image → docker push
push-image-force: .check-env ## ECRにプッシュ（既存タグを削除してから）
	@echo "Deleting existing tag if exists: ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}"
	@aws ecr batch-delete-image \
		--repository-name ${ECR_NAME}-${ENV} \
		--image-ids imageTag=${GIT_COMMIT_HASH} \
		--region ${AWS_REGION} 2>/dev/null || true
	@echo "Pushing to ECR: ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}"
	docker push ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}

# DockerイメージをビルドしてECRにpushする
# 実行タイミング: コード変更後、デプロイ前にイメージをリリースする場合
# 実行コマンド: docker-login → build-image → push-image
release-image: docker-login build-image push-image ## DockerイメージをビルドしてECRにpushする
	@echo "Release completed!"
	@echo "Image URI: ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}"
	@echo "Commit: $(shell git rev-parse HEAD)"

# キャッシュなしでビルドしてECRにpushする
# 実行タイミング: 依存関係の更新後、キャッシュを無視してビルドしたい場合
# 実行コマンド: docker-login → build-image-no-cache → push-image
release-image-no-cache: docker-login build-image-no-cache push-image ## キャッシュなしでビルドしてECRにpushする
	@echo "Release completed (no cache)!"
	@echo "Image URI: ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}"
	@echo "Commit: $(shell git rev-parse HEAD)"

# 全体デプロイ（エイリアス: release-image）
# 実行タイミング: イメージをビルドしてプッシュする場合（ECS更新は含まない）
# 実行コマンド: release-image
deploy: release-image ## 全体デプロイ（エイリアス: release-image）

# キャッシュなしで全体デプロイ
# 実行タイミング: 依存関係の更新後、キャッシュを無視してビルドする場合
# 実行コマンド: release-image-no-cache
deploy-no-cache: release-image-no-cache ## キャッシュなしで全体デプロイ

# ECSサービスを更新（イメージは既にプッシュ済みであること）
# 実行タイミング: イメージをECRにプッシュした後、ECSサービスを更新する場合
# 実行コマンド: aws ecs describe-task-definition → aws ecs register-task-definition → aws ecs update-service
deploy-ecs: .check-env ## ECSサービスを更新（イメージは既にプッシュ済みであること）
	@echo "Deploying to ECS..."
	@echo "Cluster: ${CLUSTER_NAME}"
	@echo "Service: ${SERVICE_NAME}"
	@echo "Task Definition: ${TASK_DEFINITION_FAMILY}"
	@echo "Image: ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}"
	@which jq > /dev/null || (echo "Error: jq is required. Install with: brew install jq" && exit 1)
	@aws ecs describe-task-definition \
		--task-definition ${TASK_DEFINITION_FAMILY} \
		--region ${AWS_REGION} \
		--query taskDefinition > /tmp/task-definition.json
	@if [[ "$$(uname)" == "Darwin" ]]; then \
		sed -i '' "s|\"image\": \".*\"|\"image\": \"${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}\"|g" /tmp/task-definition.json; \
	else \
		sed -i "s|\"image\": \".*\"|\"image\": \"${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}\"|g" /tmp/task-definition.json; \
	fi
	@jq 'del(.taskDefinitionArn, .revision, .status, .requiresAttributes, .compatibilities, .registeredAt, .registeredBy)' /tmp/task-definition.json > /tmp/task-definition-new.json
	@aws ecs register-task-definition --cli-input-json file:///tmp/task-definition-new.json --region ${AWS_REGION} > /dev/null
	@aws ecs update-service \
		--cluster ${CLUSTER_NAME} \
		--service ${SERVICE_NAME} \
		--task-definition ${TASK_DEFINITION_FAMILY} \
		--region ${AWS_REGION} \
		--force-new-deployment > /dev/null
	@rm -f /tmp/task-definition*.json
	@echo "Deployment initiated!"
	@echo "Check status with: make ecs-status ENV=${ENV}"

# 完全デプロイ（ビルド→プッシュ→ECS更新）
# 実行タイミング: コード変更後、ECSサービスまで更新する場合（GitHub Actionsで使用）
# 実行コマンド: release-image → deploy-ecs
deploy-full: release-image deploy-ecs ## 完全デプロイ（ビルド→プッシュ→ECS更新）
	@echo "Full deployment completed!"
	@echo "Image URI: ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}"
	@echo "Commit: $(shell git rev-parse HEAD)"

# データベースマイグレーションを実行
# 実行タイミング: デプロイ後、データベーススキーマを更新する場合
# 実行コマンド: aws ecs list-tasks → aws ecs execute-command (php artisan migrate --force)
# 注意: ECS Execが有効になっている必要があります。インタラクティブなコマンドのため、GitHub Actionsでは使用できません。
migrate: .check-env ## データベースマイグレーションを実行
	@echo "Running database migration..."
	@echo "Cluster: ${CLUSTER_NAME}"
	@echo "Service: ${SERVICE_NAME}"
	@TASK_ARN=$$(aws ecs list-tasks \
		--cluster ${CLUSTER_NAME} \
		--service-name ${SERVICE_NAME} \
		--region ${AWS_REGION} \
		--query 'taskArns[0]' \
		--output text 2>/dev/null); \
	if [ -z "$$TASK_ARN" ] || [ "$$TASK_ARN" = "None" ]; then \
		echo "Error: No running tasks found. Please wait for tasks to be running."; \
		exit 1; \
	fi; \
	TASK_ID=$${TASK_ARN##*/}; \
	echo "Executing migration on task: $$TASK_ID"; \
	aws ecs execute-command \
		--cluster ${CLUSTER_NAME} \
		--task $$TASK_ID \
		--container app \
		--interactive \
		--command "cd /var/www && php artisan migrate --force" \
		--region ${AWS_REGION} || (echo "Migration failed. Make sure ECS Exec is enabled." && exit 1)

# 完全デプロイ＋マイグレーション実行
# 実行タイミング: コード変更後、ECSサービスを更新し、データベースマイグレーションも実行する場合
# 実行コマンド: deploy-full → migrate
# 注意: migrateはインタラクティブなコマンドのため、GitHub Actionsでは使用できません。マイグレーション専用タスクを使用してください。
deploy-full-with-migrate: deploy-full migrate ## 完全デプロイ＋マイグレーション実行
	@echo "Deployment and migration completed!"
	@echo "Image URI: ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}"
	@echo "Commit: $(shell git rev-parse HEAD)"

# マイグレーション専用タスク定義を更新（新しいイメージで）
# 実行タイミング: 新しいイメージをECRにプッシュした後、マイグレーションタスク定義を更新する場合
# 実行コマンド: aws ecs describe-task-definition → aws ecs register-task-definition
deploy-migrate-task-definition: .check-env ## マイグレーション専用タスク定義を更新（新しいイメージで）
	@echo "Updating migration task definition..."
	@echo "Task Definition: ${MIGRATE_TASK_DEFINITION_FAMILY}"
	@echo "Image: ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}"
	@which jq > /dev/null || (echo "Error: jq is required. Install with: brew install jq" && exit 1)
	@aws ecs describe-task-definition \
		--task-definition ${MIGRATE_TASK_DEFINITION_FAMILY} \
		--region ${AWS_REGION} \
		--query taskDefinition > /tmp/migrate-task-definition.json
	@if [[ "$$(uname)" == "Darwin" ]]; then \
		sed -i '' "s|\"image\": \".*\"|\"image\": \"${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}\"|g" /tmp/migrate-task-definition.json; \
	else \
		sed -i "s|\"image\": \".*\"|\"image\": \"${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}\"|g" /tmp/migrate-task-definition.json; \
	fi
	@jq 'del(.taskDefinitionArn, .revision, .status, .requiresAttributes, .compatibilities, .registeredAt, .registeredBy)' /tmp/migrate-task-definition.json > /tmp/migrate-task-definition-new.json
	@aws ecs register-task-definition --cli-input-json file:///tmp/migrate-task-definition-new.json --region ${AWS_REGION} > /dev/null
	@rm -f /tmp/migrate-task-definition*.json
	@echo "Migration task definition updated!"

# マイグレーション専用タスクを実行
# 実行タイミング: デプロイ後、データベーススキーマを更新する場合（ワンショットタスク）
# 実行コマンド: aws ecs run-task
# 注意: マイグレーション専用タスク定義が必要です。GitHub Actionsでも使用可能です。
migrate-task: .check-env ## マイグレーション専用タスクを実行
	@echo "Running migration task..."
	@echo "Cluster: ${CLUSTER_NAME}"
	@echo "Task Definition: ${MIGRATE_TASK_DEFINITION_FAMILY}"
	@which jq > /dev/null || (echo "Error: jq is required. Install with: brew install jq" && exit 1)
	@echo "Fetching network configuration from ECS service..."
	@SERVICE_CONFIG=$$(aws ecs describe-services \
		--cluster ${CLUSTER_NAME} \
		--services ${SERVICE_NAME} \
		--region ${AWS_REGION} \
		--query 'services[0].networkConfiguration.awsvpcConfiguration' \
		--output json); \
	if [ -z "$$SERVICE_CONFIG" ] || [ "$$SERVICE_CONFIG" = "null" ]; then \
		echo "Error: Failed to get network configuration from ECS service"; \
		exit 1; \
	fi; \
	SUBNET_IDS=$$(echo "$$SERVICE_CONFIG" | jq -r '.subnets[]' | tr '\n' ',' | sed 's/,$$//'); \
	SG_ID=$$(echo "$$SERVICE_CONFIG" | jq -r '.securityGroups[0]'); \
	if [ -z "$$SUBNET_IDS" ] || [ -z "$$SG_ID" ]; then \
		echo "Error: Failed to parse network configuration"; \
		exit 1; \
	fi; \
	echo "Subnets: $$SUBNET_IDS"; \
	echo "Security Group: $$SG_ID"; \
	TASK_ARN=$$(aws ecs run-task \
		--cluster ${CLUSTER_NAME} \
		--task-definition ${MIGRATE_TASK_DEFINITION_FAMILY} \
		--launch-type FARGATE \
		--network-configuration "awsvpcConfiguration={subnets=[$$SUBNET_IDS],securityGroups=[$$SG_ID],assignPublicIp=DISABLED}" \
		--region ${AWS_REGION} \
		--query 'tasks[0].taskArn' \
		--output text); \
	if [ -z "$$TASK_ARN" ] || [ "$$TASK_ARN" = "None" ]; then \
		echo "Error: Failed to start migration task"; \
		exit 1; \
	fi; \
	TASK_ID=$${TASK_ARN##*/}; \
	echo "$$TASK_ID" > /tmp/migrate-task-id.txt; \
	echo "✅ Migration task started: $$TASK_ID"; \
	echo "Task ARN: $$TASK_ARN"; \
	echo "Monitor: aws ecs describe-tasks --cluster ${CLUSTER_NAME} --tasks $$TASK_ID --region ${AWS_REGION}"; \
	echo "Logs: /ecs/learning-db-migrate-task-${ENV}"

# マイグレーション専用タスクを実行して完了を待つ
# 実行タイミング: マイグレーションタスクの完了を待ってから次の処理に進む場合
# 実行コマンド: migrate-task → aws ecs wait tasks-stopped
migrate-task-wait: migrate-task ## マイグレーション専用タスクを実行して完了を待つ
	@echo "Waiting for migration task to complete..."
	@TASK_ID=$$(cat /tmp/migrate-task-id.txt 2>/dev/null || echo ""); \
	if [ -z "$$TASK_ID" ]; then \
		echo "Error: Could not get task ID. Make sure migrate-task was executed first."; \
		exit 1; \
	fi; \
	echo "Waiting for task: $$TASK_ID"; \
	aws ecs wait tasks-stopped \
		--cluster ${CLUSTER_NAME} \
		--tasks $$TASK_ID \
		--region ${AWS_REGION} || true; \
	EXIT_CODE=$$(aws ecs describe-tasks \
		--cluster ${CLUSTER_NAME} \
		--tasks $$TASK_ID \
		--region ${AWS_REGION} \
		--query 'tasks[0].containers[0].exitCode' \
		--output text 2>/dev/null || echo "null"); \
	if [ "$$EXIT_CODE" != "0" ] && [ "$$EXIT_CODE" != "null" ]; then \
		echo "❌ Migration task failed with exit code: $$EXIT_CODE"; \
		echo "Check logs: /ecs/learning-db-migrate-task-${ENV}"; \
		rm -f /tmp/migrate-task-id.txt; \
		exit 1; \
	fi; \
	rm -f /tmp/migrate-task-id.txt; \
	echo "✅ Migration task completed successfully!"

# 完全デプロイ＋マイグレーション専用タスク実行（正しい順序）
# 実行タイミング: コード変更後、マイグレーションを先に実行してからアプリをデプロイする場合
# 実行コマンド: release-image → deploy-migrate-task-definition → migrate-task-wait → deploy-ecs
# 注意: GitHub Actionsでも使用可能です。正しい順序で実行されます。
deploy-full-with-migrate-task: release-image deploy-migrate-task-definition migrate-task-wait deploy-ecs ## 完全デプロイ＋マイグレーション専用タスク実行（正しい順序）
	@echo "✅ Deployment and migration completed!"
	@echo "Image URI: ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}"
	@echo "Commit: $(shell git rev-parse HEAD)"

# ECSサービスの状態を確認
# 実行タイミング: デプロイ後、サービスの状態を確認する場合
# 実行コマンド: aws ecs describe-services
ecs-status: .check-env ## ECSサービスの状態を確認
	@echo "ECS Service Status:"
	@aws ecs describe-services \
		--cluster ${CLUSTER_NAME} \
		--services ${SERVICE_NAME} \
		--region ${AWS_REGION} \
		--query 'services[0].{Status:status,RunningCount:runningCount,DesiredCount:desiredCount,TaskDefinition:taskDefinition}' \
		--output table

# ローカルイメージを削除
# 実行タイミング: ローカルのDockerイメージをクリーンアップする場合
# 実行コマンド: docker rmi ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}
clean: ## ローカルイメージを削除
	@echo "Cleaning up local images..."
	docker rmi ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH} || true

# 現在の設定を表示
# 実行タイミング: 現在の設定を確認する場合
# 実行コマンド: 環境変数と設定値を表示
# 注意: CI環境ではAWS Account IDとECR URIは表示されません（セキュリティのため）
info: ## 現在の設定を表示
	@echo "Environment: ${ENV}"
	@if [ -z "$${CI:-}" ]; then \
		echo "AWS Account ID: ${AWS_ACCOUNT_ID}"; \
		echo "ECR URI: ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}"; \
	fi
	@echo "AWS Region: ${AWS_REGION}"
	@echo "ECR Name: ${ECR_NAME}"
	@echo "ECR Repository: ${ECR_NAME}-${ENV}"
	@echo "Git Commit Hash: ${GIT_COMMIT_HASH}"
	@echo "Full Commit Hash: $(shell git rev-parse HEAD)"
	@echo "Cluster: ${CLUSTER_NAME}"
	@echo "Service: ${SERVICE_NAME}"
	@echo "Task Definition: ${TASK_DEFINITION_FAMILY}"

# RDSのステータスを取得
# 実行タイミング: RDSインスタンスの状態を確認する場合
# 実行コマンド: aws rds describe-db-instances
db-status: ## RDSのステータスを取得
	@echo "RDS Status: $(shell aws rds describe-db-instances \
		--db-instance-identifier learning-db-stg \
		--region ap-northeast-1 \
		--query 'DBInstances[0].DBInstanceStatus' \
		--output text)"


