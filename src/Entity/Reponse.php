<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Question;

#[ORM\Entity]
class Reponse
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

        #[ORM\ManyToOne(targetEntity: Question::class, inversedBy: "reponses")]
    #[ORM\JoinColumn(name: 'question_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Question $question_id;

    #[ORM\Column(type: "string", length: 255)]
    private string $option_text;

    #[ORM\Column(type: "boolean")]
    private bool $is_correct;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getQuestion_id()
    {
        return $this->question_id;
    }

    public function setQuestion_id($value)
    {
        $this->question_id = $value;
    }

    public function getOption_text()
    {
        return $this->option_text;
    }

    public function setOption_text($value)
    {
        $this->option_text = $value;
    }

    public function getIs_correct()
    {
        return $this->is_correct;
    }

    public function setIs_correct($value)
    {
        $this->is_correct = $value;
    }
}
