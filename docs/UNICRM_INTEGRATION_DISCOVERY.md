# UniCRM integration discovery

Code review on 26 October 2026, followed by a read-only audit of the configured Laragon MySQL database after it started. Discovery only: no connector, route, schema, or business-logic change was made. Local status rows and counts below were observed directly; **production** database rows and worker configuration remain unverified.

## 1. Architecture

| Concern | What exists | Evidence |
| --- | --- | --- |
| Runtime | Laravel 12.48.1 installed locally; composer requires Laravel ^12.0 and PHP ^8.2. Local CLI is PHP 8.3.30; production PHP was not inspected. | `composer.json`, `vendor/laravel/framework` |
| Database | MySQL is configured locally; Eloquent models and migrations are the main persistence layer. | `config/database.php`, local `.env` |
| Authentication | Session guards for admin, customer, and delivery boy; Sanctum bearer tokens for mobile; Spatie permissions and Socialite are installed. | `config/auth.php`, `config/sanctum.php`, `routes/api.php` |
| API | `routes/api.php` provides public catalog/mobile routes and customer-scoped mobile routes. JSON-style admin endpoints and incoming callbacks also live in `routes/web.php`. | `bootstrap/app.php`, route files |
| Queue | Local `QUEUE_CONNECTION=sync`; database/Redis drivers and `failed_jobs` migration exist, but no application `app/Jobs` directory or queue worker deployment configuration was found. One fraud lookup is dispatched `afterResponse()`. | `config/queue.php`, `routes/console.php`, `CustomerController::order_save` |
| Cache | Local file cache. Courier send/status use `Cache::lock`; other settings/lookups use cache. Redis is configured as an option, not shown active. | `config/cache.php`, `CourierDispatchService`, `OrderStatusGate` |
| Events | `EventServiceProvider` registers catalog/search/sitemap/image observers and Laravel's `Registered` listener. `Order::updated` is a model hook for inventory, rewards, and fund credit. No application order/customer domain event/listener classes were found. | `app/Providers/EventServiceProvider.php`, `app/Models/Order.php` |
| Scheduler | `routes/console.php` schedules sitemap generation daily 03:15, courier payload pruning daily 03:45, and configurable courier status polling (default 10 minutes). It expects server cron to run `schedule:run`; the old `app/Console/Kernel.php` schedule is intentionally empty. Deployment cron was not verified. | `routes/console.php` |

Relevant code: `app/Http/Controllers/Api`, `app/Http/Controllers/Frontend`, `app/Http/Controllers/Admin`, `app/Services`, `app/Models`, `app/Support`, `app/Providers`, `routes`, and `database/migrations`.

## 2. Existing API inventory

Paths below include Laravel's `/api` prefix from `bootstrap/app.php`. “Public” means the route lacks an auth middleware; it is not a recommendation to expose its response to UniCRM. Unless noted, JSON responses have `status`, `message`, and `data`.

| Method and path | Access | Controller/method | Purpose and response |
| --- | --- | --- | --- |
| GET `/api/v1/app-config`, `slider`, `category-menu`, `hotdeal-product`, `homepage-product`, `footer-menu-left`, `footer-menu-right`, `social-media`, `contactinfo` | Public | `Api\FrontendController` matching methods | Storefront settings/catalog content; `data` is selected model data. Not customer/order reconciliation. |
| GET `/api/v1/category/{id}` | Public | `Api\FrontendController::catproduct` | Active category and product summaries; `data` products, `category` category. |
| POST `/api/v1/mobile/auth/register`, `login` | Public | `Api\Mobile\AuthController::register/login` | Creates/authenticates registered customer. Success `data.customer` plus plaintext Sanctum bearer `token`. |
| GET `/api/v1/mobile/products`, `featured`, `hot-deals`, `category/{categoryId}`, `{id}` | Public | `Api\Mobile\ProductController::index/featured/hotDeals/byCategory/show` | Product lists/details. Lists use `data.products` and pagination or `data` collection; `show` uses formatted `data`. List methods return raw Eloquent product objects, so their field surface is broader than a CRM contract. |
| GET `/api/v1/mobile/orders/track/{invoiceId}` | Public | `Api\Mobile\OrderController::track` | Looks up invoice without ownership check; returns full formatted order including shipping name, phone, address and items. **Not suitable as a CRM API or a safe public data feed.** |
| GET `/api/v1/mobile/auth/me`; PUT `profile`; POST `change-password`, `logout`, `logout-all` | `auth:sanctum` | `Api\Mobile\AuthController` | Self profile/update/token revocation. `me` returns selected customer fields. |
| GET `/api/v1/mobile/cart`, `count`; POST `add`; PUT `{id}`; DELETE `{id}`, `clear` | `auth:sanctum` | `Api\Mobile\CartController` | Customer's current cart, not historical order items. |
| GET `/api/v1/mobile/orders` | `auth:sanctum` | `Api\Mobile\OrderController::index` | Orders filtered by `customer_id=$request->user()->id`, optional `status`; `data.orders` summary array and `data.pagination`. |
| GET `/api/v1/mobile/orders/{id}` | `auth:sanctum` | `Api\Mobile\OrderController::show` | Same customer ownership check; `data` detailed order with `items`. |
| POST `/api/v1/mobile/orders` | `auth:sanctum` | `Api\Mobile\OrderController::store` | Creates order, shipping, payment and line items from cart; returns `data.order` and optional `payment_url`. |
| GET `/api/user` | `auth:sanctum` | Route closure | Serializes authenticated user model. Not a CRM-specific customer projection. |

There is **no dedicated customer-by-ID, all-customer-orders, all-orders, updated-since, payment, shipping, refund, or address REST API** in `routes/api.php`. The web UI has session-authenticated customer order/address/refund pages under `/customer/*`; admin JSON actions under `/admin/*` include inline order/payment status, customer lookup, and courier preview. They use web sessions/CSRF and are not server-to-server APIs. The three `POST /api/{redx,steadfast,pathao}/webhook` routes are **incoming courier callbacks in `routes/web.php`**, not outgoing UniCRM endpoints.

