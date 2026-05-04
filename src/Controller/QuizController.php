<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\Quiz_result;
use App\Entity\Reponse;
use App\Form\QuizType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;




#[Route('/quiz')]
final class QuizController extends AbstractController
{
    // ------------------------------------------------------------------
    //  CREATE / EDIT  —  /quiz/{id}/edit   (id = formation id)
    //  If the formation already has a quiz → edit it
    //  Otherwise → create a new one
    // ------------------------------------------------------------------

     #[Route('/{id}/new', name: 'app_quiz_new', methods: ['GET', 'POST'])]
public function new(Request $request, Formation $formation, EntityManagerInterface $em): Response
{
    $quiz = new Quiz();
    $quiz->setFormation_id($formation);
    $quiz->setTitle('Quiz — ' . $formation->getTitle());
    $quiz->setPassingScore(60);

    // Seed one empty question with 2 answers
    $q = new Question();
    $q->setQuestionText('');
    $q->setPoints(1);
    $r1 = new Reponse();
    $r1->setOptionText('');
    $r1->setIsCorrect(true);
    $r2 = new Reponse();
    $r2->setOptionText('');
    $r2->setIsCorrect(false);
    $q->addReponse($r1);
    $q->addReponse($r2);
    $quiz->addQuestion($q);

    $form = $this->createForm(QuizType::class, $quiz);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($quiz);
        $em->flush();
        $this->addFlash('success', 'Quiz créé avec succès !');
        return $this->redirectToRoute('app_formation_quiz', ['id' => $formation->getId()]);
    }

