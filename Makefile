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

.PHONY: help docker-login build-image build-image-no-cache push-image push-image-force release-image release-image-no-cache deploy deploy-no-cache deploy-ecs deploy-full deploy-full-with-migrate migrate ecs-status clean info .check-env

help: ## ヘルプを表示
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

.check-env:
ifndef ENV
	$(error ENV is required. e.g. make deploy ENV=stg)
endif

docker-login: .check-env ## ECRにログイン
	@echo "Logging in to ECR..."
	aws ecr get-login-password --region ${AWS_REGION} | docker login --username AWS --password-stdin ${IMAGE_REPOSITORY_BASE}

build-image: .check-env ## Dockerイメージをビルド
	@echo "Building Docker image: ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}"
	docker build --platform=linux/amd64 -t ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH} -f Dockerfile.ecs .

build-image-no-cache: .check-env ## Dockerイメージをキャッシュなしでビルド
	@echo "Building Docker image (no cache): ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}"
	docker build --no-cache --platform=linux/amd64 -t ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH} -f Dockerfile.ecs .

push-image: .check-env ## ECRにプッシュ
	@echo "Pushing to ECR: ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}"
	docker push ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}

push-image-force: .check-env ## ECRにプッシュ（既存タグを削除してから）
	@echo "Deleting existing tag if exists: ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}"
	@aws ecr batch-delete-image \
		--repository-name ${ECR_NAME}-${ENV} \
		--image-ids imageTag=${GIT_COMMIT_HASH} \
		--region ${AWS_REGION} 2>/dev/null || true
	@echo "Pushing to ECR: ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}"
	docker push ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}

release-image: docker-login build-image push-image ## DockerイメージをビルドしてECRにpushする
	@echo "Release completed!"
	@echo "Image URI: ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}"
	@echo "Commit: $(shell git rev-parse HEAD)"

release-image-no-cache: docker-login build-image-no-cache push-image ## キャッシュなしでビルドしてECRにpushする
	@echo "Release completed (no cache)!"
	@echo "Image URI: ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}"
	@echo "Commit: $(shell git rev-parse HEAD)"

deploy: release-image ## 全体デプロイ（エイリアス: release-image）

deploy-no-cache: release-image-no-cache ## キャッシュなしで全体デプロイ

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

deploy-full: release-image deploy-ecs ## 完全デプロイ（ビルド→プッシュ→ECS更新）
	@echo "Full deployment completed!"
	@echo "Image URI: ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}"
	@echo "Commit: $(shell git rev-parse HEAD)"

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

deploy-full-with-migrate: deploy-full migrate ## 完全デプロイ＋マイグレーション実行
	@echo "Deployment and migration completed!"
	@echo "Image URI: ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}"
	@echo "Commit: $(shell git rev-parse HEAD)"

ecs-status: .check-env ## ECSサービスの状態を確認
	@echo "ECS Service Status:"
	@aws ecs describe-services \
		--cluster ${CLUSTER_NAME} \
		--services ${SERVICE_NAME} \
		--region ${AWS_REGION} \
		--query 'services[0].{Status:status,RunningCount:runningCount,DesiredCount:desiredCount,TaskDefinition:taskDefinition}' \
		--output table

clean: ## ローカルイメージを削除
	@echo "Cleaning up local images..."
	docker rmi ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH} || true

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

db-status: ## RDSのステータスを取得
	@echo "RDS Status: $(shell aws rds describe-db-instances \
		--db-instance-identifier learning-db-stg \
		--region ap-northeast-1 \
		--query 'DBInstances[0].DBInstanceStatus' \
		--output text)"

