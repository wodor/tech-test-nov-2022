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
class Lesson
{
    /**
     * @MongoDB\Id(strategy="NONE", type="string")
     */
    protected string $id;

    /**
     * @EmbedMany(targetDocument=AbsenceRecord::class)
     */
    protected Collection $absenceRecords;

    /**
     * @MongoDB\Field(type="string")
     */
    private string $groupId;


    /**
     * @MongoDB\Field(type="int")
     */
    private int $groupCount;

    /**
     * @MongoDB\Field(type="int", strategy="increment")
     */
    private int $absenceCounter = 0;

    /**
     * @MongoDB\Field(type="bool")
     */
    private bool $isComplete = false;

    public function __construct(
        string $id,
        string $groupId,
    ) {
        $this->id = $id;
        $this->groupId = $groupId;

        $this->absenceRecords = new ArrayCollection();
        $this->absenceCounter = 0;
    }

    public function addRecord(AbsenceRecord $record): void
    {
        $this->absenceRecords->add($record);
        $this->absenceCounter++;
    }

    public function complete(): void
    {
        $this->isComplete = true;
    }

    public function isComplete(): bool
    {
        return $this->isComplete;
    }

    public function setGroupCount(int $count): void
    {
        $this->groupCount = $count;
    }

    public function getAbsenceRecords(): ArrayCollection|Collection
    {
        return $this->absenceRecords;
    }
}
