# PHPStan Static Analysis Report
**Project:** Symfony Healthcare/Wellness Application  
**Analysis Date:** May 8, 2026  
**PHPStan Level:** 8 (Strict)  
**Total Errors Found:** 296

---

## Executive Summary

This Symfony project has **296 static analysis errors** identified by PHPStan at the strictest level (8). The errors fall into several categories, with the most critical being:

1. **Undefined method calls** (40 errors) - Methods called on entities don't match actual getter/setter names
2. **Missing iterable value types** (50 errors) - Array return types and parameters lack element type specifications
3. **Missing generic types** (36 errors) - Generic classes missing type parameters
4. **Constructor/function issues** (42 errors) - Unused parameters and type narrowing issues
5. **Other type issues** (Various) - Miscellaneous type safety problems

---

## Error Analysis by Category

### 1. Undefined Methods (40 errors) - **CRITICAL**

**Pattern:** Code uses snake_case method names while Entity has camelCase implementations

**Root Cause:** The User entity defines getters/setters in camelCase (e.g., `setNumTel()`, `setPassword()`) but legacy code calls them in snake_case (e.g., `setNum_tel()`, `setMdp()`).

**Affected Files:**
- `bin/create_default_user.php` (lines 35, 40, 45, 49)
- `src/Command/CreateDefaultUserCommand.php` (lines 36, 45, 50, 56)
- `tests/Service/AiServiceTest.php` (multiple test assertions)

**Example Error:**
```
Call to an undefined method App\Entity\User::setNum_tel().
Call to an undefined method App\Entity\User::setMdp().
Call to an undefined method App\Entity\User::getId_user().
```

**Actual Methods in User Entity:**
- `setNumTel()` - not `setNum_tel()`
- `setPassword()` - not `setMdp()`
- `getId()` - not `getId_user()`

---

### 2. Missing Iterable Value Types (50 errors) - **HIGH PRIORITY**

**Pattern:** Array return types and parameters lack element type specifications

**Root Cause:** Using bare `array` type hints without specifying what type of elements the array contains. PHPStan level 8 requires `array<KeyType, ValueType>` or `ArrayType[]` syntax.

**Affected Files:**
- `src/Command/GenerateEntitiesCommand.php` (14+ errors)
- `src/Service/AiService.php` (parameters and return types)
- `src/Service/GeminiService.php`
- `src/Service/GoogleMeetService.php`
- `src/Service/MailService.php`
- `src/Service/QrCodeService.php`
- `src/Service/RecommendationService.php`
- `src/Service/SmsService.php`
- `src/Service/TranslateService.php`
- `src/Service/WeatherService.php`
- `src/Service/YouTubeService.php`
- Repository classes (all)
- Form classes (multiple)

**Example Errors:**
```
Method App\Command\GenerateEntitiesCommand::generateEntity() has parameter 
$manyToOneRelationsName with no value type specified in iterable type array.

Method App\Service\AiService::recommendCoach() return type has no value type 
specified in iterable type array.
```

**Fix Pattern:**
```php
// Before (Wrong)
public function getItems(): array

// After (Correct)
/** @return string[] */
public function getItems(): array

// Or (Better)
public function getItems(): array<string>
```

---

### 3. Missing Generic Types (36 errors) - **HIGH PRIORITY**

**Pattern:** Generic classes used without specifying their type parameters

**Root Cause:** Doctrine DBAL's `AbstractSchemaManager` is a generic class that requires a type parameter `<T>` but it's not specified.

**Affected Files:**
- `src/Command/GenerateEntitiesCommand.php`
- `src/Command/GenerateRepositoriesCommand.php`

**Example Errors:**
```
Property App\Command\GenerateEntitiesCommand::$schemaManager with generic class 
Doctrine\DBAL\Schema\AbstractSchemaManager does not specify its types: T
```

**Fix Pattern:**
```php
// Before
private AbstractSchemaManager $schemaManager;

// After
private AbstractSchemaManager<string> $schemaManager;
```

---

### 4. Constructor & Function Issues (42 errors)

**Subcategories:**

#### 4a. Unused Constructor Parameters (1 error)
```
Constructor of class App\Command\GenerateEntitiesCommand has an unused 
parameter $filesystem.
```

#### 4b. Test Mocking Issues (40 errors)
```
Call to an undefined method Doctrine\ORM\EntityManagerInterface::expects().
Call to an undefined method Psr\Log\LoggerInterface::expects().
```
These are in `tests/Service/AiServiceTest.php` - using PHPUnit mock methods on real interfaces.

#### 4c. Type Narrowing Issues (1 error)
```
Call to function method_exists() with 'Symfony\\Component\\Dotenv\\Dotenv' 
and 'bootEnv' will always evaluate to true.
```

---

## Files Needing Fixes (by Priority)

