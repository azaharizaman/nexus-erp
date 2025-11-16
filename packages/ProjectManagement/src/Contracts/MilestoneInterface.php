<?php

namespace Nexus\ProjectManagement\Contracts;

interface MilestoneInterface
{
    public function getId(): int;
    public function getProjectId(): int;
    public function getName(): string;
    public function getDescription(): ?string;
    public function getDueDate(): \DateTime;
    public function getStatus(): string; // pending, in_review, approved, rejected
    public function getDeliverables(): ?string;
}