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

.PHONY: help docker-login build-image build-image-no-cache push-image release-image release-image-no-cache deploy deploy-no-cache clean info .check-env

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

clean: ## ローカルイメージを削除
	@echo "Cleaning up local images..."
	docker rmi ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH} || true

info: ## 現在の設定を表示
	@echo "Environment: ${ENV}"
	@echo "AWS Account ID: ${AWS_ACCOUNT_ID}"
	@echo "AWS Region: ${AWS_REGION}"
	@echo "ECR Name: ${ECR_NAME}"
	@echo "ECR Repository: ${ECR_NAME}-${ENV}"
	@echo "Git Commit Hash: ${GIT_COMMIT_HASH}"
	@echo "Full Commit Hash: $(shell git rev-parse HEAD)"
	@echo "ECR URI: ${IMAGE_REPOSITORY_URI}:${GIT_COMMIT_HASH}"


db-status: ## RDSのステータスを取得
	@echo "RDS Status: $(shell aws rds describe-db-instances \
		--db-instance-identifier learning-db-stg \
		--region ap-northeast-1 \
		--query 'DBInstances[0].DBInstanceStatus' \
		--output text)"

