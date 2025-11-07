# Multi-stage build for React Frontend
FROM node:18-alpine AS base

# Set working directory
WORKDIR /app

# Development stage
FROM base AS development

# Copy package files
COPY package.json package-lock.json* ./

# Install dependencies
RUN npm ci

# Copy source code
COPY . .

# Expose port for development
EXPOSE 5173

# Start development server
CMD ["npm", "run", "dev"]

# Build stage
FROM base AS build

# Copy package files
COPY package.json package-lock.json* ./

# Install dependencies
RUN npm ci

# Copy source code
COPY . .

# Build the application
RUN npm run build

# Production stage
FROM nginx:alpine AS production

# Copy built application from build stage
COPY --from=build /app/dist /usr/share/nginx/html

# Copy nginx configuration
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Create health check script
RUN echo '#!/bin/sh\n\
curl -f http://localhost/health || exit 1\n\
' > /docker-entrypoint.d/50-healthcheck.sh && \
    chmod +x /docker-entrypoint.d/50-healthcheck.sh

# Create startup script for graceful shutdown
RUN echo '#!/bin/sh\n\
echo "Starting React Dashboard..."\n\
exec nginx -g "daemon off;"\n\
' > /docker-entrypoint.d/50-startup.sh && \
    chmod +x /docker-entrypoint.d/50-startup.sh

# Copy entrypoint for custom handling
COPY entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

# Set environment variables for production
ENV NODE_ENV=production
ENV NGINX_HOST=0.0.0.0
ENV NGINX_PORT=80

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD curl -f http://localhost/health || exit 1

# Use custom entrypoint
ENTRYPOINT ["/docker-entrypoint.sh"]

# Start nginx
CMD ["nginx", "-g", "daemon off;"]