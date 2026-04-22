<?php

namespace App\Enums;


enum SectionEnum: string
{
    const BG                       = 'bg_image';

    case HOME_BANNER                = 'home_banner';
    case HOME_BANNERS               = 'home_banners';
    case HOME_EXPLORE               = 'home_explore';

    case SERVICE_BANNER             = 'service_banner';
    case SERVICE_CALL_TO_ACTION     = 'service_call_to_action';


    case FEATURES_BANNER            = 'features_banner';
    case FEATURES_EXPERIENCE        = 'features_experience';

   
    case BLOG_BANNER                = 'blog_banner';
    case BLOG                       = 'blog';
    case BLOGS                      = 'blogs';
  

    case CONTACT_BANNER             = 'contact_banner';
    case CONTACT_SUBSCRIBER         = 'contact_subscribe';


    case NEWSLETTER                       = 'newsletter';
    case NEWSLETTERS                      = 'newsletters';



}