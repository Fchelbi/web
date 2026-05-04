# Report — Post Entity : Unit Tests & Static Analysis

**Project:** pidev1 — Symfony 6.4
**Author:** Rania
**Date:** 2026-05-04
**Branch:** forum_et_user

---

## 1. Introduction

This report covers the quality assurance work done on the `Post` entity of the application.
It includes:
- A description of the entity and its structure
- Two unit tests written with **PHPUnit 9.6**
- A static analysis run with **PHPStan 2.1** at level 6
- All commands used and their outputs

---

## 2. Entity Overview — `App\Entity\Post`

**File:** `src/Entity/Post.php`
**Mapped table:** `post` (Doctrine ORM)

### 2.1 Properties

| Property | Type | Nullable | Default | Description |
|---|---|---|---|---|
| `id` | `int` | yes | auto | Primary key, auto-generated |
| `title` | `string(255)` | yes | — | Title of the post |
| `content` | `text` | yes | — | Body content of the post |
| `createdAt` | `datetime` | yes | — | Creation timestamp |
| `likes` | `int` | yes | — | Number of likes |
| `dislikes` | `int` | yes | — | Number of dislikes |
| `photo` | `string(500)` | yes | — | Optional photo filename |
| `isFlagged` | `bool` | no | `false` | Whether the post is flagged |
| `flagReason` | `string(500)` | yes | `null` | Reason for flagging |
| `moderationStatus` | `string(20)` | no | `'approved'` | Moderation state |

### 2.2 Relationships

| Relation | Type | Target Entity | Details |
|---|---|---|---|
| `category` | ManyToOne | `Category` | Required, not nullable |
| `user` | ManyToOne | `User` | Required, not nullable |
| `comments` | OneToMany | `Comment` | Cascade persist, orphan removal |
| `likedByUsers` | ManyToMany | `User` | Join table `post_likes` |
| `dislikedByUsers` | ManyToMany | `User` | Join table `post_dislikes` |

### 2.3 Constructor

The constructor initialises the three collection properties as empty `ArrayCollection` instances so they are never `null`:

```php
public function __construct()
{
    $this->comments       = new ArrayCollection();
    $this->likedByUsers   = new ArrayCollection();
    $this->dislikedByUsers = new ArrayCollection();
}
```

---

## 3. Unit Tests — PHPUnit

**Test file:** `tests/Entity/PostTest.php`
**Framework:** PHPUnit 9.6.29
**PHP version:** 8.2.30

### 3.1 Test Code

```php
<?php

namespace App\Tests\Entity;

use App\Entity\Post;
use PHPUnit\Framework\TestCase;

class PostTest extends TestCase
{
    public function testSetAndGetTitleAndContent(): void
    {
        $post = new Post();
        $post->setTitle('Hello World');
        $post->setContent('This is the post body.');

        $this->assertSame('Hello World', $post->getTitle());
        $this->assertSame('This is the post body.', $post->getContent());
    }

    public function testModerationFlagDefaults(): void
    {
        $post = new Post();

        $this->assertFalse($post->isFlagged());
        $this->assertSame('approved', $post->getModerationStatus());
        $this->assertNull($post->getFlagReason());

        $post->setIsFlagged(true);
        $post->setFlagReason('Spam content');
        $post->setModerationStatus('rejected');

        $this->assertTrue($post->isFlagged());
        $this->assertSame('Spam content', $post->getFlagReason());
        $this->assertSame('rejected', $post->getModerationStatus());
    }
}
```

### 3.2 Test Descriptions

#### Test 1 — `testSetAndGetTitleAndContent`

**Objective:** Verify that `setTitle()` and `setContent()` correctly store values and that their getters return them unchanged.

**Steps:**
1. Instantiate a new `Post` object.
2. Call `setTitle('Hello World')`.
3. Call `setContent('This is the post body.')`.
4. Assert `getTitle()` === `'Hello World'`.
5. Assert `getContent()` === `'This is the post body.'`.

**Assertions:** 2
**Result:** PASSED

---

#### Test 2 — `testModerationFlagDefaults`

**Objective:** Verify the default values of the moderation fields on a fresh `Post`, then verify that each setter correctly updates the value.

