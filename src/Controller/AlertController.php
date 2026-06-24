<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Alert;
use App\Entity\User;
use App\Entity\WatchedAddress;
use App\Enum\AlertType;
use App\Repository\AlertRepository;
use App\Security\Voter\AlertVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/alerts', name: 'alert_')]
final class AlertController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(AlertRepository $repository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $alerts = $repository->findByUser($user);

        return $this->json(array_map(static fn (Alert $a) => [
            'id' => $a->getId(),
            'type' => $a->getType()?->value,
            'thresholdValue' => $a->getThresholdValue(),
            'isActive' => $a->isActive(),
            'triggeredAt' => $a->getTriggeredAt()?->format(\DateTimeInterface::ATOM),
            'createdAt' => $a->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'watchedAddressId' => $a->getWatchedAddress()?->getId(),
        ], $alerts));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data['watched_address_id']) || empty($data['type']) || !isset($data['threshold_value'])) {
            return $this->json(['error' => 'watched_address_id, type and threshold_value are required'], Response::HTTP_BAD_REQUEST);
        }

        $watchedAddress = $em->find(WatchedAddress::class, $data['watched_address_id']);

        if (!$watchedAddress) {
            return $this->json(['error' => 'Address not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(AlertVoter::CREATE, $watchedAddress);

        $type = AlertType::tryFrom($data['type']);
        if (!$type) {
            return $this->json([
                'error' => 'Invalid type. Allowed: '.implode(', ', array_column(AlertType::cases(), 'value')),
            ], Response::HTTP_BAD_REQUEST);
        }

        $alert = new Alert();
        $alert->setType($type);
        $alert->setThresholdValue((float) $data['threshold_value']);
        $alert->setWatchedAddress($watchedAddress);

        $em->persist($alert);
        $em->flush();

        return $this->json([
            'id' => $alert->getId(),
            'type' => $alert->getType()?->value,
            'thresholdValue' => $alert->getThresholdValue(),
            'isActive' => $alert->isActive(),
            'createdAt' => $alert->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'watchedAddressId' => $alert->getWatchedAddress()?->getId(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(
        Alert $alert,
        EntityManagerInterface $em,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(AlertVoter::DELETE, $alert);

        $em->remove($alert);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
