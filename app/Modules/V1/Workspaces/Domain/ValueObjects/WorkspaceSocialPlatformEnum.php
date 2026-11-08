<?php

namespace App\Modules\V1\Workspaces\Domain\ValueObjects;

enum WorkspaceSocialPlatformEnum: string
{
    case LINKEDIN = 'linkedin';
    case FACEBOOK = 'facebook';
    case INSTAGRAM = 'instagram';
    case X = 'x';
    case YOUTUBE = 'youtube';
    case TIKTOK = 'tiktok';
    case SNAPCHAT = 'snapchat';
    case THREADS = 'threads';
    case PINTEREST = 'pinterest';
    case REDDIT = 'reddit';
    case TELEGRAM = 'telegram';
    case WHATSAPP = 'whatsapp';
    case DISCORD = 'discord';
    case GITHUB = 'github';
    case GITLAB = 'gitlab';
    case BEHANCE = 'behance';
    case DRIBBBLE = 'dribbble';
    case MEDIUM = 'medium';
    case STACK_OVERFLOW = 'stack_overflow';
    case TWITCH = 'twitch';
    case OTHER = 'other';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
