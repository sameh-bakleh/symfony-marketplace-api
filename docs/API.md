# REST API endpoints

Base URL (Docker): `http://localhost:8080`

Authentication: `Authorization: Bearer <jwt>` unless marked **Public**. Token lifecycle and roles: [AUTH.md](AUTH.md).

## Authentication

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | `/api/auth/register` | Public | Register customer or seller |
| POST | `/api/auth/login` | Public | Login — returns `token` + `refresh_token` (firewall) |
| POST | `/api/auth/refresh` | Public | Refresh access token (firewall) |
| POST | `/api/auth/logout` | JWT | Revoke refresh token (send `refresh_token` in body) |
| GET | `/api/me` | JWT | Current user profile |

## Categories

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/categories` | Public | Active category tree |
| GET | `/api/categories/{id}` | Public | Single active category |
| POST | `/api/categories` | Admin | Create category |
| PATCH | `/api/categories/{id}` | Admin | Update category |
| DELETE | `/api/categories/{id}` | Admin | Delete category (fails if products exist) |

## Products

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/products` | Public | Paginated published products (`page`, `perPage`, `categoryId`, `search`, `sort`, …) |
| GET | `/api/products/{id}` | Public | Published product detail |
| GET | `/api/products/slug/{slug}` | Public | Published product by slug |
| POST | `/api/seller/products` | Seller | Create product |
| PATCH | `/api/seller/products/{id}` | Seller (owner) / Admin | Update product |
| DELETE | `/api/seller/products/{id}` | Seller (owner) / Admin | Delete product |
| POST | `/api/seller/products/{id}/images` | Seller (owner) / Admin | Upload images (`images[]` multipart) |

## Cart

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/cart` | JWT | Get or create cart |
| POST | `/api/cart/items` | JWT | Add or update line (`productId`, `quantity`) |
| PATCH | `/api/cart/items/{productId}` | JWT | Set line quantity |
| DELETE | `/api/cart/items/{productId}` | JWT | Remove line |
| DELETE | `/api/cart` | JWT | Clear cart |

## Orders

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | `/api/orders` | JWT | Place order from explicit line items |
| POST | `/api/orders/checkout-cart` | JWT | Checkout current cart |
| GET | `/api/orders` | JWT | List own orders (admin sees all) |
| GET | `/api/orders/{id}` | JWT | Order detail (`OrderVoter`) |
| PATCH | `/api/orders/{id}/status` | Admin | Update order status |

## Wishlist

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/wishlist` | JWT | List wishlist products |
| POST | `/api/wishlist/products/{id}` | JWT | Add published product |
| DELETE | `/api/wishlist/products/{id}` | JWT | Remove product |

## Notifications

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/notifications` | JWT | Recent notifications |
| PATCH | `/api/notifications/{id}/read` | JWT | Mark notification read |

## Profile

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| PATCH | `/api/profile` | JWT | Update user profile |
| PATCH | `/api/profile/seller` | Seller | Update seller profile |

## API Platform (catalog reads)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/platform/categories` | Public | Active categories (JSON-LD / JSON) |
| GET | `/api/platform/categories/{id}` | Public | Active category item |
| GET | `/api/platform/products` | Public | Published products collection |
| GET | `/api/platform/products/{id}` | Public | Published product item |

Interactive docs:

- REST (Nelmio): `/api/doc`
- API Platform: `/api/platform/docs`
