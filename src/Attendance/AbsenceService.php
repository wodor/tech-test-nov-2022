<?php

declare(strict_types=1);

namespace App\Attendance;

use App\Document\AbsenceRecord;
use App\Document\Lesson;
use App\Entity\Lesson as LessonEntity;
use App\Entity\StudentGroup;
use App\Repository\LessonRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;

final class AbsenceService
{
    private DocumentManager $dm;
    private EntityManagerInterface $em;
    private LessonRepository $lessonRepository;

    public function __construct(DocumentManager $dm, EntityManagerInterface $em, LessonRepository $lessonRepository)
    {
        $this->dm = $dm;
        $this->em = $em;
        $this->lessonRepository = $lessonRepository;
    }

    /**
     * Blindly trust the data in the request and save it
     * thanks to this approach, the sql database will not become a bottleneck at peak times
     */
    public function recordAbsence(string $lessonId, string $groupId, string $studentId): void
    {
        $lesson = new Lesson(
            $lessonId,
            $groupId,
        );

        // somewhat magically, upsert will append this record without reading anything from db
        $lesson->addRecord(
            new AbsenceRecord(
                $studentId,
            )
        );
        $this->dm->persist($lesson);
        $this->dm->flush();
    }

    /**
     * In this action we can "afford" an sql query, to make sure data in written to mongo is correct
     * we could also insert other denormalised data, like schoolId, topic, dates, etc
     */
    public function completeLesson(string $lessonId): void
    {
        $lessonEntity = $this->lessonRepository->find($lessonId);
        if (!$lessonEntity instanceof LessonEntity) {
            throw new AttendanceServiceException(sprintf("Lesson with id '%s' not found", $lessonId));
        }

        $group = $lessonEntity->getStudentGroup();
        if (!$group instanceof StudentGroup) {
            throw new AttendanceServiceException(sprintf("Lesson '%s' has no group assigned", $lessonId));
        }

        $lesson = new Lesson(
            $lessonId,
            (string) $group->getId(),
        );

        $lesson->complete();
        $lesson->setGroupCount($group->getStudents()->count());

        $this->dm->persist($lesson);
        $this->em->persist($lessonEntity->complete());

        $this->dm->flush();
        $this->em->flush();
    }
}
