<?php

namespace Nexus\ProjectManagement\Contracts;

interface TaskInterface
{
    public function getId(): int;
    public function getProjectId(): int;
    public function getTitle(): string;
    public function getDescription(): ?string;
    public function getAssigneeId(): ?int;
    public function getDueDate(): ?\DateTime;
    public function getPriority(): string;
    public function getStatus(): string;
    public function getParentTaskId(): ?int;
}