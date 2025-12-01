Flash Sale API (Concurrency & Correctness)
This project implements a robust API solution for selling a limited-stock product during a flash sale in Laravel 12. It emphasizes correctness, high concurrency handling without overselling, and reliable state management using an entirely MySQL-based transactional approach.
This README details the implementation strategy and running instructions based on the provided code snippets.
1. Core Implementation Strategy: MySQL Transactional Locking
The application addresses concurrency entirely through MySQL InnoDB row-level locking (SELECT ... FOR UPDATE) within explicit database transactions (DB::transaction).
This strategy guarantees strong data consistency and prevents overselling by forcing concurrent requests to effectively take turns accessing and modifying critical stock data.
Stock Model: The products table maintains stock_available (total physical stock) and stock_reserved (quantity held by active holds/pending orders).
Holds vs. Orders: A Hold is a temporary reservation; an Order is a persistent record created from a redeemed hold.
Idempotency: Webhook processing is made idempotent by storing the payment provider's unique event ID (payment_idempotency_key) on the orders table, ensuring an event is only processed once.
2. Walkthrough: Task Steps and API Implementation
Requirement 1: Product Endpoint
GET /api/products/{id} returns basic fields and accurate available stock. Stays fast under burst traffic.
Implementation: The ProductController@show method uses Cache::remember to cache product details for 2 minutes. This minimizes database load for public reads.
Requirement 2: Create Hold
POST /api/holds { product_id, qty } creates a temporary reservation (~2 minutes). Success returns { hold_id, expires_at }.
Implementation: Handled by HoldController@store. It uses DB::transaction with lockForUpdate() on the Product row to atomically check stock, create a Hold record, increment stock_reserved, and dispatch a delayed ReleaseExpiredHold job.
Requirement 3: Create Order
POST /api/orders { hold_id } creates an order in a pre-payment state. Only valid, unexpired holds can be used once.
Implementation: Handled by OrderController@store. It uses DB::transaction and lockForUpdate() on the Hold and Product rows to validate the hold, mark it as 'used', decrement both stock_reserved and stock_available, and create the final Order.
Requirement 4: Payment Webhook (Idempotent & Out-of-Order Safe)
POST /api/payments/webhook updates order status to paid or cancelled. Safe for duplicates.
Implementation: Handled by OrderController@handlePaymentWebhook. It uses DB::transaction with lockForUpdate() on the Order row. An idempotency key check prevents duplicates. If the payment fails, stock is immediately released back to the available pool.
3. How to Run the Application
Prerequisites
PHP 8.2+
MySQL 5.7+ (InnoDB)
Composer
Setup Instructions
Clone the Repository & Install Dependencies:
bash
composer install
cp .env.example .env
php artisan key:generate
يُرجى استخدام الرمز البرمجي بحذر.

Configure .env: Ensure your MySQL connection details are correct.
env
DB_CONNECTION=mysql
# ... your db credentials ...

QUEUE_CONNECTION=database 
CACHE_DRIVER=file
يُرجى استخدام الرمز البرمجي بحذر.

Run Migrations and Seed Data:
bash
php artisan migrate
# You need a ProductSeeder file to create the initial product (ID 1 assumed)
php artisan db:seed --class=ProductSeeder 
يُرجى استخدام الرمز البرمجي بحذر.

Start Queue Worker (Mandatory): This process manages the automatic release of expired holds.
bash
php artisan queue:work database --tries=3
يُرجى استخدام الرمز البرمجي بحذر.

Start Development Server:
bash
php artisan serve
يُرجى استخدام الرمز البرمجي بحذر.

4. Endpoints
Method	Endpoint	Description
GET	/api/products/{id}	Get product details and stock information.
POST	/api/holds	Create a temporary stock reservation.
POST	/api/orders	Convert a valid hold into a pending order.
POST	/api/payments/webhook	Idempotent endpoint for payment provider callbacks.