No Swagger, OpenAPI, L5-Swagger, Scribe, Postman collection, or API documentation generation system was found in dependencies/routes/project source. **No API documentation system currently found.**

## 3. Authentication and security of current APIs

`Customer` and `User` both use Sanctum `HasApiTokens`. Mobile login/register issue `createToken('mobile-app')` bearer tokens; no ability scope or explicit expiry is supplied. `config/sanctum.php` has `expiration => null`; controllers explicitly delete the current/all customer tokens on logout. The protected routes use only `auth:sanctum`, with no integration scope or CRM identity. `/api/v1/mobile/orders` and `/{id}` assume the authenticated principal is a Customer, then use its ID as `customer_id`. An admin/User token is not a safe substitute. Customer session cookies and admin session auth likewise do not provide a dedicated service identity. The public invoice tracking endpoint has no auth and discloses order PII to anyone who knows an invoice ID.

Recommendation for later work: HTTPS; a dedicated UniCRM service credential with read-only, explicitly scoped reconciliation APIs; outgoing HMAC-SHA256 over the raw body plus timestamp and event ID, per-integration secret, short replay window, rotation support, and idempotent receiver. Keep this separate from mobile customer tokens and human admin sessions. Existing courier callback secrets/signatures are provider-specific and should not be reused.

## 4. Authoritative data models

**Customer:** `App\Models\Customer` / `customers`, not admin `User`. Fields in model/migrations include `id`, `name`, `phone`, `phone_normalized`, `email`, `address`, legacy `district`/`area`, `district_id`, `thana_id`, `post_code`, `source` (`registered` or `manual`), `status`, `created_at`, `updated_at`. No `first_name`, `last_name`, or `company` field was found. `orders()` is has-many; `addresses()` is has-many to `CustomerAddress`. `CustomerUserProvider` excludes manual customers from session login; admin-created manual records can share phone/email and only warn on duplicates. Mobile registration enforces uniqueness among registered records. `Customer::forOrderPhone` chooses registered exact phone first, then earliest matching manual normalized phone. Web guest checkout reuses that customer or creates a registered-source customer with generated password; manual orders can have nullable `customer_id` and keep contact snapshots on the order. Therefore phone/email are **not** stable external IDs, and guest orders can be associated with an existing account by phone. See `CustomerController::order_save`, `CustomerManageController`, `ManualOrderController`.

**Order:** `App\Models\Order` / `orders`. Stable primary key `id`; customer-facing `invoice_id` (random five digits in web/mobile checkout; manual invoice is generated), optional `invoice_number` and `public_token` for manual orders. Key fields: `customer_id` nullable, manual contact snapshot fields, `amount` (grand total), `discount`, `order_discount`, `shipping_charge`, `paid_amount`, `due_amount`, `payment_method`, `payment_status`, `order_status` (stored string column containing status IDs), `order_source`, `note`, `order_note`, `admin_note`, `parcel_weight_kg`, `courier_type`, `consignment_id`, `courier_tracking_id`, `courier_tracking_code`, `courier_sent_at`, delivery assignment/OTP fields and timestamps. No order currency or tax column was found. Relations: `customer`, `orderdetails`/`items`, latest `payment`, `shipping`, `status` (`OrderStatus`), `refunds`, `courierParcel(s)`, creator/editor/user, delivery boy, digital downloads. Coupon is held as `coupon_code` on order, not an `Order::coupon()` relation. Saved customer addresses are separate from the order's shipping snapshot; no billing-address relation was found.

**Order item:** `App\Models\OrderDetails` / `order_details`, primary `id`, `order_id`, nullable `product_id` for manual/pack cases, `product_name`, `invoice_name`, `qty`, `sale_price`, `purchase_price`, optional `product_discount`, `line_discount`, `line_total`, `product_color`, `product_size`, `variant_price_id`, `manual_variant`, `kitten_pack_id`. Name and sold price are snapshotted; `invoice_name` is set on create. Current product is an optional relation used for images/metadata. There is no order-line SKU or tax field in the inspected model/schema. For manual lines, `line_total` is persisted; for mobile API responses, `subtotal` is calculated as `qty * sale_price`. Future snapshots should use the saved line name/price and avoid relying on the current product.

**Payment and shipping:** `payments` holds `order_id`, `customer_id`, `payment_method`, `amount`, `payment_status`, `trx_id`, `sender_number`; `Order::payment()` selects the latest row. `OrderPaymentService` reconciles latest payment amount/status into order snapshots for admin/manual paths. `shippings` holds recipient name, phone, address, area and location IDs. `refunds` holds request/decision/processing state and amounts. No single fulfillment-status column exists: order status, courier parcel normalized status, and rider delivery timestamps represent different layers.

**Contract inconsistencies to resolve before syncing:** Web/admin customer flows write `customers.status` as `active`/`inactive`, while mobile registration writes `1` and mobile login rejects values not equal to `1` (`CustomerController`, `CustomerManageController`, `Api\Mobile\AuthController`). The mobile profile controller accepts/returns `city` and `postal_code`, while the inspected Customer fillable/schema use `district_id`, `thana_id`, and `post_code`; do not treat those mobile fields as proven persisted customer data. Mobile COD order creation initializes a `payments.amount` equal to the grand total but leaves `payments.payment_status`/`orders.payment_status` as `pending`. A CRM must not infer “paid” from that amount alone (`Api\Mobile\OrderController::store`).

## 5. Exact statuses and lifecycle

The authoritative order-status **labels/slugs are database rows** in `order_statuses`, editable from the admin status controller. The configured local database has the following seven rows; production may differ:

| Stored `orders.order_status` ID | Verified local label / slug |
| --- | --- |
| `1` | Pending / `pending` |
| `2` | Processing / `processing` |
| `3` | Shipped / `on-the-way` |
| `5` | In Courier / `in-courier` |
| `6` | Delivered / `completed` |
| `8` | Unpaid / `unpaid` |
| `11` | Cancelled / `cancelled` |

