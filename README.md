<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing). 2
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 2000 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

### Premium Partners

 

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## API v1 Documentation

This project includes a REST API v1 for DHL-style logistics operations, supporting both mobile clients and admin dashboards.

### Setup

1. **Environment Variables**
   Add the following to your `.env` file:

   ```env
   FEATURE_MOBILE_API=true
   API_RATE_LIMIT=60
   API_RATE_LIMIT_ADMIN=120
   L5_SWAGGER_GENERATE_ALWAYS=false
   ```

2. **Run Migrations**
   ```bash
   php artisan migrate
   ```

3. **Seed Demo Data** (optional)
   ```bash
   php artisan db:seed --class=ApiV1DemoSeeder
   ```

4. **Generate API Documentation**
   ```bash
   php artisan l5-swagger:generate
   ```

### API Endpoints

#### Public Endpoints
- `GET /api/v1/tracking/{token}` - Public shipment tracking

#### Authentication (Mobile Client)
- `POST /api/v1/login` - Login with device binding
- `POST /api/v1/logout` - Logout and revoke token
- `GET /api/v1/me` - Get user profile
- `PATCH /api/v1/me` - Update user profile

#### Client Shipments
- `GET /api/v1/shipments` - List user's shipments
- `POST /api/v1/shipments` - Create new shipment
- `GET /api/v1/shipments/{id}` - Get shipment details
- `POST /api/v1/shipments/{id}/cancel` - Cancel shipment

#### Admin Endpoints (Dashboard)
- `GET /api/v1/admin/customers` - List customers
- `GET /api/v1/admin/customers/{id}` - Get customer details
- `PATCH /api/v1/admin/customers/{id}` - Update customer
- `GET /api/v1/admin/shipments` - List all shipments
- `GET /api/v1/admin/shipments/{id}` - Get shipment details
- `PATCH /api/v1/admin/shipments/{id}/status` - Update shipment status
- `GET /api/v1/admin/metrics` - Get dashboard metrics

### Authentication

#### Mobile Client
Use Sanctum personal access tokens with device binding:

```bash
# Login
curl -X POST http://localhost/api/v1/login \
  -H "Content-Type: application/json" \
  -H "device_uuid: your-device-uuid" \
  -d '{"email":"merchant@demo.com","password":"password"}'

# Use token in subsequent requests
curl -X GET http://localhost/api/v1/shipments \
  -H "Authorization: Bearer your-token-here"
```

#### Admin Dashboard
Use session-based authentication (existing web login).

### Sample Requests

#### Create Shipment
```bash
curl -X POST http://localhost/api/v1/shipments \
  -H "Authorization: Bearer your-token" \
  -H "Idempotency-Key: unique-key-123" \
  -H "Content-Type: application/json" \
  -d '{
    "origin_branch_id": 1,
    "dest_branch_id": 2,
    "service_level": "standard",
    "incoterm": "DDP",
    "price_amount": 50.00,
    "currency": "USD"
  }'
```

#### Track Shipment
```bash
curl -X GET http://localhost/api/v1/tracking/your-public-token
```

#### Admin: Update Customer
```bash
curl -X PATCH http://localhost/api/v1/admin/customers/1 \
  -H "Cookie: laravel_session=your-session-cookie" \
  -H "Idempotency-Key: update-customer-123" \
  -H "Content-Type: application/json" \
  -d '{"name":"Updated Name","status":"active"}'
```

### API Documentation

- **Swagger UI**: Visit `/api/docs` in your browser
- **OpenAPI JSON**: Available at `/api-docs.json`
- **OpenAPI YAML**: Available at `/api-docs.yaml`

### Security Features

- **Idempotency**: All write operations require `Idempotency-Key` header
- **Device Binding**: Mobile login requires `device_uuid` header
- **Rate Limiting**: Configurable per role (client/admin)
- **Activity Logging**: All state changes are audited via Spatie ActivityLog
- **Authorization**: Shipment ownership and admin role enforcement

### Testing

Run the API tests:

```bash
php artisan test tests/Feature/Api/V1/
```

### Feature Flags

- Set `FEATURE_MOBILE_API=false` to disable API v1 routes
- Useful for staging/production deployments

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
