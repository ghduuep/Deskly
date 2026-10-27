<?php

namespace App\Enum;

enum KnowledgeArticleStatus: string
{
    case Draft = "draft";
    case Published = "published";
    case Archived = "archived";
}
