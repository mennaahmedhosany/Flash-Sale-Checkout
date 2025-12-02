🚀 Flash Sale API (High Concurrency & Correctness)

A robust Laravel 12 API for selling limited-stock products during flash sales.
Focuses on data correctness, safe concurrency, and reliable stock/order management using MySQL transactions.

📌 Table of Contents

Core Concepts

API Endpoints

Automated Testing

Setup Instructions

Concurrency & Safety

Logging & Monitoring

🧠 Core Concepts
1️⃣ MySQL Transactional Locking

All critical operations (holds, orders, payments) are wrapped in DB transactions.

Row-level locks (SELECT ... FOR UPDATE) ensure:

✅ Strong data consistency

✅ No overselling

✅ Safe concurrent access to stock

2️⃣ Stock Model

The products table tracks:

Column	Description
stock_available	Total physical stock available
stock_reserved	Quantity currently held by active holds
3️⃣ Holds vs Orders

Hold: Temporary stock reservation (~2 minutes)

Order: Persistent record created from a redeemed hold

4️⃣ Idempotency

Payment webhooks are idempotent using payment_idempotency_key

Prevents duplicate processing of the same payment event

🔗 API Endpoints
1️⃣ Get Product Details

Method: GET

Endpoint: /api/products/{id}

Description: Returns product info with accurate stock

Caching: Uses Cache::remember for 5 minutes to reduce DB load

2️⃣ Create Hold

Method: POST

Endpoint: /api/holds

Request Body:

{
  "product_id": 1,
  "qty": 5
}


Behavior:

Starts a DB transaction and locks the product row

Checks stock availability (stock_available - stock_reserved)

Creates a Hold record and increments stock_reserved

Dispatches a delayed ReleaseExpiredHold job

Response:

{
  "hold_id": 1,
  "expires_at": "2025-12-02T01:00:00Z"
}

3️⃣ Create Order

Method: POST

Endpoint: /api/orders

Request Body:

{
  "hold_id": 1
}


Behavior:

Locks Hold and Product rows

Validates hold and marks it as redeemed

Decrements stock_reserved

Creates Order with pending_payment status

Success Response (201 Created):

{
  "data": {
    "id": 12,
    "hold_id": 1,
    "status": "pending_payment",
    "quantity": 40,
    "total_amount": "3,999.60",
    "created_at": "2025-12-02T19:00:26.000000Z",
    "updated_at": "2025-12-02T19:00:26.000000Z"
  }
}

4️⃣ Payment Webhook

Method: POST

Endpoint: /api/payments/webhook

Description: Updates order status based on payment success/failure

Behavior:

Locks the Order row for safe processing

Checks payment_idempotency_key to prevent duplicate processing

Success Response:

{
    "success": true
}


Failure Responses:

HTTP Code	Response Example
400	{ "error": "Missing idempotency key" }
404	{ "error": "Order/Resource not found" }
500	{ "error": "Internal Server Error" }

On failure:

Marks order as cancelled

Releases reserved stock back to stock_available

Marks hold as not redeemed

🧪 Automated Testing

Feature tests ensure the flash-sale system behaves correctly under high concurrency.

Running Tests
# Run all tests
php artisan test

# Run a specific test file
php artisan test tests/Feature/InventoryTest.php

Tested Scenarios

Parallel Hold Attempts (No Oversell)

Only one hold is allowed when stock is limited

Reserved stock increments correctly

Available stock remains consistent

Hold Expiry Handling

Expired holds release stock_reserved

stock_available remains unchanged

Hold status marked released with timestamp

Payment Webhook Idempotency

Duplicate webhooks do not affect stock/order status

First webhook updates order status and stock correctly

Webhook Before Order Creation

Returns proper validation error (422)

Ensures no orders or stock records are modified

Example Output
PASS  Tests\Feature\InventoryTest
✓ parallel hold attempts at stock boundary
✓ hold expiry returns availability
✓ payment webhook idempotency
✓ webhook before order creation
Tests: 4 passed (21 assertions)

⚙️ Setup Instructions
Prerequisites

PHP 8.2+

MySQL 5.7+ (InnoDB)

Composer

Installation
git clone https://github.com/<your-username>/<repository-name>.git
cd <repository-name>
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve

🔒 Concurrency & Safety

All stock updates (reserve, redeem, release) use transactions with lockForUpdate

Automatic hold release ensures stock is never blocked indefinitely

Idempotent webhooks prevent duplicate/out-of-order events from affecting stock or order integrity

📈 Logging & Monitoring

Logs all webhook events, critical errors, and stock inconsistencies

Provides an audit trail for debugging and operational monitoring