### CRITICAL (Breaks Functionality)
1. **bin/create_default_user.php** - Uses wrong method names (4 errors)
2. **src/Command/CreateDefaultUserCommand.php** - Uses wrong method names (4 errors)

### HIGH (Type Safety)
3. **src/Command/GenerateEntitiesCommand.php** - 20+ errors (generics + iterables)
4. **src/Service/*** (all 15 service files) - 50+ combined iterable type errors
5. **src/Repository/*** (all repository files) - Iterable type errors

### MEDIUM (Code Quality)
6. **src/Entity/*** (all entities) - Various type issues
7. **src/Form/*** (all forms) - Missing type hints
8. **src/Controller/*** (all controllers) - Missing return type specifications

### LOW (Testing Only)
9. **tests/Service/AiServiceTest.php** - Mock object issues

---

## Error Distribution Across Files

### Top Files by Error Count:
| File | Error Count | Error Type |
|------|-------------|-----------|
| GenerateEntitiesCommand.php | 20+ | missingType.generics, iterableValue |
| AiService.php | 8+ | missingType.iterableValue |
| GeminiService.php | 6+ | missingType.iterableValue |
| GoogleMeetService.php | 5+ | missingType.iterableValue |
| AiServiceTest.php | 14 | method.notFound (mocking) |
| User.php (commands) | 8 | method.notFound |
| Various repositories | 20+ | missingType.iterableValue |

---

## Common Error Patterns & Solutions

### Pattern 1: Wrong Method Names (User Entity)
```php
// ❌ WRONG - Snake case used
$user->setNum_tel('1234567890');
$user->setMdp($hashedPassword);
$userId = $user->getId_user();

// ✅ CORRECT - Camel case
$user->setNumTel('1234567890');
$user->setPassword($hashedPassword);
$userId = $user->getId();
```

### Pattern 2: Array Without Element Types
```php
// ❌ WRONG
public function getCoaches(): array
/** @param array $filters */
public function search($filters): void

// ✅ CORRECT
/** @return User[] */
public function getCoaches(): array
/** @param array<string, mixed> $filters */
public function search(array $filters): void
```

### Pattern 3: Generic Types Not Specified
```php
// ❌ WRONG
private AbstractSchemaManager $schemaManager;

// ✅ CORRECT - Check Doctrine version for proper type
private AbstractSchemaManager<string> $schemaManager;
```

### Pattern 4: Unused Parameters
```php
// ❌ WRONG - $filesystem is never used
public function __construct(Filesystem $filesystem, EntityManagerInterface $em)
{
    $this->em = $em;
}

// ✅ CORRECT
public function __construct(EntityManagerInterface $em)
{
    $this->em = $em;
}
```

---

## Recommended Fix Priority

### Phase 1: Critical Fixes (Prevents Runtime Errors)
**Time: ~30 minutes**
- Fix all undefined method calls in User entity usage
- Files: `bin/create_default_user.php`, `src/Command/CreateDefaultUserCommand.php`, tests
- Change: Replace snake_case methods with camelCase

### Phase 2: Type Safety (High Value)
**Time: ~2-3 hours**
- Add missing array element types to all Service classes
- Add missing array element types to all Repository classes
- Files: All services, all repositories
- Change: Add `@return Type[]` docblocks and proper type hints

### Phase 3: Generic Types
**Time: ~30 minutes**
- Fix Doctrine generic type parameters
- Files: Command classes
- Change: Specify generic type parameters for AbstractSchemaManager

### Phase 4: Code Quality
**Time: ~1-2 hours**
- Fix Entity type hints
- Fix Form type hints
- Remove unused constructor parameters
- Fix test mocking issues

---

## PHPStan Configuration

Current configuration (`phpstan.dist.neon`):
```yaml
parameters:
    level: 8
    paths:
        - bin/
        - config/
        - public/
        - src/
        - tests/
```

**Level 8 Requirements:**
- All variables must have declared types
- All return types must be fully specified
- All array types must have element types specified
- All generic types must have type parameters
- No mixed types allowed
- No loose comparisons

---

## Summary Statistics

| Metric | Count |
|--------|-------|
| Total Errors | 296 |
| Critical (Runtime) | 8 |
| High (Type Safety) | 150+ |
| Medium (Code Quality) | 100+ |
| Low (Testing/Style) | 40+ |
| Files with Errors | 85+ |
| Files in src/ | 45+ |

---

## Next Steps

1. **Review** this report with your development team
2. **Prioritize** fixes based on business impact (critical first)
3. **Implement** changes in phases to maintain code stability
4. **Test** thoroughly after each phase
5. **Re-run** PHPStan to verify fixes: `php vendor/bin/phpstan analyse`

---

**Generated by:** PHPStan v1.x  
**Analysis Completed:** 2026-05-08
