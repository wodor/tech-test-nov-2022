<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\ODM\MongoDB\Mapping\Annotations\EmbedMany;

/**
 * @MongoDB\Document
 */
class StudentGroup
{
    /**
     * @MongoDB\Id(strategy="NONE", type="string")
     */
    protected string $id;

    /**
     * @MongoDB\Field(type="date");
     */
    protected \DateTimeImmutable $startAt;

    /**
     * @EmbedMany(targetDocument=Lesson::class)
     */
    protected Collection $lessons;

    public function __construct()
    {
        $this->lessons = new ArrayCollection();
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function setStartAt(\DateTimeImmutable $startAt): void
    {
        $this->startAt = $startAt;
    }

    public function addLesson(Lesson $lessson): void
    {
        $this->lessons->add($lessson);
    }
}
