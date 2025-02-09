<?php

declare(strict_types=1);

namespace App\Magisterka\Dto;

use App\Magisterka\Enum\ApiContext;
use App\Magisterka\Enum\FileType;
use App\Magisterka\Enum\MethodType;

class CodeReviewValidatorResultDto
{
    protected ?ApiContext $contextType = null;

    protected ?FileType $fileType = null;
    protected ?MethodType $methodType = null;

    public function getContextType(): ?ApiContext
    {
        return $this->contextType;
    }

    public function setContextType(?ApiContext $contextType): self
    {
        $this->contextType = $contextType;

        return $this;
    }

    public function getFileType(): ?FileType
    {
        return $this->fileType;
    }

    public function setFileType(?FileType $fileType): self
    {
        $this->fileType = $fileType;

        return $this;
    }

    public function getMethodType(): ?MethodType
    {
        return $this->methodType;
    }

    public function setMethodType(?MethodType $methodType): self
    {
        $this->methodType = $methodType;

        return $this;
    }



}
