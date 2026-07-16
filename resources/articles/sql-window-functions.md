---
name: "SQL Window Functions with Practical Examples"
slug: sql-window-functions
short_description: "How OVER(PARTITION BY ... ORDER BY) works, ROW_NUMBER vs RANK vs DENSE_RANK, running totals, LAG/LEAD, and top-N-per-group queries."
language: en
published_at: 2027-03-12 09:00:00
is_published: true
tags: [sql, database, postgresql, mysql]
---

The first time window functions clicked for me, I was staring at a "second most recent order per customer" ticket that I'd been trying to solve with a self-join and a correlated subquery. The query worked on my seed data and fell over on production, timing out on a table with a few million rows. Someone dropped `ROW_NUMBER() OVER (PARTITION BY customer_id ORDER BY created_at DESC)` into a review comment and the whole thing collapsed into six lines. This is the tool that turns those queries from clever to boring, and boring is what you want on a hot path.

This is a working tour of the pieces you actually reach for: `OVER(...)`, the three ranking functions, running totals, `LAG`/`LEAD`, and the frame clause that most people never touch until it bites them. All of it runs on PostgreSQL (since forever) and MySQL 8.0+ (added in 2018 — if you're on 5.7, none of this exists yet).

## What a window function actually does

A regular aggregate collapses rows. `GROUP BY customer_id` with `SUM(total)` gives you one row per customer, and the individual orders are gone. A window function computes the same kind of number but keeps every row. You get the aggregate *and* the detail in the same result set.

The mechanism is the `OVER` clause. It tells the database: for this row, look at a "window" of related rows and compute something across them.

```sql
SELECT
    order_id,
    customer_id,
    total,
    SUM(total) OVER (PARTITION BY customer_id) AS customer_lifetime_total
FROM orders;
```

Every row still comes back. Alongside each order you now have that customer's total spend. No `GROUP BY`, no join back to a subquery. `PARTITION BY` is the window function's version of grouping — it splits the rows into independent buckets, and the function resets at each bucket boundary. Leave it out and the window is the whole result set.

The important mental model: window functions run *after* `WHERE`, `GROUP BY`, and `HAVING`, but *before* `ORDER BY` and `LIMIT`. That ordering explains a mistake I've made more than once — you can't filter on a window function in a `WHERE` clause, because at that point it hasn't been computed yet. You have to wrap the query in a subquery or CTE and filter on the outside. More on that below, because it's the single most common wall people hit.

## ROW_NUMBER vs RANK vs DENSE_RANK

These three look interchangeable until you have ties, and then they behave completely differently. Add `ORDER BY` inside the `OVER` clause to give the window an ordering, and each function numbers the rows against it.

```sql
SELECT
    name,
    score,
    ROW_NUMBER() OVER (ORDER BY score DESC) AS row_num,
    RANK()       OVER (ORDER BY score DESC) AS rank_num,
    DENSE_RANK() OVER (ORDER BY score DESC) AS dense_num
FROM players;
```

Say three players score 100, 100, and 90:

| name | score | row_num | rank_num | dense_num |
|------|-------|---------|----------|-----------|
| Ada  | 100   | 1       | 1        | 1         |
| Ben  | 100   | 2       | 1        | 1         |
| Cy   | 90    | 3       | 3        | 2         |

`ROW_NUMBER` doesn't care about ties — it hands out 1, 2, 3 no matter what, and which of Ada or Ben gets 1 is arbitrary unless you add a tiebreaker to the `ORDER BY`. `RANK` gives ties the same number then *skips* — the two 100s are both rank 1, and 90 jumps to 3. `DENSE_RANK` gives ties the same number but *doesn't* skip, so 90 is 2.

Which one you want depends on the question. Leaderboard where two people genuinely tie for first and the next is "third place"? `RANK`. "How many distinct score levels are above this one"? `DENSE_RANK`. "Give me exactly one row per group, I don't care which on a tie"? `ROW_NUMBER` — and always add a stable tiebreaker like `ORDER BY score DESC, id` so the result is deterministic across runs. Non-deterministic pagination is a fun bug to debug at 2am.

## Top N per group

This is the query that earns window functions their keep. "The three most expensive products in each category." Without window functions you're writing a correlated subquery that counts how many products in the same category are more expensive — quadratic, ugly, slow.

```sql
SELECT category, name, price
FROM (
    SELECT
        category,
        name,
        price,
        ROW_NUMBER() OVER (
            PARTITION BY category
            ORDER BY price DESC
        ) AS rn
    FROM products
) ranked
WHERE rn <= 3
ORDER BY category, price DESC;
```

Read it inside out. The inner query partitions by category, orders each partition by price descending, and stamps a row number. The outer query keeps only rows numbered 1 through 3. That's the whole trick, and it scales because the database sorts once per partition instead of re-scanning.

The subquery isn't optional decoration — it's the fix for that timing rule I mentioned. `WHERE rn <= 3` cannot live in the inner query because `rn` doesn't exist yet when `WHERE` runs. If you want the *top* one per group rather than three, `ROW_NUMBER` still beats `RANK` here: with `RANK` a two-way tie for first gives you two rows, which is usually not what "the top product" means.

## Running totals with SUM() OVER

Add `ORDER BY` to a `SUM() OVER` and it stops summing the whole partition and starts accumulating — a running total.

```sql
SELECT
    order_date,
    amount,
    SUM(amount) OVER (
        ORDER BY order_date
    ) AS running_total
FROM daily_sales
ORDER BY order_date;
```

Each row's `running_total` is the sum of every row up to and including this one, in date order. Combine it with `PARTITION BY` and you get a running total that resets — cumulative revenue per month, per account, whatever the partition is.

Here's the subtle part almost nobody notices until it produces a wrong number: **the moment you add `ORDER BY` to a windowed aggregate, SQL applies a default frame.** That default is `RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW`, and `RANGE` treats rows with the same `ORDER BY` value as one unit. If two sales share a date, both get the *same* running total — the sum through the end of that date — not two stepping values. That's often what you want for a daily total. It is not what you want if you expected each row to increment. When you need strict row-by-row accumulation regardless of ties, spell out the frame explicitly:

```sql
SUM(amount) OVER (
    ORDER BY order_date, order_id
    ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
) AS running_total
```

`ROWS` counts physical rows; `RANGE` counts value peers. That one word is the difference between two different (both correct) answers, and it's the frame bug I've watched trip up experienced people.

## The frame clause, briefly

The frame is the sliding sub-window inside the partition. `ROWS BETWEEN <start> AND <end>` where the bounds are `UNBOUNDED PRECEDING`, `N PRECEDING`, `CURRENT ROW`, `N FOLLOWING`, or `UNBOUNDED FOLLOWING`. A moving average over the current row plus the two before it:

```sql
SELECT
    order_date,
    amount,
    AVG(amount) OVER (
        ORDER BY order_date
        ROWS BETWEEN 2 PRECEDING AND CURRENT ROW
    ) AS three_day_avg
FROM daily_sales;
```

You only need to think about the frame with aggregates that ride on `ORDER BY` (`SUM`, `AVG`, `COUNT`, `MIN`, `MAX`). Ranking functions (`ROW_NUMBER`, `RANK`) and `LAG`/`LEAD` ignore the frame entirely, so don't waste time adding one to them.

## LAG and LEAD for row-to-row comparisons

`LAG` reaches backward to a previous row; `LEAD` reaches forward. This is how you compare a row to its neighbor without a self-join, and it's the core of every "change since last time" report.

```sql
SELECT
    month,
    revenue,
    LAG(revenue) OVER (ORDER BY month) AS prev_revenue,
    revenue - LAG(revenue) OVER (ORDER BY month) AS mom_change
FROM monthly_revenue
ORDER BY month;
```

`LAG(revenue)` grabs the revenue from the row one position back in the ordering. For January there's nothing behind it, so `LAG` returns `NULL` and `mom_change` is `NULL` — expected, not a bug. If you'd rather show zero for the first row, `LAG(revenue, 1, 0)` supplies a default: offset 1, fallback 0.

### Month-over-month change as a percentage

The version people actually ask for is the percentage, so here it is with the null and divide-by-zero cases handled:

```sql
SELECT
    month,
    revenue,
    LAG(revenue) OVER (ORDER BY month) AS prev_revenue,
    ROUND(
        100.0 * (revenue - LAG(revenue) OVER (ORDER BY month))
        / NULLIF(LAG(revenue) OVER (ORDER BY month), 0),
        1
    ) AS pct_change
FROM monthly_revenue
ORDER BY month;
```

`NULLIF(prev, 0)` turns a zero previous month into `NULL` so the division yields `NULL` instead of a divide-by-zero error. The `100.0` (not `100`) forces floating-point math — integer division would quietly floor your percentages to whole numbers in Postgres, and that's a wrong-number bug that passes every test with round seed data. If you want per-account trends, add `PARTITION BY account_id` and `LAG` won't leak the previous account's number into the first month of the next one.

## Common traps

- **Filtering on a window function in `WHERE`.** It isn't computed yet. Wrap it in a subquery or CTE and filter outside. This is the number one error.
- **Forgetting the tiebreaker in `ORDER BY`.** `ROW_NUMBER` over a non-unique column gives arbitrary, run-to-run-unstable numbering. Add a unique column to break ties.
- **`RANGE` vs `ROWS` on running totals.** The default frame is `RANGE`, which lumps ties together. Use `ROWS` for strict row-by-row accumulation.
- **Integer division in percentage math.** Multiply by `100.0`, not `100`, or cast, or your percentages round to integers.
- **MySQL 5.7.** None of this exists there. Check `SELECT VERSION();` before you promise a feature — the syntax is identical between 8.0+ and Postgres, so code written for one ports to the other with almost no changes.

## FAQ

### Can I use a window function in a WHERE clause?
No. Window functions are evaluated after `WHERE`, so the value doesn't exist when the filter runs. Put the window function in a subquery or CTE and filter on its output in the outer query. The `WHERE rn <= 3` pattern in the top-N example is exactly this.

### What's the difference between PARTITION BY and GROUP BY?
`GROUP BY` collapses each group into a single row. `PARTITION BY` keeps every row and computes the window value per group alongside the detail. Same grouping idea, opposite effect on row count.

### Do window functions work in MySQL?
Yes, from MySQL 8.0 (released 2018). MySQL 5.7 and earlier have no support at all — you'd fall back to variables or subqueries. PostgreSQL, SQL Server, and Oracle have had them for years, and the standard syntax is portable across all of them.

### Why is my running total the same for two rows with the same date?
Because the default frame is `RANGE`, which treats rows sharing an `ORDER BY` value as a single peer group and gives them the identical cumulative sum. Switch to `ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW` and add a unique tiebreaker to `ORDER BY` for a strict step-by-step total.

## Where to go from here

Window functions replace a whole category of self-joins and correlated subqueries with something the query planner handles far better. Reach for them any time the answer involves "compared to its group" or "compared to the row before it." The next patterns worth learning once these feel natural are `FIRST_VALUE`/`LAST_VALUE` for pulling a boundary value into every row, `NTILE(n)` for bucketing into quartiles, and named windows (`WINDOW w AS (...)`) so you can reuse one window definition across several columns instead of retyping the `OVER` clause. Take the month-over-month query, point it at a real table, and add a partition — that one change is usually where it stops being a demo and starts being a report someone asks for every Monday.