Every local row has `status='1'` (active). The canonicalization migration is recorded locally; it changed the display names for IDs 3 and 6 while preserving their old slugs. Do not use `DashboardController`'s hardcoded map as the contract: it labels IDs 2/3/5/7 differently from the local table. Confirm production `id,name,slug,status` before deployment.

Payment values are strings. Admin-selectable `OrderPaymentService::ADMIN_STATUSES`: **`pending`, `paid`, `unpaid`, `partial`, `failed`**. `cancelled` is additionally accepted by `state()` and written by courier cancellation/payment callbacks; it is not in the admin selectable array. `paid_amount`/`due_amount` and the latest `payments` row matter in addition to `orders.payment_status`. Refund `refunds.status` enum: **`pending`, `approved`, `rejected`, `processed`**. No separate order-level `returned` status is established; courier `returned` maps to cancelled order ID 11 when applied.

Courier-parcel display states, separate from order status, are exactly `sent`, `in_review`, `approved`, `in_progress`, `pending_pickup`, `picked_up`, `in_transit`, `at_hub`, `out_for_delivery`, `delivered`, `partially_delivered`, `on_hold`, `returning`, `returned`, `cancelled`, `unknown` (`config/courier_tracking.php`). A courier event changes the order only through `OrderStatusGate` and courier-specific mappings.

| Transition | Current write path and effects | Future integration point |
| --- | --- | --- |
| Registered customer create/update | Web `CustomerController::store/profile_update`, Socialite callback, mobile `AuthController::register/updateProfile`, admin `CustomerManageController`, guest checkout creation. | Customer model lifecycle after commit, with source/consent filtering; no existing CRM event. |
| Order placement | Web `CustomerController::order_save`, mobile `Api\Mobile\OrderController::store`, admin `ManualOrderController::store` and `OrderController::order_store`; other reseller flows also create orders. They create shipping, payment, lines and reserve inventory. | After successful transaction commit, once complete order aggregate exists. A bare `Order::created` callback is too early for line/shipping data. |
| Confirmation/processing/shipping | Admin `OrderController::changeStatus`, `InlineOrderStatusController::update`, manual edit/cancel; send via `CourierDispatchService::send` persists courier IDs and `courier_sent_at`. | After committed status transition; parcel dispatch has its own reliable send result. No separate “confirmed” ID is proven. |
| Paid | Gateway callbacks in Bkash/UddoktaPay/AamarPay controllers; admin `OrderPaymentService::updateFromAdminStatus`; courier delivery `CourierWebhookOrderService`. Some paths update order and payment separately. | After authoritative payment/order state is committed and reconciled; dedupe by order + payment state/version. |
| Delivered/cancelled | Admin/manual/rider writes; courier `OrderStatusGate` -> `CourierWebhookOrderService::applyStatusChange`; `Order::updated` applies inventory/rewards/fund side effects. | After commit of the actual order status change, not on every raw courier event. |
| Refund | Customer `Frontend\RefundController::store` makes `pending`; admin `RefundController::approve/reject/process`; processing creates a fund OUT row once and sets `processed`. | After committed refund transition; only `processed` means money was recorded as refunded. |

No `CustomerCreated`, `CustomerUpdated`, `OrderCreated`, `OrderConfirmed`, `OrderPaid`, `OrderShipped`, `OrderDelivered`, `OrderCancelled`, `OrderReturned`, or `OrderRefunded` application event classes/listeners were found. The `Order::updated` hook and courier tracking events are persistence hooks/rows, not outbound integration events. Eloquent mass updates can bypass model hooks, so a future publisher needs audited write paths and transaction-aware delivery.

## 6. Webhooks, queue, and external integrations

Bilaighor **receives** RedX, Steadfast, and Pathao callbacks on web routes, with CSRF exemptions. RedX uses a secret in the URL query derived from `APP_KEY`; Pathao compares `X-PATHAO-Signature` to a derived static secret; Steadfast checks a configured bearer token **only when one is present**, otherwise `authorizeWebhook()` returns true. These are provider-specific authentication patterns, not generic raw-body HMAC signing. `ParcelRecorder` deduplicates tracking rows by `dedupe_hash`; `OrderStatusGate` blocks stale/final order transitions and `FundHelper` has idempotent sale crediting. There is no generic outbound webhook publisher, subscription registry, UniCRM webhook secret, CRM retry log, or CRM idempotency store.

Outbound HTTP integrations include `CourierDispatchService` (Steadfast API keys), `PathaoService` (token), `RedXService` (token/client settings), `FacebookCapiService` (Graph API; event IDs and hashed customer identifiers), payment gateway controllers, SMS helpers/gateway settings, and synchronous mail/notification paths. Credentials are in configuration or integration tables, not a UniCRM credential store. Calls commonly use Laravel `Http` timeouts and log failures; this is not a shared queued delivery/retry framework. `CourierTracking\RefreshService` and scheduled `courier:check-status` are useful examples of polling/reconciliation. Courier raw payload retention is configured at 90 days and pruned daily.

Local queue is synchronous; `failed_jobs` exists, but an asynchronous worker and retry/backoff convention were not found in this repository. Future UniCRM delivery should be asynchronous **after** a durable queue/worker and failure-retry mechanism are deployed, with an after-commit dispatch or outbox so rolled-back orders are never published. Do not rely on `afterResponse()` as durable delivery.

## 7. Current API usability and minimum missing surface

| UniCRM need | Readiness now | Reason |
| --- | --- | --- |
| GET arbitrary customer by Bilaighor ID | **NOT AVAILABLE** | Only customer self-profile `/auth/me`; no service-scoped customer lookup. |
| GET customer orders | **PARTIALLY READY** | Mobile route returns only the bearer token owner's orders; no service credential or arbitrary customer lookup. |
| GET single order, items, status | **PARTIALLY READY** | Mobile `orders/{id}` contains these for the logged-in customer only. Public invoice tracking exists but exposes PII without auth and cannot be used as integration security. |
| GET payment/shipping/refund history | **NOT AVAILABLE** | No service-facing read API or stable sanitized aggregate. |

