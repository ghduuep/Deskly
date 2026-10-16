<?php

namespace App;

enum ArticleStatus: string
{
    case Draft = "draft";
    case Published = "published";
    case Archived = "archived";
}
