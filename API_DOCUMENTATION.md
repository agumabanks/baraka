# API Documentation for Logistics System

## Overview
This document outlines the API endpoints, authentication methods, and integrations for the DHL-standard logistics system.

## Authentication
- **Method**: Sanctum (Laravel Sanctum)
- **Endpoints**:
  - POST /api/v1/register - User registration
  - POST /api/v1/login - User login
  - POST /api/v1/logout - Logout
  - POST /api/v1/refresh - Refresh token

## Key API Endpoints

### Parcel Management
- GET /api/v1/parcels - List parcels
- POST /api/v1/parcels - Create parcel
- GET /api/v1/parcels/{id} - Get parcel details
- PUT /api/v1/parcels/{id} - Update parcel
- DELETE /api/v1/parcels/{id} - Delete parcel
- GET /api/v1/parcels/{id}/logs - Parcel tracking logs

### Delivery Man
- GET /api/v1/deliveryman/dashboard - Dashboard
- POST /api/v1/deliveryman/parcel/status - Update parcel status
- POST /api/v1/deliveryman/parcel/location - Update location

### Merchant
- GET /api/v1/merchant/shops - List shops
- POST /api/v1/merchant/shops - Create shop
- GET /api/v1/merchant/parcels - Merchant parcels

### Payments
- Integrations: Stripe, PayPal, Razorpay, etc.
- POST /api/v1/payments - Process payment

### Notifications
- FCM for push notifications
- Twilio/Vonage for SMS

## Security
- All endpoints require authentication via Bearer token
- Input validation using Laravel requests
- CSRF protection

## Improvements
- Add rate limiting
- Implement API versioning
- Add comprehensive error handling
- Use API resources for consistent responses