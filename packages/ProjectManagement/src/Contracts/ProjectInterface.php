<?php

namespace Nexus\ProjectManagement\Contracts;

interface ProjectInterface
{
    public function getId(): int;
    public function getName(): string;
    public function getDescription(): ?string;
    public function getClientId(): ?int;
    public function getProjectManagerId(): int;
    public function getStartDate(): \DateTime;
    public function getEndDate(): ?\DateTime;
    public function getStatus(): string;
    public function getBudget(): ?float;
    public function getTenantId(): int;
}