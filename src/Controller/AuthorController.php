<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Author;
use App\Repository\BookRepository;
use App\Repository\AuthorRepository;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
//use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class AuthorController extends AbstractController
{

    /**
     * Cette méthode permet de récupérer l'ensemble des auteurs. 
     *
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste des auteurs",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Book::class, groups={"getAuthor"}))
     *     )
     * )
     * 
     * @OA\Tag(name="Author")
     * 
     * @param AuthorRepository $authorRepository
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/author', name: 'author', methods: ['GET'])]
    public function getAllAuthor(AuthorRepository $authorRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
    {

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getAllAuthor-" . $page . "-" . $limit;

        $jsonAuthorList = $cachePool->get($idCache, function (ItemInterface $item) use ($authorRepository, $page, $limit, $serializer) {
            $item->tag("bookCache");
            $authorList = $authorRepository->findAllWithPagination($page, $limit);
            $context = SerializationContext::create()->setGroups(["getAuthor"]);
            return $serializer->serialize($authorList, 'json', $context);
        });

        return new JsonResponse($jsonAuthorList, Response::HTTP_OK, [], true);
    }

    /**
     * Cette méthode permet de récupérer un auteur en particulier en fonction de son id. 
     *
     * @OA\Response(
     *     response=200,
     *     description="Retourne un auteur",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Book::class, groups={"getAuthor"}))
     *     )
     * )
     * 
     * @OA\Tag(name="Author")
     *  
     * @param Author $author
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/author/{id}', name: 'detailsAuthor', methods: ['GET'])]
    public function getDetailAuthor(Author $author, SerializerInterface $serializer): JsonResponse
    {
        $context = SerializationContext::create()->setGroups(["getAuthor"]);
        $jsonAuthor = $serializer->serialize($author, 'json', $context);
        return new JsonResponse($jsonAuthor, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
     * Cette méthode supprime un auteur en fonction de son id. 
     * 
     * @OA\Response(
     *     response=204,
     *     description="Supprime un auteur"
     * )
     * 
     * @OA\Tag(name="Author")
     * 
     * @param Author $author
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route('/api/author/delete/{id}', name: 'deleteAuthor', methods: ['DELETE'])]
    public function deleteAuthor(Author $author, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse
    {

        $em->remove($author);
        $em->flush();

        // On vide le cache
        $cachePool->invalidateTags(["bookCache"]);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Cette méthode permet de Créer un nouvel auteur. 
     *
     *@OA\RequestBody(
     *     description="créer un auteur",
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema (
     *              type="object",
     *              @OA\Property(property="firstname", description="Le prénom de l'auteur", type="string"),
     *              @OA\Property(property="lastname", description="Le nom de l'auteur", type="string"),
     *        )
     *     )
     *)

     * @OA\Response(
     *     response=201,
     *     description="Créer un auteur",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Book::class, groups={"getAuthor"}))
     *     )
     * ) 
     *  
     * @OA\Tag(name="Author")
     *     
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @param UrlGeneratorInterface $urlGenerator
     * @return JsonResponse
     */
    #[Route('/api/author/create', name: 'createAuthor', methods: ['POST'])]
    public function createAuthor(EntityManagerInterface $em, Request $request, SerializerInterface $serializer, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $author = $serializer->deserialize($request->getContent(), Author::class, 'json');

        // On vérifie les erreurs
        $errors = $validator->validate($author);

        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($author);
        $em->flush();

        // On vide le cache
        $cachePool->invalidateTags(["bookCache"]);

        $context = SerializationContext::create()->setGroups(["getAuthor"]);
        $jsonAuthor = $serializer->serialize($author, 'json', $context);

        $location = $urlGenerator->generate('detailsAuthor', ['id' => $author->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonAuthor, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
     * Cette méthode permet de mettre à jour un auteur.
     *  
     *@OA\RequestBody(
     *     description="Modifié un auteur",
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema (
     *              type="object",
     *              @OA\Property(property="firstname", description="Le prénom de l'auteur", type="string"),
     *              @OA\Property(property="lastname", description="Le nom de l'auteur", type="string"),
     *        )
     *     )
     *)
     * 
     * @OA\Response(
     *     response=204,
     *     description="Modifié un auteur"
     * ) 
     * @OA\Tag(name="Author")
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param Author $currentAuthor
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route('/api/author/edit/{id}', name: 'editAuthor', methods: ['PUT'])]
    public function editAuthor(EntityManagerInterface $em, Request $request, SerializerInterface $serializer, Author $currentAuthor, TagAwareCacheInterface $cachePool, ValidatorInterface $validator): JsonResponse
    {
        // $author = $serializer->deserialize($request->getContent(), Author::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAuthor]);

        $newAuthor = $serializer->deserialize($request->getContent(), Author::class, 'json');
        $currentAuthor->setFirstname($newAuthor->getFirstname());
        $currentAuthor->setLastname($newAuthor->getLastname());

        $errors = $validator->validate($currentAuthor);

        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($newAuthor);
        $em->flush();

        // On vide le cache
        $cachePool->invalidateTags(["bookCache"]);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
