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

.PHONY: help docker-login build-image build-image-no-cache push-image push-image-force release-image release-image-no-cache deploy deploy-no-cache deploy-ecs deploy-full deploy-full-with-migrate migrate migrate-task deploy-full-with-migrate-task ecs-status clean info .check-env

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
		--output json 2>/dev/null); \
	if [ -z "$$SERVICE_CONFIG" ] || [ "$$SERVICE_CONFIG" = "null" ]; then \
		echo "Warning: Could not get network config from service, trying direct subnet lookup..."; \
		SUBNET_1A_ID=$$(aws ec2 describe-subnets \
			--filters "Name=tag:Name,Values=private-subnet-nat-1a-${ENV}" \
			--region ${AWS_REGION} \
			--query 'Subnets[0].SubnetId' \
			--output text 2>&1); \
		if [ -z "$$SUBNET_1A_ID" ] || [ "$$SUBNET_1A_ID" = "None" ] || echo "$$SUBNET_1A_ID" | grep -q "error"; then \
			echo "Error: Failed to get subnet ID for private-subnet-nat-1a-${ENV}"; \
			echo "Debug: $$SUBNET_1A_ID"; \
			exit 1; \
		fi; \
		SUBNET_1C_ID=$$(aws ec2 describe-subnets \
			--filters "Name=tag:Name,Values=private-subnet-nat-1c-${ENV}" \
			--region ${AWS_REGION} \
			--query 'Subnets[0].SubnetId' \
			--output text 2>&1); \
		if [ -z "$$SUBNET_1C_ID" ] || [ "$$SUBNET_1C_ID" = "None" ] || echo "$$SUBNET_1C_ID" | grep -q "error"; then \
			echo "Error: Failed to get subnet ID for private-subnet-nat-1c-${ENV}"; \
			echo "Debug: $$SUBNET_1C_ID"; \
			exit 1; \
		fi; \
		SG_ID=$$(aws ec2 describe-security-groups \
			--filters "Name=tag:Name,Values=learning-app-sg-${ENV}" \
			--region ${AWS_REGION} \
			--query 'SecurityGroups[0].GroupId' \
			--output text 2>&1); \
		if [ -z "$$SG_ID" ] || [ "$$SG_ID" = "None" ] || echo "$$SG_ID" | grep -q "error"; then \
			echo "Error: Failed to get security group ID for learning-app-sg-${ENV}"; \
			echo "Debug: $$SG_ID"; \
			exit 1; \
		fi; \
	else \
		echo "Using network configuration from ECS service..."; \
		SUBNET_IDS=$$(echo "$$SERVICE_CONFIG" | jq -r '.subnets[]' 2>/dev/null); \
		SG_ID=$$(echo "$$SERVICE_CONFIG" | jq -r '.securityGroups[0]' 2>/dev/null); \
		if [ -z "$$SUBNET_IDS" ] || [ -z "$$SG_ID" ]; then \
			echo "Error: Failed to parse network configuration from service"; \
			exit 1; \
		fi; \
		SUBNET_1A_ID=$$(echo "$$SUBNET_IDS" | head -n 1); \
		SUBNET_1C_ID=$$(echo "$$SUBNET_IDS" | tail -n 1); \
	fi; \
	echo "Subnet 1a: $$SUBNET_1A_ID"; \
	echo "Subnet 1c: $$SUBNET_1C_ID"; \
	echo "Security Group: $$SG_ID"; \
	TASK_ARN=$$(aws ecs run-task \
		--cluster ${CLUSTER_NAME} \
		--task-definition ${MIGRATE_TASK_DEFINITION_FAMILY} \
		--launch-type FARGATE \
		--network-configuration "awsvpcConfiguration={subnets=[$$SUBNET_1A_ID,$$SUBNET_1C_ID],securityGroups=[$$SG_ID],assignPublicIp=DISABLED}" \
		--region ${AWS_REGION} \
		--query 'tasks[0].taskArn' \
		--output text 2>&1); \
	if [ -z "$$TASK_ARN" ] || [ "$$TASK_ARN" = "None" ] || echo "$$TASK_ARN" | grep -q "error"; then \
		echo "Error: Failed to start migration task"; \
		echo "Debug: $$TASK_ARN"; \
		exit 1; \
	fi; \
	TASK_ID=$${TASK_ARN##*/}; \
	echo "Migration task started: $$TASK_ID"; \
	echo "Task ARN: $$TASK_ARN"; \
	echo "Monitor task status with: aws ecs describe-tasks --cluster ${CLUSTER_NAME} --tasks $$TASK_ID --region ${AWS_REGION}"; \
	echo "View logs in CloudWatch Logs: /ecs/learning-db-migrate-task-${ENV}"

# 完全デプロイ＋マイグレーション専用タスク実行
# 実行タイミング: コード変更後、ECSサービスを更新し、マイグレーション専用タスクも実行する場合
# 実行コマンド: deploy-full → migrate-task
# 注意: GitHub Actionsでも使用可能です。
deploy-full-with-migrate-task: deploy-full migrate-task ## 完全デプロイ＋マイグレーション専用タスク実行
	@echo "Deployment and migration task completed!"
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

