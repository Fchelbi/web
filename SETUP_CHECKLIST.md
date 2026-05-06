# 🚀 Gemini AI Coach Selection - Setup Checklist

## Phase 1: Configuration (5 minutes)

- [ ] Get Gemini API Key
  - Go to: https://makersuite.google.com/app/apikey
  - Click "Create API Key"
  - Copy the key (keep it safe!)

- [ ] Add API Key to `.env`
  ```env
  GEMINI_API_KEY=your-api-key-here
  ```

- [ ] Verify Symfony setup
  ```bash
  symfony check:requirements
  # All should be green
  ```

## Phase 2: Verification (10 minutes)

- [ ] Clear cache
  ```bash
  php bin/console cache:clear
  ```

- [ ] Verify files exist
  ```bash
  ls -la src/Service/AiService.php
  ls -la src/Repository/UserRepository.php
  ls -la src/Controller/ConsultationAiController.php
  ```

- [ ] Check database has coaches
  ```bash
  php bin/console doctrine:query:sql "SELECT id, prenom, nom FROM user WHERE role='Coach'"
  ```
  → Should show at least one coach

- [ ] Test the service (PHP CLI)
  ```bash
  php bin/console tinker
  
  # Inside tinker:
  $service = \App\Service\AiService
  $coach = $service->suggestMostAvailablePsy()
  echo $coach->getName() // Should print coach name
  exit()
  ```

## Phase 3: Integration (15 minutes)

Choose ONE of these integration patterns:

### Option A: Quick-Book API Endpoint
```php
// Already implemented in ConsultationAiController
POST /consultation/auto-assign
// Body: {"motif": "...", "dateConsultation": "..."}
```

- [ ] Test endpoint with cURL
  ```bash
  curl -X POST http://localhost:8000/consultation/auto-assign \
    -H "Content-Type: application/json" \
    -d '{"motif":"Test","dateConsultation":"2026-04-25T14:00:00"}'
  ```

### Option B: Form Integration
- [ ] Copy ConsultationWithAutoSuggestionType from INTEGRATION_EXAMPLES.php
- [ ] Use it in your form builder
- [ ] Coach will be auto-suggested on form load

### Option C: Service Integration
- [ ] Create ConsultationManagementService from INTEGRATION_EXAMPLES.php
- [ ] Inject into your controller
- [ ] Use createWithAutoAssignment() method

### Option D: Event Listener
- [ ] Create AutoAssignCoachListener from INTEGRATION_EXAMPLES.php
- [ ] Consultations auto-assign on creation

### Option E: Command for Batch Processing
```bash
# Already implemented
php bin/console consultation:auto-assign-pending
```
- [ ] Copy command from INTEGRATION_EXAMPLES.php
- [ ] Run to auto-assign all pending consultations

## Phase 4: Testing (10 minutes)

- [ ] Test with Gemini API
  ```bash
  # Make sure GEMINI_API_KEY is set
  php bin/console cache:clear
  curl http://localhost:8000/consultation/suggest-coach
  # Response should include a coach
  ```

- [ ] Test fallback (remove API key)
  ```bash
  # Temporarily unset in .env
  # GEMINI_API_KEY=
  php bin/console cache:clear
  curl http://localhost:8000/consultation/suggest-coach
  # Should still return a coach (using fallback)
  ```

- [ ] Test coaches availability
  ```bash
  curl http://localhost:8000/consultation/coaches-availability
  # Should show list of coaches and their consultation counts
  ```

- [ ] Run unit tests
  ```bash
  php bin/phpunit tests/Service/AiServiceTest.php
  # All tests should pass
  ```

## Phase 5: Production Readiness (20 minutes)

- [ ] Add to version control
  ```bash
  git add src/Service/AiService.php
  git add src/Repository/UserRepository.php
  git add src/Entity/User.php
  git add src/Controller/ConsultationAiController.php
  git add tests/Service/AiServiceTest.php
  git commit -m "feat: Add Gemini AI coach selection service"
  ```

- [ ] Update `.env.prod` with API key
  ```
  GEMINI_API_KEY=your-prod-api-key
  ```

- [ ] Test in production mode
  ```bash
  APP_ENV=prod php bin/console cache:clear --env=prod
  # Test endpoints
  ```

