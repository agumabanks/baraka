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

## React Dashboard

This project includes a modern React-based admin dashboard integrated with Laravel, featuring a monochrome Steve Jobs-inspired design.

### Dashboard Features

- **Authentication**: Laravel Sanctum-based login/logout with protected routes
- **Dashboard**: KPI cards, charts, workflow queue, and real-time metrics
- **Responsive Design**: Mobile-first approach with monochrome theme
- **API Integration**: RESTful API endpoints for dashboard data
- **Error Handling**: Comprehensive error boundaries and fallback states

### Setup

1. **Install Dependencies**
   ```bash
   cd react-dashboard
   npm install
   ```

2. **Build for Production**
   ```bash
   npm run build
   ```

3. **Environment Configuration**
   The React app uses the following environment variables (configured in Laravel `.env`):
   ```env
   REACT_APP_URL=http://localhost
   REACT_API_URL=https://baraka.sanaa.ug/api
   SANCTUM_STATEFUL_DOMAINS=http://localhost
   ```

4. **Laravel Configuration**
   Add to your `.env` file:
   ```env
   SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
   SANCTUM_GUARD=web
   ```

5. **Content Security Policy (CSP)**
   If your frontend needs to call APIs hosted on a different origin (for example `http://localhost:8000` during local development), whitelist them with the `CSP_CONNECT_SRC` variable in `.env`:
   ```env
   CSP_CONNECT_SRC="http://localhost:8000"
   ```
   Multiple values can be provided by separating them with spaces.

6. **Access Dashboard**
   - Login at: `https://baraka.sanaa.ug/login`
   - Dashboard at: `https://baraka.sanaa.ug/admin`
   - Admin credentials: `info@baraka.co` / `Admin@123`

### API Endpoints
<!-- https://baraka.sanaa.ug/react-dashboard/login -->
#### Authentication
- `POST /api/auth/login` - Login with email/password
- `POST /api/auth/logout` - Logout current session
- `GET /api/auth/user` - Get authenticated user info

#### Dashboard Data
- `GET /api/v10/dashboard/data` - Complete dashboard data
- `GET /api/v10/dashboard/kpis` - KPI metrics only
- `GET /api/v10/dashboard/charts` - Chart data only
- `GET /api/v10/dashboard/workflow-queue` - Workflow items

#### Headers Required
All dashboard API requests require:
```
Authorization: Bearer <token>
apiKey: 123456rx-ecourier123456
```

### Deployment

1. **Build React App**
   ```bash
   cd react-dashboard
   npm run build
   ```

2. **Serve Static Files**
   The built files are automatically served from `public/react-dashboard/` by Laravel routes.

3. **Production Environment**
   ```env
   APP_ENV=production
   APP_URL=https://yourdomain.com
   SANCTUM_STATEFUL_DOMAINS=https://yourdomain.com
   REACT_APP_URL=https://yourdomain.com
   REACT_API_URL=https://yourdomain.com/api
   ```

4. **Web Server Configuration**
   Ensure your web server serves the React app from `/admin/*` routes to `public/react-dashboard/index.html`.

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
- `GET /api/v1/shipments/{id}/events` - Get shipment events
- `POST /api/v1/shipments/{id}/cancel` - Cancel shipment

#### Client Quotes
- `GET /api/v1/quotes` - List user's quotations
- `POST /api/v1/quotes` - Create new quotation
- `GET /api/v1/quotes/{id}` - Get quotation details

#### Client Pickup Requests
- `GET /api/v1/pickups` - List user's pickup requests
- `POST /api/v1/pickups` - Create new pickup request
- `GET /api/v1/pickups/{id}` - Get pickup request details

#### Client Tasks (Drivers)
- `GET /api/v1/tasks` - List driver's tasks
- `GET /api/v1/tasks/{id}` - Get task details
- `PATCH /api/v1/tasks/{id}/status` - Update task status
- `POST /api/v1/tasks/{id}/pod` - Submit proof of delivery
- `POST /api/v1/pod/{id}/verify` - Verify POD with OTP

#### Driver Location Tracking
- `POST /api/v1/driver/locations` - Submit GPS location batch

