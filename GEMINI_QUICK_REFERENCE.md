# Gemini AI Coach Selection - Implementation Summary

## What Was Built

A production-ready Symfony service that automatically suggests the most available psychologist using the Gemini API, with automatic fallback to pure PHP logic.

## Files Created/Modified

| File | Status | Purpose |
|------|--------|---------|
| `src/Service/AiService.php` | ✅ Created | Main service with Gemini integration & fallback |
| `src/Repository/UserRepository.php` | ✅ Created | Optimized repository queries |
| `src/Entity/User.php` | ✅ Modified | Added repository mapping |
| `src/Controller/ConsultationAiController.php` | ✅ Created | Example API endpoints |
| `GEMINI_AI_SETUP.md` | ✅ Created | Full documentation |

## Key Features

### 1. Intelligent Selection via Gemini API
- Sends coaches list with consultation counts to Gemini
- Gemini analyzes and recommends the most available coach
- Returns structured response

### 2. Automatic Fallback
- If Gemini API key missing → uses PHP fallback
- If Gemini API fails → automatically switches to PHP fallback
- Fallback: simple min() function on consultation counts

### 3. Optimized Database Queries
```php
// Single query with GROUP BY (no N+1)
SELECT u.id, CONCAT(u.prenom, ' ', u.nom) as name, COUNT(c.id) as consultations
FROM `user` u
LEFT JOIN consultation_en_ligne c ON c.psychologue_id = u.id_user
WHERE u.role = 'Coach'
GROUP BY u.id
ORDER BY consultations ASC
```

### 4. Optional Date Filtering
Filter consultations from a specific date onwards:
```php
$fromDate = new \DateTime('2026-04-25');
$coach = $aiService->suggestMostAvailablePsy($fromDate);
```

## Configuration

### 1. Add Environment Variable (`.env`)
```env
GEMINI_API_KEY=your-gemini-api-key-here
```

Get your free API key: https://makersuite.google.com/app/apikey

### 2. No Other Config Needed!
Symfony autowiring handles dependency injection automatically.

## Usage Examples

### Example 1: Auto-assign Coach to New Consultation
```php
$coach = $aiService->suggestMostAvailablePsy();

if ($coach) {
    $consultation->setPsychologue($coach);
    $entityManager->persist($consultation);
    $entityManager->flush();
}
```

### Example 2: Show Available Coaches to Patient
```php
$coaches = $aiService->getCoachesWithConsultationCounts();

// Returns:
// [
//     ["id" => 1, "name" => "John Doe", "consultations" => 5],
//     ["id" => 2, "name" => "Sarah Smith", "consultations" => 2],
// ]
```

### Example 3: Filter by Future Consultations Only
```php
$now = new \DateTime();
$coach = $aiService->suggestMostAvailablePsy($now);
// Only counts consultations scheduled for today or later
```

## API Endpoints Created

### 1. `POST /consultation/auto-assign`
Automatically assign a consultation to the most available coach.
```json
Request: { "motif": "Anxiety issues", "dateConsultation": "2026-04-25 14:00" }
Response: { "success": true, "consultation_id": 1, "assigned_coach": {...} }
```

### 2. `GET /consultation/suggest-coach`
Get the most available coach without creating a consultation.
```json
Response: {
    "suggested_coach": {
        "id": 2,
        "name": "Sarah Smith",
        "email": "sarah@example.com",
        "phone": "+1234567890"
    }
}
```

### 3. `GET /consultation/coaches-availability`
View all coaches with their consultation counts.
```json
Response: {
    "coaches": [
        {"id": 1, "name": "John Doe", "consultations": 5},
        {"id": 2, "name": "Sarah Smith", "consultations": 2}
    ],
    "total": 2
}
```

### 4. `GET /consultation/suggest-coach-from-date?fromDate=2026-04-25`
Suggest coach filtering by future consultations from a specific date.

## Logging

All operations are logged:

```
INFO: Selected coach via Gemini AI [coach_id: 2]
INFO: Selected coach via fallback method [coach_id: 1, consultations: 2]
WARNING: Gemini API failed, using fallback [error: timeout]
```

View logs:
- Dev: `var/log/dev.log`
- Prod: `var/log/prod.log`

## Error Handling

```php
try {
    $coach = $aiService->suggestMostAvailablePsy();
    
    if ($coach === null) {
        // No coaches available (shouldn't happen if coaches exist)
    }
} catch (\Exception $e) {
    // Handle unexpected errors
    // Service automatically falls back, so this is rare
}
```

## Testing

Run existing tests:
```bash
./bin/phpunit
```

The service uses dependency injection, making it easy to mock:
```php
$mockHttpClient = $this->createMock(HttpClientInterface::class);
$mockEntityManager = $this->createMock(EntityManagerInterface::class);
$service = new AiService($mockEntityManager, $mockHttpClient, $logger);
```

## Performance

| Operation | Query Count | Notes |
|-----------|------------|-------|
| Get coaches with counts | 1 query | GROUP BY optimization |
| Gemini API call | ~1s | Only if key configured |
| Fallback selection | 0 queries | Pure PHP sorting |

## Database Requirements

No schema changes needed! The service uses existing tables:
- `user` (existing)
- `consultation_en_ligne` (existing)

## Security

- ✅ API key stored in environment variable (never in code)
- ✅ No SQL injection (Doctrine parameterized queries)
- ✅ No N+1 queries (optimized)
- ✅ Proper logging (no sensitive data in logs)

## Troubleshooting

### "No coaches available"
→ Check if users with role='Coach' exist in database

### Gemini API key error
→ Verify `GEMINI_API_KEY` in `.env` is correct

### Coach selection always uses fallback
→ Check Gemini API response format in logs

### Slow queries
→ Add index on `user.role` and `consultation_en_ligne.psychologue_id`

## Next Steps

1. **Get API Key**: https://makersuite.google.com/app/apikey
2. **Add to `.env`**: `GEMINI_API_KEY=your-key`
3. **Test**: `GET /consultation/suggest-coach`
4. **Integrate**: Use in your consultation booking flow

## Integration Checklist

- [ ] Add `GEMINI_API_KEY` to `.env`
- [ ] Test `/consultation/suggest-coach` endpoint
- [ ] Update consultation creation form to use auto-assignment
- [ ] Test fallback (remove API key temporarily)
- [ ] Add coaches availability widget to dashboard
- [ ] Monitor logs for any issues
- [ ] Add unit tests for your specific use cases

---

**Ready to use!** The service is production-ready and fully documented.