    return $this->render('quiz/edit.html.twig', [
        'formation' => $formation,
        'quiz'      => $quiz,
        'form'      => $form,
        'isNew'     => true,
    ]);
}

    #[Route('/{id}/edit', name: 'app_quiz_edit', methods: ['GET', 'POST'])]
    public function edit(
        Formation $formation,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $quiz = $em->getRepository(Quiz::class)
                   ->findOneBy(['formation_id' => $formation]);

        $isNew = false;
        if (!$quiz) {
            $quiz = new Quiz();
            $quiz->setFormation_id($formation);
            $quiz->setTitle('Quiz — ' . $formation->getTitle());
            $isNew = true;
        }

        // If the quiz has no questions yet, seed one empty question with 2 empty answers
        if ($quiz->getQuestions()->isEmpty()) {
            $q = new Question();
            $q->setQuestionText('');
            $q->setPoints(1);
            $r1 = new Reponse();
            $r1->setOptionText('');
            $r1->setIsCorrect(true);
            $r2 = new Reponse();
            $r2->setOptionText('');
            $r2->setIsCorrect(false);
            $q->addReponse($r1);
            $q->addReponse($r2);
            $quiz->addQuestion($q);
        }

        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isNew) {
                $em->persist($quiz);
            }
            $em->flush();

            $this->addFlash('success', $isNew ? 'Quiz créé avec succès !' : 'Quiz mis à jour !');
            return $this->redirectToRoute('app_formation_quiz', ['id' => $formation->getId()]);
        }

        return $this->render('quiz/edit.html.twig', [
            'formation' => $formation,
            'quiz'      => $quiz,
            'form'      => $form,
            'isNew'     => $isNew,
        ]);
    }

    // ------------------------------------------------------------------
    //  DELETE  —  /quiz/{id}/delete   (id = formation id)
    // ------------------------------------------------------------------
    #[Route('/{id}/delete', name: 'app_quiz_delete', methods: ['POST'])]
    public function delete(
        Formation $formation,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $quiz = $em->getRepository(Quiz::class)
                   ->findOneBy(['formation_id' => $formation]);

        if ($quiz && $this->isCsrfTokenValid('delete_quiz_' . $quiz->getId(), $request->request->get('_token'))) {
            $em->remove($quiz);
            $em->flush();
            $this->addFlash('success', 'Quiz supprimé.');
        }

        return $this->redirectToRoute('app_formation_quiz', ['id' => $formation->getId()]);
    }

    // ------------------------------------------------------------------
    //  PREVIEW  —  /quiz/{id}/preview   (id = formation id)
    //  Read-only view of the quiz for the coach/admin
    // ------------------------------------------------------------------
    #[Route('/{id}/preview', name: 'app_quiz_preview', methods: ['GET'])]
    public function preview(Formation $formation, EntityManagerInterface $em): Response
    {
        $quiz = $em->getRepository(Quiz::class)
                   ->findOneBy(['formation_id' => $formation]);

        if (!$quiz) {
            $this->addFlash('error', 'Aucun quiz trouvé pour cette formation.');
            return $this->redirectToRoute('app_formation_quiz', ['id' => $formation->getId()]);
        }

        return $this->render('quiz/preview.html.twig', [
            'formation' => $formation,
            'quiz'      => $quiz,
        ]);
    }

    // ------------------------------------------------------------------
    //  TAKE QUIZ  —  /quiz/{id}/take   (id = formation id)
    //  Patient answers the quiz
    // ------------------------------------------------------------------
    #[Route('/{id}/take', name: 'app_quiz_take', methods: ['GET'])]
    public function take(Formation $formation, EntityManagerInterface $em): Response
    {
        $quiz = $em->getRepository(Quiz::class)
                   ->findOneBy(['formation_id' => $formation]);

        if (!$quiz) {
            $this->addFlash('error', 'Aucun quiz disponible.');
            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
        }

        return $this->render('quiz/take.html.twig', [
            'formation' => $formation,
            'quiz'      => $quiz,
        ]);
    }

    // ------------------------------------------------------------------
    //  SUBMIT QUIZ  —  /quiz/{id}/submit   (id = formation id)
    //  Calculates score, stores result, shows results page
    // ------------------------------------------------------------------
    #[Route('/{id}/submit', name: 'app_quiz_submit', methods: ['POST'])]
    public function submit(
        Formation $formation,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $quiz = $em->getRepository(Quiz::class)
                   ->findOneBy(['formation_id' => $formation]);

        if (!$quiz) {
            $this->addFlash('error', 'Quiz introuvable.');
            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
        }

        $score       = 0;
        $totalPoints = 0;
        $details     = []; // per-question breakdown for the results page

        foreach ($quiz->getQuestions() as $question) {
            $totalPoints += $question->getPoints();
            $submittedId  = (int) $request->request->get('question_' . $question->getId(), 0);

            $correct       = false;
            $correctText   = '';
            $selectedText  = '';

            foreach ($question->getReponses() as $reponse) {
                if ($reponse->getIsCorrect()) {
                    $correctText = $reponse->getOptionText();
                }
                if ($reponse->getId() === $submittedId) {
                    $selectedText = $reponse->getOptionText();
                    if ($reponse->getIsCorrect()) {
                        $correct = true;
                        $score += $question->getPoints();
                    }
                }
            }

            $details[] = [
                'question'     => $question->getQuestionText(),
                'selected'     => $selectedText,
                'correctText'  => $correctText,
                'correct'      => $correct,
                'points'       => $question->getPoints(),
            ];
        }

        $percentage = $totalPoints > 0 ? round(($score / $totalPoints) * 100) : 0;
        $passed     = $percentage >= $quiz->getPassingScore();

        // Save result
        $result = new Quiz_result();
        $result->setQuiz_id($quiz);
        $result->setUser_id(1); // Replace with $this->getUser()->getId_user() later
        $result->setScore($score);
        $result->setTotal_points($totalPoints);
        $result->setPassed($passed);
        $result->setCompleted_at(new \DateTime());
        $em->persist($result);
        $em->flush();

        return $this->render('quiz/result.html.twig', [
            'formation'  => $formation,
            'quiz'       => $quiz,
            'score'      => $score,
            'totalPoints'=> $totalPoints,
            'percentage' => $percentage,
            'passed'     => $passed,
            'details'    => $details,
        ]);
    }

    // ------------------------------------------------------------------
    //  AJAX SEARCH  —  /quiz/search   (recherche dynamique)
    //  Returns JSON for live search across formations
    // ------------------------------------------------------------------
    #[Route('/search', name: 'app_quiz_search', methods: ['GET'], priority: 10)]
    public function search(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $q = trim($request->query->get('q', ''));

        if (strlen($q) < 2) {
            return $this->json([]);
        }

        $qb = $em->createQueryBuilder();
        $results = $qb->select('f')
            ->from(\App\Entity\Formation::class, 'f')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(f.title)', ':search'),
                    $qb->expr()->like('LOWER(f.description)', ':search'),
                    $qb->expr()->like('LOWER(f.category)', ':search')
                )
            )
            ->setParameter('search', '%' . strtolower($q) . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($results as $f) {
            $data[] = [
                'id'          => $f->getId(),
                'title'       => $f->getTitle(),
                'category'    => $f->getCategory(),
                'description' => mb_substr($f->getDescription() ?? '', 0, 80),
                'hasVideo'    => $f->getVideoUrl() !== null,
            ];
        }

        return $this->json($data);
    }
}