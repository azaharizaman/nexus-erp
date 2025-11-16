<?php

declare(strict_types=1);

namespace Nexus\Crm\Enums;

/**
 * Pipeline Action Types
 */
enum PipelineAction: string
{
    case UPDATE_FIELD = 'update_field';
    case ASSIGN_USERS = 'assign_users';
    case SEND_NOTIFICATION = 'send_notification';
    case CREATE_TIMER = 'create_timer';
    case EXECUTE_INTEGRATION = 'integration';
}