<?php

namespace App\Domain\Tenancy\Enums;

enum TenantPermission: string
{
    case ManageUsers = 'users.manage';
    case ManageChannels = 'channels.manage';
    case ConfigureAgent = 'agent.configure';
    case ManageKnowledge = 'knowledge.manage';
    case ViewConversations = 'conversations.view';
    case ReplyConversations = 'conversations.reply';
    case ViewAnalytics = 'analytics.view';
}
