<?php

namespace App\DataFixtures;

use App\Document\AbsenceRecord;
use App\Document\Lesson as LessonDocument;
use App\Entity\Lesson;
use App\Entity\Student;
use App\Entity\StudentGroup;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{

    private DocumentManager $dm;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    public function load(ObjectManager $manager): void
    {
        $this->dm->getDocumentCollection(LessonDocument::class)->drop();
        $this->dm->getDocumentCollection(AbsenceRecord::class)->drop();
        $groups = [];
        for ($i = 1; $i <= 10; $i++) {
            $group = new StudentGroup();
            $group->setName("Group $i");
            $groups[$i] = $group;

            for ($j = 1; $j <= rand(10, 20); $j++) {
                $student = new Student();
                $student->setName("Student $i-$j");
                $student->setSurname('Test');
                $manager->persist($student);
                $group->addStudent($student);
            }

            $manager->persist($group);

            $manager->flush();
        }

        for ($i = 1; $i <= 10; $i++) {
            for ($j = 1; $j <= 10; $j++) {
                $lesson = new Lesson();
                $lesson->setStartAt(new \DateTimeImmutable(sprintf("+%d day", $j)));
                $lesson->setStudentGroup($groups[$i]);
                $manager->persist($lesson);
            }
        }

        $manager->flush();
    }
}