Minimum later reconciliation API: service-authenticated, read-only customer by **Bilaighor numeric ID**, order by numeric ID, customer orders (including guest/manual order handling), and paginated orders updated since an ISO timestamp with stable `(updated_at,id)` cursor. Include an optional refund/parcel summary in order detail only if UniCRM workflows need it. Prefix/paths such as `/api/integrations/unicrm/orders/{id}` are proposals, **not current routes**. Limit fields and page size; define backfill and deleted/merged-contact behavior before relying on customer IDs alone.

## 8. Future event contract and example payloads

Recommended events from real transitions: `customer.created`, `customer.updated`; `order.created`; `order.status_changed` (with prior/new numeric ID and database label); `order.shipped` for ID 3 or actual courier dispatch if explicitly defined; `order.delivered` for ID 6; `order.cancelled` for ID 11; `order.payment_changed` / `order.paid` on a committed payment transition; `refund.requested` and `order.refunded` only when the refund row becomes `processed`. Do not emit `order.confirmed` or `order.returned` until their business meaning is explicitly defined. Candidate emission locations are the committed aggregate writes in the lifecycle table, with a shared transactional publisher/outbox instead of HTTP calls inside model hooks.

The following are **proposed contract examples**, not current API responses. IDs/values are illustrative; field names under `data` follow current models. `currency: "BDT"` is a proposed contract constant (no currency column exists). Omit null/unauthorized PII in production.

```json
{"eventId":"00000000-0000-4000-8000-000000000001","eventType":"customer.updated","occurredAt":"2026-10-26T10:00:00+06:00","data":{"id":42,"source":"registered","name":"Example Customer","phone":"01700000000","email":null,"status":"active","updated_at":"2026-10-26T10:00:00+06:00"}}
```

```json
{"eventId":"00000000-0000-4000-8000-000000000002","eventType":"order.created","occurredAt":"2026-10-26T10:01:00+06:00","data":{"id":501,"invoice_id":"12345","customer_id":42,"amount":920,"shipping_charge":70,"discount":0,"currency":"BDT","order_status":1,"payment_status":"pending","items":[{"id":9001,"product_id":77,"product_name":"Cat Food","qty":2,"sale_price":425}],"created_at":"2026-10-26T10:01:00+06:00"}}
```

```json
{"eventId":"00000000-0000-4000-8000-000000000003","eventType":"order.status_changed","occurredAt":"2026-10-26T10:02:00+06:00","data":{"id":501,"invoice_id":"12345","previous_order_status":1,"order_status":3,"order_status_name":"Shipped","updated_at":"2026-10-26T10:02:00+06:00"}}
```

```json
{"eventId":"00000000-0000-4000-8000-000000000004","eventType":"order.delivered","occurredAt":"2026-10-26T12:00:00+06:00","data":{"id":501,"invoice_id":"12345","customer_id":42,"order_status":6,"payment_status":"paid","amount":920,"currency":"BDT","updated_at":"2026-10-26T12:00:00+06:00"}}
```

```json
{"eventId":"00000000-0000-4000-8000-000000000005","eventType":"order.cancelled","occurredAt":"2026-10-26T12:10:00+06:00","data":{"id":502,"invoice_id":"12346","customer_id":42,"previous_order_status":1,"order_status":11,"payment_status":"cancelled","updated_at":"2026-10-26T12:10:00+06:00"}}
```

```json
{"eventId":"00000000-0000-4000-8000-000000000006","eventType":"order.refunded","occurredAt":"2026-10-26T13:00:00+06:00","data":{"id":501,"invoice_id":"12345","refund":{"id":71,"refund_id":"REF-00071","status":"processed","amount":850,"shipping_charge":70,"processed_at":"2026-10-26T13:00:00+06:00"}}}
```

Each future delivery should carry `eventId`, `eventType`, `occurredAt`, schema version, and a provider identity. Sign the **exact serialized body**; UniCRM stores `eventId` with a uniqueness constraint and treats repeats as success. Use `provider=bilaighor`, `externalCustomerId=customers.id` when present, `externalOrderId=orders.id`, `externalOrderItemId=order_details.id`, and `externalRefundId=refunds.id`. Keep `invoice_id` as a display/search reference: web checkout generates it with `rand(11111,99999)`, so it is unsuitable as the primary external key. Customer ID can be null on manual orders; then keep a contact snapshot linked to the order without fabricating a customer ID.

## 9. Source of truth, data minimization, readiness

Bilaighor remains authoritative for products, inventory, orders/lines, payment state, shipping/courier, fulfillment and refunds. UniCRM should own relationship history, CRM contact enrichment, segments, support cases, activities, follow-ups and automation. A CRM contact edit must not overwrite an order's shipping snapshot or change payment/fulfillment state. Phone-based guest/manual linking and differing customer/order contact snapshots need an explicit merge policy.

Exclude passwords/hashes, remember/reset/Sanctum tokens, OTP hashes, admin credentials, gateway/courier/SMS/Facebook secrets, `trx_id`/sender number/refund account details unless essential, raw payment/courier webhook payloads, fraud scores, IP/referrer and internal admin notes. Name, phone, email and shipping address are PII: send only fields needed for an approved CRM use case, limit access/retention, and avoid order address in routine status events. Product `purchase_price`, fund/commission data, and internal stock accounting are outside the customer CRM summary. Do not use raw Eloquent serialization as the contract.

