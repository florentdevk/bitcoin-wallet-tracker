<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\WatchedAddress;
use App\Repository\WatchedAddressRepository;
use App\Security\Voter\AddressVoter;
use App\Service\Bitcoin\AddressInfoProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/addresses', name: 'address_')]
final class AddressController extends AbstractController
{
    public function __construct(
        private readonly AddressInfoProvider $addressInfoProvider,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(WatchedAddressRepository $repository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $addresses = $repository->findBy(['owner' => $user]);

        return $this->json(array_map(static fn (WatchedAddress $a) => [
            'id' => $a->getId(),
            'address' => $a->getAddress(),
            'label' => $a->getLabel(),
            'createdAt' => $a->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ], $addresses));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(WatchedAddress $watchedAddress): JsonResponse
    {
        $this->denyAccessUnlessGranted(AddressVoter::VIEW, $watchedAddress);

        $balance = $this->addressInfoProvider->getBalance($watchedAddress->getAddress());
        $transactions = $this->addressInfoProvider->getTransactions($watchedAddress->getAddress());

        return $this->json([
            'id' => $watchedAddress->getId(),
            'address' => $watchedAddress->getAddress(),
            'label' => $watchedAddress->getLabel(),
            'createdAt' => $watchedAddress->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'balance' => $balance,
            'transactions' => $transactions,
        ]);
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

        /** @var User $user */
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
