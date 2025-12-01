# Flash Sale API (Concurrency & Correctness)

This project implements a robust API solution for selling a limited-stock product during a flash sale in **Laravel 12**. It emphasizes **correctness**, **high concurrency handling without overselling**, and reliable state management using an entirely **MySQL-based transactional approach**.

---

## Core Implementation Strategy

### MySQL Transactional Locking
The application addresses concurrency entirely through **MySQL InnoDB row-level locking** (`SELECT ... FOR UPDATE`) within explicit database transactions (`DB::transaction`). This guarantees:

- Strong data consistency
- Prevention of overselling
- Safe concurrent access to stock data

### Stock Model
- **products** table maintains:
  - `stock_available` → total physical stock
  - `stock_reserved` → quantity held by active holds/pending orders

### Holds vs Orders
- **Hold**: Temporary reservation of stock (~2 minutes)
- **Order**: Persistent record created from a redeemed hold

### Idempotency
- Payment webhook events are **idempotent**
- Each event has a unique `payment_idempotency_key` in the **orders** table
- Ensures the same event cannot be processed multiple times

---

## Walkthrough: Task Steps and API Implementation

### 1. Product Endpoint
- **Method:** GET  
- **Endpoint:** `/api/products/{id}`  
- **Description:** Returns product details and accurate available stock.  
- **Implementation:**  
  - Uses `Cache::remember` for 2 minutes to reduce database load  
  - Fast under burst traffic

### 2. Create Hold
- **Method:** POST  
- **Endpoint:** `/api/holds`  
- **Request Body:** `{ "product_id": 1, "qty": 5 }`  
- **Description:** Creates a temporary stock reservation (~2 minutes).  
- **Response:** `{ "hold_id": 1, "expires_at": "2025-12-02T01:00:00Z" }`  
- **Implementation:**  
  - `DB::transaction` with `lockForUpdate()` on the Product row  
  - Atomically checks stock, creates Hold, increments `stock_reserved`  
  - Dispatches a delayed `ReleaseExpiredHold` job

### 3. Create Order
- **Method:** POST  
- **Endpoint:** `/api/orders`  
- **Request Body:** `{ "hold_id": 1 }`  
- **Description:** Converts a valid, unexpired hold into a pending order.  
- **Implementation:**  
  - `DB::transaction` with `lockForUpdate()` on Hold and Product rows  
  - Validates the hold, marks it as used, decrements `stock_reserved` and `stock_available`  
  - Creates the final Order

### 4. Payment Webhook
- **Method:** POST  
- **Endpoint:** `/api/payments/webhook`  
- **Description:** Updates order status (`paid` or `cancelled`) safely, even if duplicate or out-of-order events occur.  
- **Implementation:**  
  - `DB::transaction` with `lockForUpdate()` on the Order row  
  - Checks `payment_idempotency_key` to prevent duplicate processing  
  - Releases stock back to available pool on payment failure

---

## How to Run the Application

### Prerequisites
- PHP 8.2+
- MySQL 5.7+ (InnoDB)
- Composer

### Setup Instructions
1. **Clone the repository & install dependencies**
```bash
composer install
cp .env.example .env
php artisan key:generate

