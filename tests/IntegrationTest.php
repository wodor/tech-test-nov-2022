<?php

namespace App\Tests;

use App\Attendance\AbsenceService;
use App\DataFixtures\AppFixtures;
use App\Document\Lesson as LessonDocument;
use App\Entity\Lesson;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class IntegrationTest extends KernelTestCase
{
    private ?DatabaseToolCollection $databaseTool;
    private ?EntityManagerInterface $entityManager;
    private ?AbsenceService $absenceService;
    private ?DocumentManager $documentManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->databaseTool = self::getContainer()->get(DatabaseToolCollection::class);
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->documentManager = self::getContainer()->get(DocumentManager::class);
        $this->absenceService = self::getContainer()->get(AbsenceService::class);
    }

    public function testAll()
    {
        // not the perfect aproach to rely on fixtures in test but ok for demo
        $this->databaseTool->get()->loadFixtures([
            AppFixtures::class,
        ]);

        $lessonRepository = $this->entityManager->getRepository(Lesson::class);
        /** @var Lesson[] $lessons */
        $lessons = $lessonRepository->findAll();

        foreach ($lessons as $lesson) {
            foreach ($lesson->getStudentGroup()->getStudents() as $student) {
                if (rand(0, 100) < 30) {
                    $this->absenceService->recordAbsence((string) $lesson->getId(), (string) $lesson->getStudentGroup()->getId(), (string) $student->getId());
                }
            }
            $this->absenceService->completeLesson((string) $lesson->getId());
        }

        $completedLessonsCount = $lessonRepository->count(['isComplete' => true]);
        $lessonDocs = $this->documentManager->getRepository(LessonDocument::class)->findAll();

        $this->assertEquals(count($lessons), $completedLessonsCount);
        $this->assertEquals(count($lessons), count($lessonDocs));
    }
}
