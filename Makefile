# 環境変数（デフォルト値）
ENV ?= stg
AWS_ACCOUNT_ID ?= $(shell aws sts get-caller-identity --query Account --output text)
AWS_REGION ?= ap-northeast-1
ECR_REPOSITORY ?= learning-app
IMAGE_TAG ?= $(shell git rev-parse --short HEAD)
LOCAL_IMAGE_NAME ?= learning-app

# ECRのフルURL
ECR_URI = $(AWS_ACCOUNT_ID).dkr.ecr.$(AWS_REGION).amazonaws.com/$(ECR_REPOSITORY)-$(ENV)

.PHONY: help ecr-login build tag push ecr-push deploy clean

help: ## ヘルプを表示
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

ecr-login: ## ECRにログイン
	@echo "Logging in to ECR..."
	aws ecr get-login-password --region $(AWS_REGION) | docker login --username AWS --password-stdin $(AWS_ACCOUNT_ID).dkr.ecr.$(AWS_REGION).amazonaws.com

build: ## Dockerイメージをビルド
	@echo "Building Docker image: $(LOCAL_IMAGE_NAME):$(IMAGE_TAG)"
	docker build -t $(LOCAL_IMAGE_NAME):$(IMAGE_TAG) .

tag: build ## イメージにECRタグを付与
	@echo "Tagging image for ECR: $(ECR_URI):$(IMAGE_TAG)"
	docker tag $(LOCAL_IMAGE_NAME):$(IMAGE_TAG) $(ECR_URI):$(IMAGE_TAG)

push: ecr-login tag ## ECRにプッシュ
	@echo "Pushing to ECR: $(ECR_URI):$(IMAGE_TAG)"
	docker push $(ECR_URI):$(IMAGE_TAG)

ecr-push: push ## ECRプッシュのエイリアス

deploy: push ## 全体デプロイ（ビルド→プッシュ）
	@echo "Deployment completed!"
	@echo "Image URI: $(ECR_URI):$(IMAGE_TAG)"
	@echo "Commit: $(shell git rev-parse HEAD)"

clean: ## ローカルイメージを削除
	@echo "Cleaning up local images..."
	docker rmi $(LOCAL_IMAGE_NAME):$(IMAGE_TAG) || true
	docker rmi $(ECR_URI):$(IMAGE_TAG) || true

info: ## 現在の設定を表示
	@echo "Environment: $(ENV)"
	@echo "AWS Account ID: $(AWS_ACCOUNT_ID)"
	@echo "AWS Region: $(AWS_REGION)"
	@echo "ECR Repository: $(ECR_REPOSITORY)"
	@echo "Image Tag: $(IMAGE_TAG)"
	@echo "Commit Hash: $(shell git rev-parse HEAD)"
	@echo "ECR URI: $(ECR_URI):$(IMAGE_TAG)"

