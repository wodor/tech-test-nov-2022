<?php

namespace App\Tests\Attendance;

use App\Attendance\AbsenceService;
use App\Attendance\AttendanceServiceException;
use App\Document\Lesson;
use App\Entity\Lesson as LessonEntity;
use App\Entity\StudentGroup;
use App\Repository\LessonRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class AbsenceServiceTest extends TestCase
{
    private DocumentManager $dm;
    private LessonRepository $lessonRepo;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {

        parent::setUp();
        $this->dm = $this->createMock(DocumentManager::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->lessonRepo = $this->createMock(LessonRepository::class);
    }

    public function testRecordingAbsence()
    {
        $lessonId = "111";
        $groupId = "22";
        $studentId = "3333";

        $this->dm->expects($this->once())->method('persist')->with(
            $this->callback(
                fn($lesson): bool => $lesson instanceof Lesson &&
                    $lesson->getAbsenceRecords()->count() === 1 &&
                    $lesson->getAbsenceRecords()->first()->getStudentId() === $studentId
            )
        );
        $sut = new AbsenceService($this->dm, $this->em, $this->lessonRepo);

        $sut->recordAbsence($lessonId, $groupId, $studentId);
    }

    public function testCompleteLesson()
    {
        $lessonId = "111";
        $lesson = new LessonEntity();
        $studentGroup = new StudentGroup();
        $lesson->setStudentGroup($studentGroup);
        $this->lessonRepo->expects($this->once())
            ->method('find')
            ->with($lessonId)
            ->willReturn($lesson);


        $this->dm->expects($this->once())->method('persist')->with(
            $this->callback(
                fn($lesson): bool => $lesson instanceof Lesson &&
                    $lesson->isComplete() == true
            )
        );

        $this->em->expects($this->once())->method('persist')->with(
            $this->callback(
                fn($lesson): bool => $lesson instanceof LessonEntity &&
                    $lesson->isComplete() == true
            )
        );

        $sut = new AbsenceService($this->dm, $this->em, $this->lessonRepo);

        $sut->completeLesson($lessonId);
    }

    public function testCompleteLessonHandlingMissingStudentGroup()
    {
        $this->expectException(AttendanceServiceException::class);
        $lessonId = "111";
        $lesson = new LessonEntity();
        $lesson->setStudentGroup(null);
        $this->lessonRepo->expects($this->once())->method('find')->with($lessonId)->willReturn($lesson);

        $sut = new AbsenceService($this->dm, $this->em, $this->lessonRepo);

        $sut->completeLesson($lessonId);
    }

    public function testCompleteLessonHandlingMissingLesson()
    {
        $this->expectException(AttendanceServiceException::class);
        $this->expectExceptionMessage("Lesson with id '111' not found");
        $lessonId = "111";
        $lesson = new LessonEntity();
        $lesson->setStudentGroup(null);
        $this->lessonRepo->expects($this->once())->method('find')->with($lessonId)->willReturn(null);

        $sut = new AbsenceService($this->dm, $this->em, $this->lessonRepo);

        $sut->completeLesson($lessonId);
    }
}
