<?php
namespace App\Controller;

use App\Entity\Quiz;
use App\Form\QuizType;
use App\Repository\FormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuizController extends AbstractController
{
    #[Route('/formation/{id}/quiz', name: 'app_quiz_manage')]
    public function manage(int $id, FormationRepository $formationRepo): Response
    {
        $formation = $formationRepo->find($id);
        if (!$formation) throw $this->createNotFoundException();

        return $this->render('formation/quiz.html.twig', [
            'formation' => $formation,
            'quiz'      => $this->findQuiz($formation),
        ]);
    }

    #[Route('/formation/{id}/quiz/edit', name: 'app_quiz_edit')]
    public function edit(
        int $id,
        Request $request,
        FormationRepository $formationRepo,
        EntityManagerInterface $em
    ): Response {
        $formation = $formationRepo->find($id);
        if (!$formation) throw $this->createNotFoundException();

        $quiz  = $this->findQuiz($formation);
        $isNew = ($quiz === null);

        if ($isNew) {
            $quiz = new Quiz();
            $quiz->setFormation_id($formation);  // owning side FK
            $formation->addQuiz($quiz);           // inverse side collection
        }

        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            // ----------------------------------------------------------------
            // KEY FIX: dump ALL validation errors as visible flash messages.
            // Previously the form failed silently — the user saw nothing and
            // nothing was saved. Now every broken field is reported.
            // ----------------------------------------------------------------
            if (!$form->isValid()) {
                foreach ($form->getErrors(deep: true, flatten: true) as $err) {
                    $field = $err->getOrigin() ? $err->getOrigin()->getName() : '?';
                    $this->addFlash('error', '⚠️ [' . $field . '] ' . $err->getMessage());
                }
            } else {

                $logicOk = true;

                foreach ($quiz->getQuestions() as $i => $question) {

                    // Wire owning sides so Doctrine knows the parent
                    $question->setQuiz($quiz);
                    foreach ($question->getReponses() as $rep) {
                        $rep->setQuestion($question);
                    }

                    // Test logique 1: at least one correct answer per question
                    $hasCorrect = false;
                    foreach ($question->getReponses() as $rep) {
                        if ($rep->getIsCorrect()) { $hasCorrect = true; break; }
                    }
                    if (!$hasCorrect) {
                        $logicOk = false;
                        $this->addFlash('error', sprintf(
                            '❌ Question %d : cochez au moins une réponse correcte.',
                            $i + 1
                        ));
                    }

                    // Test logique 2: no duplicate answer text in the same question
                    $seen = [];
                    foreach ($question->getReponses() as $rep) {
                        $t = mb_strtolower(trim($rep->getOptionText() ?? ''));
                        if ($t !== '' && in_array($t, $seen, true)) {
                            $logicOk = false;
                            $this->addFlash('error', sprintf(
                                '❌ Question %d : deux réponses identiques détectées.',
                                $i + 1
                            ));
                            break;
                        }
                        $seen[] = $t;
                    }
                }

                // Test logique 3: passing score must be 0-100
                if ($quiz->getPassingScore() < 0 || $quiz->getPassingScore() > 100) {
                    $logicOk = false;
                    $this->addFlash('error', '❌ Le score de passage doit être entre 0 et 100.');
                }

                if ($logicOk) {
                    $em->persist($quiz);
                    $em->flush();
                    $this->addFlash('success', $isNew ? '✅ Quiz créé !' : '✅ Quiz mis à jour !');
                    return $this->redirectToRoute('app_quiz_manage', ['id' => $formation->getId()]);
                }
            }
        }

        return $this->render('quiz/edit.html.twig', [
            'formation' => $formation,
            'quiz'      => $quiz,
            'form'      => $form->createView(),
            'isNew'     => $isNew,
        ]);
    }

    #[Route('/formation/{id}/quiz/preview', name: 'app_quiz_preview')]
    public function preview(int $id, FormationRepository $formationRepo): Response
    {
        $formation = $formationRepo->find($id);
        if (!$formation) throw $this->createNotFoundException();

        $quiz = $this->findQuiz($formation);
        if (!$quiz) {
            $this->addFlash('warning', 'Aucun quiz à afficher.');
            return $this->redirectToRoute('app_quiz_manage', ['id' => $id]);
        }
        return $this->render('quiz/preview.html.twig', [
            'formation' => $formation,
            'quiz'      => $quiz,
        ]);
    }

    #[Route('/formation/{id}/quiz/delete', name: 'app_quiz_delete', methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        FormationRepository $formationRepo,
        EntityManagerInterface $em
    ): Response {
        $formation = $formationRepo->find($id);
        $quiz      = $formation ? $this->findQuiz($formation) : null;

        if ($quiz && $this->isCsrfTokenValid('delete_quiz_' . $quiz->getId(), $request->request->get('_token'))) {
            $em->remove($quiz);
            $em->flush();
            $this->addFlash('success', 'Quiz supprimé.');
        }
        return $this->redirectToRoute('app_quiz_manage', ['id' => $id]);
    }

    private function findQuiz($formation): ?Quiz
    {
        foreach ($formation->getQuizs() as $q) { return $q; }
        return null;
    }
}