| Dimension | Score | Evidence |
| --- | ---: | --- |
| API readiness | 3/10 | Mobile customer-scoped reads exist; no CRM read surface/cursor. |
| Webhook readiness | 2/10 | Inbound courier callbacks and tracking dedupe exist; no outbound business-event publisher. |
| Queue readiness | 2/10 | Driver options/failed jobs exist, but local sync and no verified worker or retry convention. |
| Auth readiness | 2/10 | Sanctum customer tokens exist; no dedicated scoped service identity, and public invoice lookup exposes PII. |
| Data-model clarity | 7/10 locally | Order/customer/line IDs and local status catalog are clear; production status rows, guest identity, customer status and payment snapshot differences still need validation. |
| Overall | 3/10 | Enough to design a connector, not enough to connect UniCRM safely today. |

Later sequence: (1) verify **production** status rows, privacy scope and guest/manual customer identity; (2) define sanitized customer/order snapshots and dedicated read-only service auth; (3) add after-commit durable event delivery with HMAC, retry and idempotency for order/payment/refund transitions; (4) add cursor-based reconciliation/backfill and alerting; (5) enable UniCRM segments/automations only after event and reconciliation parity checks. This document does not implement any phase.

## 10. Follow-up verification: live statuses (26 October 2026)

**Local statuses verified: YES; production statuses verified: NO.** The configured Laragon MySQL database at `127.0.0.1:3306` responded after the service started. The following is a read-only snapshot of all local `order_statuses` rows and current order counts, not a production assertion. `order_statuses.name`, `slug`, and `status` are admin-editable. No row has a deprecated flag, and there is no status seed file in the repository. The canonicalization migration is recorded in local `migrations`.

| Stored `orders.order_status` value / status-row ID | Local display label | Local slug | Local row flag | Local orders | Code transition into value |
| --- | --- | --- | --- | ---: | --- |
| `1` | Pending | `pending` | `1` active | 1 | Web/mobile/manual/admin checkout; gateway reset/success |
| `2` | Processing | `processing` | `1` active | 0 | Admin status edit/inline selector |
| `3` | Shipped | `on-the-way` | `1` active | 0 | Admin status edit/inline selector |
| `5` | In Courier | `in-courier` | `1` active | 0 | Admin status edit/inline selector |
| `6` | Delivered | `completed` | `1` active | 11 | Admin/inline/manual status, rider delivery, courier mapped delivery |
| `8` | Unpaid | `unpaid` | `1` active | 0 | Admin status edit/inline selector |
| `11` | Cancelled | `cancelled` | `1` active | 0 | Admin/manual/rider cancellation or courier mapped cancellation/return |

There are no other local status rows or observed `orders.order_status` values. `3`/`6` retain the old slugs after their labels were renamed; API consumers should carry ID and current label separately and never derive the label from the slug. Production status rows, flags and counts are **UNVERIFIED**.

`order_statuses.status` is a string column. Admin `active`/`inactive` actions write `1`/`0`; the status editor can change rows or delete them. This is a row-availability flag, **not** an order state. `Admin\DashboardController` has a contradictory hardcoded map (`2=Confirmed`, `3=Processing`, `5=Shipped`, `7=Cancelled`), while `Frontend\CustomerController::orders` groups `6` under a **UI tab** called `confirmed`. Neither establishes a genuine confirmed transition. Sources: `OrderStatusController`, `InlineOrderStatusController`, `InventoryService`, `2026_07_15_000003_canonicalize_order_status_labels.php`.

| Field | Code/schema-proven stored values and display labels | Transition / availability |
| --- | --- | --- |
| `orders.payment_status`, `payments.payment_status` | `pending`, `paid`, `unpaid`, `partial`, `failed` (admin selectable, displayed as the same words); `cancelled` is additionally written by courier/gateway cancellation. They are free strings. | Local `orders`: `paid` 11, `unpaid` 1. Local `payments`: `paid` 11, `partial` 1, `pending` 1, `unpaid` 1. No active/deprecated flag. Production distribution **UNVERIFIED**. |
| `courier_parcels.status` | Normalized keys and English labels: `sent` Sent to courier; `in_review` In review; `approved` Approved; `in_progress` In progress; `pending_pickup` Pending pickup; `picked_up` Picked up; `in_transit` In transit; `at_hub` At hub; `out_for_delivery` With rider; `delivered` Delivered; `partially_delivered` Partly delivered; `on_hold` On hold; `returning` Returning; `returned` Returned; `cancelled` Cancelled; `unknown` Unknown. | Local table has one `delivered` parcel; other keys have zero local rows. `CourierStatusMap`/`ParcelRecorder` normalize incoming courier states; `config/courier_tracking.php` defines labels. No active/deprecated flag. Production distribution **UNVERIFIED**. |
| `refunds.status` | DB enum exactly `pending`, `approved`, `rejected`, `processed`; UI labels correspond to these workflow stages. | Local `refunds` table has **zero rows**, so no observed refund value. Customer request -> pending; admin approval/rejection; admin payment processing -> processed. No active/deprecated flag. There is no separate order-return status field or local `returns` table. Courier `returned` can map the *order* to `11`; purchase-item returns are a different inventory process. |
| `customers.status` | String column; web/admin write `active` and `inactive`; mobile registration writes `1`; mobile login accepts only a value loosely equal to `1`. | Local `customers`: `active` 2; no `inactive` or `'1'` observed. No status catalog/deprecated flag. The cross-channel conflict remains in code even though it is not present in these two local rows. Production distribution **UNVERIFIED**. |

Code-proven transitions for the non-catalog statuses (the same literal is its default English display label unless a view provides presentation text):

