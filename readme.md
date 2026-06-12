# POC - Bug symfony/ux-autocomplete SQLSTATE[HY093] on PostgreSQL

## Context

This repository reproduces a bug introduced in `symfony/ux-autocomplete` v2.33+ where using `searchable_fields` in combination with a `query_builder` containing a `ManyToOne` relation referencing a **non-id string column** causes a crash on PostgreSQL.

## Bug description

When typing in an autocomplete field, the following exception is thrown on PostgreSQL:

```
An exception occurred while executing a query: SQLSTATE[HY093]: Invalid parameter number: parameter was not defined
```

The root cause is in `EntitySearchUtil::addSearchClause()` where the generated `LIKE` clause includes `ESCAPE '\'`:

```sql
LOWER(p.name) LIKE :query_for_text ESCAPE '\'
```

PostgreSQL rejects this because the `ESCAPE` clause must be a single character. Combined with the parameters already set in the `query_builder`, PDO throws an `Invalid parameter number` error.

## Key conditions to reproduce

Three conditions must be met simultaneously:

1. **PostgreSQL** as the database driver
2. **`searchable_fields`** defined on the autocomplete field
3. A **`query_builder`** with a `setParameter` on a `ManyToOne` relation where the join references a **non-id string column** (`referencedColumnName: 'code'`)

```php
// Product entity
#[ORM\ManyToOne]
#[ORM\JoinColumn(referencedColumnName: 'code', nullable: false)]
private ?Category $category = null;

// Category entity — 'code' is the primary key, not an auto-incremented id
#[ORM\Id]
#[ORM\Column(length: 25, nullable: false)]
#[ORM\GeneratedValue(strategy: 'NONE')]
private ?string $code = null;
```

```php
// ProductAutocompleteField
'searchable_fields' => ['name', 'libelle'],
'query_builder' => fn (ProductRepository $repository) => $repository->createQueryBuilder('p')
    ->andWhere('p.category = :valide')
    ->setParameter('valide', 'Validé')
    ->addOrderBy('p.id', 'DESC'),
```

## Tested databases

| Database   | Bug reproduced | Notes                           |
|------------|----------------|---------------------------------|
| PostgreSQL | ✅ Yes          | SQLSTATE[HY093] on keystroke    |
| MySQL      | ❌ No           | `ESCAPE '\'` accepted natively  |
| SQLite     | ❌ No           | No error, search works          |

## How to reproduce

```bash
composer install
# Configure DATABASE_URL in .env for PostgreSQL
bin/console doctrine:schema:create
bin/console app:load-products
symfony server:start
```

Open `http://localhost:8000/product` and type in the autocomplete field.

## Proposed fix

In `src/Doctrine/EntitySearchUtil.php`, replace:

```php
'text_query' => '%'.addcslashes($lowercaseQuery, '\\%_').'%',
```

with:

```php
'text_query' => '%'.$queryBuilder->getEntityManager()->getConnection()->getDatabasePlatform()->escapeStringForLike($lowercaseQuery, '\\').'%',
```

And replace:

```php
$expressions[] = \sprintf("LOWER(%s.%s) LIKE :query_for_text ESCAPE '\\'", $entityName, $propertyName);
```

with:

```php
$expressions[] = \sprintf('LOWER(%s.%s) LIKE :query_for_text', $entityName, $propertyName);
```

Removing the `ESCAPE` clause is safe since wildcard escaping is already handled upstream. The `escapeStringForLike` method makes the escaping driver-aware, covering PostgreSQL, MySQL, and SQLite correctly.

## Related issue

https://github.com/symfony/ux/issues/3668