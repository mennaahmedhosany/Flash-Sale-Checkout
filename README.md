Flash Sale API (Concurrency & Correctness)
This project implements a robust API solution for selling a limited-stock product during a flash sale in Laravel 12. It emphasizes correctness, high concurrency handling without overselling, and reliable state management using an entirely MySQL-based transactional approach.

1. Core Implementation Strategy: MySQL Transactional Locking
The provided codebase addresses concurrency entirely through MySQL InnoDB row-level locking (SELECT ... FOR UPDATE) within explicit database transactions (DB::transaction).
This strategy guarantees strong data consistency and prevents overselling by forcing concurrent requests to effectively take turns accessing and modifying critical stock data.
Stock Model: The products table maintains stock_available (total physical stock) and stock_reserved (quantity held by active holds/pending orders).
Holds vs. Orders:
A Hold is a temporary, time-limited reservation that increases stock_reserved.
An Order is a persistent record created from a redeemed hold, pending final payment.
Idempotency: Webhook processing is made idempotent by storing the payment provider's unique event ID (payment_idempotency_key) on the orders table, ensuring an event is only processed once.
2. Walkthrough: Task Steps and API Implementation
Requirement 1: Product Endpoint
GET /api/products/{id}
Implementation: The ProductController@show method uses Laravel's Cache::remember to cache the product details and stock information for 2 minutes. This keeps the public-facing endpoint fast and reduces load on the database for simple reads.
Concurrency Handling: While the read is cached, the stock writes occur transactionally, ensuring the cache is eventually updated via tag invalidation in the webhook handler (though this wasn't strictly demonstrated in the snippets, it's the assumed behavior). The stock metrics shown in the response (stock_available / stock_reserved) are ultimately derived from the database source of truth.
Requirement 2: Create Hold
POST /api/holds { product_id, qty }
Implementation: Handled by HoldController@store.
It begins a DB::transaction.
It acquires a lock on the target Product row using lockForUpdate().
It checks that qty is less than stock_available - stock_reserved.
It creates a Hold record and increments the stock_reserved count in the database.
A ReleaseExpiredHold job is dispatched to run in 2 minutes, ensuring the reservation is automatically undone if the user doesn't proceed.
Concurrency Handling: The lockForUpdate() is critical here. Concurrent requests trying to create a hold on the same product wait in line for the lock to be released, preventing the condition where two requests mistakenly think the same unit of stock is available.
Requirement 3: Create Order
POST /api/orders { hold_id }
Implementation: Handled by OrderController@store.
It starts a DB::transaction.
It locks the specific Hold row using lockForUpdate().
It validates the hold status and expiration time, marking it as 'used'/'redeemed'.
It locks the Product row again to decrement both stock_reserved and stock_available (moving reserved stock to a physically sold state).
It creates the final Order record in a pending_payment state.
Concurrency Handling: This ensures a hold is used exactly once to create an order, and the transition from 'reserved' to 'sold' happens atomically.
Requirement 4: Payment Webhook (Idempotent & Out-of-Order Safe)
POST /api/payments/webhook
Implementation: Handled by OrderController@handlePaymentWebhook.
It starts a DB::transaction.
It uses lockForUpdate() on the Order row being processed.
Idempotency Check: It checks if the webhook's unique idempotencyKey matches the order->payment_idempotency_key. If it matches, the request is ignored as it's a duplicate.
State Check: It checks if the order status is already paid or cancelled. If so, the operation stops early (out-of-order safety).
Failure Handling: If the status is 'failed'/'cancelled', it executes a critical block to increment stock_available and decrement stock_reserved in the products table, returning the stock to the pool.
Concurrency Handling: The database lock and unique payment_idempotency_key constraint ensure that multiple webhook retries do not cause stock discrepancies or multiple final status changes.
3. How to Run the Application
Follow these steps to run the API locally:
Prerequisites
PHP 8.2+
MySQL 5.7+
Composer
Setup Instructions
Clone the Repository & Install Dependencies:
bash
composer install
cp .env.example .env
php artisan key:generate
يُرجى استخدام الرمز البرمجي بحذر.

Configure .env: Ensure your MySQL connection details are correct:
env
DB_CONNECTION=mysql
QUEUE_CONNECTION=database 
CACHE_DRIVER=file
يُرجى استخدام الرمز البرمجي بحذر.

Run Migrations and Seed Data:
bash
php artisan migrate
# You would need a simple seeder here:
# php artisan db:seed --class=ProductSeeder 
يُرجى استخدام الرمز البرمجي بحذر.

Start Queue Worker (Mandatory): The system relies on this for hold expiry and job processing.
bash
php artisan queue:work database --tries=3
يُرجى استخدام الرمز البرمجي بحذر.

Start Development Server:
bash
php artisan serve
يُرجى استخدام الرمز البرمجي بحذر.




