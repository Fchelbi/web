<?php

/**
 * ============================================================================
 * GEMINI AI SERVICE - INTEGRATION EXAMPLES
 * ============================================================================
 * 
 * This file contains real-world examples of how to integrate the AiService
 * into your Symfony application.
 */

// ============================================================================
// EXAMPLE 1: Simple Controller Integration
// ============================================================================

namespace App\Examples;

use App\Service\AiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SimpleControllerExample extends AbstractController
{
    #[Route('/suggestion', name: 'simple_suggestion')]
    public function getSuggestion(AiService $aiService): Response
    {
        // Get the most available coach
        $coach = $aiService->suggestMostAvailablePsy();

        if (!$coach) {
            return $this->json(['error' => 'No coaches available'], 503);
        }

        return $this->json([
            'coach_id' => $coach->getId(),
            'coach_name' => $coach->getName(),
            'coach_email' => $coach->getEmail(),
        ]);
    }
}

// ============================================================================
// EXAMPLE 2: Command for Batch Processing
// ============================================================================

namespace App\Command;

use App\Entity\ConsultationEnLigne;
use App\Service\AiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'consultation:auto-assign-pending',
    description: 'Auto-assign all pending consultations to available coaches'
)]
class AutoAssignPendingConsultationsCommand extends Command
{
    public function __construct(
        private AiService $aiService,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $consultations = $this->entityManager
            ->getRepository(ConsultationEnLigne::class)
            ->findBy(['statut' => ConsultationEnLigne::STATUT_EN_ATTENTE]);

        $assigned = 0;
        $failed = 0;

        foreach ($consultations as $consultation) {
            // Skip if already assigned
            if ($consultation->getPsychologue() !== null) {
                continue;
            }

            $coach = $this->aiService->suggestMostAvailablePsy();

            if ($coach) {
                $consultation->setPsychologue($coach);
                $assigned++;
                $output->writeln("✓ Assigned consultation {$consultation->getId()} to {$coach->getName()}");
            } else {
                $failed++;
                $output->writeln("✗ Failed to assign consultation {$consultation->getId()}");
            }
        }

        $this->entityManager->flush();

        $output->writeln("\n✓ Completed: $assigned assigned, $failed failed");
        return Command::SUCCESS;
    }
}

// ============================================================================
// EXAMPLE 3: Event Listener for Automatic Assignment
// ============================================================================

namespace App\EventListener;

use App\Entity\ConsultationEnLigne;
use App\Service\AiService;
use Doctrine\ORM\Events;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;

#[AsDoctrineListener(event: Events::prePersist, entity: ConsultationEnLigne::class)]
class AutoAssignCoachListener
{
    public function __construct(private AiService $aiService) {}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $consultation = $args->getObject();

        // Only auto-assign if no coach is assigned yet
        if ($consultation->getPsychologue() === null) {
            $coach = $this->aiService->suggestMostAvailablePsy();

            if ($coach) {
                $consultation->setPsychologue($coach);
            }
        }
    }
}

// ============================================================================
// EXAMPLE 4: Service for Consultation Management
// ============================================================================

namespace App\Service;

use App\Entity\ConsultationEnLigne;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ConsultationManagementService
{
    public function __construct(
        private AiService $aiService,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Create and auto-assign a consultation to the best available coach
     */
    public function createWithAutoAssignment(
        User $patient,
        string $motif,
        \DateTimeInterface $dateConsultation,
    ): ConsultationEnLigne {
        $consultation = new ConsultationEnLigne();
        $consultation->setUser($patient);
        $consultation->setMotif($motif);
        $consultation->setDateConsultation($dateConsultation);
        $consultation->setStatut(ConsultationEnLigne::STATUT_EN_ATTENTE);

        // Auto-assign the best coach
        $coach = $this->aiService->suggestMostAvailablePsy();

        if ($coach) {
            $consultation->setPsychologue($coach);
        }

        $this->entityManager->persist($consultation);
        $this->entityManager->flush();

        return $consultation;
    }

    /**
     * Reassign a consultation to the current best available coach
     */
    public function reassignToAvailableCoach(ConsultationEnLigne $consultation): bool
    {
        $coach = $this->aiService->suggestMostAvailablePsy();

        if (!$coach) {
            return false;
        }

        $consultation->setPsychologue($coach);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Check coach availability
     */
    public function getCoachAvailability(): array
    {
        return $this->aiService->getCoachesWithConsultationCounts();
    }
}

// ============================================================================
// EXAMPLE 5: Form with Coach Selection
// ============================================================================

namespace App\Form;

use App\Entity\ConsultationEnLigne;
use App\Entity\User;
use App\Service\AiService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsultationWithAutoSuggestionType extends AbstractType
{
    public function __construct(private AiService $aiService) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('motif', TextType::class, [
                'label' => 'Reason for consultation',
                'required' => true,
            ])
            ->add('dateConsultation', DateTimeType::class, [
                'label' => 'Consultation date and time',
                'required' => true,
            ])
            ->add('psychologue', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn(User $user) => $user->getName(),
                'label' => 'Select a coach',
                'required' => false,
                'placeholder' => 'Suggest the best available coach',
            ])
        ;

        // Pre-populate with suggested coach
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            // If creating new consultation, suggest a coach
            if (!$data || !$data->getId()) {
                $suggestedCoach = $this->aiService->suggestMostAvailablePsy();
                if ($suggestedCoach) {
                    $form->get('psychologue')->setData($suggestedCoach);
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConsultationEnLigne::class,
        ]);
    }
}

