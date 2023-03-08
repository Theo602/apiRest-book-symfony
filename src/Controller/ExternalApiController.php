<?php

namespace App\Controller;

 
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExternalApiController extends AbstractController
{
    /**
     * Cette méthode fait appel à la route https://api.github.com/repos/symfony/symfony-docs
     * récupère les données et les transmets telles quelles.
     *
     *
     * @param HttpClientInterface $httpClient
     * @return JsonResponse
     */
    #[Route('/api/external/getSfDoc', name: 'external_api', methods: ['GET'])]
    public function getSymfonyDoc(HttpClientInterface $HttpClient): JsonResponse
    {
        $response = $HttpClient->request(
            'GET',
            'https://api.github.com/repos/symfony/symfony-docs'
        );
        return new JsonResponse($response->getContent(), $response->getStatusCode(), [], true);
    }

}