#### Admin Endpoints (Dashboard)
- `GET /api/v1/admin/customers` - List customers
- `GET /api/v1/admin/customers/{id}` - Get customer details
- `PATCH /api/v1/admin/customers/{id}` - Update customer
- `GET /api/v1/admin/shipments` - List all shipments (with advanced filters)
- `GET /api/v1/admin/shipments/{id}` - Get shipment details
- `PATCH /api/v1/admin/shipments/{id}/status` - Update shipment status
- `POST /api/v1/admin/shipments/export` - Export shipments
- `GET /api/v1/admin/dispatch/unassigned` - Get unassigned shipments
- `GET /api/v1/admin/dispatch/drivers` - Get available drivers
- `POST /api/v1/admin/dispatch/assign` - Assign driver to shipment
- `POST /api/v1/admin/dispatch/optimize` - Optimize dispatch routes
- `GET /api/v1/admin/metrics` - Get dashboard metrics

### Authentication

#### Mobile Client
Use Sanctum personal access tokens with device binding:

```bash
# Login
curl -X POST https://baraka.sanaa.ug/api/v1/login \
  -H "Content-Type: application/json" \
  -H "device_uuid: your-device-uuid" \
  -d '{"email":"merchant@demo.com","password":"password"}'

# Use token in subsequent requests
curl -X GET https://baraka.sanaa.ug/api/v1/shipments \
  -H "Authorization: Bearer your-token-here"
```

#### Admin Dashboard
Use session-based authentication (existing web login).

### Sample Requests

#### Create Shipment
```bash
curl -X POST https://baraka.sanaa.ug/api/v1/shipments \
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
curl -X GET https://baraka.sanaa.ug/api/v1/tracking/your-public-token
```

#### Admin: Update Customer
```bash
curl -X PATCH https://baraka.sanaa.ug/api/v1/admin/customers/1 \
  -H "Cookie: laravel_session=your-session-cookie" \
  -H "Idempotency-Key: update-customer-123" \
  -H "Content-Type: application/json" \
  -d '{"name":"Updated Name","status":"active"}'
```

#### Create Quotation
```bash
curl -X POST https://baraka.sanaa.ug/api/v1/quotes \
  -H "Authorization: Bearer your-token" \
  -H "Idempotency-Key: quote-key-123" \
  -H "Content-Type: application/json" \
  -d '{
    "origin_branch_id": 1,
    "destination_country": "US",
    "service_type": "standard",
    "pieces": 2,
    "weight_kg": 5.5,
    "currency": "USD"
  }'
```

#### Submit Driver Location
```bash
curl -X POST https://baraka.sanaa.ug/api/v1/driver/locations \
  -H "Authorization: Bearer driver-token" \
  -H "Content-Type: application/json" \
  -d '{
    "locations": [
      {
        "latitude": 40.7128,
        "longitude": -74.0060,
        "timestamp": "2025-01-01T10:00:00Z",
        "accuracy": 10.0,
        "speed": 15.5
      }
    ]
  }'
```

#### Submit POD
```bash
curl -X POST https://baraka.sanaa.ug/api/v1/tasks/1/pod \
  -H "Authorization: Bearer driver-token" \
  -H "Idempotency-Key: pod-key-123" \
  -F "signature=@signature.png" \
  -F "photo=@delivery_photo.jpg" \
  -F "notes=Delivered successfully"
```

#### Assign Driver to Shipment
```bash
curl -X POST https://baraka.sanaa.ug/api/v1/dispatch/assign \
  -H "Cookie: laravel_session=admin-session" \
  -H "Idempotency-Key: assign-key-123" \
  -H "Content-Type: application/json" \
  -d '{
    "shipment_id": 1,
    "driver_id": 2,
    "priority": "high",
    "scheduled_at": "2025-01-01T14:00:00Z"
  }'
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

### Troubleshooting

#### React Dashboard Issues

**Dashboard not loading after login:**
- Ensure React app is built: `cd react-dashboard && npm run build`
- Check Laravel routes: `/admin/*` should serve `public/react-dashboard/index.html`
- Verify Sanctum configuration in `.env`

**API authentication errors:**
- Check `apiKey` header: must be `123456rx-ecourier123456`
- Verify Bearer token is valid and not expired
- Ensure user has admin role for dashboard access

**CORS issues:**
- Add your domain to `SANCTUM_STATEFUL_DOMAINS` in `.env`
- Clear config cache: `php artisan config:clear`

**Build issues:**
- Clear node_modules: `rm -rf node_modules && npm install`
- Check Node.js version: requires Node 16+
- Verify Vite configuration in `react-dashboard/vite.config.ts`

#### Common Laravel Issues

**Database connection:**
```bash
php artisan migrate:status
php artisan config:clear
```

**Permission issues:**
```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

**Queue/cache issues:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Feature Flags

- Set `FEATURE_MOBILE_API=false` to disable API v1 routes
- Useful for staging/production deployments

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