| Field/value | Defined or written by | Transition into it | Active/deprecated |
| --- | --- | --- | --- |
| Payment `pending` | `OrderPaymentService::ADMIN_STATUSES`, checkout and gateway controllers | New order or awaiting payment; admin can explicitly select it | Local orders 0, payments 1 (orphan); no catalog flag |
| Payment `paid` | `OrderPaymentService`, gateway controllers, `CourierWebhookOrderService` | Admin paid amount/full payment, successful gateway or courier delivery | Local orders 11, payments 11; no catalog flag |
| Payment `unpaid` | `OrderPaymentService` | Admin zero paid amount / explicit Unpaid | Local orders 1, payments 1; no catalog flag |
| Payment `partial` | `OrderPaymentService` | Admin partial paid amount between zero and grand total | Local orders 0, payments 1 (orphan); no catalog flag |
| Payment `failed` | `OrderPaymentService`, gateway controllers | Admin selection or failed gateway payment | Local orders/payments 0; no catalog flag |
| Payment `cancelled` | `CourierWebhookOrderService`, AamarPay cancellation path | Courier/order payment cancellation | Local orders/payments 0; no catalog flag |
| Refund `pending` | `refunds` enum, `Frontend\RefundController::store` | New refund request | Local rows 0; no catalog flag |
| Refund `approved` | `Admin\RefundController::approve` | Pending request authorized, not yet paid | Local rows 0; no catalog flag |
| Refund `rejected` | `Admin\RefundController::reject` | Pending/approved request rejected | Local rows 0; no catalog flag |
| Refund `processed` | `Admin\RefundController::process` | Approved refund payment recorded | Local rows 0; no catalog flag |
| Customer `active` | `CustomerController`, `CustomerManageController`, `SocialAuthController` | Web/guest/admin creation or admin activation | Local rows 2; no catalog flag |
| Customer `inactive` | `CustomerManageController` | Admin deactivation | Local rows 0; no catalog flag |
| Customer `'1'` | `Api\Mobile\AuthController::register` | Mobile registration; mobile login accepts this | Local rows 0; no catalog flag |

There are **12 local orders and 14 payment rows**. The two extra payments are orphan rows with statuses `pending` and `partial` (their `order_id` has no matching order); no current order has multiple payments. For all 12 current orders, the latest payment status and `orders.payment_status` agree in this snapshot. Reconciliation must join payments through existing orders and select the latest payment, rather than count every historical payment row. This observation does not establish production data quality.

For **each** courier display state listed above, the exact key and label are defined by `config/courier_tracking.php`, and `CourierStatusMap::normalize` plus `ParcelRecorder` transition a parcel into it from courier-specific raw events. `sent` also represents the dispatch baseline; `unknown` is the schema default and unmapped-event fallback. `delivered`, `partially_delivered`, `returned`, and `cancelled` are marked final by config; all other keys are nonfinal. There is no enabled/deprecated flag for an individual courier status; which states actually occur on production is **UNVERIFIED**. Raw courier statuses are provider-specific and must not be presented as order-status IDs.

