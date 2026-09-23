# PostgreSQL JSONB and Indexing

Status: VERIFIED
Last Updated: 2026-09-23
Tags: #knowledge #database #postgresql

## 1. Why JSONB in E-Commerce
PostgreSQL's `jsonb` type stores parsed binary JSON, which supports:
- Efficient key-value lookups.
- Dynamic attributes per product (e.g. dimensions, color variants) without sparse tables.
- Storing full raw Stripe webhook event objects for dispute evidence and audit trails.

## 2. GIN Indexing for JSONB
To query nested JSON keys rapidly without full table scans:
```sql
CREATE INDEX idx_orders_metadata ON orders USING GIN (metadata);
```
In Laravel migrations:
```php
$table->jsonb('metadata')->nullable();
// Indexing raw expression:
DB::statement('CREATE INDEX idx_orders_metadata ON orders USING GIN (metadata)');
```

## 3. Concurrency & Row-Level Locking
During checkout, prevent negative inventory race conditions:
```php
DB::transaction(function () use ($cart) {
    foreach ($cart->items as $item) {
        $product = Product::where('id', $item->product_id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($product->stock < $item->quantity) {
            throw new OutOfStockException("Insufficient stock for {$product->title}");
        }

        $product->decrement('stock', $item->quantity);
    }
});
```

## Related Links
- [[CORE_MEMORY]]
- [[Data_Flow_and_Storage]]
- [[ADR-002_PostgreSQL_16_over_MySQL]]