// ============================================================================
// EXAMPLE 6: API Resource with Coach Info
// ============================================================================

namespace App\ApiResource;

use Symfony\Component\Serializer\Annotation\Groups;

class CoachAvailability
{
    #[Groups(['read'])]
    public int $id;

    #[Groups(['read'])]
    public string $name;

    #[Groups(['read'])]
    public int $consultations;

    #[Groups(['read'])]
    public string $email;

    #[Groups(['read'])]
    public ?string $phone;

    #[Groups(['read'])]
    public bool $isRecommended;
}

// ============================================================================
// EXAMPLE 7: Consultation Controller using ConsultationManagementService
// ============================================================================

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\ConsultationManagementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConsultationApiController extends AbstractController
{
    #[Route('/api/consultation/quick-book', name: 'api_consultation_quick_book', methods: ['POST'])]
    public function quickBook(
        Request $request,
        ConsultationManagementService $consultationService,
    ): Response {
        /**
         * Quick-book endpoint: create consultation with auto-assignment
         * 
         * POST /api/consultation/quick-book
         * {
         *     "motif": "Anxiety treatment",
         *     "dateConsultation": "2026-04-25T14:00:00"
         * }
         */

        $data = json_decode($request->getContent(), true);

        if (!isset($data['motif'], $data['dateConsultation'])) {
            return $this->json(['error' => 'Missing required fields'], 400);
        }

        try {
            $dateTime = new \DateTime($data['dateConsultation']);
            
            $consultation = $consultationService->createWithAutoAssignment(
                $this->getUser(),
                $data['motif'],
                $dateTime
            );

            return $this->json([
                'success' => true,
                'consultation_id' => $consultation->getId(),
                'coach' => [
                    'id' => $consultation->getPsychologue()?->getId(),
                    'name' => $consultation->getPsychologue()?->getName(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/api/coaches/availability', name: 'api_coaches_availability', methods: ['GET'])]
    public function getAvailability(
        ConsultationManagementService $consultationService,
    ): Response {
        $availability = $consultationService->getCoachAvailability();

        return $this->json([
            'coaches' => $availability,
            'total' => count($availability),
        ]);
    }
}

// ============================================================================
// EXAMPLE 8: Dashboard Widget Component
// ============================================================================

namespace App\Twig\Components;

use App\Service\AiService;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class CoachAvailabilityWidget
{
    public function __construct(private AiService $aiService) {}

    public function getCoachData(): array
    {
        $coaches = $this->aiService->getCoachesWithConsultationCounts();

        // Add recommendation
        return array_map(
            (fn($coach, $idx) => [...$coach, 'isRecommended' => $idx === 0])
                ->bindTo(null),
            $coaches,
            array_keys($coaches),
        );
    }
}

// ============================================================================
// TEMPLATE USAGE (coaches_availability_widget.html.twig)
// ============================================================================

/*
<div class="coaches-availability">
    <h2>Available Coaches</h2>
    <div class="coaches-list">
        {% for coach in this.getCoachData() %}
            <div class="coach-card {% if coach.isRecommended %}recommended{% endif %}">
                <h3>{{ coach.name }}</h3>
                <p>Current Consultations: <strong>{{ coach.consultations }}</strong></p>
                {% if coach.isRecommended %}
                    <span class="badge">Recommended (Most Available)</span>
                {% endif %}
            </div>
        {% endfor %}
    </div>
</div>
*/

// ============================================================================
// USAGE SUMMARY
// ============================================================================

/*
Quick Integration Steps:

1. SIMPLE: Inject into controller
   public function create(AiService $aiService) {
       $coach = $aiService->suggestMostAvailablePsy();
   }

2. FORM: Auto-suggest in form
   Use ConsultationWithAutoSuggestionType

3. SERVICE: Dedicated business logic
   Use ConsultationManagementService

4. EVENT: Automatic assignment on create
   Use AutoAssignCoachListener

5. COMMAND: Batch processing
   php bin/console consultation:auto-assign-pending

6. API: REST endpoint
   POST /api/consultation/quick-book

7. WIDGET: Dashboard display
   {{ component('coach_availability_widget') }}
*/
