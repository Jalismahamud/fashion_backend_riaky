<?php


namespace App\Enums;

enum PageEnum: string
{
    const AUTH     =   'login';
    
    case HOME      =   'home';

    case SERVICE   =   'service';

    case FEATURES  =   'features';

    case BLOG      =   'blog';

    case CONTACT   =   'contact';

    case NEWS      =   'news';
}