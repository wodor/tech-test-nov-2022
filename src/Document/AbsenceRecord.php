<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
 */
class AbsenceRecord
{
    /**
     * @MongoDB\Id(strategy="NONE", type="string")
     */
    protected string $id;

    /**
     * @MongoDB\Field(type="date");
     */
    protected \DateTime $registeredAt;

    public function __construct(string $studentId)
    {
        $this->id = $studentId;
        $this->registeredAt = new \DateTime();
    }

    public function getStudentId(): string
    {
        return $this->id;
    }
}