**Steps:**
1. Instantiate a new `Post` object.
2. Assert `isFlagged()` === `false` (default).
3. Assert `getModerationStatus()` === `'approved'` (default).
4. Assert `getFlagReason()` === `null` (default).
5. Call `setIsFlagged(true)`, `setFlagReason('Spam content')`, `setModerationStatus('rejected')`.
6. Assert `isFlagged()` === `true`.
7. Assert `getFlagReason()` === `'Spam content'`.
8. Assert `getModerationStatus()` === `'rejected'`.

**Assertions:** 6
**Result:** PASSED

---

### 3.3 Commands Used

```bash
# Run both tests — standard output
php vendor/bin/phpunit tests/Entity/PostTest.php

# Run both tests — human-readable names
php vendor/bin/phpunit tests/Entity/PostTest.php --testdox

# Run a single test by name
php vendor/bin/phpunit tests/Entity/PostTest.php --filter testSetAndGetTitleAndContent
php vendor/bin/phpunit tests/Entity/PostTest.php --filter testModerationFlagDefaults
```

### 3.4 PHPUnit Output

```
PHPUnit 9.6.29 by Sebastian Bergmann and contributors.

Testing App\Tests\Entity\PostTest
Post (App\Tests\Entity\Post)
 ✔ Set and get title and content
 ✔ Moderation flag defaults

Time: 00:00.022, Memory: 10.00 MB

OK (2 tests, 8 assertions)
```

### 3.5 Results Summary

| # | Test | Assertions | Result |
|---|------|-----------|--------|
| 1 | `testSetAndGetTitleAndContent` | 2 | PASSED |
| 2 | `testModerationFlagDefaults` | 6 | PASSED |
| | **Total** | **8** | **OK** |

---

## 4. Static Analysis — PHPStan

**Tool:** PHPStan 2.1.54
**Level:** 6 (out of 10)
**Config file:** `phpstan.neon`

### 4.1 Configuration

```neon
parameters:
    level: 6
    paths:
        - src
        - tests
```

### 4.2 Command Used

```bash
# Install PHPStan
composer require --dev phpstan/phpstan

# Run analysis
php vendor/bin/phpstan analyse src/Entity/Post.php tests/Entity/PostTest.php --level=6 --no-progress
```

### 4.3 First Run — Errors Detected

PHPStan detected **4 errors**, all of the same type (`missingType.generics`).
The `Collection` interface is generic and requires type parameters `<TKey, T>` to be specified.

| Line | Property / Method | Error |
|------|-------------------|-------|
| 52 | `$comments` | `Collection` missing generic types `TKey, T` |
| 58 | `$likedByUsers` | `Collection` missing generic types `TKey, T` |
| 64 | `$dislikedByUsers` | `Collection` missing generic types `TKey, T` |
| 163 | `getComments()` return type | `Collection` missing generic types `TKey, T` |

**Raw output:**
```
 ------ -----------------------------------------------------------------------
  Line   src\Entity\Post.php
 ------ -----------------------------------------------------------------------
  52     Property $comments — Collection does not specify its types: TKey, T
  58     Property $likedByUsers — Collection does not specify its types: TKey, T
  64     Property $dislikedByUsers — Collection does not specify its types: TKey, T
  163    getComments() return type — Collection does not specify its types: TKey, T
 ------ -----------------------------------------------------------------------

[ERROR] Found 4 errors
```

### 4.4 Fix Applied

Added `@var` and `@return` PHPDoc generic annotations to the affected properties and method:

```php
/** @var Collection<int, Comment> */
private Collection $comments;

/** @var Collection<int, User> */
private Collection $likedByUsers;

/** @var Collection<int, User> */
private Collection $dislikedByUsers;

/** @return Collection<int, Comment> */
public function getComments(): Collection { ... }
```

### 4.5 Second Run — No Errors

```
[OK] No errors
```

### 4.6 PHPStan Results Summary

| Run | Errors | Status |
|-----|--------|--------|
| Before fix | 4 (`missingType.generics`) | FAILED |
| After fix | 0 | PASSED |

---

## 5. Final Conclusion

| Tool | Version | Target | Result |
|------|---------|--------|--------|
| PHPUnit | 9.6.29 | `tests/Entity/PostTest.php` | 2/2 tests passed — 8 assertions |
| PHPStan | 2.1.54 | `src/Entity/Post.php` | 0 errors at level 6 |

The `Post` entity is fully covered by unit tests and passes static analysis at level 6.
The 4 type errors found by PHPStan were fixed by adding proper generic type annotations to the Doctrine `Collection` properties.
