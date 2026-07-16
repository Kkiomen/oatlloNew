---
title: "Repositories: a collection of aggregates"
slug: repositories
seo_title: "Repository Pattern in DDD: Persist Aggregates (PHP)"
seo_description: "Learn the repository pattern in Domain-Driven Design: a collection-like interface that stores and retrieves aggregates, hiding the database. PHP example."
---

A **repository** behaves like an in-memory collection of aggregates: you ask it for one by id
or hand it one to save, and it hides the database. One repository per aggregate root, no more.

## What is the repository pattern?

The repository pattern gives the domain a collection-shaped interface for storage and keeps
every trace of SQL on the far side of it. Without one, persistence bleeds into your domain:

```php
public function markOrderPaid(string $orderId): void
{
    $row = $this->db->query('SELECT * FROM orders WHERE id = ?', [$orderId]);
    $order = new Order($row['id']);       // rebuild by hand
    // ... business logic ...
    $this->db->execute('UPDATE orders SET status = ? WHERE id = ?', ['paid', $orderId]);
}
```

The domain rule ("mark an order paid") is now tangled with table names, column names, and SQL.
You can't test it without a database, and if storage changes, the domain changes with it - it
shouldn't know orders live in a table at all.

## The repository as an interface

The move is to declare the repository as an **interface** that speaks the domain's language -
`save`, `find` - and lives with the domain. It says nothing about SQL.

```php
<?php
declare(strict_types=1);

interface OrderRepository
{
    public function save(Order $order): void;

    public function ofId(string $id): ?Order;

    /** @return Order[] */
    public function unpaidOrders(): array;
}
```

Read `ofId` and `unpaidOrders` out loud - domain vocabulary, not database vocabulary. The
domain depends only on this interface and has no idea whether orders sit in MySQL, a file, or an
array. One detail worth copying: `ofId()` returns `?Order`. A repository that throws on a
missing id forces every caller into a try/catch; returning null lets the caller decide what
"not found" means.

## The database lives in the implementation

The storage code goes in a concrete class that implements the interface, in the infrastructure
layer, away from the domain.

```php
final class SqlOrderRepository implements OrderRepository
{
    public function __construct(
        private readonly PDO $db,
    ) {}

    public function save(Order $order): void
    {
        // Map the aggregate to rows and write it. SQL stays HERE, nowhere else.
        $stmt = $this->db->prepare(
            'INSERT INTO orders (id, status) VALUES (:id, :status)
             ON DUPLICATE KEY UPDATE status = :status'
        );
        $stmt->execute(['id' => $order->id, 'status' => $order->status()->value]);
    }

    public function ofId(string $id): ?Order
    {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Order::fromStorage($row) : null;
    }

    /** @return Order[] */
    public function unpaidOrders(): array
    {
        // ... query and rebuild Order aggregates ...
        return [];
    }
}
```

All the SQL now sits in one place. The domain just calls `$orders->save($order)` and
`$orders->ofId('ord_4021')`, as if `$orders` were a plain collection. Swap `SqlOrderRepository`
for an in-memory version in a test and the domain never notices.

## One repository per aggregate root

A repository deals in **aggregate roots**, one per root - `OrderRepository`,
`CustomerRepository`. You don't build an `OrderLineRepository`, because a line isn't a root;
you reach it through its `Order`. The repository loads and saves the whole aggregate as a unit
- exactly the consistency boundary [the aggregates
lesson](/course/software-architecture/ddd-tactical-patterns/aggregates) drew.

## Common mistake: a repository that returns half an aggregate

A frequent mistake is treating the repository as a generic query bag - methods returning loose
rows, single columns, or a bare `OrderLine` with no `Order` around it. That breaks the
boundary: now code holds a piece of the cluster without the root that guards it. Keep the
repository on whole aggregate roots (save one, fetch one, fetch a list). Reporting queries that
don't fit the aggregate shape belong elsewhere.

## FAQ

### Is a repository the same as a DAO?

They overlap, but the intent differs. A DAO (Data Access Object) is usually a thin wrapper
around table CRUD. A repository is a **domain** concept: it pretends to be a collection of
aggregates and speaks the domain's language, hiding that a database exists at all.

### Isn't this just Laravel's Eloquent model?

Not quite, and that's a debate of its own. Eloquent mixes the aggregate and its persistence in
one class, whereas the DDD repository keeps them apart behind an interface. When it's worth
adding a repository on top of Laravel's defaults is covered in the Laravel chapter later - here
we're only defining the pattern.

### Why one repository per aggregate root, not per table?

Because the aggregate, not the table, is the unit the domain cares about. One aggregate can
span several tables (an order and its lines). The repository loads and saves that whole unit,
so it's tied to the root, not to any single table.
