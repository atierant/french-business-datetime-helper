<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class IndexController extends AbstractController
{
    /**
     * @Route(path="index", name="index")
     */
    public function __invoke(): Response
    {
        return $this->render('index.twig', [
            'title' => "French Business Date Helper",
            'id' => "French Business Date Helper",
            'commit_limit' => 5,
        ]);
    }
}
