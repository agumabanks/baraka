# Courier Platform - Production Deployment Guide

## Overview
This document provides comprehensive guidance for deploying the Courier multi-tier reporting and analytics platform to production environments with 99.9% uptime guarantee.

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Infrastructure Setup](#infrastructure-setup)
3. [Container Deployment](#container-deployment)
4. [Kubernetes Deployment](#kubernetes-deployment)
5. [Database Migration](#database-migration)
6. [Security Configuration](#security-configuration)
7. [Monitoring Setup](#monitoring-setup)
8. [SSL/TLS Configuration](#ssltls-configuration)
9. [Backup and Recovery](#backup-and-recovery)
10. [Performance Optimization](#performance-optimization)
11. [Troubleshooting](#troubleshooting)

## Prerequisites

### System Requirements
- **CPU**: 8+ cores (16+ recommended for production)
- **Memory**: 32GB+ RAM (64GB+ recommended)
- **Storage**: 500GB+ SSD (1TB+ recommended for production data)
- **Network**: 10Gbps+ bandwidth
- **Operating System**: Ubuntu 22.04 LTS or RHEL 9+

### Software Dependencies
- Docker 24.0+
- Kubernetes 1.28+
- kubectl 1.28+
- helm 3.12+
- AWS CLI v2 (for AWS deployments)
- cert-manager 1.13+
- ingress-nginx 1.8+

### Cloud Infrastructure
- AWS EKS cluster (recommended)
- RDS MySQL 8.0 instance
- ElastiCache Redis
- Application Load Balancer
- CloudFront CDN
- Route53 DNS
- AWS Certificate Manager

## Infrastructure Setup

### 1. AWS EKS Cluster Setup
```bash
# Create EKS cluster
eksctl create cluster \
  --name courier-production \
  --region eu-west-1 \
  --version 1.28 \
  --nodegroup-name standard-workers \
  --node-type m5.xlarge \
  --nodes 6 \
  --nodes-min 3 \
  --nodes-max 20 \
  --managed

# Update kubeconfig
aws eks update-kubeconfig --name courier-production --region eu-west-1

# Install required addons
helm repo add ingress-nginx https://kubernetes.github.io/ingress-nginx
helm repo add jetstack https://charts.jetstack.io
helm repo add prometheus-community https://prometheus-community.github.io/helm-charts
helm repo add grafana https://grafana.github.io/helm-charts

helm install ingress-nginx ingress-nginx/ingress-nginx \
  --set controller.replicaCount=2 \
  --set controller.nodeSelector."beta\.kubernetes\.io/os"=linux \
  --set defaultBackend.nodeSelector."beta\.kubernetes\.io/os"=linux

helm install cert-manager jetstack/cert-manager \
  --namespace cert-manager \
  --create-namespace \
  --set installCRDs=true
```

### 2. Database Infrastructure
```bash
# Create RDS instance
aws rds create-db-instance \
  --db-instance-identifier courier-mysql \
  --db-instance-class db.r5.xlarge \
  --engine mysql \
  --engine-version 8.0.35 \
  --master-username admin \
  --master-user-password ${DB_PASSWORD} \
  --allocated-storage 100 \
  --storage-type gp3 \
  --storage-encrypted \
  --backup-retention-period 30 \
  --preferred-backup-window "03:00-04:00" \
  --preferred-maintenance-window "sun:04:00-sun:05:00" \
  --vpc-security-group-ids ${SECURITY_GROUP_ID} \
  --db-subnet-group-name courier-db-subnet-group

# Create read replica
aws rds create-db-instance-read-replica \
  --db-instance-identifier courier-mysql-replica \
  --source-db-instance-identifier courier-mysql \
  --db-instance-class db.r5.large
```

## Container Deployment

### 1. Environment Configuration
```bash
# Copy environment template
cp .env.example .env.production

# Configure environment variables
vim .env.production
```

**Critical Production Settings:**
```bash
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-generated-key
DB_CONNECTION=mysql
DB_HOST=your-rds-endpoint
DB_DATABASE=courier
DB_USERNAME=admin
DB_PASSWORD=your-secure-password
REDIS_HOST=your-redis-endpoint
MAIL_DRIVER=ses
AWS_ACCESS_KEY_ID=your-aws-key
AWS_SECRET_ACCESS_KEY=your-aws-secret
AWS_DEFAULT_REGION=eu-west-1
```

### 2. Docker Deployment
```bash
# Build and push images
docker build -f Dockerfile.backend -t ${DOCKER_REGISTRY}/courier-backend:latest .
docker build -f react-dashboard/Dockerfile -t ${DOCKER_REGISTRY}/courier-frontend:latest .

# Push to registry
docker push ${DOCKER_REGISTRY}/courier-backend:latest
docker push ${DOCKER_REGISTRY}/courier-frontend:latest

# Deploy with Docker Compose
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

## Kubernetes Deployment

### 1. Namespace and Secret Setup
```bash
# Create namespace
kubectl create namespace courier-production

# Apply secrets
kubectl apply -f kubernetes/manifests/secrets/

# Configure environment
export DOCKER_REGISTRY=your-registry.com
export VERSION=latest
```

### 2. Core Infrastructure
```bash
# Deploy database and Redis
kubectl apply -f kubernetes/manifests/00-namespaces.yml
kubectl apply -f kubernetes/manifests/03-database-infrastructure.yml

# Wait for database to be ready
kubectl wait --for=condition=available --timeout=300s deployment/mysql -n courier-production
```

### 3. Application Deployment
```bash
# Deploy backend
kubectl apply -f kubernetes/manifests/01-backend-deployment.yml

# Deploy frontend
kubectl apply -f kubernetes/manifests/02-frontend-deployment.yml

# Verify deployment
kubectl get pods -n courier-production
kubectl get services -n courier-production
```

## Database Migration

### 1. Initial Migration
```bash
# Run migrations
kubectl run migration-job --rm -it \
  --image=${DOCKER_REGISTRY}/courier-backend:latest \
  --command -- php artisan migrate --force

# Seed initial data (optional)
kubectl run seed-job --rm -it \
  --image=${DOCKER_REGISTRY}/courier-backend:latest \
  --command -- php artisan db:seed
```

### 2. ETL Pipeline Setup
```bash
# Run ETL pipeline
kubectl run etl-job --rm -it \
  --image=${DOCKER_REGISTRY}/courier-backend:latest \
  --command -- php artisan etl:run --full-refresh

# Verify data integrity
kubectl run verify-job --rm -it \
  --image=${DOCKER_REGISTRY}/courier-backend:latest \
  --command -- php artisan etl:verify-data
```

## Security Configuration

### 1. SSL/TLS Setup
```bash
# Create cluster issuer for Let's Encrypt
kubectl apply -f - <<EOF
apiVersion: cert-manager.io/v1
kind: ClusterIssuer
metadata:
  name: letsencrypt-prod
spec:
  acme:
    server: https://acme-v02.api.letsencrypt.org/directory
    email: admin@courier.com
    privateKeySecretRef:
      name: letsencrypt-prod
    solvers:
    - http01:
        ingress:
          class: nginx
EOF

# Create TLS certificate
kubectl apply -f - <<EOF
apiVersion: cert-manager.io/v1
kind: Certificate
metadata:
  name: courier-tls
  namespace: courier-production
spec:
  secretName: courier-tls-secret
  issuerRef:
    name: letsencrypt-prod
    kind: ClusterIssuer
  dnsNames:
  - courier.com
  - www.courier.com
  - api.courier.com
EOF
```

### 2. Security Hardening
```bash
# Apply Pod Security Standards
kubectl apply -f - <<EOF
apiVersion: v1
kind: Namespace
metadata:
  name: courier-production
  labels:
    pod-security.kubernetes.io/enforce: restricted
    pod-security.kubernetes.io/audit: restricted
    pod-security.kubernetes.io/warn: restricted
EOF

# Network policies
kubectl apply -f kubernetes/manifests/network-policies.yml
```

## Monitoring Setup

### 1. Prometheus and Grafana
```bash
# Install monitoring stack
helm install prometheus prometheus-community/kube-prometheus-stack \
  --namespace monitoring \
  --create-namespace \
  --set grafana.adminPassword=your-grafana-password

# Apply custom monitoring configuration
kubectl apply -f monitoring/prometheus/
kubectl apply -f monitoring/grafana/
```

### 2. Application Monitoring
```bash
# Deploy monitoring exporters
kubectl apply -f monitoring/exporters/

# Configure custom metrics
kubectl apply -f monitoring/custom-metrics.yml
```

## SSL/TLS Configuration

### 1. Certificate Management
- **Automatic**: cert-manager with Let's Encrypt
- **Manual**: AWS Certificate Manager
- **Custom**: Bring your own certificates

### 2. Security Headers
```nginx
# Implemented in nginx configuration
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
```

## Backup and Recovery

### 1. Automated Backups
```bash
# Database backup
kubectl apply -f kubernetes/manifests/backup-cron.yml

# File storage backup
kubectl apply -f kubernetes/manifests/file-backup-cron.yml
```

### 2. Disaster Recovery
```bash
# Point-in-time recovery
aws rds restore-db-instance-from-db-snapshot \
  --db-instance-identifier courier-mysql-recovery \
  --db-snapshot-identifier courier-snapshot-$(date +%Y%m%d)

# Application state recovery
kubectl apply -f kubernetes/manifests/disaster-recovery/
```

## Performance Optimization

### 1. Database Optimization
- Connection pooling enabled
- Query optimization
- Index optimization
- Read replicas for read-heavy workloads

### 2. Application Optimization
- Redis caching
- CDN configuration
- Image optimization
- Database query optimization

### 3. Infrastructure Optimization
```bash
# Enable horizontal pod autoscaling
kubectl apply -f kubernetes/manifests/hpa/

# Configure resource requests and limits
kubectl apply -f kubernetes/manifests/resource-optimization/
```

## Troubleshooting

### Common Issues

#### 1. Pod Startup Issues
```bash
# Check pod status
kubectl describe pod <pod-name> -n courier-production

# Check logs
kubectl logs <pod-name> -n courier-production

# Check events
kubectl get events -n courier-production --sort-by=.metadata.creationTimestamp
```

#### 2. Database Connection Issues
```bash
# Test database connectivity
kubectl run db-test --rm -it --image=mysql:8.0 --command -- mysql -h <db-endpoint> -u admin -p

# Check database logs
kubectl logs deployment/mysql -n courier-production
```

#### 3. Performance Issues
```bash
# Check resource usage
kubectl top pods -n courier-production

# Check HPA status
kubectl get hpa -n courier-production

# Check metrics
kubectl get --raw /apis/metrics.k8s.io/v1beta1/nodes | jq
```

### Log Analysis
```bash
# Centralized logging
kubectl logs -l app=courier-backend -n courier-production --tail=100

# Search for errors
kubectl logs -l app=courier-backend -n courier-production | grep ERROR
```

## Monitoring Dashboards

### Key Dashboards
1. **System Overview**: CPU, memory, disk, network
2. **Application Performance**: Response times, throughput, errors
3. **Database Performance**: Connections, queries, slow queries
4. **Business Metrics**: Financial reporting KPIs, user activity
5. **Security**: Authentication attempts, rate limiting, threats

### Alert Configuration
Critical alerts are configured in `monitoring/prometheus/alert_rules.yml`

## Scaling

### Horizontal Scaling
- Auto-scaling based on CPU/memory
- Manual scaling for predictable loads
- Database read replicas for scaling reads

### Vertical Scaling
- Resource optimization
- Database instance scaling
- Cache optimization

## Maintenance

### Regular Maintenance Tasks
1. **Daily**: Log rotation, backup verification
2. **Weekly**: Security updates, performance review
3. **Monthly**: Dependency updates, security audit
4. **Quarterly**: Disaster recovery testing, capacity planning

### Update Procedures
```bash
# Rolling update
kubectl set image deployment/courier-backend courier-backend=${NEW_IMAGE} -n courier-production

# Blue-green deployment
kubectl apply -f kubernetes/manifests/blue-green-deployment.yml
```

## Support and Escalation

### 24/7 Monitoring
- Automated alerts
- On-call rotation
- Escalation procedures

### Contact Information
- **Technical Lead**: tech-lead@courier.com
- **DevOps Team**: devops@courier.com
- **Emergency**: +1-555-EMERGENCY

---

## Appendix

### A. Environment Variables Reference
### B. API Documentation
### C. Database Schema
### D. Network Architecture
### E. Security Policies

For additional support, refer to the complete documentation at [docs.courier.com](https://docs.courier.com)