- [ ] Set up monitoring/logging
  ```bash
  # Check logs
  tail -f var/log/prod.log | grep AiService
  # Or in dev
  tail -f var/log/dev.log | grep AiService
  ```

- [ ] Backup database (before deploying)
  ```bash
  pg_dump app > backup.sql
  # or mysqldump depending on your DB
  ```

## Phase 6: Documentation (10 minutes)

- [ ] Document in your project README
  ```markdown
  ## Coach Selection
  
  The system uses Gemini AI to suggest the most available coach.
  - Set `GEMINI_API_KEY` in .env
  - Use `/consultation/suggest-coach` endpoint
  - Falls back to PHP if Gemini fails
  ```

- [ ] Add to team knowledge base
  - Share GEMINI_AI_SETUP.md with team
  - Share INTEGRATION_EXAMPLES.php for patterns

- [ ] Update deployment checklist
  - Ensure GEMINI_API_KEY is set in production
  - Ensure GEMINI_API_KEY is in CI/CD secrets

## Phase 7: Monitoring (Ongoing)

- [ ] Monitor API usage
  - Check Gemini API dashboard for usage
  - Set up quotas/alerts if needed

- [ ] Monitor logs
  ```bash
  # Look for warnings
  grep -i "warning\|error" var/log/prod.log | grep -i "gemini\|coach"
  ```

- [ ] Monitor performance
  - Coach suggestion should take <1.5 seconds
  - If slower, check database indexes

- [ ] Monitor coach distribution
  - Are consultations distributed fairly?
  - Are coaches balanced in workload?

## Troubleshooting Reference

| Problem | Solution |
|---------|----------|
| No coaches found | Add users with role='Coach' to database |
| API key error | Verify GEMINI_API_KEY in .env |
| Slow response | Check if database has indexes on role, psychologue_id |
| Gemini returns wrong coach | Check Gemini API response format in logs |
| Service not found | Ensure src/ is in config/services.yaml |
| Tests fail | Check that mocks match method signatures |

## Command Reference

```bash
# Clear cache
php bin/console cache:clear

# Test endpoints
curl http://localhost:8000/consultation/suggest-coach
curl http://localhost:8000/consultation/coaches-availability
curl -X POST http://localhost:8000/consultation/auto-assign -H "Content-Type: application/json" -d '{"motif":"Test","dateConsultation":"2026-04-25T14:00:00"}'

# Run tests
php bin/phpunit tests/Service/AiServiceTest.php
php bin/phpunit tests/Service/AiServiceTest.php::testSuggestMostAvailablePsyFallback

# Check database
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM user WHERE role='Coach'"

# Interactive testing
php bin/console tinker

# View logs
tail -f var/log/dev.log | grep -i coach
```

## File Manifest

**Created:**
- src/Service/AiService.php
- src/Repository/UserRepository.php
- src/Controller/ConsultationAiController.php
- tests/Service/AiServiceTest.php

**Modified:**
- src/Entity/User.php (added repository mapping)

**Documentation:**
- GEMINI_AI_SETUP.md
- GEMINI_QUICK_REFERENCE.md
- INTEGRATION_EXAMPLES.php
- IMPLEMENTATION_COMPLETE.md
- This file

## Support

If you encounter issues:

1. Check logs: `var/log/dev.log` or `var/log/prod.log`
2. Review GEMINI_AI_SETUP.md (Troubleshooting section)
3. Check INTEGRATION_EXAMPLES.php for your use case
4. Verify database: Check coaches exist with correct role

## Success Indicators

✅ Setup is successful if:

- [ ] Service class loads without errors
- [ ] `GET /consultation/suggest-coach` returns a coach
- [ ] `GET /consultation/coaches-availability` shows coaches
- [ ] Tests pass: `php bin/phpunit tests/Service/AiServiceTest.php`
- [ ] Logs show "Selected coach via Gemini AI" or "Selected coach via fallback method"
- [ ] Consultation can be created with auto-assigned coach

## Next Steps

After setup:

1. Choose an integration pattern from INTEGRATION_EXAMPLES.php
2. Add to your consultation booking flow
3. Test with real data
4. Monitor performance
5. Gather user feedback

---

**Estimated Total Time: 70 minutes**

Ready to proceed? Start with Phase 1! 🚀
