<?php

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();

$collection->add('article_homepage', new Route('/', array(
    '_controller' => 'ArticleBundle:Default:index',
)));

return $collection;
