<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\WatchedAddress;
use App\Repository\WatchedAddressRepository;
use App\Security\Voter\AddressVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/addresses', name: 'address_')]
final class AddressController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(WatchedAddressRepository $repository): JsonResponse
    {
        $addresses = $repository->findBy(['owner' => $this->getUser()]);

        return $this->json(array_map(fn(WatchedAddress $a) => [
            'id' => $a->getId(),
            'address' => $a->getAddress(),
            'label' => $a->getLabel(),
            'createdAt' => $a->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ], $addresses));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        WatchedAddressRepository $repository,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data['address'])) {
            return $this->json(['error' => 'Address is required'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();

        if ($repository->findOneBy(['address' => $data['address'], 'owner' => $user])) {
            return $this->json(['error' => 'Address already watched'], Response::HTTP_CONFLICT);
        }

        $watchedAddress = new WatchedAddress();
        $watchedAddress->setAddress($data['address']);
        $watchedAddress->setLabel($data['label'] ?? null);
        $watchedAddress->setOwner($user);

        $em->persist($watchedAddress);
        $em->flush();

        return $this->json([
            'id' => $watchedAddress->getId(),
            'address' => $watchedAddress->getAddress(),
            'label' => $watchedAddress->getLabel(),
            'createdAt' => $watchedAddress->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(
        WatchedAddress $watchedAddress,
        EntityManagerInterface $em,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(AddressVoter::DELETE, $watchedAddress);

        $em->remove($watchedAddress);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}