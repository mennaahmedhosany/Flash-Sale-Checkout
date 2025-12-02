Flash Sale API (High Concurrency & Correctness)

A robust Laravel 12 API for selling limited-stock products during flash sales. This project emphasizes data correctness, safe concurrency handling, and reliable stock/order management using MySQL transactions.

Table of Contents

Core Concepts

API Endpoints

Automated Testing

Setup Instructions

Concurrency & Safety

Logging & Monitoring

Core Concepts
1. MySQL Transactional Locking

All critical operations (holds, orders, payments) are wrapped in DB transactions.

Row-level locks (SELECT ... FOR UPDATE) ensure:

Strong data consistency

No overselling

Safe concurrent access to stock data

2. Stock Model

The products table tracks:

Column	Description
stock_available	Total physical stock available
stock_reserved	Quantity currently held by active holds
3. Holds vs Orders

Hold: Temporary reservation of stock (~2 minutes).

Order: Persistent record created from a redeemed hold.

4. Idempotency

Payment webhooks are idempotent using payment_idempotency_key.

Prevents duplicate processing of the same payment event.

API Endpoints
1. Get Product Details

Method: GET /api/products/{id}

Description: Returns product info with accurate stock.

Caching: Uses Cache::remember for 5 minutes to reduce DB load.

2. Create Hold

Method: POST /api/holds

Request Body:

{
  "product_id": 1,
  "qty": 5
}


Description: Temporarily reserves stock for a user.

Behavior:

Starts a DB transaction and locks the product row

Checks stock availability (stock_available - stock_reserved)

Creates a Hold record and increments stock_reserved

Dispatches a delayed ReleaseExpiredHold job to release unredeemed holds

Response:

{
  "hold_id": 1,
  "expires_at": "2025-12-02T01:00:00Z"
}

3. Create Order

Method: POST /api/orders

Request Body:

{
  "hold_id": 1
}


Description: Converts a valid hold into a pending order.

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

4. Payment Webhook

Method: POST /api/payments/webhook

Description: Updates order status based on payment success/failure.

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

Automated Testing

Feature tests ensure the flash-sale system behaves correctly under high concurrency.

Running Tests
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/InventoryTest.php

Tested Scenarios

Parallel Hold Attempts (No Oversell)

Only one hold is allowed when stock is limited

Reserved stock increments correctly

Available stock remains consistent

Hold Expiry Handling

Expired holds release stock_reserved

stock_available remains unchanged

Hold status is marked released with a timestamp

Payment Webhook Idempotency

Duplicate webhooks do not affect stock or order status

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

Setup Instructions
Prerequisites

PHP 8.2+

MySQL 5.7+ (InnoDB)

Composer

Installation
git clone <repository-url>
cd <repository-directory>
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve

Concurrency & Safety

All stock updates (reserve, redeem, release) use transactions with lockForUpdate.

Automatic hold release prevents stock from being blocked indefinitely.

Idempotent webhooks ensure duplicate or out-of-order payment events do not affect stock or order integrity.