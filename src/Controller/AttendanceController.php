<?php

namespace App\Controller;

use App\Attendance\AbsenceService;
use App\Attendance\AttendanceServiceException;
use App\Document\Lesson;
use App\Repository\LessonRepository;
use App\Repository\StudentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AttendanceController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['get'])]
    public function index(LessonRepository $lessons, StudentRepository $studentRepository): Response
    {
        return $this->render(
            'index.html.twig',
            [
                'lessons' => $lessons->findAll(),
                'students' => $studentRepository->findAll(),
            ]
        );

    }


    #[Route('/lesson/{lessonId}', name: 'lesson', methods: ['get'])]
    public function lesson(string $lessonId, LessonRepository $lessons, Request $request, DocumentManager $dm): Response
    {
        $lesson = $lessons->find($lessonId);
        $absentees = [];

        if ($lesson->isComplete()) {
            $lessonDocument = $dm->find(Lesson::class, $lesson->getId());
            if ($lessonDocument instanceof Lesson) {
                foreach ($lessonDocument->getAbsenceRecords()->toArray() as $record) {
                    $absentees[$record->getStudentId()] = $record;
                }
            }
        }

        return $this->render(
            'lesson.html.twig',
            [
                'absentees' => $absentees,
                'lesson' => $lesson,
                'group' => $lesson->getStudentGroup(),
                'students' => $lesson->getStudentGroup()->getStudents(),
            ]
        );
    }

    #[Route('/lesson/{lessonId}/complete', name: 'app_lesson_complete', methods: ['post', 'get'])]
    public function completeLesson(
        string $lessonId,
        AbsenceService $absenceService,
        LoggerInterface $logger
    ): JsonResponse {
        try {
            $logger->info("Completing Lesson", ['lessonId' => $lessonId]);
            $absenceService->completeLesson($lessonId);

            return $this->json(
                [
                    'message' => 'ok',
                ]
            );
        } catch (AttendanceServiceException $e) {
            $logger->error("Failed completing lesson", ['msg' => $e->getMessage()]);
            return $this->json(
                [
                    'message' => $e->getMessage(),
                ],
                400
            );
        } catch (\Throwable $e) {
            $logger->error("Failed completing lesson", ['msg' => $e->getMessage()]);
            $logger->error($e->getMessage());
            return $this->json(
                [
                    'message' => 'there was a problem',
                ],
                500
            );
        }
    }

    #[Route('/group/{groupId}/lesson/{lessonId}/absence/{studentId}', name: 'app_record_absence', methods: ['post'])]
    public function recordAbsence(
        string $groupId,
        string $lessonId,
        string $studentId,
        LoggerInterface $logger,
        AbsenceService $absenceService
    ): JsonResponse {
        try {
            $logger->info("Saving absence", ['lessonId' => $lessonId, 'groupId' => $groupId, 'studentId' => $studentId]);
            $absenceService->recordAbsence($lessonId, $groupId, $studentId);

            return $this->json(
                [
                    'message' => 'ok',
                ]
            );
        } catch (AttendanceServiceException $e) {
            $logger->error($e->getMessage());
            return $this->json(
                [
                    'message' => $e->getMessage(),
                ],
                400
            );
        } catch (\Throwable $e) {
            $logger->error($e->getMessage());
            return $this->json(
                [
                    'message' => 'there was a problem',
                ],
                500
            );
        }
    }

    #[Route('/stats', name: 'app_stats', methods: ['get'])]
    public function stats(DocumentManager $dm): JsonResponse
    {
        $results = $dm->getDocumentCollection(Lesson::class)->aggregate(
            [
                ['$match' => ['isComplete' => true]],
                ['$group' => [
                    '_id' => '$groupId',
                    'expectedAttendance' => ['$sum' => '$groupCount'],
                    'absences' => ['$sum' => '$absenceCounter'],
                ]],
                ['$project' => [
                    'groupId' => '$_id',
                    'absences' => 1,
                    'expectedAttendance' => 1,
                    'attendance' => ['$subtract' => [1, ['$divide' => ['$absences', '$expectedAttendance']]]]]],
                ['$sort' => ['attendance' => -1]],
            ]
        );

        return new JsonResponse(['data' => iterator_to_array($results)]);
    }

    #[Route('/student/{studentId}/stats', name: 'app_stats_student', methods: ['get'])]
    public function studentStats(
        string $studentId,
        StudentRepository $studentRepository,
        LessonRepository $lessonRepository,
        DocumentManager $dm
    ): JsonResponse {
        $student = $studentRepository->find($studentId);
        if (null === $student) {
            return new JsonResponse(['message' => 'Student not found'], 404);
        }
        $absenceCount = $dm->getDocumentCollection(Lesson::class)->countDocuments(
            ['absenceRecords._id' => $studentId]
        );
        $lessonCount = $lessonRepository->count(['studentGroup' => $student->getStudentGroup(), 'isComplete' => true]);

        return new JsonResponse([
            'absences' => $absenceCount,
            'lessons' => $lessonCount,
            'attendance' => $lessonCount > 0 ? 1 - $absenceCount / $lessonCount : null,
        ]);
    }
}
