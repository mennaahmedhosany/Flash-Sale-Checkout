Flash Sale API (High Concurrency & Correctness)

This project implements a robust API for selling limited-stock products during a flash sale using Laravel 12. It emphasizes correctness, safe concurrency handling, and reliable stock and order state management using MySQL transactions.

Core Concepts
1. MySQL Transactional Locking

All critical operations (holds, orders, payments) are wrapped in DB transactions using DB::transaction.

Row-level locks (SELECT ... FOR UPDATE) guarantee:

Strong data consistency

No overselling

Safe concurrent access to stock data

2. Stock Model

The products table tracks:

Column	Description
stock_available	Total physical stock available
stock_reserved	Quantity currently held by active holds
3. Holds vs Orders

Hold: A temporary reservation of stock (default: 2 minutes).

Order: A persistent record created after a redeemed hold.

4. Idempotency

Payment webhooks are idempotent.

Each event has a unique payment_idempotency_key in the orders table.

Prevents duplicate processing of the same payment event.

API Endpoints
1. Get Product Details

Method: GET /api/products/{id}

Description: Returns product details and accurate available stock.

Implementation:

Uses Cache::remember for 5 minutes to reduce database load.

Returns stock_available and stock_reserved for concurrency-safe display.

2. Create Hold

Method: POST /api/holds

Request Body:

{
  "product_id": 1,
  "qty": 5
}


Description: Temporarily reserves stock for a user.

Implementation:

Starts a DB transaction and locks the product row.

Checks stock availability (stock_available - stock_reserved).

Creates a Hold record and increments stock_reserved.

Dispatches a delayed ReleaseExpiredHold job to release unredeemed holds after 2 minutes.

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


Description: Converts a valid, unexpired hold into a pending order.

Implementation:

DB transaction locks the Hold and Product rows.

Validates the hold, marks it as redeemed.

Decrements stock_reserved.

Creates a new Order with status pending_payment.

4. Payment Webhook

Method: POST /api/payments/webhook

Description: Safely updates the order status based on payment success/failure, even under duplicate or out-of-order events.

Implementation:

Locks the Order row using lockForUpdate.

Validates payment_idempotency_key to prevent duplicate processing.

On success:

Marks order as paid

Decrements both stock_available and stock_reserved.

On failure:

Marks order as cancelled

Releases reserved stock back to stock_available

Marks hold as not redeemed.

How to Run the Application
Prerequisites

PHP 8.2+

MySQL 5.7+ (InnoDB)

Composer

Setup Instructions
git clone <repository-url>
cd <repository-directory>
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve

Notes on Concurrency

All stock changes (reserve, redeem, release) use transactions with lockForUpdate.

This ensures no overselling occurs, even under heavy traffic.

Holds that expire automatically release reserved stock via a queued ReleaseExpiredHold job.

Logging & Monitoring

Logs all webhook events and critical errors for auditability.

Warnings logged if stock reserved is insufficient on payment success or release attempts.

This README now clearly explains the purpose, API endpoints, concurrency handling, and setup in a way that's understandable for developers and reviewers.

If you want, I can also add a visual flow diagram showing Hold → Order → Payment Webhook → Stock Update, which makes it even clearer for the README.

Do you want me to do that?

أنت قلت:
i want that for me readme wite it sutible for readme
قال ChatGPT:

Got it! Here’s a polished, readable version of your README that you can directly use in your project:

Flash Sale API (High Concurrency & Correctness)

This project implements a robust API for selling limited-stock products during flash sales using Laravel 12. It focuses on correctness, safe concurrency handling, and reliable stock management using MySQL transactions.

Core Concepts
MySQL Transactional Locking

All critical operations (holds, orders, payments) are wrapped in DB transactions.

Uses row-level locks (SELECT ... FOR UPDATE) to:

Guarantee strong data consistency

Prevent overselling

Allow safe concurrent access to stock

Stock Model

The products table tracks:

Column	Description
stock_available	Total physical stock available
stock_reserved	Quantity held by active holds/orders
Holds vs Orders

Hold: Temporary stock reservation (~2 minutes).

Order: Persistent record created from a redeemed hold.

Idempotency

Payment webhooks are idempotent using payment_idempotency_key.

Prevents duplicate processing of the same payment event.

API Endpoints
1. Get Product Details

Method: GET /api/products/{id}

Description: Returns product info with accurate available stock.

Caching: Uses Cache::remember for 5 minutes to reduce database load.

2. Create Hold

Method: POST /api/holds

Request Body:

{
  "product_id": 1,
  "qty": 5
}


Description: Temporarily reserves stock for a user.

Behavior:

Starts a DB transaction and locks the product row.

Checks stock availability (stock_available - stock_reserved).

Creates a Hold record and increments stock_reserved.

Dispatches a delayed ReleaseExpiredHold job to release unredeemed holds.

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

DB transaction locks the Hold and Product.

Validates hold and marks it as redeemed.

Decrements stock_reserved.

Creates a new Order with status pending_payment.

4. Payment Webhook

Method: POST /api/payments/webhook

Description: Updates order status based on payment success/failure.

Behavior:

Locks the Order row for safe processing.

Checks payment_idempotency_key to prevent duplicate processing.

On success:

Marks order as paid

Decrements both stock_reserved and stock_available.

On failure:

Marks order as cancelled

Releases reserved stock back to stock_available

Marks hold as not redeemed.

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

Automatic hold release ensures stock is never blocked indefinitely.

Idempotent webhooks ensure duplicate or out-of-order payment events do not affect stock or order integrity.

Logging & Monitoring

Logs all webhook events, critical errors, and stock inconsistencies.

Provides audit trail for debugging and operational monitoring.