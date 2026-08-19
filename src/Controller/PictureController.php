<?php

namespace App\Controller;

use App\Entity\Picture;
use App\Repository\PictureRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/picture', name: 'app_api_picture_')]
final class PictureController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private PictureRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
    ){
    }

    #[Route(methods: 'POST')]
    public function new(Request $request): JsonResponse
    {
        $picture = $this->serializer->deserialize($request->getContent(), Picture::class, 'json');
        $picture->setCreatedAt(new DateTimeImmutable());


        $this->manager->persist($picture);
        $this->manager->flush();

        $responseData = $this->serializer->serialize($picture, 'json');
        $location = $this->urlGenerator->generate(
            'app_api_restaurant_show',
            ['id' => $picture->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return new JsonResponse($responseData, Response::HTTP_CREATED, ["Location" => $location], true);
    }
    #[Route('/{id}', name: 'show', methods: 'GET')]
    public function show(int $id): Response
    {
        $picture = $this->repository->findOneBy(['id' => $id]);

        if ($picture) {
            $responseData = $this->serializer->serialize($picture, 'json');

            return new JsonResponse($responseData, Response::HTTP_OK, [], true);
        }

        return new jsonResponse(null, Response::HTTP_NOT_FOUND);
    }
    #[Route('/{id}', name: 'edit', methods: 'PUT')]
    public function edit(int $id, Request $request): Response
    {
        $picture = $this->repository->findOneBy(['id' => $id]);

        if ($picture) {
            $picture = $this->serializer->deserialize(
                $request->getContent(),
                Picture::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $picture]
            );
            $picture->setUpdatedAt(new DateTimeImmutable());

            $this->manager->flush();

            return new jsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new jsonResponse(null, Response::HTTP_NOT_FOUND);
    }
    #[Route('/{id}', name: 'delete', methods: 'DELETE')]
    public function delete(int $id): Response
    {
        $picture = $this->repository->findOneBy(['id' => $id]);
        if (!$picture) {
            throw $this->createNotFoundException("No Picture found for {$id} id");
        }

        $this->manager->remove($picture);
        $this->manager->flush();

        return $this->json(['message' => "Picture resource deleted"], Response::HTTP_NO_CONTENT);
    }
}
