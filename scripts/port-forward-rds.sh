#!/bin/bash
# scripts/port-forward-rds.sh

ENV=${1:-stg}
REGION=ap-northeast-1

# 踏み台サーバーのインスタンスIDを取得
INSTANCE_ID=$(aws ec2 describe-instances \
  --filters "Name=tag:Name,Values=learning-bastion-${ENV}" \
  --query 'Reservations[0].Instances[0].InstanceId' \
  --output text \
  --region ${REGION})

if [ -z "$INSTANCE_ID" ] || [ "$INSTANCE_ID" = "None" ]; then
  echo "Error: Bastion host not found"
  exit 1
fi

# RDSエンドポイントを取得
RDS_ENDPOINT=$(aws rds describe-db-instances \
  --db-instance-identifier learning-db-${ENV} \
  --query 'DBInstances[0].Endpoint.Address' \
  --output text \
  --region ${REGION})

if [ -z "$RDS_ENDPOINT" ] || [ "$RDS_ENDPOINT" = "None" ]; then
  echo "Error: RDS endpoint not found"
  exit 1
fi

echo "Starting port forwarding..."
echo "Local port: 5433"
echo "RDS endpoint: ${RDS_ENDPOINT}"
echo ""
echo "Connect to TablePlus/pgAdmin with:"
echo "  Host: localhost"
echo "  Port: 5433"
echo "  Database: kanaoka"
echo "  Username: postgres"
echo ""
echo "Press Ctrl+C to stop port forwarding"

aws ssm start-session \
  --target ${INSTANCE_ID} \
  --document-name AWS-StartPortForwardingSessionToRemoteHost \
  --parameters "{\"host\":[\"${RDS_ENDPOINT}\"],\"portNumber\":[\"5432\"],\"localPortNumber\":[\"5433\"]}" \
  --region ${REGION}

