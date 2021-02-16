<?php

declare(strict_types=1);

namespace App\Controller;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class Controller extends AbstractController
{
    function index()
    {
        return new JsonResponse('ReallyDirty API v1.0');
    }

    function doctor(Request $request)
    {
        /** @var EntityManagerInterface $doctrineManager */
        $doctrineManager = $this->getDoctrine()->getManager();

        if ($request->getMethod() === 'GET') {
            $doctorId = intval($request->get('id'));
            $doctor = $this->getDoctorById($doctorId, $doctrineManager);
            return $this->respondWithDoctor($doctor);
        } elseif ($request->getMethod() === 'POST') {
            $doctorId = $this->addDoctor($request, $doctrineManager);
            return new JsonResponse(['id' => $doctorId]);
        }

        return new JsonResponse([], 400);
    }

    /**
     * @param DoctorEntity|null $doctorEntity
     * @return JsonResponse
     */
    private function respondWithDoctor(?DoctorEntity $doctorEntity): JsonResponse
    {
        if (is_null($doctorEntity)) {
            return new JsonResponse([], 404);
        }

        return new JsonResponse(
            [
                'id' => $doctorEntity->getId(),
                'firstName' => $doctorEntity->getFirstName(),
                'lastName' => $doctorEntity->getLastName(),
                'specialization' => $doctorEntity->getSpecialization(),
            ]
        );
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $doctrineManager
     * @return int
     */
    private function addDoctor(Request $request, EntityManagerInterface $doctrineManager): int
    {
        $doctor = new DoctorEntity();
        $doctor->setFirstName($request->get('firstName'));
        $doctor->setLastName($request->get('lastName'));
        $doctor->setSpecialization($request->get('specialization'));

        $doctrineManager->persist($doctor);
        $doctrineManager->flush();

        return $doctor->getId();
    }

    /**
     * @param int $doctorId
     * @param Request $request
     * @return JsonResponse
     * @throws NonUniqueResultException
     */
    function slots(int $doctorId, Request $request): JsonResponse
    {
        /** @var EntityManagerInterface $doctrineManager */
        $doctrineManager = $this->getDoctrine()->getManager();

        $doctor = $this->getDoctorById($doctorId, $doctrineManager);

        if ($doctor) {
            if ($request->getMethod() === 'GET') {
                $slots = $doctor->slots();
                return $this->respondWithSlots($slots);
            } elseif ($request->getMethod() === 'POST') {
                $slotId = $this->addSlot($request, $doctor, $doctrineManager);
                return new JsonResponse(['id' => $slotId]);
            }
        } else {
            return new JsonResponse([], 404);
        }

        return new JsonResponse([], 400);
    }

    /**
     * @param int $doctorId
     * @param EntityManagerInterface $doctrineManager
     * @return DoctorEntity|null
     * @throws NonUniqueResultException
     */
    private function getDoctorById(int $doctorId, EntityManagerInterface $doctrineManager): ?DoctorEntity
    {
        return $doctrineManager->createQueryBuilder()
            ->select('doctor')
            ->from(DoctorEntity::class, 'doctor')
            ->where('doctor.id=:id')
            ->setParameter('id', $doctorId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param SlotEntity[] $slots
     * @return JsonResponse
     */
    private function respondWithSlots(array $slots): JsonResponse
    {
        if (!count($slots)) {
            return new JsonResponse([]);
        }

        $responseData = [];
        foreach ($slots as $slot) {
            $responseData[] = [
                'id' => $slot->getId(),
                'day' => $slot->getDay()->format('Y-m-d'),
                'from_hour' => $slot->getFromHour(),
                'duration' => $slot->getDuration()
            ];
        }

        return new JsonResponse($responseData);
    }

    /**
     * @param Request $request
     * @param DoctorEntity $doctor
     * @param EntityManagerInterface $doctrineManager
     * @return int
     * @throws \Exception
     */
    private function addSlot(Request $request, DoctorEntity $doctor, EntityManagerInterface $doctrineManager): int
    {
        $slot = new SlotEntity();
        $slot->setDay(new DateTime($request->get('day')));
        $slot->setDoctor($doctor);
        $slot->setDuration((int)$request->get('duration'));
        $slot->setFromHour($request->get('from_hour'));

        $doctrineManager->persist($slot);
        $doctrineManager->flush();

        return $slot->getId();
    }
}
