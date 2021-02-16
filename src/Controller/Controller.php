<?php

declare(strict_types=1);

namespace App\Controller;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class Controller extends AbstractController
{
    function index()
    {
        return new JsonResponse('ReallyDirty API v1.0');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getDoctorController(Request $request): JsonResponse
    {
        $doctorId = intval($request->get('id'));
        $doctor = $this->getDoctorById($doctorId);

        return $this->respondWithDoctor($doctor);
    }

    /**
     * @param int $doctorId
     * @return DoctorEntity|null
     */
    private function getDoctorById(int $doctorId): ?DoctorEntity
    {
        return $this->getDoctrineManager()->createQueryBuilder()
            ->select('doctor')
            ->from(DoctorEntity::class, 'doctor')
            ->where('doctor.id=:id')
            ->setParameter('id', $doctorId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
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
     * @return JsonResponse
     */
    function addDoctorController(Request $request): JsonResponse
    {
        $doctorId = $this->addDoctor($request);
        return new JsonResponse(['id' => $doctorId]);
    }

    /**
     * @param Request $request
     * @return int
     */
    private function addDoctor(Request $request): int
    {
        $doctor = new DoctorEntity();
        $doctor->setFirstName($request->get('firstName'));
        $doctor->setLastName($request->get('lastName'));
        $doctor->setSpecialization($request->get('specialization'));

        $doctrineManager = $this->getDoctrineManager();
        $doctrineManager->persist($doctor);
        $doctrineManager->flush();

        return $doctor->getId();
    }

    /**
     * @param int $doctorId
     * @param Request $request
     * @return JsonResponse
     */
    function getSlotController(int $doctorId, Request $request): JsonResponse
    {
        $doctor = $this->getDoctorById($doctorId);

        if (is_null($doctor)) {
            return new JsonResponse([], 404);
        }

        $slots = $doctor->slots();
        return $this->respondWithSlots($slots);
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
     * @param int $doctorId
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    function addSlotController(int $doctorId, Request $request): JsonResponse
    {
        /** @var EntityManagerInterface $doctrineManager */
        $doctrineManager = $this->getDoctrine()->getManager();
        $doctor = $this->getDoctorById($doctorId);

        if (is_null($doctor)) {
            return new JsonResponse([], 404);
        }

        if ($doctor) {
            $slotId = $this->addSlot($request, $doctor, $doctrineManager);
            return new JsonResponse(['id' => $slotId]);
        }

        return new JsonResponse([], 400);
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

        $doctrineManager = $this->getDoctrineManager();
        $doctrineManager->persist($slot);
        $doctrineManager->flush();

        return $slot->getId();
    }

    /**
     * @return \Doctrine\Persistence\ObjectManager
     */
    private function getDoctrineManager(): \Doctrine\Persistence\ObjectManager
    {
        return $this->getDoctrine()->getManager();
    }
}
