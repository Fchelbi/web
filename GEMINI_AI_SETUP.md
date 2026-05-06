# Gemini AI Service for Coach Selection

## Overview

The `AiService` integrates Gemini API to suggest the most available psychologist (coach) based on consultation counts. It includes:

- ✅ Gemini API integration for intelligent selection
- ✅ Fallback logic (returns coach with lowest consultation count)
- ✅ Optimized database queries (GROUP BY, no N+1)
- ✅ Production-ready error handling and logging

## Configuration

### 1. Add Gemini API Key to `.env`

```env
GEMINI_API_KEY=your-gemini-api-key-here
```

### 2. Update `config/services.yaml`

If you need to inject the API key explicitly:

```yaml
services:
    App\Service\AiService:
        arguments:
            $geminiApiKey: '%env(GEMINI_API_KEY)%'
```

The service uses autowiring by default, so no configuration is needed if you're using standard Symfony setup.

## Usage

### Basic Usage - Get Most Available Coach

```php
<?php

namespace App\Controller;

use App\Service\AiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConsultationController extends AbstractController
{
    #[Route('/consultation/new', name: 'consultation_new')]
    public function new(AiService $aiService): Response
    {
        // Get the most available coach
        $suggestedCoach = $aiService->suggestMostAvailablePsy();

        if ($suggestedCoach === null) {
            throw $this->createNotFoundException('No coaches available');
        }

        return $this->render('consultation/new.html.twig', [
            'suggestedCoach' => $suggestedCoach,
        ]);
    }
}
```

### Advanced Usage - Filter by Date

Filter consultations from a specific date onwards:

```php
<?php

$now = new \DateTime();
$suggestedCoach = $aiService->suggestMostAvailablePsy($now);
// Only counts consultations scheduled for today or later
```

## How It Works

### 1. Fetch Coaches with Consultation Counts

Uses optimized Doctrine query with `GROUP BY`:

```sql
SELECT u.id, CONCAT(u.prenom, ' ', u.nom) as name, COUNT(c.id) as consultations
FROM `user` u
LEFT JOIN consultation_en_ligne c ON c.psychologue_id = u.id_user
WHERE u.role = 'Coach'
GROUP BY u.id
ORDER BY consultations ASC
```

**Benefits:**
- Single query (no N+1)
- Efficient aggregation at database level
- Ordered by lowest consultations first

### 2. Call Gemini API

Sends coaches data with a prompt asking for the most available one:

```
You are a scheduling assistant for a psychology consultation platform.

Below is a list of available coaches and their current consultation counts:

- Coach ID: 1, Name: John Doe, Current Consultations: 5
- Coach ID: 2, Name: Sarah Smith, Current Consultations: 2

Your task: Suggest the MOST AVAILABLE coach (the one with the LOWEST number of consultations).
Return ONLY the coach ID...
```

### 3. Fallback Logic

If Gemini API fails or no key is configured:
- Automatically returns the coach with the **lowest consultation count** using pure PHP
- All operations are logged for debugging

## Logging

The service logs all important events:

```php
// Via Symfony logger (automatically injected)
$this->logger->info('Selected coach via Gemini AI', ['coach_id' => 2]);
$this->logger->warning('Gemini API failed, using fallback', ['error' => $e->getMessage()]);
$this->logger->info('Selected coach via fallback method', ['coach_id' => 1, 'consultations' => 2]);
```

View logs in: `var/log/dev.log` or `var/log/prod.log`

## Error Handling

```php
try {
    $coach = $aiService->suggestMostAvailablePsy();
    
    if ($coach === null) {
        // No coaches available
    } else {
        // Use $coach (User entity)
        echo "Suggested coach: " . $coach->getName();
    }
} catch (\Exception $e) {
    // Handle unexpected errors
    $this->logger->error('Coach selection failed', ['error' => $e->getMessage()]);
}
```

## Testing

### Unit Test Example

```php
<?php

namespace App\Tests\Service;

use App\Entity\ConsultationEnLigne;
use App\Entity\User;
use App\Service\AiService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiServiceTest extends TestCase
{
    public function testSuggestMostAvailablePsy(): void
    {
        // Create mock coaches
        $coach1 = new User();
        $coach1->setId(1)->setPrenom('John')->setNom('Doe')->setRole(User::ROLE_COACH);

        $coach2 = new User();
        $coach2->setId(2)->setPrenom('Sarah')->setNom('Smith')->setRole(User::ROLE_COACH);

        // Mock repository
        $repository = $this->createMock(UserRepository::class);
        $repository->method('findCoachesWithConsultationCounts')
            ->willReturn([
                ['id' => 1, 'name' => 'John Doe', 'consultations' => 5],
                ['id' => 2, 'name' => 'Sarah Smith', 'consultations' => 2],
            ]);

        // Test fallback (without Gemini)
        $service = new AiService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(HttpClientInterface::class),
            $this->createMock(LoggerInterface::class),
            null // No API key
        );

        // Should select coach with minimum consultations
        $selected = $service->suggestMostAvailablePsy();
        $this->assertEquals(2, $selected->getId());
    }
}
```

## Performance Considerations

1. **Query Optimization**: Single GROUP BY query instead of N+1
2. **Caching**: Consider caching coaches data for a few minutes
3. **Timeout**: Gemini API calls have a default timeout; adjust if needed
4. **Fallback**: No API call overhead if key is not configured

## Troubleshooting

### No Coaches Found
```
Message: "No coaches available"
Action: Check that users with role='Coach' exist in database
```

### Gemini API Key Error
```
Message: "Gemini API request failed"
Action: Verify GEMINI_API_KEY in .env
Action: Check if API key has proper permissions
```

### Coach ID Not Found in Response
```
Message: "Could not parse coach ID from Gemini response"
Action: Check Gemini's response format
Action: Review logs in var/log/dev.log
```

## API Key Setup

1. Get Gemini API key from [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Add to `.env`:
   ```
   GEMINI_API_KEY=sk-...
   ```
3. The service will automatically use it

## Files Modified

- ✅ `src/Service/AiService.php` - Main service
- ✅ `src/Repository/UserRepository.php` - Optimized queries
- ✅ `src/Entity/User.php` - Added repository mapping