Run these **read-only** statements against the actual target database (for example in phpMyAdmin SQL, or a MySQL client using the deployment's own credentials). Share only aggregate/status rows, never credentials or customer/order PII:

```sql
SELECT id, name, slug, status FROM order_statuses ORDER BY id;
SELECT order_status, COUNT(*) AS rows_found FROM orders GROUP BY order_status ORDER BY order_status;
SELECT payment_status, COUNT(*) AS rows_found FROM orders GROUP BY payment_status ORDER BY payment_status;
SELECT payment_status, COUNT(*) AS rows_found FROM payments GROUP BY payment_status ORDER BY payment_status;
SELECT status, COUNT(*) AS rows_found FROM courier_parcels GROUP BY status ORDER BY status;
SELECT status, COUNT(*) AS rows_found FROM refunds GROUP BY status ORDER BY status;
SELECT status, COUNT(*) AS rows_found FROM customers GROUP BY status ORDER BY status;
SELECT migration FROM migrations WHERE migration LIKE '%canonicalize_order_status_labels%';
```

The local audit above used the configured Laravel database connection without writing data. For production verification, run the SQL on the production DB using existing access; **do not substitute local rows for production rows**. If `courier_parcels` is absent there, its migration has not run and no production parcel status can be claimed.

## 11. Order lifecycle and future emission boundaries

These are **proposed after-commit publication points**, not existing events. Most writes are split across controllers; there is no one shared order lifecycle service. A publisher would need to observe the completed aggregate and the before/after persisted state, record a durable event within the business transaction, then deliver only after commit. `Order::updated` runs current inventory/reward/fund side effects *during* saves and can fire before related `payments`/`shippings` are updated; do not make outbound HTTP calls there. Bulk Eloquent updates can bypass it.

| Real transition | Current code path | Safest future emission condition |
| --- | --- | --- |
| Checkout -> created, status `1` | `Frontend\CustomerController::order_save`, `Api\Mobile\OrderController::store`, `Admin\ManualOrderController::store`, `Admin\OrderController::order_store`; reseller/incomplete-order paths also create orders | Shared after-commit aggregate boundary, after order + lines + shipping + payment + stock reservation succeed; not bare `Order::created` |
| Confirmed | **No distinct proven order status or transition**; customer `confirmed` tab currently means status `6` delivered | No `order.confirmed` event |
| Payment pending/partial/paid/failed/cancelled | `OrderPaymentService` for admin/manual; Bkash/UddoktaPay/AamarPay gateway controllers; `CourierWebhookOrderService` writes order then latest payment | Compare committed order **and** latest payment; emit payment-state transition only after consistency check, dedupe on transition. COD `payments.amount` alone is not proof of payment. |
| Processing `2`, shipped `3`, in courier `5` | Admin `OrderController::changeStatus`, `InlineOrderStatusController::update`, manual edit; courier dispatch records `courier_sent_at`/IDs but does **not** itself assign status `3` | Committed change to a verified status row. Distinguish `order.status_changed` from parcel dispatch; do not equate dispatch with shipped until the business meaning is agreed. |
| Delivered `6` | Admin/manual/rider; courier `OrderStatusGate` -> `CourierWebhookOrderService::applyStatusChange` | Committed order status change to `6`, with corresponding payment snapshot finalized where the path updates it |
| Cancelled `11` / courier returned | Admin/manual/rider; courier return maps to order `11` through `OrderStatusGate` while parcel may show `returned` | Emit `order.cancelled` on committed `11`. Do **not** emit `order.returned` from order status alone; if needed later, use a separate current-parcel `returned` transition with an explicitly defined meaning. |
| Refund requested -> approved/rejected -> processed | `Frontend\RefundController::store`, `Admin\RefundController::approve/reject/process` | `refund.requested` after insert; `order.refunded` only after `approved -> processed` commits with fund deduction. This does not necessarily change `orders.order_status` or `orders.payment_status`. |

## 12. Production queue readiness

**Classification: PARTIALLY READY in code, production readiness UNVERIFIED; treat reliable asynchronous webhook delivery as NOT READY for rollout.** The running local app confirms `queue.default=sync`. The local DB has `failed_jobs` but **no `jobs` table**. `config/queue.php` defines database/Redis drivers with `retry_after=90`, `after_commit=false`, and a `failed_jobs` database-UUID driver. No `jobs` table migration is present in this repository. No Horizon dependency, application Jobs directory, webhook retry/backoff convention, deployment worker service, or production `.env` was found. The checked-in Sail `docker/8.2/supervisord.conf` starts only `artisan serve`, not `queue:work`; it is not proof of the cPanel production setup. No production SSH/process-manager access was available, so an existing production worker cannot be ruled out.

On the actual production host, an operator can verify without exposing secrets:

```bash
cd /home/bilaigho/public_html
php artisan tinker --execute='dump(config("queue.default"), config("queue.connections.".config("queue.default").".retry_after"));'
php artisan queue:failed
ps -ef | grep '[q]ueue:work'
supervisorctl status
```

Also check whether `jobs` and `failed_jobs` tables exist if using the database driver, and whether the server's process manager restarts workers. The minimum later production change is a durable database or Redis queue, required storage/table migrations, a continuously supervised worker with `--timeout` lower than `retry_after` (90 seconds for current database/Redis defaults), explicit tries/backoff and dead-letter/failed-job monitoring, deploy-time worker restart, and after-commit/outbox publication. Confirm capacity and retry behavior before sending a real webhook. These are recommendations; nothing was changed in production.

## 13. Public invoice lookup: separate security finding

**Risk: HIGH; blocker before reusing public APIs or widening integrations.** `routes/api.php:54` exposes unauthenticated `GET /api/v1/mobile/orders/track/{invoiceId}`. `Api\Mobile\OrderController::track` looks up `orders.invoice_id` and returns `formatOrder($order, true)` without customer ownership, signed token, OTP, or rate limiting specific to this route. The response contains the order DB ID, invoice ID, total/discount/shipping charge, order/payment status and method, order time, shipping **name, phone, address, area**, item names/quantities/prices and product images. It does not return card secrets, but it exposes personal/contact and purchase information. Web/mobile checkout generates a random **five-digit** invoice (`rand(11111,99999)`), making IDs enumerable; the `orders` migration does not enforce invoice uniqueness, and `first()` could return an arbitrary duplicate. Manual invoices use a different generated format and `public_token` exists for manual verification, but this API does not require it. Production exploitability/rate limits were not tested; the exposure follows directly from route and formatter code.

Recommended separate fix, after UX compatibility review: require authenticated order ownership **or** a high-entropy, expiring, signed tracking token; return only non-sensitive tracking status on a public view; apply throttling and audit access; make the lookup use a unique identifier. Do not use this endpoint as a UniCRM feed. No route or response was changed in this discovery task.

## 14. Minimum UniCRM data contract (proposed, not implemented)

Use decimal amounts as **strings** to avoid JSON floating-point rounding. `BDT` is a contract constant, not a stored `orders.currency` column. Times are ISO-8601 with offset. Examples use illustrative data. A full customer record is not guaranteed for a manual order (`customer_id` is nullable); keep its order contact snapshot separate. `company` is omitted because no authoritative customer company field exists. Do not export the inconsistent customer `status` until the live values and meaning are reconciled.

```json
{"externalCustomerId":"42","source":"registered","name":"Example Customer","email":null,"phone":"01700000000","createdAt":"2026-10-26T10:00:00+06:00","updatedAt":"2026-10-26T10:00:00+06:00"}
```

`phone`/`email` are shared only if UniCRM needs contact matching and access/retention are approved. Do not send `password`, `verify`, `forgot`, token, customer notes, saved addresses or admin metadata. Customer ID comes from `customers.id`; do not merge people automatically by phone, which can be shared by registered/manual records.

```json
{"externalOrderId":"501","orderNumber":"12345","externalCustomerId":"42","orderContact":{"name":"Example Customer"},"orderStatus":{"value":"1","label":"Pending"},"paymentStatus":"pending","shippingStatus":null,"currency":"BDT","discount":"0.00","shippingCharge":"70.00","total":"920.00","orderedAt":"2026-10-26T10:01:00+06:00","updatedAt":"2026-10-26T10:01:00+06:00","items":[{"externalOrderItemId":"9001","productId":"77","name":"Cat Food","quantity":2,"unitPrice":"425.00"}]}
```

`orderStatus.value` is the **stored** `orders.order_status` string; `label` must come from `order_statuses.name` (local ID 1 is `Pending`; confirm production). `shippingStatus` is null without a current `courier_parcels.status`, not a fabricated order field. All 12 local orders satisfy `amount = SUM(order_details.qty * sale_price) - discount + shipping_charge`; the local sample has no line discounts. This does **not** prove a universal subtotal/discount formula across web/mobile/manual orders: the database has no `orders.subtotal` column, and some paths persist per-line discounts and `line_total`. Define and test those semantics before adding `subtotal` or item `lineTotal` to the contract. Do not infer paid from COD payment amount. For a manual order with null customer ID, `externalCustomerId` stays null and `orderContact` may include the order's saved name/phone/email under the same PII policy. Do not send shipping address, payment transaction ID, gateway account data, fraud data, IP, purchase cost or internal notes in routine CRM events.

## 15. Proposed events, envelope, security and reconciliation

**Event list provisional pending production status audit and shared publishing boundary.** Local status IDs/labels are now verified. All listed events are retryable *only after* an event is durably recorded and the receiver deduplicates `eventId`. `version=1` is the proposed payload version for every event. Existing code does not emit any of them.

| Event | Trigger | Future Laravel emission point | Version / retryable |
| --- | --- | --- | --- |
| `customer.created`, `customer.updated` | Committed Customer insert / CRM-relevant field change | Customer lifecycle boundary/after-commit observer; exclude internal-only field updates | 1 / yes, with durable outbox |
| `order.created` | Completed committed order aggregate | Shared aggregate completion boundary covering all checkout paths | 1 / yes, with durable outbox |
| `order.status_changed` | Committed change of stored order status | Shared order transition publisher after status save/transaction; audit mass updates | 1 / yes, with durable outbox |
| `order.paid` | Committed transition to authoritative `paid` in order + latest payment | Shared payment-state transition boundary after gateway/admin/courier reconciliation | 1 / yes, with durable outbox |
| `order.delivered` | Committed transition to verified order status `6` | Shared order transition publisher, after payment/courier path finishes | 1 / yes, with durable outbox |
| `order.cancelled` | Committed transition to verified order status `11` | Shared order transition publisher, with source/reason separated if needed | 1 / yes, with durable outbox |
| `refund.requested` | Committed pending refund insert | Refund lifecycle after commit | 1 / yes, with durable outbox |
| `order.refunded` | Committed `refunds.status: approved -> processed` | Refund processing boundary after fund effect succeeds | 1 / yes, with durable outbox |

`order.shipped` is **deferred** despite local status `3` being labeled Shipped: the meaning of manual status changes versus courier dispatch and the production status row still need confirmation. `order.confirmed` has no distinct source transition. `order.returned` has no distinct order state; courier parcel return is a separate signal and can result in order cancellation. If UniCRM needs parcel events later, define `parcel.returned` with parcel ID and current-parcel semantics separately. Avoid duplicate specialized events when `order.status_changed` alone suffices.

Common outbound envelope (proposed):

```json
{"eventId":"00000000-0000-4000-8000-000000000001","eventType":"order.created","version":1,"occurredAt":"2026-10-26T10:01:00+06:00","data":{}}
```

Generate one UUID `eventId` per **business transition**, persist it before delivery, and reuse it unchanged on every retry. UniCRM should enforce uniqueness on `(provider="bilaighor",eventId)` and treat duplicate delivery as success. Preserve order per aggregate or version transitions so late retries cannot overwrite a newer CRM state. For outbound HTTPS, hold a per-integration shared secret outside Git, rotate it with an overlap window, and send `X-UniCRM-Timestamp` (Unix seconds), `X-UniCRM-Event-Id` (same UUID as body), and `X-UniCRM-Signature` (versioned `sha256=<lowercase hex HMAC-SHA256>`). Sign the bytes of `timestamp + "." + exact raw request body`; verify against the received raw body with constant-time comparison. Reject timestamps outside **five minutes**, malformed IDs/signatures, or an event-ID/header mismatch; use event-ID dedupe for replay protection. This is a **proposed** bilateral contract, not a current UniCRM API guarantee.

Dedicated **read-only, service-scoped** reconciliation APIs proposed for later implementation:

| Method/path (proposed) | Purpose | Minimum constraints |
| --- | --- | --- |
| `GET /api/integrations/unicrm/customers/{id}` | Customer by `customers.id` | Restricted customer projection; 404 for unknown; no password/address dump |
| `GET /api/integrations/unicrm/orders/{id}` | Order by `orders.id`, with saved lines and optional latest payment/current parcel status | Sanitized contract above; no raw Eloquent serialization |
| `GET /api/integrations/unicrm/customers/{id}/orders` | Customer's orders | Stable pagination; manual orders with null `customer_id` require a separately agreed matching policy |
| `GET /api/integrations/unicrm/orders?updated_since=...&cursor=...` | Incremental order reconciliation/backfill | UTC ISO timestamp + `(updated_at,id)` cursor, bounded page size, deterministic sort; consider related payment/refund/parcel changes that do not touch `orders.updated_at` |

These routes do **not** currently exist. Use a dedicated service credential with explicit read scopes, HTTPS, rotation and audit logging; never reuse mobile customer tokens or admin cookies. The `updated_since` API must account for changes to child records (payments/refunds/parcel) that leave `orders.updated_at` unchanged; a dedicated change cursor/outbox is safer than assuming parent timestamps always move.

## 16. Readiness gate

| Gate | Result | Reason |
| --- | --- | --- |
| Live statuses verified | **YES locally; NO in production** | All seven local order rows, status counts, customer/payment/refund/parcel distributions checked read-only. Production DB unavailable. |
| Production queue ready | **PARTIAL** | Framework drivers exist, but local runtime is sync with no `jobs` table; production connection/worker/table/monitoring unverified. Not approved for webhook delivery. |
| Invoice endpoint safe | **NEEDS FIX** | Public five-digit invoice lookup reveals shipping PII and purchases. |
| Customer payload finalized | **YES, proposed v1 shape** | Fields map to Customer; export of PII still requires approval, status omitted. |
| Order payload finalized | **NO** | Safe base shape and local status labels are drafted; cross-channel subtotal/discount semantics and child-change reconciliation need validation. |
| Event list finalized | **NO** | Core events are mapped, but shared emission boundary, production status rows and retry support remain unresolved. |
| Security contract finalized | **YES, proposed outbound v1** | HMAC/envelope defined; secret exchange and UniCRM agreement still needed. |
| Reconciliation contract finalized | **NO** | Endpoints/scopes proposed; child-change cursor and manual-contact matching need agreement. |

**NOT READY TO IMPLEMENT.** Resolve the public tracking exposure as a separate security fix, verify **production** status rows/payment/customer distributions, confirm production queue/worker operation, and settle the cross-channel order financial fields and change cursor. No customer/order/payment behavior, route, schema, webhook, or infrastructure was modified for this follow-up.
