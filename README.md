# Online Recycle System API

A comprehensive Laravel 12 REST API for managing recycling operations — orders, inventory, payments, branch management, and more.

## Tech Stack

- **Laravel 12** (PHP 8.2+)
- **Laravel Sanctum** — API token authentication
- **Laravel Socialite** — Social login (Google, Microsoft, Apple)
- **MySQL** — Database

## Quick Setup

```bash
# 1. Install dependencies
composer install

# 2. Configure environment
cp .env.example .env
php artisan key:generate

# 3. Update .env with your database credentials
DB_DATABASE=online_recycle_system
DB_USERNAME=root
DB_PASSWORD=

# 4. Run migrations & seed
php artisan migrate
php artisan db:seed

# 5. Start server
php artisan serve
```

## Default Seed Users

| Role            | Email                    | Password    |
|-----------------|--------------------------|-------------|
| Admin           | admin@ors.com            | password123 |
| Branch Manager  | manager@ors.com          | password123 |
| Staff           | staff@ors.com            | password123 |
| Customer        | customer@ors.com         | password123 |

## API Base URL

```
http://localhost:8000/api/v1
```

All endpoints return JSON. Include `Authorization: Bearer {token}` for authenticated routes.

## API Endpoints

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/register` | Register new customer |
| POST | `/auth/login` | Login (email + password) |
| POST | `/auth/social` | Social login (token-based for mobile) |
| GET | `/auth/social/{provider}/redirect` | Social login redirect (web) |
| GET | `/auth/social/{provider}/callback` | Social login callback (web) |
| POST | `/auth/logout` | Logout current device |
| POST | `/auth/logout-all` | Logout all devices |
| GET | `/auth/me` | Get current user |
| PUT | `/auth/change-password` | Change password |

### Users (Admin)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/users` | List users (search, filter by role/status) |
| GET | `/users/{id}` | Get user details |
| PUT | `/users/profile` | Update own profile |
| PUT | `/users/{id}` | Admin update user |
| DELETE | `/users/{id}` | Soft delete user |

### Branches
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/branches` | List branches (public) |
| GET | `/branches/{id}` | Branch details (public) |
| POST | `/branches` | Create branch (admin) |
| PUT | `/branches/{id}` | Update branch (admin) |
| DELETE | `/branches/{id}` | Delete branch (admin) |
| POST | `/branches/{id}/staff` | Assign staff (branch_manager+) |
| DELETE | `/branches/{id}/staff/{userId}` | Remove staff (branch_manager+) |
| GET | `/branches/{id}/staff` | List branch staff (branch_manager+) |

### Recyclable Categories
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/categories` | List categories (public) |
| GET | `/categories/{id}` | Category details (public) |
| POST | `/categories` | Create category (admin) |
| PUT | `/categories/{id}` | Update category (admin) |
| DELETE | `/categories/{id}` | Delete category (admin) |

### Recycle Orders
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/orders` | List orders (role-filtered) |
| POST | `/orders` | Create order (customer) |
| GET | `/orders/{id}` | Order details |
| PUT | `/orders/{id}/status` | Update status (staff+) |
| POST | `/orders/{id}/cancel` | Cancel order (customer) |
| POST | `/orders/estimate` | Price estimate (public) |

### Payments
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/payments` | List payments |
| GET | `/payments/{id}` | Payment details |
| POST | `/payments/{orderId}/process` | Process payment (staff+) |
| PUT | `/payments/{id}/status` | Update payment status (admin) |

### Inventory
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/inventory` | List records (staff+) |
| POST | `/inventory` | Add record (staff+) |
| GET | `/inventory/branch/{branchId}` | Branch stock summary (staff+) |
| GET | `/inventory/{id}` | Record details (staff+) |

### Dashboard & Reports (Admin/Manager)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/dashboard/admin` | Admin dashboard |
| GET | `/dashboard/branch/{branchId}` | Branch dashboard |
| GET | `/reports/collection` | Collection report |
| GET | `/reports/orders` | Order statistics |
| GET | `/reports/revenue` | Revenue statistics |

### Notifications
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/notifications` | List notifications |
| GET | `/notifications/unread-count` | Unread count |
| PUT | `/notifications/{id}/read` | Mark as read |
| POST | `/notifications/read-all` | Mark all as read |
| DELETE | `/notifications/{id}` | Delete notification |

## Order Flow

```
pending → accepted → in_progress → completed
       → rejected
       → cancelled (by customer, only when pending)
```

## Social Login (Mobile)

Send POST to `/api/v1/auth/social` with:
```json
{
  "provider": "google",
  "token": "<oauth-access-token>"
}
```

## Project Structure

```
app/
├── Enums/           # UserRole, OrderStatus, PaymentStatus, etc.
├── Http/
│   ├── Controllers/Api/V1/   # All API controllers
│   ├── Middleware/            # CheckRole, ForceJsonResponse
│   ├── Requests/             # Form request validation
│   └── Resources/            # API resource transformers
├── Models/          # Eloquent models
├── Services/        # Business logic (Order, Pricing, Inventory, etc.)
└── Traits/          # ApiResponse trait
```

## License

MIT
