<?php

namespace Nexus\ProjectManagement\Contracts;

interface TaskDependencyInterface
{
    public function getId(): int;
    public function getTaskId(): int;
    public function getDependsOnTaskId(): int;
}